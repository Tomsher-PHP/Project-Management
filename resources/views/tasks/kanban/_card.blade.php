@php
    $authUser = auth()->user();
    $authUserId = $authUser?->id;
    $taskTimerService = app(\App\Services\TaskServices::class);
    $project = $task->project;
    $customer = $project?->customer;
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

    $totalTrackedSeconds = (int) ($task->kanban_timer_tracked_seconds ?? 0);
    $timerCurrentSeconds = (int) ($task->kanban_timer_current_seconds ?? $totalTrackedSeconds);
    $timerStartedAt = $task->kanban_timer_started_at_iso;
    $isTimerRunning = (bool) ($task->kanban_timer_is_running ?? false);
    $timerTimeColorClass = (string) ($task->kanban_timer_time_color_class ?? 'text-bgray-700 dark:text-bgray-300');
    $startRestriction = $authUser ? $taskTimerService->getStartRestriction($task, $authUser) : ['message' => 'Not allowed to start timer for this task.'];
    $canStartTimer = $authUser ? $startRestriction === null || in_array($startRestriction['reason'] ?? null, ['running_timer_exists', 'already_running'], true) : false;
    $canStopTimer = $authUser ? $taskTimerService->isAllowedToStop($task, $authUser) : false;
    $canControlTimer = $canStartTimer || $canStopTimer;
    $isTimerDisabled = $isTimerRunning ? !$canStopTimer : !$canStartTimer;
    $timerButtonTitle = $isTimerRunning ? ($canStopTimer ? 'Stop timer' : 'You are not allowed to stop this task timer.') : ($canStartTimer ? 'Start timer' : $startRestriction['message'] ?? 'You are not allowed to start this task timer.');

    $taskName = $task->name ?? ($task->code ?? 'Untitled task');
    $projectName = $project?->name;
    $milestoneName = $milestone?->name;
    $sprintName = $sprint?->name;
    $assigneeName = $task->currentAssignee?->name;
    $commentsCount = $task->comments_count ?? ($task->relationLoaded('comments') ? $task->comments->count() : 0);

    $prjLimit = $milestoneName ? 16 : 40;
@endphp

