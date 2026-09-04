<?php

namespace App\Services\Reports;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ProjectServices;
use Illuminate\Http\Request;

class ProjectReportService
{
    protected ProjectServices $projectServices;

    public function __construct(ProjectServices $projectServices)
    {
        $this->projectServices = $projectServices;
    }

    /**
     * Base Query
     */
    protected function query($request)
    {
        return Project::query()
            ->accessibleBy(auth()->user())

            ->with([
                'customer:id,name',
                'projectMilestones:id,project_id,status_id',
                'salesPerson:id,name',
                'projectStatus:id,name,color',
                'projectStage:id,name,color',
            ])

            ->withSum('tasks', 'actual_time_seconds')

            ->withCount([
                'projectMilestones as total_milestones',

                'projectMilestones as completed_milestones' => function ($q) {
                    $q->where('status_id', ProjectMilestone::STATUS_COMPLETED);
                }
            ])

            ->filter($request->all())
            ->sort($request->all());
    }

    public function getColumnLabels(): array
    {
        return [
            'project_name' => 'Project Name',
            'customer' => 'Customer',
            'sales_person' => 'Sales Person',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'estimated_hours' => 'Estimated Hours',
            'actual_hours' => 'Actual Hours',
            'progress' => 'Progress',
            'priority' => 'Priority',
            'milestone_status' => 'Milestone Status',
            'status' => 'Status',
            'stage' => 'Stage',
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

    /**
     * Project List
     */
    public function getProjects($request, $perPage)
    {
        $projects = $this->query($request)
            ->paginate($perPage)
            ->withQueryString();

        $projects->getCollection()->transform(function ($project) {

            // Timeline
            $timeline =
                $this->projectServices
                ->getTimelines($project);

            $project->project_timeline =
                $timeline['projectTimeline'] ?? [];

            $estimatedSeconds = (int) ($project->estimated_time_seconds ?? 0);
            $actualSeconds = (int) ($project->tasks_sum_actual_time_seconds ?? 0);

            // Estimated hours
            $project->estimated_hours = round($estimatedSeconds / 3600);

            // Actual hours
            $project->actual_hours = round($actualSeconds / 3600);

            // Progress
            $project->progress_percentage = $estimatedSeconds > 0
                ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
                : 0;

            // Status badge class
            $status =
                strtolower($project->projectStatus->name ?? '');

            $project->status_badge_class = match ($status) {
                'completed' => 'bg-green-100 text-green-700',
                'in progress' => 'bg-yellow-100 text-yellow-700',
                'not started' => 'bg-gray-100 text-gray-700',
                default => 'bg-blue-100 text-blue-700',
            };

            return $project;
        });

        return $projects;
    }

    /**
     * Export
     */
    public function exportProjects($request)
    {
        return $this->query($request)->get();
    }
}
