@if ($taskGroups->isEmpty())
    <div class="rounded-[24px] border border-dashed border-bgray-300 bg-bgray-50 px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
        <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">
            {{ filled($search ?? null) ? 'No tasks found' : 'No tasks added yet' }}
        </h4>
        <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300">
            {{ filled($search ?? null) ? 'No tasks match your search criteria.' : 'Tasks will appear here once they are linked to this project.' }}
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
            'allGroupsOpen' => filled($search ?? null),
            'search' => $search ?? null,
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

