<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\ProjectTimeline;
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
        $isCrossProject = $this->boolean('move_to_another_project');
        $currentProject = $this->resolveProject();
        $effectiveProjectId = $isCrossProject
            ? (int) $this->input('target_project_id')
            : $currentProject?->id;
        $timelineRules = [
            'required',
            'integer',
            Rule::exists('project_timelines', 'id')->where(
                fn ($query) => $query
                    ->where('project_id', $effectiveProjectId)
                    ->whereNull('deleted_at')
                    ->whereIn('status', [
                        ProjectTimeline::STATUS_ACTIVE,
                        ProjectTimeline::STATUS_COMPLETED,
                    ])
            ),
        ];

        if (! $isCrossProject) {
            $projectId = $currentProject?->id;

            return [
                'move_to_another_project' => ['nullable', 'boolean'],
                'project_timeline_id' => $timelineRules,
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
            ];
        }

        return [
            'move_to_another_project' => ['nullable', 'boolean'],
            'project_timeline_id' => $timelineRules,
            'target_project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->whereNull('deleted_at')
                ),
            ],
            'target_milestone_id' => ['nullable', 'integer'],
            'target_sprint_id' => ['nullable', 'integer'],
            'project_milestone_id' => ['nullable', 'integer'],
            'project_sprint_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_project_id.required' => 'Please select a project to move this task to.',
            'target_project_id.integer' => 'The selected target project is invalid.',
            'target_project_id.exists' => 'The selected target project is invalid.',
            'project_milestone_id.integer' => 'The selected milestone is invalid.',
            'project_milestone_id.exists' => 'The selected milestone is invalid.',
            'project_sprint_id.required' => 'Please choose a sprint to move this task to.',
            'project_sprint_id.integer' => 'The selected sprint is invalid.',
            'project_sprint_id.exists' => 'The selected sprint is invalid.',
            'project_timeline_id.required' => 'Please select a project timeline.',
            'project_timeline_id.integer' => 'The selected project timeline is invalid.',
            'project_timeline_id.exists' => 'The selected project timeline is invalid for the destination project.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $task = $this->resolveTask();
                $isCrossProject = $this->boolean('move_to_another_project');

                if (! $isCrossProject) {
                    $targetSprintId = $this->filled('project_sprint_id')
                        ? (int) $this->input('project_sprint_id')
                        : null;
                    $targetTimelineId = $this->filled('project_timeline_id')
                        ? (int) $this->input('project_timeline_id')
                        : null;

                    if (! $task) {
                        return;
                    }

                    $timelineChanged = $targetTimelineId
                        && (int) ($task->project_timeline_id ?? 0) !== $targetTimelineId;

                    if (! $targetSprintId && ! $timelineChanged && ! $validator->errors()->has('project_timeline_id')) {
                        $validator->errors()->add(
                            'project_timeline_id',
                            'Please select another timeline or choose a different sprint.'
                        );

                        return;
                    }

                    if ($targetSprintId && $this->resolveProject()?->is_linear) {
                        $validator->errors()->add(
                            'project_sprint_id',
                            'Sprint movement is not available for Linear projects.'
                        );

                        return;
                    }

                    if ($targetSprintId && $task->parent_task_id !== null) {
                        $validator->errors()->add(
                            'project_sprint_id',
                            'Subtasks cannot be moved to another sprint.'
                        );

                        return;
                    }

                    if ($targetSprintId && (int) ($task->project_sprint_id ?? 0) === $targetSprintId) {
                        $validator->errors()->add(
                            'project_sprint_id',
                            'Please choose a different sprint.'
                        );
                    }

                    return;
                }

                // Cross-project move validation
                $targetProjectId = (int) $this->input('target_project_id');

                if (! $targetProjectId) {
                    $validator->errors()->add('target_project_id', 'Please select a project.');

                    return;
                }

                $targetProject = Project::query()->find($targetProjectId);

                if (! $targetProject) {
                    $validator->errors()->add('target_project_id', 'The selected project is invalid.');

                    return;
                }

                $user = $this->user();
                $isAccessible = Project::query()->accessibleBy($user)->where('id', $targetProject->id)->exists();

                if (! $isAccessible) {
                    $validator->errors()->add('target_project_id', 'You are not authorized to move tasks to this project.');

                    return;
                }

                if ($targetProject->is_linear) {
                    return;
                }

                $targetMilestoneId = $this->filled('target_milestone_id')
                    ? (int) $this->input('target_milestone_id')
                    : ($this->filled('project_milestone_id') ? (int) $this->input('project_milestone_id') : null);

                $targetSprintId = $this->filled('target_sprint_id')
                    ? (int) $this->input('target_sprint_id')
                    : ($this->filled('project_sprint_id') ? (int) $this->input('project_sprint_id') : null);

                if ($targetMilestoneId && ! $targetSprintId) {
                    $validator->errors()->add(
                        'project_sprint_id',
                        'Please choose a sprint for the selected milestone.'
                    );

                    return;
                }

                if ($targetMilestoneId) {
                    $milestone = ProjectMilestone::query()
                        ->where('project_id', $targetProject->id)
                        ->find($targetMilestoneId);

                    if (! $milestone) {
                        $validator->errors()->add('target_milestone_id', 'The selected milestone is invalid for the target project.');
                    }
                }

                if ($targetSprintId) {
                    $sprint = ProjectSprint::query()
                        ->where('project_id', $targetProject->id)
                        ->find($targetSprintId);

                    if (! $sprint) {
                        $validator->errors()->add('target_sprint_id', 'The selected sprint is invalid for the target project.');

                        return;
                    }

                    if ($targetMilestoneId && (int) ($sprint->project_milestone_id ?? 0) !== $targetMilestoneId) {
                        $validator->errors()->add('target_sprint_id', 'The selected sprint does not belong to the selected milestone.');
                    }
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
