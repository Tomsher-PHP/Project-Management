<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAgileTaskPlacement;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    use ValidatesAgileTaskPlacement;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $task = $this->route('task');
        $project = $task?->project;
        $projectId = $project?->id;
        $taskId = $task?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where(
                    fn ($query) => $query->where('flow_type', $project?->project_flow)->where('is_active', true)
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
                Rule::exists('project_members', 'user_id')->where(
                    fn ($query) => $query
                        ->where('project_id', $projectId)
                        ->whereNull('removed_at')
                        ->where('is_active', true)
                ),
            ],
            'due_date' => ['nullable', 'date'],
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
            'project_module_id.exists' => 'The selected module is invalid.',
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

                $this->validateAgileTaskPlacement(
                    $validator,
                    $project,
                    $project?->id,
                    $this->nullableIntegerInput('project_module_id'),
                    $this->nullableIntegerInput('project_sprint_id')
                );
            },
        ];
    }

    private function resolveProject(): ?Project
    {
        return $this->route('task')?->project;
    }
}
