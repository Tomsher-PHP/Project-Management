<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\ProjectTimeService;
use App\Services\TaskStatusSyncService;

class TaskObserver
{
    public function __construct(
        protected ProjectTimeService $projectTimeService,
        protected TaskStatusSyncService $taskStatusSyncService,
    ) {}

    public function created(Task $task): void
    {
        $this->projectTimeService->recalculateByTask($task->id);
        $this->taskStatusSyncService->syncAgileParentStatus($task);
    }

    public function updating(Task $task): void
    {
        if (! $task->isDirty('status_id')) {
            return;
        }

        $originalStatusId = $task->getOriginal('status_id');
        $newStatusId = $task->status_id;
        $completionStates = TaskStatus::withTrashed()
            ->whereKey(array_filter([$originalStatusId, $newStatusId]))
            ->pluck('is_completed', 'id');

        $wasCompleted = (bool) $completionStates->get($originalStatusId, false);
        $isCompleted = (bool) $completionStates->get($newStatusId, false);

        if ($wasCompleted === $isCompleted) {
            return;
        }

        $task->completed_at = $isCompleted ? now() : null;
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

        if ($task->wasChanged(['project_sprint_id', 'project_milestone_id'])) {
            $this->taskStatusSyncService->syncAgileParentStatusByRelations(
                isset($original['project_sprint_id']) ? (int) $original['project_sprint_id'] : null,
                isset($original['project_milestone_id']) ? (int) $original['project_milestone_id'] : null
            );
        }

        if ($task->wasChanged([
            'status_id',
            'project_sprint_id',
            'project_milestone_id',
            'request_status',
        ])) {
            $this->taskStatusSyncService->syncAgileParentStatus($task);
        }
    }

    public function deleted(Task $task): void
    {
        if (method_exists($task, 'isForceDeleting') && $task->isForceDeleting()) {
            return;
        }

        $this->recalculateDeletedTaskRelations($task);
        $this->taskStatusSyncService->syncAgileParentStatusByRelations(
            $task->project_sprint_id ? (int) $task->project_sprint_id : null,
            $task->project_milestone_id ? (int) $task->project_milestone_id : null,
            true
        );
    }

    public function restored(Task $task): void
    {
        $this->projectTimeService->recalculateByTask($task->id);
        $this->taskStatusSyncService->syncAgileParentStatus($task);
    }

    public function forceDeleted(Task $task): void
    {
        $this->recalculateDeletedTaskRelations($task);
        $this->taskStatusSyncService->syncAgileParentStatusByRelations(
            $task->project_sprint_id ? (int) $task->project_sprint_id : null,
            $task->project_milestone_id ? (int) $task->project_milestone_id : null,
            true
        );
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
