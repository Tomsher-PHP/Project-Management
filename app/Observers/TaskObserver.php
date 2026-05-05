<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\ProjectTimeService;

class TaskObserver
{
    public function __construct(
        protected ProjectTimeService $projectTimeService
    ) {}

    public function created(Task $task): void
    {
        $this->projectTimeService->recalculateByTask($task->id);
    }

    public function updated(Task $task): void
    {
        $original = $task->getOriginal();

        if ($task->wasChanged(['parent_task_id', 'project_sprint_id', 'project_milestone_id'])) {
            $this->projectTimeService->recalculateOldTaskRelations($original);
        }

        if ($task->wasChanged([
            'estimated_time_seconds',
            'request_status',
            'parent_task_id',
            'project_sprint_id',
            'project_milestone_id',
            'actual_time_seconds',
        ])) {
            $this->projectTimeService->recalculateByTask($task->id);
        }
    }

    public function deleted(Task $task): void
    {
        if (method_exists($task, 'isForceDeleting') && $task->isForceDeleting()) {
            return;
        }

        $this->recalculateDeletedTaskRelations($task);
    }

    public function restored(Task $task): void
    {
        $this->projectTimeService->recalculateByTask($task->id);
    }

    public function forceDeleted(Task $task): void
    {
        $this->recalculateDeletedTaskRelations($task);
    }

    private function recalculateDeletedTaskRelations(Task $task): void
    {
        if ($task->parent_task_id) {
            $this->projectTimeService->recalculateTaskDerived((int) $task->parent_task_id);
        }

        if ($task->project_sprint_id) {
            $this->projectTimeService->recalculateBySprint((int) $task->project_sprint_id);
        }

        if ($task->project_milestone_id) {
            $this->projectTimeService->recalculateMilestoneTimes((int) $task->project_milestone_id);
        }
    }
}
