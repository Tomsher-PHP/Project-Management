@php
    $authUser = auth()->user();
    $authUserId = $authUser?->id;
    $taskTimerService = app(\App\Services\TaskServices::class);
    $project = $task->project;
    $milestone = $task->projectMilestone;
    $sprint = $task->projectSprint;
    $canOpenTask = $project && $authUser && ($authUser->is_super_admin || $authUser->can('task.view_all_tasks') || $authUser->can('task.view') || (int) ($task->current_assignee_id ?? 0) === (int) $authUserId);

    $isCompleted = $status->is_completed ?? false;
    $priority = $task->priority ?? 'medium';
    $priorityConfig = $priorities[$priority] ?? [];
    $priorityTextClass = $priorityConfig['text_class'] ?? 'text-gray-900';
    $priorityLabel = $priorityConfig['label'] ?? ucfirst($priority);

    $estimatedTime = $task->estimatedTimeFormatted;
    $estimatedSeconds = (int) ($task->estimated_time_seconds ?? 0);
    $dueDate = $task->due_date_time;
    $dueDateDisplay = \App\Providers\AppServiceProvider::formatAppDateTime($dueDate);
    $dueDateStatus = $task->status ?? ($status ?? null);

    $runningLog = $task->relationLoaded('activeTimeLog')
        ? $task->activeTimeLog
        : $task->timeLogs()->where('is_running', 1)->latest('started_at')->first();
    $totalTrackedSeconds = (int) ($task->actual_time_seconds ?? 0);
    $timerStartedAt = $runningLog?->started_at?->toISOString();
    $isTimerRunning = $runningLog !== null;
    $startRestriction = $authUser ? $taskTimerService->getStartRestriction($task, $authUser) : ['message' => 'Not allowed to start timer for this task.'];
    $canStartTimer = $authUser ? ($startRestriction === null || in_array(($startRestriction['reason'] ?? null), ['running_timer_exists', 'already_running'], true)) : false;
    $canStopTimer = $authUser ? $taskTimerService->isAllowedToStop($task, $authUser) : false;
    $canControlTimer = $canStartTimer || $canStopTimer;
    $isTimerDisabled = $isTimerRunning ? !$canStopTimer : !$canStartTimer;
    $timerButtonTitle = $isTimerRunning
        ? ($canStopTimer ? 'Stop timer' : 'You are not allowed to stop this task timer.')
        : ($canStartTimer ? 'Start timer' : ($startRestriction['message'] ?? 'You are not allowed to start this task timer.'));

    $taskName = $task->name ?? ($task->code ?? 'Untitled task');
    $projectName = $project?->name;
    $milestoneName = $milestone?->name;
    $sprintName = $sprint?->name;
    $assigneeName = $task->currentAssignee?->name;

    $limitText = fn(?string $value, int $length = 20, string $end = '...'): string => \Illuminate\Support\Str::limit($value ?? '', $length, $end);
@endphp

@once
    @push('styles')
        <style>
            @keyframes task-timer-button-pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.24);
                }

                70% {
                    box-shadow: 0 0 0 8px rgba(220, 38, 38, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
                }
            }

            .task-timer-btn--running {
                background-color: #fee2e2 !important;
                color: #dc2626 !important;
                animation: task-timer-button-pulse 1.35s ease-in-out infinite;
            }

            .task-timer-btn--running:hover {
                background-color: #fecaca !important;
                color: #b91c1c !important;
            }
        </style>
    @endpush
@endonce

