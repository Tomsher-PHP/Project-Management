<?php

namespace App\Services\Task;

use App\Models\TaskTimeLog;

class RunningTaskNavbarService
{
    public function getForUser(?int $userId): array
    {
        $state = $this->emptyState();

        if ($userId === null) {
            return $state;
        }

        $runningTimeLog = TaskTimeLog::query()
            ->with('task:id,name,estimated_time_seconds')
            ->where('user_id', $userId)
            ->where('is_running', true)
            ->latest('started_at')
            ->first();

        $runningTask = $runningTimeLog?->task;
        $trackedSeconds = $runningTask
            ? (int) TaskTimeLog::query()
                ->where('task_id', $runningTask->id)
                ->where('user_id', $userId)
                ->where('is_running', false)
                ->sum('duration_seconds')
            : 0;
        $elapsedSeconds = $runningTimeLog?->started_at
            ? $runningTimeLog->started_at->diffInSeconds(now())
            : 0;
        $currentSeconds = $trackedSeconds + $elapsedSeconds;
        $estimatedSeconds = (int) ($runningTask?->estimated_time_seconds ?? 0);

        return [
            'runningTimeLog' => $runningTimeLog,
            'runningTask' => $runningTask,
            'trackedSeconds' => $trackedSeconds,
            'elapsedSeconds' => $elapsedSeconds,
            'currentSeconds' => $currentSeconds,
            'estimatedSeconds' => $estimatedSeconds,
            'timeColorClass' => $this->resolveTimeColorClass($currentSeconds, $estimatedSeconds),
        ];
    }

    private function emptyState(): array
    {
        return [
            'runningTimeLog' => null,
            'runningTask' => null,
            'trackedSeconds' => 0,
            'elapsedSeconds' => 0,
            'currentSeconds' => 0,
            'estimatedSeconds' => 0,
            'timeColorClass' => 'text-bgray-500 dark:text-bgray-300',
        ];
    }

    private function resolveTimeColorClass(int $currentSeconds, int $estimatedSeconds): string
    {
        if ($estimatedSeconds <= 0) {
            return 'text-bgray-500 dark:text-bgray-300';
        }

        return $currentSeconds <= $estimatedSeconds
            ? 'text-success-400 dark:text-success-300'
            : 'text-error-300 dark:text-red-300';
    }
}
