@props([
    'task',
    'project' => null,
    'context' => 'project',
    'groupKey' => '',
    'canAddSubTask' => false,
    'canEditTask' => false,
    'canViewLogs' => false,
    'canMoveTask' => false,
    'canDeleteTask' => false,
    'shouldPrefillPlacement' => false,
])

@php
    $isTaskIndex = $context === 'task-index';
    $hasActions = $canAddSubTask || $canEditTask || $canViewLogs || $canMoveTask || $canDeleteTask;
    $taskCode = $task->code ?: 'T-' . str_pad($task->id, 3, '0', STR_PAD_LEFT);
@endphp

@if ($hasActions)
    <div class="relative inline-flex" data-project-task-row-dropdown>
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-bgray-200 bg-white text-bgray-700 transition hover:border-success-200 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" data-project-task-row-menu-trigger aria-expanded="false" aria-label="Task actions">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 3.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
            </svg>
        </button>

        <div class="z-1 hidden min-w-[148px] overflow-hidden rounded-xl border border-bgray-200 bg-white py-1 shadow-lg dark:border-darkblack-400 dark:bg-darkblack-500" data-project-task-row-menu>
            @if ($canAddSubTask && $project)
                @if ($isTaskIndex)
                    <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-task-create-open data-task-create-project-id="{{ $project->id }}" data-task-create-project-name="{{ $project->name }}" data-task-create-project-code="{{ $project->project_code }}" data-task-create-milestone-id="{{ $shouldPrefillPlacement ? $task->project_milestone_id ?? '' : '' }}" data-task-create-sprint-id="{{ $shouldPrefillPlacement ? $task->project_sprint_id ?? '' : '' }}" data-task-create-parent-task-id="{{ $task->id }}">
                @else
                    <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-modal-open data-project-task-milestone-id="{{ $shouldPrefillPlacement ? $task->project_milestone_id ?? '' : '' }}" data-project-task-sprint-id="{{ $shouldPrefillPlacement ? $task->project_sprint_id ?? '' : '' }}" data-project-task-parent-task-id="{{ $task->id }}">
                @endif
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 4a.75.75 0 01.75.75v4.5h4.5a.75.75 0 010 1.5h-4.5v4.5a.75.75 0 01-1.5 0v-4.5h-4.5a.75.75 0 010-1.5h4.5v-4.5A.75.75 0 0110 4z" clip-rule="evenodd" />
                    </svg>
                    <span>Add Sub Task</span>
                </button>
            @endif

            @if ($canEditTask)
                @if ($project)
                    <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-detail-open data-project-task-detail-url="{{ route('projects.tasks.modal', [$project, $task]) }}" data-project-task-group-key="{{ $groupKey }}">
                @else
                    <a href="{{ route('tasks.edit', $task) }}" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                @endif
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                    </svg>
                    <span>Edit</span>
                @if ($project)
                    </button>
                @else
                    </a>
                @endif
            @endif

            @if ($canViewLogs && $project)
                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-task-log-open data-task-log-url="{{ route('projects.tasks.logs', [$project, $task]) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 3a.75.75 0 00-1.5 0v5c0 .199.079.389.22.53l2.5 2.5a.75.75 0 001.06-1.06l-2.28-2.28V5z" clip-rule="evenodd" />
                    </svg>
                    <span>View Logs</span>
                </button>
            @endif

            @if ($canMoveTask && $project)
                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-move-open data-project-task-move-url="{{ route('projects.tasks.move', [$project, $task]) }}" data-project-task-name="{{ $task->name }}" data-project-task-code="{{ $taskCode }}" data-project-task-current-sprint="{{ $task->projectSprint?->name ?? 'Unscheduled' }}" data-project-task-current-sprint-id="{{ $task->project_sprint_id ?? '' }}" data-project-task-current-module="{{ $task->projectMilestone?->name ?? 'None' }}" data-project-task-current-timeline="{{ $task->projectTimeline?->name ?? 'None' }}" data-project-task-current-timeline-id="{{ $task->project_timeline_id ?? '' }}" data-project-task-source-project-id="{{ $project->id }}" data-project-task-source-project-name="{{ $project->name }}" data-project-task-source-project-code="{{ $project->project_code }}" data-project-task-source-project-flow="{{ $project->project_flow }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.25 3a.75.75 0 01.75.75v8.69l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06L9.5 12.44V3.75A.75.75 0 0110.25 3z" clip-rule="evenodd" />
                    </svg>
                    <span>Move</span>
                </button>
            @endif

            @if ($canDeleteTask)
                @if ($isTaskIndex)
                    <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-red-500 transition hover:bg-red-50 hover:text-red-600 dark:text-red-300 dark:hover:bg-darkblack-400 dark:hover:text-red-200">
                @else
                    <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-red-500 transition hover:bg-red-50 hover:text-red-600 dark:text-red-300 dark:hover:bg-darkblack-400 dark:hover:text-red-200" data-project-task-delete data-project-task-delete-url="{{ route('projects.tasks.destroy', [$project, $task]) }}" data-project-task-name="{{ $task->name }}">
                @endif
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M 6.496094 1 C 5.675781 1 5 1.675781 5 2.496094 L 5 3 L 2 3 L 2 4 L 3 4 L 3 12.5 C 3 13.328125 3.671875 14 4.5 14 L 10.5 14 C 11.328125 14 12 13.328125 12 12.5 L 12 4 L 13 4 L 13 3 L 10 3 L 10 2.496094 C 10 1.675781 9.324219 1 8.503906 1 Z M 6.496094 2 L 8.503906 2 C 8.785156 2 9 2.214844 9 2.496094 L 9 3 L 6 3 L 6 2.496094 C 6 2.214844 6.214844 2 6.496094 2 Z M 5 5 L 6 5 L 6 12 L 5 12 Z M 7 5 L 8 5 L 8 12 L 7 12 Z M 9 5 L 10 5 L 10 12 L 9 12 Z" />
                    </svg>
                    <span>Delete</span>
                </button>
                @if ($isTaskIndex)
                    </form>
                @endif
            @endif
        </div>
    </div>
@endif
