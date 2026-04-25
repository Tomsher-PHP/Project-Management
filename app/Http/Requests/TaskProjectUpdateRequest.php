<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAgileTaskPlacement;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskProjectUpdateRequest extends FormRequest
{
    use ValidatesAgileTaskPlacement;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $task = $this->route('task');
        $projectId = is_object($project) ? $project->id : $project;
        $taskId = is_object($task) ? $task->id : $task;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where(
                    fn ($query) => $query->where('flow_type', $project->project_flow)->where('is_active', true)
                ),
            ],
            'project_milestone_id' => [
                'nullable',
                'integer',
                Rule::exists('project_milestones', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
            ],
            'project_sprint_id' => [
                'nullable',
                'integer',
                Rule::exists('project_sprints', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
            ],
            'parent_task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
                Rule::notIn([$taskId]),
            ],
            'task_type_id' => ['required', 'integer', Rule::exists('task_types', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'task_mode_id' => ['required', 'integer', Rule::exists('task_modes', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'priority' => ['required', Rule::in(array_keys(config('project_constants.task_priorities', [])))],
            'current_assignee_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId, $task) {
                    if (! filled($value) || ! $projectId) {
                        return;
                    }

                    $assigneeId = (int) $value;
                    $isCurrentAssignee = $task && (int) ($task->current_assignee_id ?? 0) === $assigneeId;
                    $isActiveProjectMember = DB::table('project_members')
                        ->where('project_id', $projectId)
                        ->where('user_id', $assigneeId)
                        ->whereNull('removed_at')
                        ->where('is_active', true)
                        ->exists();

                    if (! $isActiveProjectMember && ! $isCurrentAssignee) {
                        $fail('The selected assignee is invalid.');
                    }
                },
            ],
            'due_date_time' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
            'is_billable' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a task name.',
            'status_id.exists' => 'The selected task status is invalid.',
            'project_milestone_id.exists' => 'The selected milestone is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
            'parent_task_id.exists' => 'The selected parent task is invalid.',
            'parent_task_id.not_in' => 'A task cannot be its own parent.',
            'task_type_id.required' => 'Please choose a task type.',
            'task_type_id.exists' => 'The selected task type is invalid.',
            'task_mode_id.required' => 'Please choose a task mode.',
            'task_mode_id.exists' => 'The selected task mode is invalid.',
            'priority.required' => 'Please choose a task priority.',
            'current_assignee_id.exists' => 'The selected assignee is invalid.',
            'estimated_time_minutes.min' => 'Estimate time cannot be less than 0 minutes.',
            'sort_order.min' => 'Sort order must be at least 1.',
            'tag_ids.*.max' => 'Tags cannot be longer than 100 characters.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $project = $this->resolveProject();
                $task = $this->resolveTask();
                $projectMilestoneId = $this->nullableIntegerInput('project_milestone_id');
                $projectSprintId = $this->nullableIntegerInput('project_sprint_id');
                $parentTaskId = $this->nullableIntegerInput('parent_task_id');

                $this->validateAgileTaskPlacement(
                    $validator,
                    $project,
                    $project?->id,
                    $projectMilestoneId,
                    $projectSprintId
                );

                if ($task && $parentTaskId && in_array($parentTaskId, $this->getDescendantTaskIds($task), true)) {
                    $validator->errors()->add('parent_task_id', 'A task cannot be assigned to one of its subtasks.');
                }

                if (! $task || ! filled($task->parent_task_id)) {
                    return;
                }

                if ($projectMilestoneId !== $this->normalizeNullableInt($task->project_milestone_id)) {
                    $validator->errors()->add('project_milestone_id', 'Subtasks inherit the milestone from the parent task.');
                }

                if ($projectSprintId !== $this->normalizeNullableInt($task->project_sprint_id)) {
                    $validator->errors()->add('project_sprint_id', 'Subtasks inherit the sprint from the parent task.');
                }
            },
        ];
    }

    private function resolveProject(): ?Project
    {
        $project = $this->route('project');

        if ($project instanceof Project) {
            return $project;
        }

        if (blank($project)) {
            return null;
        }

        return Project::query()->find($project);
    }

    private function resolveTask(): ?Task
    {
        $task = $this->route('task');

        if ($task instanceof Task) {
            return $task;
        }

        if (blank($task)) {
            return null;
        }

        return Task::query()->find($task);
    }

    private function normalizeNullableInt($value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function getDescendantTaskIds(Task $task): array
    {
        $descendantTaskIds = [];
        $pendingParentIds = [(int) $task->id];

        while ($pendingParentIds !== []) {
            $childIds = Task::query()
                ->whereIn('parent_task_id', $pendingParentIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $pendingParentIds = array_values(array_diff($childIds, $descendantTaskIds));
            $descendantTaskIds = [...$descendantTaskIds, ...$pendingParentIds];
        }

        return $descendantTaskIds;
    }
}
