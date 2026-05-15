<?php

namespace App\Services\Reports;

use App\Models\Project;
use App\Services\ProjectServices;

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
                'salesPerson:id,name',
                'projectStatus:id,name',
                'projectStage:id,name',
            ])

            ->withCount([
                'projectMilestones as total_milestones',

                'projectMilestones as completed_milestones' => function ($q) {
                    $q->where('status_id', 4);
                }
            ])

            ->filter($request->all())
            ->sort($request->all());
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

            // Estimated hours
            $project->estimated_hours =
                round(($project->estimated_time_seconds ?? 0) / 3600);

            // Actual hours
            $project->actual_hours =
                round(($project->actual_time_seconds ?? 0) / 3600);

            // Progress
            $project->progress_percentage =
                $project->progress ?? 0;

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