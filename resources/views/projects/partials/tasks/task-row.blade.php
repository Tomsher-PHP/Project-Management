@php
    $isSubtask = $isSubtask ?? false;
    $parentTaskId = $parentTaskId ?? null;
    $depth = $depth ?? 0;
    $isBacklogPlacement = $project->project_flow !== 'linear' && !$task->project_sprint_id;
    $shouldPrefillPlacement = !$isBacklogPlacement && !($task->projectSprint?->is_backlog || $task->projectSprint?->is_system || $task->projectMilestone?->is_backlog || $task->projectMilestone?->is_system);
    $statusColor = $task->status?->color ?: '#CBD5E1';
    $priorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');
    $typeColor = $task->taskType?->color ?: '#64748B';
    $typeLabel = $task->taskType?->name ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'feature'));
    $modeColor = $task->taskMode?->color ?: '#3B82F6';
    $modeLabel = $task->taskMode?->name ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'new'));
    $canAddSubTask = auth()->user()?->can('task.create');
    $canMoveTask = $showTaskActionColumn && !$isSubtask && auth()->user()?->can('move', $task);
    $canDeleteTask = $showTaskActionColumn && auth()->user()?->can('delete', $task);
    $canEditTask = $showTaskActionColumn && auth()->user()?->can('task.edit');
@endphp

