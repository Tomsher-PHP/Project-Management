@php
    $pagination = $pagination ?? [
        'page' => 1,
        'next_page' => null,
        'has_more_pages' => false,
    ];
@endphp

<div class="overflow-hidden rounded-[20px] border border-bgray-200 dark:border-darkblack-400" data-project-task-group-task-list data-current-page="{{ $pagination['page'] }}" data-next-page="{{ $pagination['next_page'] ?? '' }}" data-has-more-pages="{{ $pagination['has_more_pages'] ? 'true' : 'false' }}">
    <div class="max-h-[34rem] overflow-y-auto" data-project-task-group-scroll>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
            <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                <tr>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Task</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Assignee</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Status</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Type</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Task Mode</th>
                    <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:border-r-darkblack-400 dark:text-bgray-100">Estimate Time</th>
                    <th class="border-b border-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-b-darkblack-400 dark:text-bgray-100">Due Date</th>
                </tr>
            </thead>

                <tbody class="bg-white dark:bg-darkblack-600" data-project-task-group-rows>
                    @include('projects.partials.tasks.task-rows', [
                        'project' => $project,
                        'group' => $group,
                        'tasks' => $tasks,
                        'showEmptyState' => true,
                    ])
                </tbody>
            </table>
        </div>

        @if ($pagination['has_more_pages'])
            <div class="flex justify-center px-4 py-3" data-project-task-group-tasks-loading hidden>
                <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">Loading more tasks...</span>
            </div>
            <div class="h-1 w-full" data-project-task-group-tasks-sentinel aria-hidden="true"></div>
        @endif
    </div>
</div>
