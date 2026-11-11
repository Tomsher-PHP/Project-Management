<?php

namespace App\Services\Reports;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProjectReportService
{
    /**
     * Base Query
     */
    protected function query(Request $request): Builder
    {
        $filters = $request->all();

        $query = $this->filteredProjectQuery($request)
            ->with([
                'customer:id,name',
                'salesPerson:id,name',
                'projectStatus:id,name,color',
                'projectStage:id,name,color',
                'projectTimelines' => fn($query) => $query
                    ->select([
                        'id',
                        'project_id',
                        'name',
                        'status',
                        'start_date',
                        'end_date',
                        'customer_end_date',
                        'estimated_time_seconds',
                        'customer_estimate_seconds',
                        'actual_time_seconds',
                        'sort_order',
                    ])
                    ->reorder()
                    ->eligibleForProjectReport()
                    ->orderForProjectReport(),
            ])
            ->withCount([
                'projectMilestones as total_milestones',
                'projectMilestones as completed_milestones' => function ($q) {
                    $q->where('status_id', ProjectMilestone::STATUS_COMPLETED);
                },
            ]);

        $this->applySorting($query, $filters);

        return $query;
    }

    public function canViewCustomerColumns(): bool
    {
        return auth()->user()?->can('project.customer_end_date') ?? false;
    }

    public function getColumnLabels(?bool $includeCustomerColumns = null): array
    {
        $includeCustomerColumns ??= $this->canViewCustomerColumns();

        $columns = [
            'project_name' => 'Project Name',
            'customer' => 'Customer',
            'sales_person' => 'Sales Person',
            'timeline' => 'Timeline',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
        ];

        if ($includeCustomerColumns) {
            $columns['customer_end_date'] = 'Cust End Date';
        }

        $columns['estimated_hours'] = 'Estimated Hours';

        if ($includeCustomerColumns) {
            $columns['customer_estimated_hours'] = 'Cust Estimate';
        }

        $columns['actual_hours'] = 'Actual Hours';
        $columns['progress'] = 'Progress';

        if ($includeCustomerColumns) {
            $columns['customer_progress'] = 'Cust Progress';
        }

        return array_merge($columns, [
            'priority' => 'Priority',
            'milestone_status' => 'Milestone Status',
            'status' => 'Status',
            'stage' => 'Stage',
        ]);
    }

    public function getExportColumnLabels(): array
    {
        $columns = [];

        foreach ($this->getColumnLabels() as $key => $label) {
            $columns[$key] = $key === 'timeline' ? 'Timeline Name' : $label;

            if ($key === 'timeline') {
                $columns['timeline_status'] = 'Timeline Status';
            }
        }

        return $columns;
    }

    public function resolveExportColumns(Request $request): array
    {
        return $this->resolveExportColumnsFromFilters($request->all());
    }

    public function resolveExportColumnsFromFilters(array $filters): array
    {
        $allowedColumns = $this->getExportColumnLabels();
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

        if ($requestedLookup->has('timeline')) {
            $requestedLookup->put('timeline_status', true);
        }

        $columns = collect($allowedColumns)
            ->filter(fn($_label, $key) => $requestedLookup->has($key))
            ->all();

        return $columns !== [] ? $columns : $allowedColumns;
    }

    /**
     * Project List
     */
    public function getProjects(Request $request, int $perPage)
    {
        $projects = $this->query($request)
            ->paginate($perPage)
            ->withQueryString();

        $this->prepareProjects($projects->getCollection());

        return $projects;
    }

    public function getStats(Request $request): array
    {
        $filteredQuery = $this->filteredProjectQuery($request);

        return [
            'total' => (clone $filteredQuery)->count(),
            'completed' => (clone $filteredQuery)->completed()->count(),
            'in_progress' => (clone $filteredQuery)->inProgress()->count(),
            'open' => (clone $filteredQuery)->open()->count(),
            'archieved' => (clone $filteredQuery)->archived()->count(),
        ];
    }

    /**
     * Export
     */
    public function exportProjects(Request $request): EloquentCollection
    {
        $projects = $this->query($request)->get();

        $this->prepareProjects($projects);

        return $projects;
    }

    public function prepareProjects(Collection $projects): Collection
    {
        return $projects->transform(fn(Project $project) => $this->prepareProject($project));
    }

    public function prepareProject(Project $project): Project
    {
        $timelines = $project->relationLoaded('projectTimelines')
            ? $project->projectTimelines->values()
            : $project->projectTimelines()
            ->reorder()
            ->eligibleForProjectReport()
            ->orderForProjectReport()
            ->get();

        $timelines->each(function (ProjectTimeline $timeline) {
            $estimatedSeconds = max(0, (int) ($timeline->estimated_time_seconds ?? 0));
            $customerEstimatedSeconds = max(0, (int) ($timeline->customer_estimate_seconds ?? 0));
            $actualSeconds = max(0, (int) ($timeline->actual_time_seconds ?? 0));

            $timeline->setAttribute('report_estimated_seconds', $estimatedSeconds);
            $timeline->setAttribute('report_customer_estimated_seconds', $customerEstimatedSeconds);
            $timeline->setAttribute('report_actual_seconds', $actualSeconds);
            $timeline->setAttribute(
                'report_progress_percentage',
                $estimatedSeconds > 0
                    ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
                    : 0
            );
            $timeline->setAttribute(
                'report_customer_progress_percentage',
                $customerEstimatedSeconds > 0
                    ? round(($actualSeconds / $customerEstimatedSeconds) * 100, 2)
                    : 0
            );
            $timeline->setAttribute(
                'report_status_label',
                ucfirst((string) config(
                    'project_constants.project_timeline_statuses.' . $timeline->status,
                    'Unknown'
                ))
            );
        });

        $project->setRelation('reportTimelines', $timelines);
        $project->setRelation('primaryReportTimeline', $timelines->first());
        $project->setAttribute('eligible_timelines_count', $timelines->count());

        return $project;
    }

    protected function filteredProjectQuery(Request $request): Builder
    {
        $filters = $request->all();
        $query = Project::query()
            ->accessibleBy(auth()->user())
            ->filter(Arr::except($filters, ['start_date', 'end_date']));

        $this->applyPrimaryTimelineDateFilter($query, $filters);

        return $query;
    }

    protected function applyPrimaryTimelineDateFilter(Builder $query, array $filters): void
    {
        if (empty($filters['start_date']) || empty($filters['end_date'])) {
            return;
        }

        $query
            ->where(
                $this->primaryTimelineColumnSubquery('start_date'),
                '<=',
                $filters['end_date']
            )
            ->where(
                $this->primaryTimelineColumnSubquery('end_date'),
                '>=',
                $filters['start_date']
            );
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        $allowedTimelineSortColumns = ['start_date', 'end_date'];

        if ($this->canViewCustomerColumns()) {
            $allowedTimelineSortColumns[] = 'customer_end_date';
        }

        if (in_array($sortBy, $allowedTimelineSortColumns, true)) {
            $query
                ->orderBy($this->primaryTimelineColumnSubquery($sortBy), $sortDir)
                ->orderByDesc('projects.id');

            return;
        }

        if ($sortBy === 'customer_end_date') {
            $filters = Arr::except($filters, ['sort_by', 'sort_dir']);
        }

        $query->sort($filters);
    }

    protected function primaryTimelineColumnSubquery(string $column): Builder
    {
        return ProjectTimeline::query()
            ->select('project_timelines.' . $column)
            ->whereColumn('project_timelines.project_id', 'projects.id')
            ->eligibleForProjectReport()
            ->orderForProjectReport()
            ->limit(1);
    }
}
