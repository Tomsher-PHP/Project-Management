@php
    $isDeletedProjectView = $project->trashed();
    $taskGroupsPageUrl = $isDeletedProjectView ? route('projects.restore.tasks.groups.index', ['id' => $project->id]) : route('projects.tasks.groups.index', $project);
    $taskCreateDefaultSprintId = $projectSprints
        ->reject(fn($projectSprint) => (bool) ($projectSprint->is_backlog || $projectSprint->is_system))
        ->first()?->id;
@endphp

<div class="space-y-3" data-project-tasks-root>
    <div class="flex flex-col gap-2 rounded-[8px] border-b border-bgray-200 bg-bgray-50/70 dark:border-darkblack-400 dark:bg-darkblack-500/60 px-4 py-3 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-success-400">Tasks</p>
            <h3 class="mt-0.5 text-[15px] font-bold text-bgray-900 dark:text-white">
                {{ $isLinearFlow ? 'Project task board' : 'Sprint task board' }}
            </h3>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span title="Task count" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Total Tasks <span class="ml-1">{{ $totalTaskCount }}</span>
            </span>

            @unless ($isLinearFlow)
                <span title="Sprint count" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                    Sprints <span class="ml-1">{{ $sprintCount }}</span>
                </span>
            @endunless

            @if (!$isDeletedProjectView)
                @can('task.create')
                    <button type="button" class="inline-flex items-center rounded-full bg-success-300 px-3 py-1 text-xs font-semibold text-white transition hover:bg-success-400" data-task-create-open data-task-create-sprint-id="{{ $taskCreateDefaultSprintId ?? '' }}">
                        + Task
                    </button>
                @endcan
            @endif
        </div>
    </div>

    @if ($taskGroups->isEmpty())
        <div class="rounded-[24px] border border-dashed border-bgray-300 bg-bgray-50 px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">No tasks added yet</h4>
            <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300">
                Tasks will appear here once they are linked to this project.
            </p>
        </div>
    @else
        <div class="space-y-4" data-project-task-group-list @unless ($isLinearFlow) data-load-url="{{ $taskGroupsPageUrl }}" data-current-page="{{ $taskGroupsPagination['page'] ?? 1 }}" data-next-page="{{ $taskGroupsPagination['next_page'] ?? '' }}" data-has-more-pages="{{ !empty($taskGroupsPagination['has_more_pages']) ? 'true' : 'false' }}" @endunless>
            @include('projects.partials.tasks.group-cards', [
                'project' => $project,
                'taskGroups' => $taskGroups,
                'initialGroupKey' => $initialGroupKey,
                'initialTasks' => $initialTasks,
                'initialTasksPagination' => $initialTasksPagination ?? ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
            ])
        </div>

        @unless ($isLinearFlow)
            @if (!empty($taskGroupsPagination['has_more_pages']))
                <div class="flex justify-center" data-project-task-group-pagination-loading hidden>
                    <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">Loading more sprints...</span>
                </div>
                <div class="h-1 w-full" data-project-task-group-pagination-sentinel aria-hidden="true"></div>
            @endif
        @endunless
    @endif

    @include('projects.partials.modals.task-move', [
        'project' => $project,
        'isDeletedProjectView' => $isDeletedProjectView,
        'projectTimelines' => $projectTimelines,
        'projectMilestones' => $projectMilestones,
        'projectSprints' => $projectSprints,
    ])

    <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-project-task-detail-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-detail-close></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-[95vw] 2xl:max-w-[1550px]" data-project-task-detail-content></div>
        </div>
    </div>

    @include('projects.partials.modals.task-logs')
</div>
