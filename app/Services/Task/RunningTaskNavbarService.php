<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskTimeLog;
use Illuminate\Support\Collection;

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

        return $this->buildState($runningTimeLog, $runningTask, $trackedSeconds);
    }

    public function getTaskStatesForUser(?int $userId, iterable $tasks): array
    {
        $taskCollection = Collection::make($tasks)
            ->filter(fn($task) => $task instanceof Task && $task->id)
            ->keyBy(fn(Task $task) => (int) $task->id);

        if ($taskCollection->isEmpty()) {
            return [];
        }

        $states = $taskCollection
            ->mapWithKeys(fn(Task $task, int $taskId) => [$taskId => $this->buildState(null, $task, 0)])
            ->all();

        if ($userId === null) {
            return $states;
        }

        $taskIds = $taskCollection->keys()->all();
        $trackedSecondsByTask = TaskTimeLog::query()
            ->selectRaw('task_id, COALESCE(SUM(duration_seconds), 0) as tracked_seconds')
            ->whereIn('task_id', $taskIds)
            ->where('user_id', $userId)
            ->where('is_running', false)
            ->groupBy('task_id')
            ->pluck('tracked_seconds', 'task_id');
        $runningLogsByTask = TaskTimeLog::query()
            ->whereIn('task_id', $taskIds)
            ->where('user_id', $userId)
            ->where('is_running', true)
            ->latest('started_at')
            ->get()
            ->unique('task_id')
            ->keyBy(fn(TaskTimeLog $timeLog) => (int) $timeLog->task_id);

        foreach ($taskCollection as $taskId => $task) {
            $states[$taskId] = $this->buildState(
                $runningLogsByTask->get((int) $taskId),
                $task,
                (int) ($trackedSecondsByTask[(int) $taskId] ?? 0)
            );
        }

        return $states;
    }

    private function emptyState(): array
    {
        return $this->buildState();
    }

    private function buildState(
        ?TaskTimeLog $runningTimeLog = null,
        ?Task $runningTask = null,
        int $trackedSeconds = 0
    ): array {
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

    private function resolveTimeColorClass(int $currentSeconds, int $estimatedSeconds): string
    {
        if ($estimatedSeconds <= 0) {
            return 'text-bgray-700 dark:text-bgray-300';
        }

        return $currentSeconds <= $estimatedSeconds
            ? 'text-success-400 dark:text-success-300'
            : 'text-error-300 dark:text-red-300';
    }
}
