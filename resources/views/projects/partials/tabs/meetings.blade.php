@php
    $isDeletedProjectView = $project->trashed();
    $upcomingLoadUrl = $isDeletedProjectView ? route('projects.restore.meetings.groups.show', ['id' => $project->id, 'group' => 'upcoming']) : route('projects.meetings.groups.show', ['project' => $project, 'group' => 'upcoming']);
    $pastLoadUrl = $isDeletedProjectView ? route('projects.restore.meetings.groups.show', ['id' => $project->id, 'group' => 'past']) : route('projects.meetings.groups.show', ['project' => $project, 'group' => 'past']);
@endphp

<div class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-meetings-root>
    <div class="max-h-[38rem] overflow-y-auto" data-project-meetings-scroll>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <!-- COMMON TABLE HEAD -->
                <thead class="sticky top-0 z-10 bg-bgray-50/90 backdrop-blur dark:bg-darkblack-500">
                    <tr>
                        <th class="border-b border-r border-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">Meeting</th>
                        <th class="border-b border-r border-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">Date & Time</th>
                        <th class="border-b border-r border-bgray-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">Organizer</th>
                        <th class="border-b border-bgray-200 px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.16em] text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">Actions</th>
                    </tr>
                </thead>

                @php
                    $rowClass = 'bg-bgray-200 dark:bg-darkblack-400 select-text';
                @endphp
                <!-- UPCOMING MEETINGS SECTION -->
                <tbody class="bg-white dark:bg-darkblack-600" data-project-meeting-group data-group-key="upcoming" data-load-url="{{ $upcomingLoadUrl }}" data-current-page="{{ $upcomingPagination['page'] }}" data-next-page="{{ $upcomingPagination['next_page'] ?? '' }}" data-has-more-pages="{{ $upcomingPagination['has_more_pages'] ? 'true' : 'false' }}">
                    <!-- Section Header Row -->
                    <tr class="{{ $rowClass }}">
                        <td colspan="4" class="border-y border-bgray-200 px-4 py-2.5 dark:border-darkblack-400">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-900 dark:text-white">Upcoming Meetings</h4>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 shadow-sm dark:bg-darkblack-600 dark:text-bgray-300">
                                    {{ $upcomingPagination['total'] ?? count($upcomingMeetings) }}
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Upcoming Meeting Rows -->
                    @include('projects.partials.meetings.meeting-rows', [
                        'project' => $project,
                        'groupKey' => 'upcoming',
                        'meetings' => $upcomingMeetings,
                        'showEmptyState' => true,
                    ])

                    @if ($upcomingPagination['has_more_pages'])
                        <tr data-project-meeting-group-sentinel-row>
                            <td colspan="4" class="p-0 border-b border-bgray-200 dark:border-darkblack-400">
                                <div class="flex justify-center px-4 py-3" data-project-meeting-group-loading hidden>
                                    <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">Loading more upcoming meetings...</span>
                                </div>
                                <div class="h-1 w-full" data-project-meeting-group-sentinel aria-hidden="true"></div>
                            </td>
                        </tr>
                    @endif
                </tbody>

                <!-- PAST MEETINGS SECTION -->
                <tbody class="bg-white dark:bg-darkblack-600" data-project-meeting-group data-group-key="past" data-load-url="{{ $pastLoadUrl }}" data-current-page="{{ $pastPagination['page'] }}" data-next-page="{{ $pastPagination['next_page'] ?? '' }}" data-has-more-pages="{{ $pastPagination['has_more_pages'] ? 'true' : 'false' }}">
                    <!-- Section Header Row -->
                    <tr class="{{ $rowClass }}">
                        <td colspan="4" class="border-y border-bgray-200 px-4 py-2.5 dark:border-darkblack-400">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-900 dark:text-white">Past Meetings</h4>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-bgray-700 shadow-sm dark:bg-darkblack-600 dark:text-bgray-300">
                                    {{ $pastPagination['total'] ?? count($pastMeetings) }}
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Past Meeting Rows -->
                    @include('projects.partials.meetings.meeting-rows', [
                        'project' => $project,
                        'groupKey' => 'past',
                        'meetings' => $pastMeetings,
                        'showEmptyState' => true,
                    ])

                    @if ($pastPagination['has_more_pages'])
                        <tr data-project-meeting-group-sentinel-row>
                            <td colspan="4" class="p-0 border-b border-bgray-200 dark:border-darkblack-400">
                                <div class="flex justify-center px-4 py-3" data-project-meeting-group-loading hidden>
                                    <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">Loading more past meetings...</span>
                                </div>
                                <div class="h-1 w-full" data-project-meeting-group-sentinel aria-hidden="true"></div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
