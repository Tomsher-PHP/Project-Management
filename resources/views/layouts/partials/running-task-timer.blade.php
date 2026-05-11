@php
    $navbarRunningTimeLog = \App\Models\TaskTimeLog::query()
        ->with('task:id,name,estimated_time_seconds')
        ->where('user_id', auth()->id())
        ->where('is_running', true)
        ->latest('started_at')
        ->first();

    $navbarRunningTask = $navbarRunningTimeLog?->task;
    $navbarTrackedSeconds = $navbarRunningTask
        ? (int) \App\Models\TaskTimeLog::query()
            ->where('task_id', $navbarRunningTask->id)
            ->where('user_id', auth()->id())
            ->where('is_running', false)
            ->sum('duration_seconds')
        : 0;
    $navbarElapsedSeconds = $navbarRunningTimeLog?->started_at
        ? $navbarRunningTimeLog->started_at->diffInSeconds(now())
        : 0;
    $navbarCurrentSeconds = $navbarTrackedSeconds + $navbarElapsedSeconds;
    $navbarEstimatedSeconds = (int) ($navbarRunningTask?->estimated_time_seconds ?? 0);
    $navbarTimeColorClass = 'text-bgray-500 dark:text-bgray-300';

    if ($navbarEstimatedSeconds > 0) {
        $navbarTimeColorClass = $navbarCurrentSeconds <= $navbarEstimatedSeconds
            ? 'text-success-400 dark:text-success-300'
            : 'text-error-300 dark:text-red-300';
    }
@endphp

<div id="running-task-bar" data-running-task-timer data-running-task-active="{{ $navbarRunningTimeLog ? '1' : '0' }}" data-running-task-id="{{ $navbarRunningTask?->id ?? '' }}" data-running-task-name="{{ $navbarRunningTask?->name ?? '' }}" data-running-task-seconds="{{ $navbarCurrentSeconds }}" data-running-task-base-seconds="{{ $navbarTrackedSeconds }}" data-running-task-estimated-seconds="{{ $navbarEstimatedSeconds }}" data-running-task-state="{{ $navbarRunningTimeLog ? 'running' : 'stopped' }}" data-running-task-started-at="{{ $navbarRunningTimeLog?->started_at?->toISOString() ?? '' }}"
    data-running-task-stop-url="{{ $navbarRunningTask ? route('tasks.stop', $navbarRunningTask) : '' }}" class="running-task-bar {{ $navbarRunningTimeLog ? '' : 'hidden' }}" aria-live="polite">
    <div class="running-task-bar__pulse" aria-hidden="true"></div>

    <div class="running-task-bar__name-wrap">
        <p id="running-task-name" data-running-task-name class="running-task-bar__name" title="{{ $navbarRunningTask?->name ?? '' }}">
            {{ $navbarRunningTask?->name ?? '' }}
        </p>
    </div>

    <p id="running-task-timer" data-running-task-time class="running-task-bar__time {{ $navbarTimeColorClass }}">
        {{ gmdate('H:i:s', $navbarCurrentSeconds) }}
    </p>

    <button id="running-task-toggle" type="button" data-running-task-toggle class="running-task-bar__toggle" aria-label="Stop running task" title="Stop running task">
        <span id="running-task-toggle-icon" data-running-task-toggle-icon aria-hidden="true"></span>
    </button>
</div>
