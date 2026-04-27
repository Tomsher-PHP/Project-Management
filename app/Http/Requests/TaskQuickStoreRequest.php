<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAgileTaskPlacement;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskQuickStoreRequest extends FormRequest
{
    use ValidatesAgileTaskPlacement;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->resolveProject();
        $projectId = $project?->id;
        $routeProject = $this->route('project');
        $requestTypes = config('project_constants.task_request_types', []);

        return [
            'project_id' => [
                Rule::requiredIf(blank($routeProject)),
                'nullable',
                'integer',
                Rule::exists('projects', 'id'),
            ],
            'request_type' => ['nullable', Rule::in($requestTypes)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where(
                    fn($query) => $query->where('flow_type', $project?->project_flow)->where('is_active', true)
                ),
            ],
            'project_milestone_id' => [
                'nullable',
                'integer',
                Rule::exists('project_milestones', 'id')->where(
                    fn($query) => $query->where('project_id', $projectId)
                ),
            ],
            'project_sprint_id' => [
                'nullable',
                'integer',
                Rule::exists('project_sprints', 'id')->where(
                    fn($query) => $query->where('project_id', $projectId)
                ),
            ],
            'parent_task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where(
                    fn($query) => $query->where('project_id', $projectId)
                ),
            ],
            'task_type_id' => ['nullable', 'integer', Rule::exists('task_types', 'id')->where(fn($query) => $query->where('is_active', true))],
            'task_mode_id' => ['nullable', 'integer', Rule::exists('task_modes', 'id')->where(fn($query) => $query->where('is_active', true))],
            'priority' => ['nullable', Rule::in(array_keys(config('project_constants.task_priorities', [])))],
            'current_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('project_members', 'user_id')->where(
                    fn($query) => $query
                        ->where('project_id', $projectId)
                        ->whereNull('removed_at')
                        ->where('is_active', true)
                ),
            ],
            'due_date_time' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:0'],
            'is_billable' => ['nullable', 'boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Please choose a project.',
            'project_id.exists' => 'The selected project is invalid.',
            'request_type.in' => 'The selected request type is invalid.',
            'name.required' => 'Please enter a task name.',
            'status_id.exists' => 'The selected task status is invalid.',
            'project_milestone_id.exists' => 'The selected milestone is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
            'parent_task_id.exists' => 'The selected parent task is invalid.',
            'task_type_id.exists' => 'The selected task type is invalid.',
            'task_mode_id.exists' => 'The selected task mode is invalid.',
            'current_assignee_id.exists' => 'The selected assignee is invalid.',
            'estimated_time_minutes.min' => 'Estimate time cannot be less than 0 minutes.',
            'tag_ids.*.max' => 'Tags cannot be longer than 100 characters.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $project = $this->resolveProject();
                $projectId = $project?->id;
                $parentTaskId = $this->nullableIntegerInput('parent_task_id');
                $projectMilestoneId = $this->nullableIntegerInput('project_milestone_id');
                $selectedSprintId = $this->nullableIntegerInput('project_sprint_id');
                $requestType = $this->input('request_type', 'assigned');

                if (! $project || ! $projectId) {
                    return;
                }

                $isAccessibleProject = Project::query()
                    ->accessibleBy($this->user())
                    ->whereKey($projectId)
                    ->exists();

                if (! $isAccessibleProject) {
                    $validator->errors()->add('project_id', 'The selected project is invalid.');

                    return;
                }

                $this->validateAgileTaskPlacement(
                    $validator,
                    $project,
                    $projectId,
                    $projectMilestoneId,
                    $selectedSprintId
                );

                if ($requestType === 'self' && $this->user()?->id) {
                    $isProjectMember = $project->activeMembers()
                        ->whereKey($this->user()->id)
                        ->exists();

                    if (! $isProjectMember) {
                        $validator->errors()->add('project_id', 'You are not allowed to request a task for the selected project.');

                        return;
                    }
                }

                if (! $parentTaskId) {
                    return;
                }

                $parentTask = Task::query()
                    ->where('project_id', $projectId)
                    ->find($parentTaskId);

                if (! $parentTask) {
                    return;
                }

                if ($project?->project_flow === 'linear') {
                    if (! empty($parentTask->project_sprint_id)) {
                        $validator->errors()->add('parent_task_id', 'The selected parent task is invalid.');
                    }

                    return;
                }

                if ($selectedSprintId && (int) $parentTask->project_sprint_id !== $selectedSprintId) {
                    $validator->errors()->add('parent_task_id', 'Please choose a parent task from the selected sprint.');
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

        $projectId = $project ?: $this->input('project_id');

        if (blank($projectId)) {
            return null;
        }

        return Project::query()->find($projectId);
    }
}
