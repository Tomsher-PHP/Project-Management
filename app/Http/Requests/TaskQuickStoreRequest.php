<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskQuickStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->resolveProject();
        $projectId = $project?->id;
        $routeProject = $this->route('project');

        return [
            'project_id' => [
                Rule::requiredIf(blank($routeProject)),
                'nullable',
                'integer',
                Rule::exists('projects', 'id'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where(
                    fn($query) => $query->where('flow_type', $project?->project_flow)->where('is_active', true)
                ),
            ],
            'project_module_id' => [
                Rule::requiredIf($project?->project_flow === 'agile'),
                'nullable',
                'integer',
                Rule::exists('project_modules', 'id')->where(
                    fn($query) => $query->where('project_id', $projectId)
                ),
            ],
            'project_sprint_id' => [
                'nullable',
                'integer',
                Rule::requiredIf($project?->project_flow === 'agile'),
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
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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
            'title.required' => 'Please enter a task name.',
            'status_id.exists' => 'The selected task status is invalid.',
            'project_module_id.required' => 'The module is required.',
            'project_module_id.exists' => 'The selected module is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
            'project_sprint_id.required' => 'The sprint is required.',
            'parent_task_id.exists' => 'The selected parent task is invalid.',
            'task_type_id.exists' => 'The selected task type is invalid.',
            'task_mode_id.exists' => 'The selected task mode is invalid.',
            'current_assignee_id.exists' => 'The selected assignee is invalid.',
            'due_date.after_or_equal' => 'The due date must be the same as or after the start date.',
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
                $parentTaskId = $this->filled('parent_task_id') ? (int) $this->input('parent_task_id') : null;
                $projectModuleId = $this->filled('project_module_id') ? (int) $this->input('project_module_id') : null;
                $selectedSprintId = $this->filled('project_sprint_id') ? (int) $this->input('project_sprint_id') : null;

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

                if ($selectedSprintId) {
                    $selectedSprint = ProjectSprint::query()
                        ->where('project_id', $projectId)
                        ->find($selectedSprintId);

                    if ($selectedSprint && $projectModuleId && (int) $selectedSprint->project_module_id !== $projectModuleId) {
                        $validator->errors()->add('project_sprint_id', 'Please choose a sprint from the selected module.');
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

                if (filled($parentTask->parent_task_id) && (int) $parentTask->parent_task_id > 0) {
                    $validator->errors()->add('parent_task_id', 'Please choose a top-level parent task.');
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
