@php
    $pagination = $pagination ?? [
        'page' => 1,
        'next_page' => null,
        'has_more_pages' => false,
        'all_pages_loaded' => true,
    ];
@endphp

<div class="space-y-3 border-l-2 border-dashed border-bgray-200 pl-4 transition duration-200 dark:border-darkblack-400 md:pl-6" data-project-sprint-list data-all-pages-loaded="{{ $pagination['all_pages_loaded'] ? 'true' : 'false' }}" data-current-page="{{ $pagination['page'] }}" @can('project_sprint.edit') data-reorder-url="{{ route('projects.milestones.sprints.reorder', [$project, $milestone]) }}" @endcan>
    @include('projects.partials.milestone.sprint-cards', [
        'project' => $project,
        'milestone' => $milestone,
        'projectSprints' => $projectSprints,
        'allPagesLoaded' => $pagination['all_pages_loaded'],
        'showEmptyState' => true,
    ])
</div>

@if ($pagination['has_more_pages'])
    <div class="flex justify-center pt-2" data-project-sprint-pagination-loading hidden>
        <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">Loading more sprints...</span>
    </div>
    <div class="h-1 w-full" data-project-sprint-pagination-sentinel aria-hidden="true"></div>
@endif
