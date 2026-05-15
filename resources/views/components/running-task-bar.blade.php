<div id="running-task-bar" data-running-task-timer data-running-task-active="{{ $shouldShowTimer ? '1' : '0' }}" data-running-task-id="{{ $runningTask?->id ?? '' }}" data-running-task-name="{{ $runningTask?->name ?? '' }}" data-running-task-seconds="{{ $currentSeconds }}" data-running-task-base-seconds="{{ $trackedSeconds }}" data-running-task-estimated-seconds="{{ $estimatedSeconds }}" data-running-task-state="{{ $timerState }}" data-running-task-started-at="{{ $isRunning ? ($selectedTimeLog?->started_at?->toISOString() ?? '') : '' }}"
    data-running-task-start-url="{{ $runningTask ? route('tasks.start', $runningTask) : '' }}" data-running-task-stop-url="{{ $isRunning && $runningTask ? route('tasks.stop', $runningTask) : '' }}" class="running-task-bar {{ $shouldShowTimer ? '' : 'hidden' }}" aria-live="polite">
    <div class="running-task-bar__pulse" aria-hidden="true"></div>

    <div class="running-task-bar__name-wrap">
        <p id="running-task-name" data-running-task-name class="running-task-bar__name" title="{{ $runningTask?->name ?? '' }}">
            {{ $runningTask?->name ?? '' }}
        </p>
    </div>

    <p id="running-task-timer" data-running-task-time class="running-task-bar__time {{ $timeColorClass }}">
        {{ gmdate('H:i:s', $currentSeconds) }}
    </p>

    <button id="running-task-toggle" type="button" data-running-task-toggle class="running-task-bar__toggle" aria-label="{{ $isRunning ? 'Stop running task' : 'Start task timer' }}" title="{{ $isRunning ? 'Stop running task' : 'Start task timer' }}">
        <span id="running-task-toggle-icon" data-running-task-toggle-icon aria-hidden="true"></span>
    </button>
</div>
