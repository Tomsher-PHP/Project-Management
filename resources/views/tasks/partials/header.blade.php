@php
    $priorityBarClass = $taskPriorityConfig['bg_class'] ?? 'bg-primary';
    $statusColor = $task->status?->color ?: '#94A3B8';
@endphp

<div class="rounded-lg bg-white p-5 dark:bg-darkblack-600">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex items-start gap-3">
                <div class="mt-1 h-12 w-1.5 rounded {{ $priorityBarClass }}"></div>

                <div class="min-w-0">
                    <h2 class="truncate text-xl font-bold text-bgray-900 dark:text-white">
                        {{ $task->name }}
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
                <span><strong>Due Date:</strong> {{ \App\Providers\AppServiceProvider::formatAppDateTime($task->due_date_time) }}</span>
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

        <button type="button" data-task-id="{{ $task->id }}" data-running="{{ $isRunning ? 1 : 0 }}" id="task-timer-btn" class="task-timer-btn whitespace-nowrap rounded-lg px-4 py-1 text-sm font-semibold text-white transition {{ $isRunning ? 'bg-error-300 hover:bg-red-500' : 'bg-success-400 hover:bg-success-300' }}">
            {{ $isRunning ? 'Stop' : 'Start' }}
        </button>

        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
            <span class="whitespace-nowrap rounded-full px-4 py-1 text-sm font-semibold text-white" style="border: 1px solid {{ $statusColor }}; background-color: {{ $statusColor }};">
                {{ $task->status?->name ?? 'No Status' }}
            </span>

            <span class="whitespace-nowrap rounded-full border border-bgray-200 bg-bgray-100 px-4 py-1 text-sm font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                {{ $task->currentAssignee?->name ?? 'Unassigned' }}
            </span>
        </div>
    </div>
</div>
