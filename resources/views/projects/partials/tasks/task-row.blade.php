@php
    $isSubtask = $isSubtask ?? false;
    $parentTaskId = $parentTaskId ?? null;
    $depth = $depth ?? 0;
    $isDeletedProjectView = $project->trashed();
    $isBacklogPlacement = $project->project_flow !== 'linear' && !$task->project_sprint_id;
    $shouldPrefillPlacement = !$isBacklogPlacement && !($task->projectSprint?->is_backlog || $task->projectSprint?->is_system || $task->projectMilestone?->is_backlog || $task->projectMilestone?->is_system);
    $statusColor = $task->status?->color ?: '#CBD5E1';
    $priorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');
    $typeColor = $task->taskType?->color ?: '#64748B';
    $typeLabel = $task->taskType?->name ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'feature'));
    $modeColor = $task->taskMode?->color ?: '#3B82F6';
    $modeLabel = $task->taskMode?->name ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'new'));
    $canAddSubTask = !$isDeletedProjectView && auth()->user()?->can('task.create');
    $canMoveTask = !$isDeletedProjectView && $showTaskActionColumn && !$isSubtask && auth()->user()?->can('move', $task);
    $canDeleteTask = !$isDeletedProjectView && $showTaskActionColumn && auth()->user()?->can('delete', $task);
    $canEditTask = !$isDeletedProjectView && $showTaskActionColumn && auth()->user()?->can('task.edit');
@endphp

<tr class="transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/60 {{ $isSubtask ? 'hidden bg-bgray-50/30 dark:bg-darkblack-500/20' : '' }}" data-project-task-id="{{ $task->id }}" @if ($isSubtask) data-project-task-parent-id="{{ $parentTaskId }}" hidden @endif>
    <td class="group border border-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        <div class="flex items-start gap-2.5">
            @if ($isSubtask)
                <span class="mt-1 h-5 flex-shrink-0" style="width: {{ max(0, $depth) * 16 }}px" aria-hidden="true"></span>
            @endif

            <div class="flex min-w-0 flex-1 items-start justify-between gap-2.5">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($isDeletedProjectView)
                            <div class="inline-flex items-center gap-2 font-semibold text-bgray-900 dark:text-white">
                                <x-task-name-status :name="$task->name" :request-type="$task->request_type" :request-status="$task->request_status" :limit="20" limit-end=".." show-priority-indicator priority-indicator="line" :priority-class="$priorityConfig['bg_class'] ?? 'bg-primary'" :text-class="($isSubtask ? 'text-sm' : 'text-base') . ' font-semibold'" class="max-w-full" />
                            </div>
                        @else
                            <a href="{{ route('tasks.edit', $task) }}" class="inline-flex items-center gap-2 font-semibold text-bgray-900 transition hover:text-success-400 dark:text-white dark:hover:text-success-300">
                                <x-task-name-status :name="$task->name" :request-type="$task->request_type" :request-status="$task->request_status" :limit="20" limit-end=".." show-priority-indicator priority-indicator="line" :priority-class="$priorityConfig['bg_class'] ?? 'bg-primary'" :text-class="($isSubtask ? 'text-sm' : 'text-base') . ' font-semibold transition hover:text-success-400 dark:hover:text-success-300'" class="max-w-full" />
                            </a>
                        @endif

                        <span class="rounded-full bg-bgray-100 px-2 py-0.5 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                            {{ $task->code ?: 'T-' . str_pad($task->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    @if ($task->child_tasks_count > 0)
                        <button type="button" class="mt-1 inline-flex max-w-full items-center gap-1.5 text-xs text-bgray-700 transition hover:text-success-400 dark:text-bgray-300 dark:hover:text-success-300" data-project-task-subtasks-toggle data-project-task-subtasks-parent="{{ $task->id }}" aria-expanded="false">
                            <span class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-bgray-700 transition duration-200 dark:text-bgray-300" data-project-task-subtasks-icon>
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

                    @if ($task->parentTask && !$isSubtask)
                        <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">Child of {{ $task->parentTask->name }}</p>
                    @endif
                </div>

                @if (!$isDeletedProjectView)
                    <button type="button" class="invisible inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border border-transparent text-bgray-600 opacity-0 transition duration-150 group-hover:visible group-hover:opacity-100 hover:border-success-200 hover:text-success-400 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" title="Open task" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}" data-project-task-group-key="{{ $group['key'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        @if ($task->currentAssignee)
            <div class="flex items-center gap-2">
                <x-user-avatar :user="$task->currentAssignee" size="xs" class="ring-2 ring-white dark:ring-darkblack-500" />
                <div>
                    <p class="text-xs text-bgray-900 dark:text-white">{{ $task->currentAssignee->name }}</p>
                </div>
            </div>
        @else
            <span class="inline-flex rounded-full bg-bgray-100 px-2 py-0.5 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                Unassigned
            </span>
        @endif
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        @if ($task->status)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-bgray-200 px-2 py-0.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                <span class="h-2 w-2 rounded-full" style="background-color: {{ $statusColor }}"></span>
                {{ $task->status->name }}
            </span>
        @else
            <span class="inline-flex rounded-full bg-bgray-100 px-2 py-0.5 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                No status
            </span>
        @endif
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-bgray-200 px-2 py-0.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
            <span class="h-2 w-2 rounded-full" style="background-color: {{ $typeColor }}"></span>
            {{ $typeLabel }}
        </span>
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-bgray-200 px-2 py-0.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
            <span class="h-2 w-2 rounded-full" style="background-color: {{ $modeColor }}"></span>
            {{ $modeLabel }}
        </span>
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        <div class="text-xs font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</div>
        <div class="text-xs text-bgray-700 dark:text-bgray-300">Actual {{ $task->actual_time_formatted }}</div>
    </td>

    <td class="border-b border-bgray-200 px-3 py-2 align-top dark:border-darkblack-400">
        @if ($task->due_date_time)
            <div class="inline-flex items-center gap-0.5 text-xs font-medium {{ taskDueDateClass($task->due_date_time, $task->estimated_time_seconds, $task) }}">
                {!! taskDueDateIcon($task->due_date_time, $task->estimated_time_seconds, $task) !!}
                <span>@appDateTime($task->due_date_time)</span>
            </div>
        @else
            <span class="text-xs text-bgray-700 dark:text-bgray-300">No due date</span>
        @endif
    </td>

    @if ($showTaskActionColumn)
        <td class="border-b border-bgray-200 px-3 py-2 align-top text-right dark:border-darkblack-400">
            <x-task-action-context-menu
                :task="$task"
                :project="$project"
                :group-key="$group['key']"
                :can-add-sub-task="$canAddSubTask"
                :can-edit-task="$canEditTask"
                :can-view-logs="!$isDeletedProjectView"
                :can-move-task="$canMoveTask"
                :can-delete-task="$canDeleteTask"
                :should-prefill-placement="$shouldPrefillPlacement"
            />
        </td>
    @endif
</tr>
