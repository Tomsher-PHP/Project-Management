<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTaskUpdateRequest extends FormRequest
{
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('project_task_statuses', 'id')->where(
                    fn ($query) => $query->where('flow_type', $project->project_flow)->where('is_active', true)
                ),
            ],
            'project_module_id' => [
                'nullable',
                'integer',
                Rule::exists('project_modules', 'id')->where(
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
                Rule::exists('project_tasks', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
                Rule::notIn([$taskId]),
            ],
            'task_type' => ['required', Rule::in(array_keys(config('project_constants.task_type', [])))],
            'task_mode' => ['required', Rule::in(array_keys(config('project_constants.task_mode', [])))],
            'priority' => ['required', Rule::in(array_keys(config('project_constants.task_priorities', [])))],
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
            'title.required' => 'Please enter a task name.',
            'status_id.exists' => 'The selected task status is invalid.',
            'project_module_id.exists' => 'The selected module is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
            'parent_task_id.exists' => 'The selected parent task is invalid.',
            'parent_task_id.not_in' => 'A task cannot be its own parent.',
            'task_type.required' => 'Please choose a task type.',
            'task_mode.required' => 'Please choose a task mode.',
            'priority.required' => 'Please choose a task priority.',
            'current_assignee_id.exists' => 'The selected assignee is invalid.',
            'due_date.after_or_equal' => 'The due date must be the same as or after the start date.',
            'estimated_time_minutes.min' => 'Estimate time cannot be less than 0 minutes.',
            'sort_order.min' => 'Sort order must be at least 1.',
            'tag_ids.*.max' => 'Tags cannot be longer than 100 characters.',
        ];
    }
}