<tr class="transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/60 {{ $isSubtask ? 'hidden bg-bgray-50/30 dark:bg-darkblack-500/20' : '' }}" data-project-task-id="{{ $task->id }}" @if ($isSubtask) data-project-task-parent-id="{{ $parentTaskId }}" hidden @endif>
    <td class="group border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
        <div class="flex items-start gap-3">
            @if ($isSubtask)
                <span class="mt-1 h-5 flex-shrink-0" style="width: {{ max(0, $depth) * 16 }}px" aria-hidden="true"></span>
            @endif

            <div class="flex min-w-0 flex-1 items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('tasks.edit', $task) }}" class="inline-flex items-center gap-2 font-semibold text-bgray-900 transition hover:text-success-400 dark:text-white dark:hover:text-success-300">
                            <x-task-name-status :name="$task->name" :request-type="$task->request_type" :request-status="$task->request_status" :limit="20" limit-end=".." show-priority-indicator priority-indicator="line" :priority-class="$priorityConfig['bg_class'] ?? 'bg-primary'" :text-class="($isSubtask ? 'text-base' : 'text-lg') . ' font-semibold text-bgray-900 transition hover:text-success-400 dark:text-white dark:hover:text-success-300'" class="max-w-full" />
                        </a>

                        <span class="rounded-full bg-bgray-100 px-2 py-0.5 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                            {{ $task->code ?: 'T-' . str_pad($task->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    @if ($task->child_tasks_count > 0)
                        <button type="button" class="mt-2 inline-flex max-w-full items-center gap-2 text-sm text-bgray-700 transition hover:text-success-400 dark:text-bgray-200 dark:hover:text-success-300" data-project-task-subtasks-toggle data-project-task-subtasks-parent="{{ $task->id }}" aria-expanded="false">
                            <span class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-bgray-700 transition duration-200 dark:text-bgray-100" data-project-task-subtasks-icon>
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
                        <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Child of {{ $task->parentTask->name }}</p>
                    @endif
                </div>

                <button type="button" class="invisible inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border border-transparent text-bgray-400 opacity-0 transition duration-150 group-hover:visible group-hover:opacity-100 hover:border-success-200 hover:text-success-400 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" title="Open task" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}" data-project-task-group-key="{{ $group['key'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                    </svg>
                </button>
            </div>
        </div>
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
        @if ($task->currentAssignee)
            <div class="flex items-center gap-3">
                <img src="{{ $task->currentAssignee->profile_image_url }}" alt="{{ $task->currentAssignee->name }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-white dark:ring-darkblack-500">
                <div>
                    <p class="font-medium text-bgray-900 dark:text-white">{{ $task->currentAssignee->name }}</p>
                </div>
            </div>
        @else
            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                Unassigned
            </span>
        @endif
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
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

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $typeColor }}"></span>
            {{ $typeLabel }}
        </span>
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $modeColor }}"></span>
            {{ $modeLabel }}
        </span>
    </td>

    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
        <div class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</div>
        <div class="text-xs text-bgray-500 dark:text-bgray-300">Actual {{ $task->actual_time_formatted }}</div>
    </td>

    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
        @if ($task->due_date_time)
            <div class="inline-flex items-center gap-0.5 text-sm font-medium text-bgray-900 dark:text-white {{ taskDueDateClass($task->due_date_time, $task->estimated_time_seconds, $task->status) }}">
                {!! taskDueDateIcon($task->due_date_time, $task->estimated_time_seconds, $task->status) !!}
                <span>@appDateTime($task->due_date_time)</span>
            </div>
        @else
            <span class="text-sm text-bgray-500 dark:text-bgray-300">No due date</span>
        @endif
    </td>

    @if ($showTaskActionColumn)
        <td class="border-b border-bgray-200 px-4 py-4 align-top text-right dark:border-b-darkblack-400">
            @if ($canAddSubTask || $canMoveTask || $canDeleteTask || $canEditTask)
                <div class="relative inline-flex" data-project-task-row-dropdown>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-bgray-200 bg-white text-bgray-500 transition hover:border-success-200 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" data-project-task-row-menu-trigger aria-expanded="false" aria-label="Task actions">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                        </svg>
                    </button>

                    <div class="hidden min-w-[148px] overflow-hidden rounded-xl border border-bgray-200 bg-white py-1 shadow-lg dark:border-darkblack-400 dark:bg-darkblack-500" data-project-task-row-menu>
                        @if ($canAddSubTask)
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-modal-open data-project-task-module-id="{{ $shouldPrefillPlacement ? $task->project_milestone_id ?? '' : '' }}" data-project-task-sprint-id="{{ $shouldPrefillPlacement ? $task->project_sprint_id ?? '' : '' }}" data-project-task-parent-task-id="{{ $task->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 4a.75.75 0 01.75.75v4.5h4.5a.75.75 0 010 1.5h-4.5v4.5a.75.75 0 01-1.5 0v-4.5h-4.5a.75.75 0 010-1.5h4.5v-4.5A.75.75 0 0110 4z" clip-rule="evenodd" />
                                </svg>
                                <span>Add Sub Task</span>
                            </button>
                        @endif

                        @if ($canEditTask)
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}" data-project-task-group-key="{{ $group['key'] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                                </svg>
                                <span>Edit</span>
                            </button>
                        @endif

                        @if ($canMoveTask)
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-move-open data-project-task-move-url="{{ route('projects.tasks.move', [$project, $task]) }}" data-project-task-name="{{ $task->name }}" data-project-task-current-sprint="{{ $task->projectSprint?->name ?? 'Unscheduled' }}" data-project-task-current-module="{{ $task->projectMilestone?->name ?? 'None' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.25 3a.75.75 0 01.75.75v8.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06L9.5 12.44V3.75A.75.75 0 0110.25 3z" clip-rule="evenodd" />
                                </svg>
                                <span>Move</span>
                            </button>
                        @endif

                        @if ($canDeleteTask)
                            <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-red-500 transition hover:bg-red-50 hover:text-red-600 dark:text-red-300 dark:hover:bg-darkblack-400 dark:hover:text-red-200" data-project-task-delete data-project-task-delete-url="{{ route('projects.tasks.destroy', [$project, $task]) }}" data-project-task-name="{{ $task->name }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M 6.496094 1 C 5.675781 1 5 1.675781 5 2.496094 L 5 3 L 2 3 L 2 4 L 3 4 L 3 12.5 C 3 13.328125 3.671875 14 4.5 14 L 10.5 14 C 11.328125 14 12 13.328125 12 12.5 L 12 4 L 13 4 L 13 3 L 10 3 L 10 2.496094 C 10 1.675781 9.324219 1 8.503906 1 Z M 6.496094 2 L 8.503906 2 C 8.785156 2 9 2.214844 9 2.496094 L 9 3 L 6 3 L 6 2.496094 C 6 2.214844 6.214844 2 6.496094 2 Z M 5 5 L 6 5 L 6 12 L 5 12 Z M 7 5 L 8 5 L 8 12 L 7 12 Z M 9 5 L 10 5 L 10 12 L 9 12 Z"></path>
                                </svg>
                                <span>Delete</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </td>
    @endif
</tr>
