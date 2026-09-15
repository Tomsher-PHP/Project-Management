@php
    $showEmptyState = $showEmptyState ?? true;
    $groupKey = $groupKey ?? 'upcoming';
@endphp

@forelse ($meetings as $meeting)
    @include('projects.partials.meetings.meeting-row', [
        'project' => $project,
        'meeting' => $meeting,
    ])
@empty
    @if ($showEmptyState)
        <tr>
            <td colspan="4" class="px-6 py-10 text-center">
                <div class="mx-auto max-w-md rounded-[8px] border border-dashed border-bgray-300 bg-bgray-50 px-6 py-8 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                        {{ $groupKey === 'upcoming' ? 'No upcoming meetings.' : 'No past meetings.' }}
                    </p>
                    <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300">
                        {{ $groupKey === 'upcoming' ? 'There are no upcoming meetings scheduled for this project.' : 'There are no past meetings recorded for this project.' }}
                    </p>
                </div>
            </td>
        </tr>
    @endif
@endforelse
