@php
    $isDeletedProjectView = $project->trashed();
    $loadUrl = $isDeletedProjectView
        ? route('projects.restore.meetings.groups.show', ['id' => $project->id, 'group' => $groupKey])
        : route('projects.meetings.groups.show', ['project' => $project, 'group' => $groupKey]);
@endphp

<article class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm transition dark:border-darkblack-400 dark:bg-darkblack-600" data-project-meeting-group data-group-key="{{ $groupKey }}" data-load-url="{{ $loadUrl }}" style="border-left-width: 4px; border-left-color: {{ $accentColor }}; border-color: {{ $accentColor }};">
    <div class="flex items-center justify-between gap-4 overflow-x-auto px-4 py-3 text-left transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/70">
        <div class="flex min-w-0 flex-1 items-center gap-3 whitespace-nowrap">
            <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-bgray-100 dark:bg-darkblack-500" style="color: {{ $accentColor }};">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </span>

            <div class="flex min-w-0 flex-1 items-center gap-3 select-text">
                <h4 class="truncate text-base font-semibold text-bgray-900 dark:text-white">{{ $groupTitle }}</h4>
            </div>
        </div>

        <div class="flex flex-shrink-0 items-center gap-2 whitespace-nowrap select-text">
            <span title="Meeting count" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Meetings <span class="ml-1">{{ $pagination['total'] ?? count($meetings) }}</span>
            </span>
        </div>
    </div>

    <div data-project-meeting-group-panel>
        <div class="border-t border-bgray-200 px-3 py-3 dark:border-darkblack-400 sm:px-5" data-project-meeting-group-body>
            @include('projects.partials.meetings.meeting-group-body', [
                'project' => $project,
                'groupKey' => $groupKey,
                'meetings' => $meetings,
                'pagination' => $pagination,
            ])
        </div>
    </div>
</article>
