<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->resolveProject();
        $projectId = $project?->id;

        return [
            'project_module_id' => [
                'nullable',
                'integer',
                Rule::exists('project_modules', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
            ],
            'project_sprint_id' => [
                'required',
                'integer',
                Rule::exists('project_sprints', 'id')->where(
                    fn ($query) => $query->where('project_id', $projectId)
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'project_module_id.integer' => 'The selected module is invalid.',
            'project_module_id.exists' => 'The selected module is invalid.',
            'project_sprint_id.required' => 'Please choose a sprint to move this task to.',
            'project_sprint_id.integer' => 'The selected sprint is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $task = $this->resolveTask();
                $targetSprintId = $this->filled('project_sprint_id')
                    ? (int) $this->input('project_sprint_id')
                    : null;

                if (! $task || ! $targetSprintId) {
                    return;
                }

                if ($task->parent_task_id !== null) {
                    $validator->errors()->add(
                        'project_sprint_id',
                        'Subtasks cannot be moved to another sprint.'
                    );

                    return;
                }

                if ((int) ($task->project_sprint_id ?? 0) === $targetSprintId) {
                    $validator->errors()->add(
                        'project_sprint_id',
                        'Please choose a different sprint.'
                    );
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
}
