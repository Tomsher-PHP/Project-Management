@php
    $priorityBarClass = $taskPriorityConfig['bg_class'] ?? 'bg-primary';
@endphp

<div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-start gap-3">
                <div class="mt-1 h-12 w-1.5 rounded {{ $priorityBarClass }}"></div>

                <div class="min-w-0">
                    <h2 class="min-w-0">
                        <x-task-name-status
                            :name="$task->name"
                            :request-type="$task->request_type"
                            :request-status="$task->request_status"
                            :truncate="false"
                            display="flex"
                            text-class="text-xl font-bold text-bgray-900 dark:text-white"
                            name-class="break-words"
                            class="max-w-full"
                        />
                    </h2>
                    <p class="text-sm text-bgray-500">
                        Code: {{ $task->code ?: 'TSK-' . str_pad($task->id, 5, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-bgray-600 dark:text-bgray-300">
                <span class="inline-flex items-center gap-2">
                    <strong>Project:</strong>
                    @if ($project)
                        <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center gap-2 transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                            <x-project-flow-icon :flow="$project->project_flow" size="sm" />
                            <span>{{ $project->name }}</span>
                        </a>
                    @else
                        <span>--</span>
                    @endif
                </span>
                @if ($task->projectMilestone)
                    <span>
                        <strong>Milestone:</strong>
                        @if ($project)
                            <a href="{{ route('projects.edit', ['project' => $project, 'tab' => 'milestones', 'milestone' => $task->projectMilestone->id]) }}" class="transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                                {{ $task->projectMilestone->name }}
                            </a>
                        @else
                            {{ $task->projectMilestone->name }}
                        @endif
                    </span>
                @endif
                @if ($task->projectSprint)
                    <span>
                        <strong>Sprint:</strong>
                        @if ($project)
                            <a href="{{ route('projects.edit', ['project' => $project, 'tab' => 'milestones', 'milestone' => $task->projectSprint->project_milestone_id ?: $task->project_milestone_id, 'sprint' => $task->projectSprint->id]) }}" class="transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                                {{ $task->projectSprint->name }}
                            </a>
                        @else
                            {{ $task->projectSprint->name }}
                        @endif
                    </span>
                @endif
                @if ($task->parentTask)
                    <span>
                        <strong>Parent Task:</strong>
                        <a href="{{ route('tasks.edit', $task->parentTask) }}" class="transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                            {{ $task->parentTask->name }}
                        </a>
                    </span>
                @endif
                <span><strong>Type:</strong> {{ $taskTypeLabel }}</span>
                <span><strong>Mode:</strong> {{ $taskModeLabel }}</span>
                <span><strong>Due Date:</strong> @appDateTime($task->due_date_time)</span>
            </div>
        </div>

        @php
            $runningLog = $task->timeLogs()->where('is_running', 1)->latest()->first();
            $timerStartedAt = $runningLog?->started_at->toISOString() ?? null;
        @endphp

        @if ($runningLog)
            <div id="task-timer-display" class="flex items-center gap-2 text-sm font-semibold text-success-500" data-started-at="{{ $timerStartedAt }}" data-total-seconds="{{ $totalTrackedSeconds }}">
                ⏱ <span id="timer-text">00:00:00</span>
            </div>
        @endif

        @php
            $isRunning = $task->timeLogs()->where('is_running', 1)->exists();
        @endphp

        <button type="button"
            data-task-id="{{ $task->id }}"
            data-running="{{ $isRunning ? 1 : 0 }}"
            data-current-user-id="{{ auth()->id() ?? '' }}"
            data-assignee-id="{{ $task->current_assignee_id ?? '' }}"
            data-assignee-name="{{ $task->currentAssignee?->name ?? 'the assignee' }}"
            data-task-name="{{ $task->name }}"
            id="task-timer-btn"
            class="task-timer-btn whitespace-nowrap rounded-lg px-4 py-1 text-sm font-semibold text-white transition {{ $isRunning ? 'bg-error-300 hover:bg-red-500' : 'bg-success-400 hover:bg-success-300' }}">
            {{ $isRunning ? 'Stop' : 'Start' }}
        </button>

        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
            @if ($project && auth()->user()->can('view', $project) && auth()->user()->can('task.edit'))
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-4 py-1 text-sm font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}" data-project-task-group-key="">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M14.166 2.5C14.385 2.28103 14.645 2.10732 14.9311 1.98879C15.2173 1.87026 15.524 1.8092 15.8338 1.8092C16.1435 1.8092 16.4503 1.87026 16.7364 1.98879C17.0225 2.10732 17.2823 2.28103 17.5013 2.5C17.7202 2.71897 17.8939 2.97874 18.0125 3.26487C18.131 3.551 18.1921 3.85768 18.1921 4.16746C18.1921 4.47723 18.131 4.78391 18.0125 5.07004C17.8939 5.35617 17.7202 5.61594 17.5013 5.83491L6.25033 17.0858L1.66602 18.3341L2.91435 13.7498L14.166 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Edit Task</span>
                </button>
            @endif

            <div class="flex items-center gap-2.5">
                <x-task-status-dropdown
                    :task="$task"
                    :statuses="$taskStatusOptions ?? collect()"
                    :can-change="$canChangeTaskStatus ?? false"
                    :transition-url="$taskStatusTransitionUrl ?? null"
                    :include-task-detail="true"
                />
            </div>

            <span class="whitespace-nowrap rounded-full border border-bgray-200 bg-bgray-100 px-4 py-1 text-sm font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                {{ $task->currentAssignee?->name ?? 'Unassigned' }}
            </span>
        </div>
    </div>
</div>
