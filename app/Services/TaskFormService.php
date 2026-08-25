<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;

class TaskFormService
{
    public function getCreateData($user): array
    {
        return [
            'taskCreateProjects' => [], // $this->getProjects($user),

            'taskTypeOptions' => $this->getTaskTypes(),
            'taskModeOptions' => $this->getTaskModes(),
            'nextTaskTypeSortOrder' => ((int) TaskType::max('sort_order')) + 1,
            'nextTaskModeSortOrder' => ((int) TaskMode::max('sort_order')) + 1,
            'tagOptions' => $this->getTags(),

            'taskPriorityOptions' => $this->getTaskPriorities(),
            'defaultTaskPriority' => $this->getDefaultPriority(),
        ];
    }

    public function getProjects($user)
    {
        return Project::query()
            ->accessibleBy($user)
            ->orderBy('name')
            ->get(['id', 'project_code', 'name', 'project_flow', 'default_billable', 'default_task_estimate_seconds']);
    }

    public function searchProjects($user, ?string $query = null)
    {
        $search = trim((string) $query);

        return Project::query()
            ->accessibleBy($user)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('project_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'project_code']);
    }

    public function getInitialDependencies(): array
    {
        $statusOptionsByFlow = TaskStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'flow_type'])
            ->groupBy('flow_type')
            ->map(fn($statuses) => $statuses->map(fn(TaskStatus $status) => [
                'value' => (string) $status->id,
                'text' => $status->name,
            ])->values())
            ->toArray();

        return [
            'projects' => [],
            'status_options_by_flow' => $statusOptionsByFlow,
            'statuses_by_flow' => $statusOptionsByFlow,
            'defaults' => [
                'project_id' => null,
                'priority' => $this->getDefaultPriority(),
                'due_date_time' => now(config('constants.timezone'))->addDay()->format('Y-m-d H:i'),
            ],
            'parent_options_url' => route('tasks.quick-create-parent-options'),
            'dependencies_url_template' => route('projects.task-create-dependencies', ['project' => ':id']),
        ];
    }

    public function getProjectDependencies(Project $project): array
    {
        $flow = $project->project_flow;
        $defaultStatusId = $this->getDefaultTaskStatusIdForFlow($flow);
        $defaultEstimateMinutes = $project->default_task_estimate_seconds !== null
            ? intdiv((int) $project->default_task_estimate_seconds, 60)
            : 0;

        $milestones = $project->projectMilestones()
            ->where('is_backlog', false)
            ->where('is_system', false)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($m) => [
                'value' => (string) $m->id,
                'text' => $m->name,
            ])
            ->values()
            ->toArray();

        $sprints = $project->projectSprints()
            ->where('is_backlog', false)
            ->where('is_system', false)
            ->orderBy('name')
            ->get(['id', 'project_milestone_id', 'name'])
            ->map(fn($s) => [
                'value' => (string) $s->id,
                'text' => $s->name,
                'project_milestone_id' => (string) ($s->project_milestone_id ?? ''),
            ])
            ->values()
            ->toArray();

        $assignees = $project->activeMembers()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn(User $u) => [
                'value' => (string) $u->id,
                'text' => $u->name,
                'subtype' => $u->email ?? '',
            ])
            ->values()
            ->toArray();

        return [
            'id' => $project->id,
            'flow' => $flow,
            'default_billable' => (bool) $project->default_billable,
            'default_status_id' => $defaultStatusId,
            'default_task_estimate_minutes' => $defaultEstimateMinutes,
            'milestones' => $milestones,
            'sprints' => $sprints,
            'assignees' => $assignees,
        ];
    }

    public function buildDependencies($projects = null): array
    {
        if ($projects instanceof Project) {
            return [
                'projects' => [
                    (string) $projects->id => $this->getProjectDependencies($projects),
                ],
            ];
        }

        if ($projects instanceof \Illuminate\Support\Collection && $projects->isNotEmpty()) {
            return [
                'projects' => $projects->mapWithKeys(fn(Project $project) => [
                    (string) $project->id => $this->getProjectDependencies($project),
                ])->toArray(),
            ];
        }

        return [
            'projects' => [],
        ];
    }

    public function getDefaultTaskStatusIdForFlow(?string $flowType): ?int
    {
        if (blank($flowType)) {
            return null;
        }

        return TaskStatus::query()
            ->active()
            ->where('flow_type', $flowType)
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN sort_order = 1 THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    private function getTaskTypes()
    {
        return TaskType::active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function getTaskModes()
    {
        return TaskMode::active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function getTags()
    {
        return Tag::active()->get();
    }

    private function getTaskPriorities()
    {
        return collect(config('project_constants.task_priorities', []))
            ->map(fn($config, $key) => (object)[
                'value' => $key,
                'label' => $config['label'] ?? ucfirst($key),
            ])
            ->values();
    }

    private function getDefaultPriority(): string
    {
        $priorities = config('project_constants.task_priorities', []);

        if (array_key_exists('medium', $priorities)) {
            return 'medium';
        }

        return (string) (array_key_first($priorities) ?? 'medium');
    }
}
