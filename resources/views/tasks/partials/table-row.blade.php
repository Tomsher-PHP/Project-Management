@php
    $isSubtask = $isSubtask ?? false;
    $parentTaskId = $parentTaskId ?? null;
    $depth = $depth ?? 0;
    $statusColor = $task->status?->color ?: '#CBD5E1';
    $priorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');
    $typeColor = $task->taskType?->color ?: '#64748B';
    $modeColor = $task->taskMode?->color ?: '#3B82F6';
    $typeLabel = $task->taskType?->name ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'feature'));
    $modeLabel = $task->taskMode?->name ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'new'));
@endphp

<tr class="transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/60 {{ $isSubtask ? 'hidden bg-bgray-50/40 dark:bg-darkblack-500/20' : '' }}" data-task-id="{{ $task->id }}" @if ($isSubtask) data-task-parent-id="{{ $parentTaskId }}" hidden @endif>
    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        <div class="flex items-start gap-3">
            @if ($isSubtask)
                <span class="mt-1 h-5 flex-shrink-0" style="width: {{ max(0, $depth) * 16 }}px" aria-hidden="true"></span>
            @endif

            <div class="min-w-0">
                <a href="{{ route('tasks.edit', $task) }}" class="block">
                    <p class="flex items-center gap-2 font-semibold text-bgray-900 transition hover:text-success-400 dark:text-white dark:hover:text-success-300 {{ $isSubtask ? 'text-base' : 'text-lg' }}" title="{{ $task->name }}">
                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $priorityConfig['bg_class'] ?? 'bg-primary' }}"></span>
                        {{ \Illuminate\Support\Str::limit($task->name, 20, '..') }}
                    </p>
                    <p class="mt-1 text-sm text-[#7C97C1] dark:text-bgray-300">
                        {{ $task->code ?: 'TSK-' . str_pad($task->id, 5, '0', STR_PAD_LEFT) }}
                    </p>
                </a>

                @if ($task->child_tasks_count > 0)
                    <button type="button" class="mt-2 inline-flex max-w-full items-center gap-2 text-sm text-bgray-700 transition hover:text-success-400 dark:text-bgray-200 dark:hover:text-success-300" data-task-subtasks-toggle data-task-subtasks-parent="{{ $task->id }}" aria-expanded="false">
                        <span class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-bgray-700 transition duration-200 dark:text-bgray-100" data-task-subtasks-icon>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.22 4.97a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 11-1.06-1.06L10.94 10 7.22 6.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="font-medium">Subtasks</span>
                        <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded bg-[#DCEAFE] px-1.5 py-0.5 text-[11px] font-semibold leading-none text-[#4F7DBF] dark:bg-success-900/30 dark:text-success-200">
                            {{ $task->child_tasks_count }}
                        </span>
                    </button>
                @endif

                @if ($task->project?->project_flow === 'agile')
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-bgray-500 dark:text-bgray-300">
                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 dark:bg-darkblack-500" title="{{ $task->projectMilestone?->name ?? '--' }}">
                            Milestone: {{ \Illuminate\Support\Str::limit($task->projectMilestone?->name ?? '--', 20, '..') }}
                        </span>
                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 dark:bg-darkblack-500" title="{{ $task->projectSprint?->name ?? '--' }}">
                            Sprint: {{ \Illuminate\Support\Str::limit($task->projectSprint?->name ?? '--', 20, '..') }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        <div class="min-w-0">
            <p class="flex items-center gap-2 truncate text-sm font-semibold text-bgray-900 dark:text-white">
                @if ($task->project)
                    <a href="{{ route('projects.edit', $task->project) }}" class="inline-flex min-w-0 flex-col items-start gap-1 transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                        <span class="inline-flex min-w-0 items-center gap-2">
                            <x-project-flow-icon :flow="$task->project->project_flow" size="sm" />
                            <span class="truncate" title="{{ $task->project->name }}">{{ \Illuminate\Support\Str::limit($task->project->name, 20, '..') }}</span>
                        </span>
                        <span class="pl-6 text-xs font-normal text-[#7C97C1] dark:text-bgray-300">
                            {{ $task->project->project_code ?: '--' }}
                        </span>
                    </a>
                @else
                    <span class="truncate">--</span>
                @endif
            </p>
        </div>
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        @if ($task->currentAssignee)
            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-100">
                {{ $task->currentAssignee->name }}
            </span>
        @else
            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                Unassigned
            </span>
        @endif
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        @if ($task->status)
            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColor }}"></span>
                {{ $task->status->name }}
            </span>
        @else
            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                No status
            </span>
        @endif
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $typeColor }}"></span>
            {{ $typeLabel }}
        </span>
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $modeColor }}"></span>
            {{ $modeLabel }}
        </span>
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        <div class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</div>
        <div class="text-xs text-bgray-500 dark:text-bgray-300">Actual {{ $task->actual_time_formatted }}</div>
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        @if ($task->due_date_time)
            <div class="text-sm font-medium text-bgray-900 dark:text-white">@appDateTime($task->due_date_time)</div>
        @else
            <span class="text-sm text-bgray-500 dark:text-bgray-300">No due date</span>
        @endif
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        <div class="flex items-center gap-2">
            @if ($task->project)
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-500 shadow-sm transition duration-200 hover:border-success-200 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" title="Open task" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$task->project, $task]) }}" data-project-task-group-key="">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                    </svg>
                </button>
            @else
                <a href="{{ route('tasks.edit', $task) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" title="Open task">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                    </svg>
                </a>
            @endif

            @can('delete', $task)
                <x-delete-form :action="route('tasks.destroy', $task->id)" />
            @endcan
        </div>
    </td>
</tr>
