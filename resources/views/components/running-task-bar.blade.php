<div id="running-task-bar" data-running-task-timer data-running-task-active="{{ $runningTimeLog ? '1' : '0' }}" data-running-task-id="{{ $runningTask?->id ?? '' }}" data-running-task-name="{{ $runningTask?->name ?? '' }}" data-running-task-seconds="{{ $currentSeconds }}" data-running-task-base-seconds="{{ $trackedSeconds }}" data-running-task-estimated-seconds="{{ $estimatedSeconds }}" data-running-task-state="{{ $runningTimeLog ? 'running' : 'stopped' }}" data-running-task-started-at="{{ $runningTimeLog?->started_at?->toISOString() ?? '' }}"
    data-running-task-stop-url="{{ $runningTask ? route('tasks.stop', $runningTask) : '' }}" class="running-task-bar {{ $runningTimeLog ? '' : 'hidden' }}" aria-live="polite">
    <div class="running-task-bar__pulse" aria-hidden="true"></div>

    <div class="running-task-bar__name-wrap">
        <p id="running-task-name" data-running-task-name class="running-task-bar__name" title="{{ $runningTask?->name ?? '' }}">
            {{ $runningTask?->name ?? '' }}
        </p>
    </div>

    <p id="running-task-timer" data-running-task-time class="running-task-bar__time {{ $timeColorClass }}">
        {{ gmdate('H:i:s', $currentSeconds) }}
    </p>

    <button id="running-task-toggle" type="button" data-running-task-toggle class="running-task-bar__toggle" aria-label="Stop running task" title="Stop running task">
        <span id="running-task-toggle-icon" data-running-task-toggle-icon aria-hidden="true"></span>
    </button>
</div>
