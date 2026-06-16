@php
    $priorityBarClass = $taskPriorityConfig['bg_class'] ?? 'bg-primary';
    $customer = $project?->customer;
@endphp

<div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-start gap-3">
                <div class="mt-1 h-12 w-1.5 rounded {{ $priorityBarClass }}"></div>

                <div class="min-w-0">
                    <h2 class="min-w-0">
                        <x-task-name-status :name="$task->name" :request-type="$task->request_type" :request-status="$task->request_status" :truncate="false" display="flex" text-class="text-xl font-bold text-bgray-900 dark:text-white" name-class="break-words" class="max-w-full" />
                    </h2>
                    <p class="text-sm text-bgray-700">
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
                <span>
                    <strong>Due Date:</strong>
                    <span class="{{ taskDueDateClass($task->due_date_time, $task->estimated_time_seconds, $task->status) }} inline-flex items-center gap-0.5">
                        {!! taskDueDateIcon($task->due_date_time, $task->estimated_time_seconds, $task->status) !!}
                        @if ($task->due_date_time)
                            @appDateTime($task->due_date_time)
                        @else
                            --
                        @endif
                    </span>
                </span>
            </div>
        </div>

        @php
            $canStartTimerFromStatus = ($task->status?->type ?? null) === 'active';
        @endphp

        @php
            $isRunning = $task->timeLogs()->where('is_running', 1)->exists();
            $isStartDisabled = !$isRunning && !$canStartTimerFromStatus;
        @endphp

        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
            @if ($customer)
                <x-profile-grade-badge :grade="$customer->profileGrade" size="lg" />
            @endif

            <div class="min-w-0">
                @if ($task->currentAssignee)
                    <x-user-avatar :user="$task->currentAssignee" size="md" title="Assignee: {{ $task->currentAssignee->name }}" />
                @else
                    <p class="text-sm font-semibold text-bgray-700 dark:text-bgray-300">Unassigned</p>
                @endif
            </div>

            <div class="flex items-center gap-2" data-task-timer-root data-task-id="{{ $task->id }}">
                <button type="button" data-task-id="{{ $task->id }}" data-running="{{ $isRunning ? 1 : 0 }}" data-current-user-id="{{ auth()->id() ?? '' }}" data-assignee-id="{{ $task->current_assignee_id ?? '' }}" data-assignee-name="{{ $task->currentAssignee?->name ?? 'the assignee' }}" data-task-name="{{ $task->name }}" data-total-seconds="{{ $totalTrackedSeconds }}" data-estimated-seconds="{{ (int) ($task->estimated_time_seconds ?? 0) }}" data-start-disabled="{{ $isStartDisabled ? 1 : 0 }}" data-disabled-variant="strong" id="task-timer-btn" @disabled($isStartDisabled) @if ($isStartDisabled) title="Move this task to an active status before starting the timer." @endif
                    class="task-timer-btn inline-flex h-9 items-center justify-center whitespace-nowrap rounded-lg px-3 text-xs font-semibold transition {{ $isRunning ? 'bg-error-300 text-white hover:bg-red-500' : ($isStartDisabled ? 'cursor-not-allowed bg-bgray-300 text-bgray-600 dark:bg-darkblack-400 dark:text-bgray-300' : 'bg-success-400 text-white hover:bg-success-300') }}">
                    {{ $isRunning ? 'Stop' : 'Start' }}
                </button>
            </div>

            <div class="flex items-center gap-2.5 [&_[data-task-status-dropdown]]:min-w-[132px] [&_[data-task-status-trigger]]:h-9 [&_[data-task-status-trigger]]:w-[132px] [&_[data-task-status-trigger]]:px-3 [&_[data-task-status-trigger]]:text-xs [&_[data-task-status-dropdown]]:sm:min-w-[140px] [&_[data-task-status-trigger]]:sm:w-[140px] [&_[data-task-status-menu]]:top-11">
                <x-task-status-dropdown :task="$task" :statuses="$taskStatusOptions ?? collect()" :can-change="$canChangeTaskStatus ?? false" :transition-url="$taskStatusTransitionUrl ?? null" :include-task-detail="true" />
            </div>

            @if ($project && auth()->user()->can('view', $project) && auth()->user()->can('task.edit'))
                <button type="button" class="inline-flex h-9 items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}" data-project-task-group-key="">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M14.166 2.5C14.385 2.28103 14.645 2.10732 14.9311 1.98879C15.2173 1.87026 15.524 1.8092 15.8338 1.8092C16.1435 1.8092 16.4503 1.87026 16.7364 1.98879C17.0225 2.10732 17.2823 2.28103 17.5013 2.5C17.7202 2.71897 17.8939 2.97874 18.0125 3.26487C18.131 3.551 18.1921 3.85768 18.1921 4.16746C18.1921 4.47723 18.131 4.78391 18.0125 5.07004C17.8939 5.35617 17.7202 5.61594 17.5013 5.83491L6.25033 17.0858L1.66602 18.3341L2.91435 13.7498L14.166 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Edit</span>
                </button>
            @endif
        </div>
    </div>
</div>
