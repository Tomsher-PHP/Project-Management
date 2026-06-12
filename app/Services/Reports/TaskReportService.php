<?php

namespace App\Services\Reports;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskReportService
{
    protected function baseVisibleQuery(Request $request): Builder
    {
        $user = $request->user();

        $query = Task::query()
            ->select('tasks.*')
            ->whereNull('tasks.break_work_request_id')
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('tasks.request_type')
                    ->orWhere('tasks.request_type', '!=', Task::REQUEST_TYPE_BREAK);
            })
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('tasks.request_status')
                    ->orWhere('tasks.request_status', '!=', Task::REQUEST_REJECTED);
            })
            ->whereHas('project', function (Builder $projectQuery) {
                $projectQuery
                    ->withTrashed()
                    ->where('projects.is_system', false);
            });

        if ($user->is_super_admin || $user->can('task.view_all_tasks')) {
            return $query;
        }

        return $query->where(function (Builder $taskQuery) use ($user) {
            $taskQuery
                ->where('tasks.current_assignee_id', $user->id)
                ->orWhereHas('project.teamLeader', function (Builder $teamLeaderQuery) use ($user) {
                    $teamLeaderQuery->whereKey($user->id);
                })
                ->orWhereHas('projectMilestone', function (Builder $milestoneQuery) use ($user) {
                    $milestoneQuery
                        ->withTrashed()
                        ->where('owner_id', $user->id);
                })
                ->orWhereExists(function ($subQuery) use ($user) {
                    $subQuery->selectRaw(1)
                        ->from('task_assignment_logs')
                        ->whereColumn('task_assignment_logs.task_id', 'tasks.id')
                        ->where('task_assignment_logs.user_id', $user->id)
                        ->where('task_assignment_logs.worked_time_seconds', '>', 0);
                });
        });
    }

    protected function query(Request $request): Builder
    {
        $query = $this->baseVisibleQuery($request)
            ->with([
                'project' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'projectMilestone' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'project_id', 'name', 'deleted_at'),
                'projectSprint' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'project_id', 'project_milestone_id', 'name', 'deleted_at'),
                'parentTask' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'currentAssignee' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'status' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'color', 'type', 'is_completed'),
                'taskType' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'color'),
                'taskMode' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'color'),
            ]);

        $this->applyFilters($query, $request->all());
        $this->applySorting($query, $request->all());

        return $query;
    }

    public function getColumnLabels(): array
    {
        return [
            'task' => 'Task',
            'parent_task' => 'Parent Task',
            'project' => 'Project',
            'milestone' => 'Milestone',
            'sprint' => 'Sprint',
            'status' => 'Status',
            'type' => 'Type',
            'mode' => 'Mode',
            'priority' => 'Priority',
            'assignee' => 'Assignee',
            'due_date' => 'Due Date',
            'completed_at' => 'Completed At',
            'estimated' => 'Estimated',
            'actual' => 'Actual',
            'progress' => 'Progress',
            'billable' => 'Billable',
            'created_at' => 'Created At',
        ];
    }

    public function getTasks(Request $request, int $perPage)
    {
        $tasks = $this->query($request)
            ->paginate($perPage)
            ->withQueryString();

        $tasks->getCollection()->transform(
            fn(Task $task) => $this->hydrateTask($task)
        );

        return $tasks;
    }

    public function exportTasks(Request $request): Collection
    {
        return $this->query($request)
            ->get()
            ->map(fn(Task $task) => $this->hydrateTask($task))
            ->values();
    }

    public function getProjects(Request $request): Collection
    {
        $projectIds = $this->baseVisibleQuery($request)
            ->distinct()
            ->pluck('project_id')
            ->filter()
            ->values();

        if ($projectIds->isEmpty()) {
            return collect();
        }

        return Project::query()
            ->withTrashed()
            ->whereIn('id', $projectIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getMilestones(Request $request): Collection
    {
        $milestoneIds = $this->filterOptionsTaskQuery($request)
            ->whereNotNull('project_milestone_id')
            ->distinct()
            ->pluck('project_milestone_id')
            ->filter()
            ->values();

        if ($milestoneIds->isEmpty()) {
            return collect();
        }

        return ProjectMilestone::query()
            ->withTrashed()
            ->with(['project' => fn($query) => $query->withTrashed()->select('id', 'name')])
            ->whereIn('id', $milestoneIds)
            ->select('id', 'project_id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn(ProjectMilestone $milestone) => (object) [
                'id' => $milestone->id,
                'name' => collect([
                    $milestone->project?->name,
                    $milestone->name,
                ])->filter()->implode(' / '),
            ]);
    }

    public function getSprints(Request $request): Collection
    {
        $sprintIds = $this->filterOptionsTaskQuery($request)
            ->whereNotNull('project_sprint_id')
            ->distinct()
            ->pluck('project_sprint_id')
            ->filter()
            ->values();

        if ($sprintIds->isEmpty()) {
            return collect();
        }

        return ProjectSprint::query()
            ->withTrashed()
            ->with([
                'project' => fn($query) => $query->withTrashed()->select('id', 'name'),
                'projectMilestone' => fn($query) => $query->withTrashed()->select('id', 'name'),
            ])
            ->whereIn('id', $sprintIds)
            ->select('id', 'project_id', 'project_milestone_id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn(ProjectSprint $sprint) => (object) [
                'id' => $sprint->id,
                'name' => collect([
                    $sprint->project?->name,
                    $sprint->projectMilestone?->name,
                    $sprint->name,
                ])->filter()->implode(' / '),
            ]);
    }

    public function getAssignees(Request $request): Collection
    {
        $assigneeIds = $this->baseVisibleQuery($request)
            ->whereNotNull('current_assignee_id')
            ->distinct()
            ->pluck('current_assignee_id')
            ->filter()
            ->values();

        if ($assigneeIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->withTrashed()
            ->whereIn('id', $assigneeIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getStatuses(): Collection
    {
        return TaskStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getPriorityOptions(): Collection
    {
        return collect(config('project_constants.task_priorities', []))
            ->map(fn(array $priority, string $key) => (object) [
                'id' => $key,
                'name' => $priority['label'] ?? ucfirst($key),
            ])
            ->values();
    }

    public function getTaskTypes(): Collection
    {
        return TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getTaskModes(): Collection
    {
        return TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getStats(Request $request): array
    {
        $filteredQuery = $this->query($request);

        return [
            'total' => (clone $filteredQuery)->count(),
            'pending' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', TaskStatus::TYPE_PENDING))
                ->count(),
            'active' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', TaskStatus::TYPE_ACTIVE))
                ->count(),
            'archived' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', TaskStatus::TYPE_ARCHIVED))
                ->count(),
            'completed' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', TaskStatus::TYPE_COMPLETED))
                ->count(),
            'total_estimated' => (int) (clone $filteredQuery)->sum('tasks.estimated_time_seconds'),
            'total_actual' => (int) (clone $filteredQuery)->sum('tasks.actual_time_seconds'),
        ];
    }

    public function resolveExportColumns(Request $request): array
    {
        return $this->resolveExportColumnsFromFilters($request->all());
    }

    public function resolveExportColumnsFromFilters(array $filters): array
    {
        $allowedColumns = $this->getColumnLabels();
        $requestedColumns = $filters['visible_columns'] ?? [];

        if (is_string($requestedColumns)) {
            $requestedColumns = array_filter(explode(',', $requestedColumns));
        }

        if (! is_array($requestedColumns)) {
            $requestedColumns = [];
        }

        $requestedLookup = collect($requestedColumns)
            ->map(fn($column) => (string) $column)
            ->filter()
            ->values()
            ->flip();

        $columns = collect($allowedColumns)
            ->filter(fn($_label, $key) => $requestedLookup->has($key))
            ->all();

        return $columns !== [] ? $columns : $allowedColumns;
    }

    protected function filterOptionsTaskQuery(Request $request): Builder
    {
        $query = $this->baseVisibleQuery($request);
        $filters = $request->all();

        $projectIds = $this->normalizeIds($filters['project_id'] ?? []);

        if ($projectIds !== []) {
            $query->whereIn('tasks.project_id', $projectIds);
        }

        $milestoneIds = $this->normalizeIds($filters['project_milestone_id'] ?? []);

        if ($milestoneIds !== []) {
            $query->whereIn('tasks.project_milestone_id', $milestoneIds);
        }

        return $query;
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $filterColumns = [
            'project_id' => 'tasks.project_id',
            'project_milestone_id' => 'tasks.project_milestone_id',
            'project_sprint_id' => 'tasks.project_sprint_id',
            'current_assignee_id' => 'tasks.current_assignee_id',
            'status_id' => 'tasks.status_id',
            'task_type_id' => 'tasks.task_type_id',
            'task_mode_id' => 'tasks.task_mode_id',
        ];

        foreach ($filterColumns as $requestKey => $column) {
            $ids = $this->normalizeIds($filters[$requestKey] ?? []);

            if ($ids !== []) {
                $query->whereIn($column, $ids);
            }
        }

        $priorities = collect($filters['priority'] ?? [])
            ->flatten()
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        if ($priorities !== []) {
            $query->whereIn('tasks.priority', $priorities);
        }

        if (filled($filters['start_date'] ?? null)) {
            $query->whereDate('tasks.created_at', '>=', $filters['start_date']);
        }

        if (filled($filters['end_date'] ?? null)) {
            $query->whereDate('tasks.created_at', '<=', $filters['end_date']);
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = (string) ($filters['sort_by'] ?? '');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        match ($sortBy) {
            'name' => $query->orderBy('tasks.name', $sortDir),
            'parent_task_id' => $query->orderBy(
                DB::table('tasks as parent_tasks')
                    ->select('parent_tasks.name')
                    ->whereColumn('parent_tasks.id', 'tasks.parent_task_id')
                    ->limit(1),
                $sortDir
            ),
            'project_id' => $query->orderBy(
                Project::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('projects.id', 'tasks.project_id')
                    ->limit(1),
                $sortDir
            ),
            'project_milestone_id' => $query->orderBy(
                ProjectMilestone::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('project_milestones.id', 'tasks.project_milestone_id')
                    ->limit(1),
                $sortDir
            ),
            'project_sprint_id' => $query->orderBy(
                ProjectSprint::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('project_sprints.id', 'tasks.project_sprint_id')
                    ->limit(1),
                $sortDir
            ),
            'status_id' => $query->orderBy(
                TaskStatus::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('task_statuses.id', 'tasks.status_id')
                    ->limit(1),
                $sortDir
            ),
            'task_type_id' => $query->orderBy(
                TaskType::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('task_types.id', 'tasks.task_type_id')
                    ->limit(1),
                $sortDir
            ),
            'task_mode_id' => $query->orderBy(
                TaskMode::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('task_modes.id', 'tasks.task_mode_id')
                    ->limit(1),
                $sortDir
            ),
            'priority' => $query->orderBy('tasks.priority', $sortDir),
            'current_assignee_id' => $query->orderBy(
                User::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('users.id', 'tasks.current_assignee_id')
                    ->limit(1),
                $sortDir
            ),
            'due_date_time' => $query->orderBy('tasks.due_date_time', $sortDir),
            'completed_at' => $query->orderBy('tasks.completed_at', $sortDir),
            'estimated_time_seconds' => $query->orderBy('tasks.estimated_time_seconds', $sortDir),
            'actual_time_seconds' => $query->orderBy('tasks.actual_time_seconds', $sortDir),
            'created_at' => $query->orderBy('tasks.created_at', $sortDir),
            default => $query->orderByDesc('tasks.id'),
        };
    }

    protected function hydrateTask(Task $task): Task
    {
        $estimatedSeconds = (int) ($task->estimated_time_seconds ?? 0);
        $actualSeconds = (int) ($task->actual_time_seconds ?? 0);

        $task->progress_percentage = $estimatedSeconds > 0
            ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
            : 0;

        return $task;
    }

    protected function normalizeIds(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->flatten()
            ->filter(fn($item) => filled($item))
            ->map(fn($item) => (int) $item)
            ->filter(fn(int $item) => $item > 0)
            ->values()
            ->all();
    }
}
