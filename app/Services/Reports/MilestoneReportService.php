<?php

namespace App\Services\Reports;

use App\Models\AgileMilestoneStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MilestoneReportService
{
    protected function query(Request $request): Builder
    {
        $user = $request->user();

        $query = ProjectMilestone::query()
            ->select('project_milestones.*')
            ->where('project_milestones.is_backlog', false)
            ->whereHas('project', function (Builder $projectQuery) use ($user) {
                $projectQuery
                    ->withTrashed()
                    ->accessibleBy($user);
            })
            ->with([
                'project' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'owner' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'status' => fn($query) => $query
                    ->select('id', 'name', 'color', 'type'),
            ])
            ->withCount([
                'projectSprints as total_sprints' => fn($query) => $query
                    ->where('is_backlog', false),
                'tasks as total_tasks',
            ]);

        $this->applyFilters($query, $request->all());
        $this->applySorting($query, $request->all());

        return $query;
    }

    public function getColumnLabels(): array
    {
        return [
            'milestone' => 'Milestone',
            'project' => 'Project',
            'owner' => 'Owner',
            'start' => 'Start',
            'end' => 'End',
            'total_sprints' => 'Total Sprints',
            'total_tasks' => 'Total Tasks',
            'estimated' => 'Estimated',
            'derived' => 'Derived',
            'actual' => 'Actual',
            'progress' => 'Progress',
            'status' => 'Status',
        ];
    }

    public function getMilestones(Request $request, int $perPage)
    {
        $milestones = $this->query($request)
            ->paginate($perPage)
            ->withQueryString();

        $milestones->getCollection()->transform(
            fn(ProjectMilestone $milestone) => $this->hydrateMilestone($milestone)
        );

        return $milestones;
    }

    public function exportMilestones(Request $request): Collection
    {
        return $this->query($request)
            ->get()
            ->map(fn(ProjectMilestone $milestone) => $this->hydrateMilestone($milestone))
            ->values();
    }

    public function getProjects(Request $request): Collection
    {
        return Project::query()
            ->withTrashed()
            ->accessibleBy($request->user())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getOwners(Request $request): Collection
    {
        $ownerIds = ProjectMilestone::query()
            ->where('is_backlog', false)
            ->whereHas('project', function (Builder $projectQuery) use ($request) {
                $projectQuery
                    ->withTrashed()
                    ->accessibleBy($request->user());
            })
            ->whereNotNull('owner_id')
            ->distinct()
            ->pluck('owner_id')
            ->filter()
            ->values();

        if ($ownerIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->withTrashed()
            ->whereIn('id', $ownerIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getStatuses(): Collection
    {
        return AgileMilestoneStatus::query()
            ->select('id', 'name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getStats(Request $request): array
    {
        $filteredQuery = $this->query($request);

        return [
            'total' => (clone $filteredQuery)->count(),
            'open' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', AgileMilestoneStatus::TYPE_OPEN))
                ->count(),
            'in_progress' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', AgileMilestoneStatus::TYPE_IN_PROGRESS))
                ->count(),
            'completed' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', AgileMilestoneStatus::TYPE_CLOSED))
                ->count(),
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

    protected function applyFilters(Builder $query, array $filters): void
    {
        $projectIds = collect($filters['project_id'] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values()
            ->all();

        if ($projectIds !== []) {
            $query->whereIn('project_milestones.project_id', $projectIds);
        }

        $ownerIds = collect($filters['owner_id'] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values()
            ->all();

        if ($ownerIds !== []) {
            $query->whereIn('project_milestones.owner_id', $ownerIds);
        }

        $statusIds = collect($filters['status_id'] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values()
            ->all();

        if ($statusIds !== []) {
            $query->whereIn('project_milestones.status_id', $statusIds);
        }

        if (filled($filters['start_date'] ?? null)) {
            $query->whereDate('project_milestones.start_date', '>=', $filters['start_date']);
        }

        if (filled($filters['end_date'] ?? null)) {
            $query->whereDate('project_milestones.end_date', '<=', $filters['end_date']);
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = (string) ($filters['sort_by'] ?? '');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        match ($sortBy) {
            'name' => $query->orderBy('project_milestones.name', $sortDir),
            'project_id' => $query->orderBy(
                Project::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('projects.id', 'project_milestones.project_id')
                    ->limit(1),
                $sortDir
            ),
            'owner_id' => $query->orderBy(
                User::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('users.id', 'project_milestones.owner_id')
                    ->limit(1),
                $sortDir
            ),
            'start_date' => $query->orderBy('project_milestones.start_date', $sortDir),
            'end_date' => $query->orderBy('project_milestones.end_date', $sortDir),
            'status_id' => $query->orderBy(
                AgileMilestoneStatus::query()
                    ->select('name')
                    ->whereColumn('agile_milestone_statuses.id', 'project_milestones.status_id')
                    ->limit(1),
                $sortDir
            ),
            default => $query->orderByDesc('project_milestones.id'),
        };
    }

    protected function hydrateMilestone(ProjectMilestone $milestone): ProjectMilestone
    {
        $estimatedSeconds = (int) ($milestone->estimated_time_seconds ?? 0);
        $actualSeconds = (int) ($milestone->actual_time_seconds ?? 0);

        $milestone->progress_percentage = $estimatedSeconds > 0
            ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
            : 0;

        return $milestone;
    }
}
