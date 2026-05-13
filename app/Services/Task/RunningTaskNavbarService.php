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

        $selectedTimeLog = TaskTimeLog::query()
            ->with([
                'task:id,name,estimated_time_seconds,status_id',
                'task.status:id,type',
            ])
            ->where('user_id', $userId)
            ->where('is_running', true)
            ->latest('started_at')
            ->first();

        if (! $selectedTimeLog) {
            $selectedTimeLog = TaskTimeLog::query()
                ->with([
                    'task:id,name,estimated_time_seconds,status_id',
                    'task.status:id,type',
                ])
                ->where('user_id', $userId)
                ->where('is_running', false)
                ->whereHas('task.status', function ($query) {
                    $query->where('type', 'active');
                })
                ->latest('ended_at')
                ->latest('updated_at')
                ->first();
        }

        $runningTask = $selectedTimeLog?->task;
        $trackedSeconds = $runningTask
            ? (int) TaskTimeLog::query()
                ->where('task_id', $runningTask->id)
                ->where('user_id', $userId)
                ->where('is_running', false)
                ->sum('duration_seconds')
            : 0;

        return $this->buildState(
            $selectedTimeLog,
            $runningTask,
            $trackedSeconds,
            $selectedTimeLog !== null && $runningTask !== null
        );
    }

    public function getFrontendStateForUser(?int $userId): array
    {
        return $this->toFrontendState($this->getForUser($userId));
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
                (int) ($trackedSecondsByTask[(int) $taskId] ?? 0),
                false
            );
        }

        return $states;
    }

    private function emptyState(): array
    {
        return $this->buildState();
    }

    private function buildState(
        ?TaskTimeLog $selectedTimeLog = null,
        ?Task $runningTask = null,
        int $trackedSeconds = 0,
        bool $shouldShowTimer = false
    ): array {
        $isRunning = (bool) ($selectedTimeLog?->is_running ?? false);
        $elapsedSeconds = $isRunning && $selectedTimeLog?->started_at
            ? $selectedTimeLog->started_at->diffInSeconds(now())
            : 0;
        $currentSeconds = $trackedSeconds + $elapsedSeconds;
        $estimatedSeconds = (int) ($runningTask?->estimated_time_seconds ?? 0);

        return [
            'selectedTimeLog' => $selectedTimeLog,
            'runningTimeLog' => $selectedTimeLog,
            'runningTask' => $runningTask,
            'trackedSeconds' => $trackedSeconds,
            'elapsedSeconds' => $elapsedSeconds,
            'currentSeconds' => $currentSeconds,
            'estimatedSeconds' => $estimatedSeconds,
            'timeColorClass' => $this->resolveTimeColorClass($currentSeconds, $estimatedSeconds),
            'isRunning' => $isRunning,
            'shouldShowTimer' => $shouldShowTimer,
            'timerState' => $isRunning ? 'running' : 'stopped',
        ];
    }

    public function toFrontendState(array $state): array
    {
        $task = $state['runningTask'] ?? null;
        $selectedTimeLog = $state['selectedTimeLog'] ?? null;
        $isRunning = (bool) ($state['isRunning'] ?? false);
        $shouldShowTimer = (bool) ($state['shouldShowTimer'] ?? false);

        return [
            'active' => $shouldShowTimer,
            'taskId' => $task?->id ? (string) $task->id : '',
            'taskName' => $task?->name ?? '',
            'seconds' => (int) ($state['currentSeconds'] ?? 0),
            'baseSeconds' => (int) ($state['trackedSeconds'] ?? 0),
            'estimatedSeconds' => (int) ($state['estimatedSeconds'] ?? 0),
            'startedAt' => $isRunning ? ($selectedTimeLog?->started_at?->toISOString() ?? '') : '',
            'startUrl' => $task ? route('tasks.start', $task) : '',
            'stopUrl' => $isRunning && $task ? route('tasks.stop', $task) : '',
            'state' => ($state['timerState'] ?? 'stopped') === 'running' ? 'running' : 'stopped',
            'isRunning' => $isRunning,
            'shouldShowTimer' => $shouldShowTimer,
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
