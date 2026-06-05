<?php

namespace App\Services\Reports;

use App\Models\AgileSprintStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SprintReportService
{
    protected function query(Request $request): Builder
    {
        $user = $request->user();

        $query = ProjectSprint::query()
            ->select('project_sprints.*')
            ->where('project_sprints.is_backlog', false)
            ->whereHas('project', function (Builder $projectQuery) use ($user) {
                $projectQuery
                    ->withTrashed()
                    ->accessibleBy($user);
            })
            ->with([
                'project' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'projectMilestone' => fn($query) => $query
                    ->withTrashed()
                    ->select('id', 'name', 'deleted_at'),
                'status' => fn($query) => $query
                    ->select('id', 'name', 'color', 'type'),
            ])
            ->withCount([
                'tasks as total_tasks',
            ]);

        $this->applyFilters($query, $request->all());
        $this->applySorting($query, $request->all());

        return $query;
    }

    public function getColumnLabels(): array
    {
        return [
            'sprint' => 'Sprint',
            'project' => 'Project',
            'milestone' => 'Milestone',
            'start' => 'Start',
            'end' => 'End',
            'total_tasks' => 'Total Tasks',
            'estimated' => 'Estimated',
            'derived' => 'Derived',
            'actual' => 'Actual',
            'progress' => 'Progress',
            'status' => 'Status',
        ];
    }

    public function getSprints(Request $request, int $perPage)
    {
        $sprints = $this->query($request)
            ->paginate($perPage)
            ->withQueryString();

        $sprints->getCollection()->transform(
            fn(ProjectSprint $sprint) => $this->hydrateSprint($sprint)
        );

        return $sprints;
    }

    public function exportSprints(Request $request): Collection
    {
        return $this->query($request)
            ->get()
            ->map(fn(ProjectSprint $sprint) => $this->hydrateSprint($sprint))
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

    public function getMilestones(Request $request): Collection
    {
        $projectIds = collect($request->input('project_id', []))
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values()
            ->all();

        return ProjectMilestone::query()
            ->withTrashed()
            ->where('is_backlog', false)
            ->whereHas('project', function (Builder $projectQuery) use ($request) {
                $projectQuery
                    ->withTrashed()
                    ->accessibleBy($request->user());
            })
            ->when($projectIds !== [], fn($query) => $query->whereIn('project_id', $projectIds))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getStatuses(): Collection
    {
        return AgileSprintStatus::query()
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
                ->whereHas('status', fn(Builder $query) => $query->where('type', AgileSprintStatus::TYPE_OPEN))
                ->count(),
            'in_progress' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', AgileSprintStatus::TYPE_IN_PROGRESS))
                ->count(),
            'completed' => (clone $filteredQuery)
                ->whereHas('status', fn(Builder $query) => $query->where('type', AgileSprintStatus::TYPE_CLOSED))
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
            $query->whereIn('project_sprints.project_id', $projectIds);
        }

        $milestoneIds = collect($filters['project_milestone_id'] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values()
            ->all();

        if ($milestoneIds !== []) {
            $query->whereIn('project_sprints.project_milestone_id', $milestoneIds);
        }

        $statusIds = collect($filters['status_id'] ?? [])
            ->flatten()
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->values()
            ->all();

        if ($statusIds !== []) {
            $query->whereIn('project_sprints.status_id', $statusIds);
        }

        if (filled($filters['start_date'] ?? null)) {
            $query->whereDate('project_sprints.start_date', '>=', $filters['start_date']);
        }

        if (filled($filters['end_date'] ?? null)) {
            $query->whereDate('project_sprints.end_date', '<=', $filters['end_date']);
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = (string) ($filters['sort_by'] ?? '');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        match ($sortBy) {
            'name' => $query->orderBy('project_sprints.name', $sortDir),
            'project_id' => $query->orderBy(
                Project::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('projects.id', 'project_sprints.project_id')
                    ->limit(1),
                $sortDir
            ),
            'project_milestone_id' => $query->orderBy(
                ProjectMilestone::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('project_milestones.id', 'project_sprints.project_milestone_id')
                    ->limit(1),
                $sortDir
            ),
            'start_date' => $query->orderBy('project_sprints.start_date', $sortDir),
            'end_date' => $query->orderBy('project_sprints.end_date', $sortDir),
            'status_id' => $query->orderBy(
                AgileSprintStatus::query()
                    ->select('name')
                    ->whereColumn('agile_sprint_statuses.id', 'project_sprints.status_id')
                    ->limit(1),
                $sortDir
            ),
            default => $query->orderByDesc('project_sprints.id'),
        };
    }

    protected function hydrateSprint(ProjectSprint $sprint): ProjectSprint
    {
        $estimatedSeconds = (int) ($sprint->estimated_time_seconds ?? 0);
        $actualSeconds = (int) ($sprint->actual_time_seconds ?? 0);

        $sprint->progress_percentage = $estimatedSeconds > 0
            ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
            : 0;

        return $sprint;
    }
}
