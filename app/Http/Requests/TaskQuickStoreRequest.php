<?php

namespace App\Http\Requests;

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
        $project = $this->route('project');
        $projectId = is_object($project) ? $project->id : $project;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where(
                    fn ($query) => $query->where('flow_type', $project->project_flow)->where('is_active', true)
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
            ],
            'task_type' => ['nullable', Rule::in(array_keys(config('project_constants.task_type', [])))],
            'task_mode' => ['nullable', Rule::in(array_keys(config('project_constants.task_mode', [])))],
            'priority' => ['nullable', Rule::in(array_keys(config('project_constants.task_priorities', [])))],
            'current_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('project_members', 'user_id')->where(
                    fn ($query) => $query
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
            'title.required' => 'Please enter a task name.',
            'status_id.exists' => 'The selected task status is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
            'parent_task_id.exists' => 'The selected parent task is invalid.',
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
                $project = $this->route('project');
                $projectId = is_object($project) ? $project->id : (int) $project;
                $parentTaskId = $this->filled('parent_task_id') ? (int) $this->input('parent_task_id') : null;

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

                $selectedSprintId = $this->filled('project_sprint_id') ? (int) $this->input('project_sprint_id') : null;

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
}
