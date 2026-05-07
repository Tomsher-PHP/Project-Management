<?php

namespace App\Observers;

use App\Models\TaskTimeLog;
use App\Services\ProjectTimeService;

class TaskTimeLogObserver
{
    public function __construct(
        protected ProjectTimeService $projectTimeService
    ) {}

    public function created(TaskTimeLog $taskTimeLog): void
    {
        $this->projectTimeService->recalculateByTask($taskTimeLog->task_id);
    }

    public function updated(TaskTimeLog $taskTimeLog): void
    {
        if (! $taskTimeLog->wasChanged(['duration_seconds', 'is_approved', 'task_id'])) {
            return;
        }

        $oldTaskId = $this->normalizeId($taskTimeLog->getOriginal('task_id'));
        $newTaskId = $this->normalizeId($taskTimeLog->task_id);

        if ($oldTaskId !== null && $oldTaskId !== $newTaskId) {
            $this->projectTimeService->recalculateByTask($oldTaskId);
        }

        $this->projectTimeService->recalculateByTask($newTaskId);
    }

    public function deleted(TaskTimeLog $taskTimeLog): void
    {
        $this->projectTimeService->recalculateByTask($taskTimeLog->task_id);
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