<div class="card rounded-md bg-white shadow-sm transition hover:shadow-md dark:bg-darkblack-600" data-task-id="{{ $task->id }}">
    <div class="space-y-3 p-4">
        <div class="{{ $canOpenTask ? 'cursor-pointer' : 'cursor-default' }}" @if ($canOpenTask) data-project-task-detail-open
                data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}"
                data-project-task-group-key=""
                title="Open task" @endif>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 text-[11px] font-medium text-bgray-900 dark:text-bgray-300">
                        @if ($project)
                            <x-project-flow-icon :flow="$project->project_flow" size="sm" />
                            <span class="truncate" title="{{ $projectName }}">{{ $limitText($projectName, 16) }}</span>
                        @endif

                        @if (($project?->project_flow ?? null) === 'agile' && $milestoneName)
                            <span class="shrink-0 text-bgray-900 dark:text-bgray-500">></span>
                            <span class="truncate" title="{{ $milestoneName }}">{{ $limitText($milestoneName, 16) }}</span>
                        @endif
                    </div>
                </div>

                @if ($sprintName)
                    <span class="inline-flex max-w-[112px] shrink-0 rounded-full bg-success-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-success-400 dark:bg-darkblack-500 dark:text-success-300" title="{{ $sprintName }}">
                        <span class="truncate">{{ $limitText($sprintName, 14) }}</span>
                    </span>
                @endif
            </div>

            <div class="mt-3" title="{{ $taskName }}">
                <x-task-name-status :name="$taskName" :request-type="$task->request_type" :request-status="$task->request_status" :limit="34" text-class="text-sm font-semibold leading-snug {{ $isCompleted ? 'line-through text-gray-400 dark:text-gray-500' : 'text-bgray-900 dark:text-white' }}" name-class="block" class="max-w-full" />
            </div>

            <div class="mt-3 flex items-center justify-between gap-3 text-[11px]">
                <span class="inline-flex shrink-0 border bg-white px-1 py-0 font-semibold {{ $priorityTextClass }}" style="border-color: currentColor;" title="Priority: {{ $priorityLabel }}">
                    {{ $priorityLabel }}
                </span>

                @if ($dueDate)
                    <span class="inline-flex min-w-0 items-center gap-1 text-right leading-5 {{ taskDueDateClass($dueDate, $task->estimated_time_seconds, $dueDateStatus) }}" title="{{ $dueDateDisplay }}">
                        {!! taskDueDateIcon($dueDate, $task->estimated_time_seconds, $dueDateStatus) !!}
                        <span class="truncate text-[11px] font-medium">{{ $dueDateDisplay }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2" title="{{ $assigneeName ?: 'Not Assigned' }}">
                @if ($task->currentAssignee)
                    @if (!empty($task->currentAssignee->profileImageUrl))
                        <img src="{{ $task->currentAssignee->profileImageUrl }}" alt="{{ $assigneeName }}" class="h-8 w-8 rounded-full object-cover" />
                    @else
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-semibold uppercase text-white">
                            {{ strtoupper(substr($assigneeName, 0, 1)) }}
                        </div>
                    @endif
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-[10px] font-semibold uppercase text-gray-600 dark:bg-darkblack-500 dark:text-gray-300">
                        NA
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2 text-[12px] font-semibold">
                <span class="shrink-0 text-bgray-500 dark:text-bgray-300" title="Estimated time: {{ $estimatedTime }}">
                    {{ $estimatedTime }}
                </span>
                <span class="text-bgray-300 dark:text-bgray-500">|</span>

                <div class="flex items-center gap-2" data-task-timer-root data-task-id="{{ $task->id }}" data-task-timer-persist-display="true">
                    <div class="text-[12px] font-semibold text-bgray-500 dark:text-bgray-300" data-task-timer-display data-task-id="{{ $task->id }}" data-started-at="{{ $timerStartedAt }}" data-total-seconds="{{ $totalTrackedSeconds }}" data-estimated-seconds="{{ $estimatedSeconds }}" data-compare-estimated="true" title="Worked time">
                        <span data-task-timer-text>00:00:00</span>
                    </div>
                    <span class="text-bgray-300 dark:text-bgray-500">|</span>

                    <button type="button" data-task-id="{{ $task->id }}" data-running="{{ $isTimerRunning ? 1 : 0 }}" data-current-user-id="{{ $authUserId ?? '' }}" data-assignee-id="{{ $task->current_assignee_id ?? '' }}" data-assignee-name="{{ $assigneeName ?? 'the assignee' }}" data-task-name="{{ $taskName }}" data-total-seconds="{{ $totalTrackedSeconds }}" data-start-disabled="{{ $canStartTimer ? 0 : 1 }}" data-can-control-timer="{{ $canControlTimer ? 1 : 0 }}" data-disabled-variant="soft" data-button-style="icon" data-enable-running-indicator="1" data-start-switch-enabled="1" @disabled($isTimerDisabled) title="{{ $timerButtonTitle }}"
                        aria-label="{{ $isTimerRunning ? 'Stop timer' : 'Start timer' }}" class="task-timer-btn inline-flex h-7 w-7 items-center justify-center rounded-md transition {{ $isTimerRunning ? 'bg-error-300 text-white hover:bg-red-500' : ($isTimerDisabled ? 'cursor-not-allowed bg-bgray-200 text-bgray-500 dark:bg-darkblack-400 dark:text-bgray-300' : 'bg-success-400 text-white hover:bg-success-300') }} {{ $isTimerRunning ? 'task-timer-btn--running' : '' }}">
                        @if ($isTimerRunning)
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <rect x="2" y="2" width="8" height="8" rx="1.5" />
                            </svg>
                        @else
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M3 2.25V9.75L9.25 6L3 2.25Z" />
                            </svg>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
