@forelse($meetings as $m)
    @php
        $color = $m->meetingType?->color ?: ($m->meetingStatus?->color ?: '#3B82F6');
        $startTime = $m->start_at ? $m->start_at->format('H:i') : '';
        $endTime = $m->end_at ? $m->end_at->format('H:i') : '';
        $timeRange = "{$startTime} - {$endTime}";
        $type = $m->meetingType?->name ?? 'Meeting';
        $status = $m->meetingStatus?->name ?? 'Scheduled';
        $organizer = $m->organizer?->name ?? 'N/A';
        $editUrl = route('meetings.edit', $m->id);
        $updateUrl = route('meetings.update', $m->id);
        $isFutureMeeting = $m->start_at && ! $m->start_at->isPast();
    @endphp

    <div class="rounded-lg border border-bgray-200 p-3 dark:border-darkblack-400 flex items-center justify-between" style="border-left: 4px solid {{ $color }}">
        <div>
            <button type="button" class="preview-meeting-btn text-left text-sm font-bold text-bgray-900 dark:text-white hover:text-success-300" data-id="{{ $m->id }}">
                {{ $m->title }}
            </button>
            <div class="text-xs text-bgray-700 mt-0.5 dark:text-bgray-400">
                {{ $timeRange }} • {{ $type }} • Status: {{ $status }}
            </div>
            <div class="text-xs text-bgray-700 mt-0.5 dark:text-bgray-400">Organizer: {{ $organizer }}</div>
        </div>
        <div class="flex items-center gap-2">
            <x-edit-button action="javascript:void(0)" class="edit-meeting-btn" data-url="{{ $editUrl }}" data-update-url="{{ $updateUrl }}" data-id="{{ $m->id }}" title="Edit Meeting" />
            @if ($isFutureMeeting)
                @can('meeting.delete')
                    <x-delete-form :action="route('meetings.destroy', $m->id)" ajax confirm-title="Delete Meeting" confirm-message="Are you sure you want to delete this meeting?" title="Delete Meeting" />
                @endcan
            @endif
        </div>
    </div>
@empty
    <div class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
        No meetings scheduled for this date.
    </div>
@endforelse
