<?php

namespace App\Observers;

use App\Models\ProjectSprint;
use App\Services\ProjectTimeService;

class ProjectSprintObserver
{
    public function __construct(
        protected ProjectTimeService $projectTimeService
    ) {}

    public function created(ProjectSprint $projectSprint): void
    {
        $this->projectTimeService->recalculateBySprint($projectSprint->id);
    }

    public function updated(ProjectSprint $projectSprint): void
    {
        $original = $projectSprint->getOriginal();

        if ($projectSprint->wasChanged('project_milestone_id')) {
            $this->projectTimeService->recalculateOldSprintRelations($original);
        }

        if ($projectSprint->wasChanged([
            'estimated_time_seconds',
            'actual_time_seconds',
            'derived_time_seconds',
            'project_milestone_id',
        ])) {
            $this->projectTimeService->recalculateBySprint($projectSprint->id);
        }
    }

    public function deleted(ProjectSprint $projectSprint): void
    {
        if (method_exists($projectSprint, 'isForceDeleting') && $projectSprint->isForceDeleting()) {
            return;
        }

        if ($projectSprint->project_milestone_id) {
            $this->projectTimeService->recalculateMilestoneTimes((int) $projectSprint->project_milestone_id);
        }
    }

    public function restored(ProjectSprint $projectSprint): void
    {
        $this->projectTimeService->recalculateBySprint($projectSprint->id);
    }

    public function forceDeleted(ProjectSprint $projectSprint): void
    {
        if ($projectSprint->project_milestone_id) {
            $this->projectTimeService->recalculateMilestoneTimes((int) $projectSprint->project_milestone_id);
        }
    }
}