<div class="card rounded-md bg-white shadow-sm transition hover:shadow-md dark:bg-darkblack-600" data-task-id="{{ $task->id }}">
    <div class="space-y-3 p-4">
        <div class="{{ $canOpenTask ? 'cursor-pointer' : 'cursor-default' }}" @if ($canOpenTask) data-project-task-detail-open
                data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}"
                data-project-task-group-key=""
                title="Open task" @endif>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1 text-[11px] font-medium text-bgray-900 dark:text-bgray-300">
                        @if ($project)
                            @if ($customer)
                                <x-profile-grade-badge :grade="$customer->profileGrade" size="sm" class="mt-0.5" />
                            @endif
                            <span class="truncate" title="{{ $projectName }}">{{ limitStringChar($projectName, $prjLimit) }}</span>
                        @endif

                        @if (($project?->project_flow ?? null) === 'agile' && $milestoneName)
                            <span class="shrink-0 text-bgray-900 dark:text-bgray-700">
                                <svg width="8" height="8" viewBox="0 0 6 12" fill="none" class="fill-current transition-transform" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M0.531506 0.414376C0.20806 0.673133 0.155619 1.1451 0.414376 1.46855L4.03956 6.00003L0.414376 10.5315C0.155618 10.855 0.208059 11.3269 0.531506 11.5857C0.854952 11.8444 1.32692 11.792 1.58568 11.4685L5.58568 6.46855C5.80481 6.19464 5.80481 5.80542 5.58568 5.53151L1.58568 0.531506C1.32692 0.20806 0.854953 0.155619 0.531506 0.414376Z" />
                                </svg>
                            </span>
                            <span class="truncate" title="{{ $milestoneName }}">{{ limitStringChar($milestoneName, 16) }}</span>
                        @endif
                    </div>
                </div>

                @if ($sprintName)
                    <span class="inline-flex max-w-[112px] shrink-0 rounded-full bg-success-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-success-400 dark:bg-darkblack-500 dark:text-success-300" title="{{ $sprintName }}">
                        <span class="truncate">{{ limitStringChar($sprintName, 16) }}</span>
                    </span>
                @endif
            </div>

            <div class="mt-3" title="{{ $taskName }}">
                <x-task-name-status :name="$taskName" :request-type="$task->request_type" :request-status="$task->request_status" :limit="40" text-class="text-sm font-semibold leading-snug {{ $isCompleted ? 'line-through text-gray-400 dark:text-gray-500' : 'text-bgray-900 dark:text-white' }}" name-class="block" class="max-w-full" />
            </div>

            <div class="mt-3 flex items-center justify-between gap-3 text-[11px]">
                <div class="flex items-center gap-2">
                    <span class="inline-flex shrink-0 border bg-white px-1 py-0 font-semibold {{ $priorityTextClass }}" style="border-color: currentColor;" title="Priority: {{ $priorityLabel }}">
                        {{ $priorityLabel }}
                    </span>
                </div>

                @if ($dueDate)
                    <span class="inline-flex min-w-0 items-center gap-1 text-right leading-5 {{ taskDueDateClass($dueDate, $task->estimated_time_seconds, $task) }}" title="{{ $dueDateDisplay }}">
                        {!! taskDueDateIcon($dueDate, $task->estimated_time_seconds, $task) !!}
                        <span class="truncate text-[11px] font-medium">{{ $dueDateDisplay }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div title="{{ $assigneeName ?: 'Not Assigned' }}">
                    @if ($task->currentAssignee)
                        <x-user-avatar :user="$task->currentAssignee" size="sm" />
                    @else
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-[10px] font-semibold uppercase text-gray-600 dark:bg-darkblack-500 dark:text-gray-300">
                            NA
                        </div>
                    @endif
                </div>

                <button type="button" class="group inline-flex items-center gap-1 px-2 py-0.5 font-semibold text-bgray-600 transition-all duration-150 dark:text-bgray-300" title="View Comments" data-task-insights-trigger data-task-insights-url="{{ route('tasks.comments.modal', $task) }}">
                    <span class="inline-flex shrink-0 text-bgray-500 group-hover:text-bgray-700 dark:text-bgray-400 dark:group-hover:text-bgray-200 transition-colors">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" />
                        </svg>
                    </span>
                    <span class="font-medium text-[11px]">{{ $commentsCount }}</span>
                </button>
            </div>

            <div class="flex items-center gap-2 text-[12px] font-semibold">
                <span class="shrink-0 text-bgray-600 dark:text-bgray-300" title="Estimated time: {{ $estimatedTime }}">
                    {{ $estimatedTime }}
                </span>
                <span class="text-bgray-300 dark:text-bgray-700">|</span>

                <div class="flex items-center gap-2" data-task-timer-root data-task-id="{{ $task->id }}" data-task-timer-persist-display="true">
                    <div class="text-[12px] font-semibold {{ $timerTimeColorClass }}" data-task-timer-display data-task-id="{{ $task->id }}" data-started-at="{{ $timerStartedAt }}" data-total-seconds="{{ $totalTrackedSeconds }}" data-estimated-seconds="{{ $estimatedSeconds }}" data-compare-estimated="true" title="Worked time">
                        <span data-task-timer-text>{{ gmdate('H:i:s', $timerCurrentSeconds) }}</span>
                    </div>

                    <button type="button" data-task-id="{{ $task->id }}" data-running="{{ $isTimerRunning ? 1 : 0 }}" data-current-user-id="{{ $authUserId ?? '' }}" data-assignee-id="{{ $task->current_assignee_id ?? '' }}" data-assignee-name="{{ $assigneeName ?? 'the assignee' }}" data-task-name="{{ $taskName }}" data-total-seconds="{{ $totalTrackedSeconds }}" data-start-disabled="{{ $canStartTimer ? 0 : 1 }}" data-can-control-timer="{{ $canControlTimer ? 1 : 0 }}" data-disabled-variant="soft" data-button-style="icon" data-enable-running-indicator="1"
                        data-start-switch-enabled="1" @disabled($isTimerDisabled) title="{{ $timerButtonTitle }}" aria-label="{{ $isTimerRunning ? 'Stop timer' : 'Start timer' }}" class="task-timer-btn inline-flex h-7 w-7 items-center justify-center rounded-md transition {{ $isTimerRunning ? 'bg-error-300 text-white hover:bg-red-500' : ($isTimerDisabled ? 'cursor-not-allowed bg-bgray-200 text-bgray-700 dark:bg-darkblack-400 dark:text-bgray-300' : 'bg-success-400 text-white hover:bg-success-300') }} {{ $isTimerRunning ? 'task-timer-btn--running' : '' }}">
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
