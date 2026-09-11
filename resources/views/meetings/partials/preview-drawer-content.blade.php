@php
    $tz = $globalTimezone ?? (config('constants.timezone') ?? config('app.timezone'));
    $typeColor = $meeting->meetingType?->color ?: '#3B82F6';
    $statusColor = $meeting->meetingStatus?->color ?: '#6B7280';
    $isFutureMeeting = $meeting->start_at && !$meeting->start_at->copy()->shiftTimezone($tz)->isPast();
    $timeFormat = $globalTimeFormat ?? 'H:i';
    $startTimeStr = $meeting->start_at ? $meeting->start_at->format($timeFormat) : '';
    $endTimeStr = $meeting->end_at ? $meeting->end_at->format($timeFormat) : '';
    $durationStr = $meeting->start_at && $meeting->end_at ? $meeting->start_at->diffForHumans($meeting->end_at, true) : '';
    $editUrl = route('meetings.edit', $meeting->id);
    $updateUrl = route('meetings.update', $meeting->id);
@endphp

<div class="flex flex-col h-full bg-white dark:bg-darkblack-600">

    <!-- Drawer Header -->
    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 shrink-0">
        <div class="flex items-center gap-2 min-w-0 flex-1 pr-4">
            <span class="h-3.5 w-3.5 rounded-full shrink-0" style="background-color: {{ $typeColor }};"></span>
            <h3 id="meeting-preview-title" class="text-lg font-bold text-bgray-900 dark:text-white truncate" title="{{ $meeting->title }}">
                {{ $meeting->title }}
            </h3>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <!-- Edit Action -->
            @can('meeting.edit')
                <x-edit-button action="javascript:void(0)" class="edit-meeting-btn !h-8 !w-8" icon-class="h-4 w-4" data-url="{{ $editUrl }}" data-update-url="{{ $updateUrl }}" data-id="{{ $meeting->id }}" title="Edit Meeting" />
            @endcan

            <!-- Delete Action -->
            @if ($isFutureMeeting)
                @can('meeting.delete')
                    <x-delete-form :action="route('meetings.destroy', $meeting->id)" ajax confirm-title="Delete Meeting" confirm-message="Are you sure you want to delete this meeting?" class="!h-8 !w-8" icon-class="h-4 w-4" title="Delete Meeting" />
                @endcan
            @endif

            <!-- Close Drawer -->
            <button type="button" id="close-meeting-preview-btn" class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" title="Close Preview">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Drawer Content Scroll Area -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6">

        <!-- Status & Type Header Badges -->
        <div class="flex flex-wrap items-center gap-2">
            @if ($meeting->meetingStatus)
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                    Status: {{ $meeting->meetingStatus->name }}
                </span>
            @endif

            @if ($meeting->meetingType)
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $typeColor }}20; color: {{ $typeColor }}; border: 1px solid {{ $typeColor }}40;">
                    Type: {{ $meeting->meetingType->name }}
                </span>
            @endif

            @if ($meeting->project)
                <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                    Project: {{ $meeting->project->name }} @if ($meeting->project->project_code)
                        ({{ $meeting->project->project_code }})
                    @endif
                </span>
            @endif
        </div>

        <!-- Time & Date Grid -->
        <div class="rounded-xl border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/50 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-bgray-700 dark:text-bgray-300 block mb-0.5">Date</span>
                    <span class="font-semibold text-bgray-900 dark:text-white">
                        {{ $meeting->start_at ? $meeting->start_at->format('d M Y (l)') : 'N/A' }}
                    </span>
                </div>
                <div>
                    <span class="text-bgray-700 dark:text-bgray-300 block mb-0.5">Time & Duration</span>
                    <span class="font-semibold text-bgray-900 dark:text-white">
                        {{ $startTimeStr }} - {{ $endTimeStr }}
                        @if ($durationStr)
                            <span class="text-bgray-700 dark:text-bgray-300 font-normal">({{ $durationStr }})</span>
                        @endif
                    </span>
                </div>
            </div>

            @if ($meeting->meetingLocation || $meeting->location_details || $meeting->url)
                <div class="pt-2 border-t border-bgray-200 dark:border-darkblack-400 space-y-2 text-xs">
                    @if ($meeting->meetingLocation)
                        <div>
                            <span class="text-bgray-700 dark:text-bgray-300">Location: </span>
                            <span class="font-medium text-bgray-900 dark:text-white">{{ $meeting->meetingLocation->name }}</span>
                            @if ($meeting->location_details)
                                <span class="text-bgray-600 dark:text-bgray-300"> - {{ $meeting->location_details }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($meeting->url)
                        <div class="truncate">
                            <span class="text-bgray-700 dark:text-bgray-300">Meeting URL: </span>
                            <a href="{{ $meeting->url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-success-400 hover:underline inline-flex items-center gap-1">
                                {{ $meeting->url }}
                                <svg class="h-3 w-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Organizer -->
        <div class="space-y-1.5">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Organizer</h4>
            <div class="flex items-center gap-3 rounded-lg border border-bgray-200 p-3 dark:border-darkblack-400">
                <x-user-avatar :user="$meeting->organizer" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-semibold text-bgray-900 dark:text-white truncate">
                        {{ $meeting->organizer?->name ?? 'N/A' }}
                    </div>
                    <div class="text-[11px] text-bgray-700 dark:text-bgray-300 truncate">
                        {{ $meeting->organizer?->email ?? '' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Participants -->
        <div class="space-y-2">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">
                Participants ({{ $meeting->participants->count() }})
            </h4>

            <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                @forelse ($meeting->participants as $participant)
                    <div class="flex items-center justify-between min-w-0">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            @if ($participant->is_external)
                                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-xs font-bold dark:bg-amber-900/30 dark:text-amber-300 shrink-0">
                                    {{ strtoupper(substr($participant->name ?? 'E', 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-medium text-bgray-900 dark:text-white truncate">
                                        {{ $participant->name ?? 'External Guest' }}
                                        <span class="ml-1 text-[10px] bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 px-1.5 py-0.5 rounded-full">External</span>
                                    </div>
                                    <div class="text-[11px] text-bgray-700 dark:text-bgray-300 truncate">
                                        {{ $participant->email }}
                                    </div>
                                </div>
                            @else
                                <x-user-avatar :user="$participant->user" size="xs" />
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-medium text-bgray-900 dark:text-white truncate">
                                        {{ $participant->user?->name ?? 'User' }}
                                    </div>
                                    <div class="text-[11px] text-bgray-700 dark:text-bgray-300 truncate">
                                        {{ $participant->user?->email }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-xs text-bgray-700 dark:text-bgray-300 py-2">No participants added.</div>
                @endforelse
            </div>
        </div>

        <!-- Description -->
        @if ($meeting->description)
            <div class="space-y-1.5">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Description</h4>
                <div class="max-h-48 overflow-y-auto rounded-lg border border-bgray-200 p-3.5 text-xs text-bgray-800 dark:border-darkblack-400 dark:text-bgray-300 [&_*]:dark:text-bgray-300 leading-relaxed prose dark:prose-invert max-w-none">
                    {!! $meeting->description !!}
                </div>
            </div>
        @endif

        <!-- Attachments -->
        @if ($meeting->attachments && $meeting->attachments->count() > 0)
            <div class="space-y-2">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">
                    Attachments ({{ $meeting->attachments->count() }})
                </h4>
                <div class="max-h-44 overflow-y-auto space-y-2 pr-1">
                    @foreach ($meeting->attachments as $attachment)
                        <div class="flex items-center justify-between rounded-lg border border-bgray-200 p-2.5 text-xs dark:border-darkblack-400">
                            <div class="flex items-center gap-2 truncate min-w-0 flex-1">
                                <svg class="h-4 w-4 text-bgray-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <span class="truncate text-bgray-900 dark:text-white font-medium" title="{{ $attachment->original_name }}">
                                    {{ $attachment->original_name }}
                                </span>
                            </div>
                            <a href="{{ $attachment->url }}" target="_blank" class="ml-2 font-medium text-success-400 hover:underline shrink-0">
                                Download
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Meeting Minutes Section -->
        <div class="space-y-3 pt-4 border-t border-bgray-200 dark:border-darkblack-400" id="meeting_minutes_section">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-bgray-900 dark:text-white flex items-center gap-2">
                    <svg class="h-4 w-4 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Meeting Minutes
                </h4>

                @if ($canAddMinutes)
                    <div id="minutes_view_actions">
                        @if ($meeting->minutes)
                            <button type="button" id="edit_minutes_btn" class="inline-flex items-center gap-1 text-xs font-semibold text-success-400 hover:text-success-500 transition">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1-1-4 9.5-9.5z" />
                                </svg>
                                Edit Minutes
                            </button>
                        @else
                            <button type="button" id="add_minutes_btn" class="inline-flex items-center gap-1 rounded-md bg-success-300 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-success-400">
                                + Add Minutes
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Static Minutes View Display -->
            <div id="minutes_static_view">
                @if ($meeting->minutes)
                    <div class="max-h-60 overflow-y-auto rounded-xl border border-bgray-200 bg-white p-4 text-xs text-bgray-800 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 leading-relaxed prose dark:prose-invert max-w-none shadow-sm">
                        {!! $meeting->minutes !!}
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-bgray-300 p-4 text-center text-xs text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                        @if ($canAddMinutes)
                            No meeting minutes added yet. Click "+ Add Minutes" to record key takeaways and decisions.
                        @else
                            Meeting minutes are not available for this meeting.
                        @endif
                    </div>
                @endif
            </div>

            <!-- Minutes Quill Editor Wrapper (Hidden by default) -->
            @if ($canAddMinutes)
                <div id="minutes_editor_wrapper" class="hidden space-y-3" data-save-url="{{ route('meetings.minutes', $meeting->id) }}">
                    <div class="custom-quill-wrapper rounded-lg border border-bgray-300 dark:border-darkblack-400 overflow-hidden bg-white dark:bg-darkblack-500">
                        <div id="meeting_minutes_quill_editor" class="min-h-[140px] text-xs text-bgray-900 dark:text-white"></div>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" id="cancel_minutes_btn" class="rounded-lg border border-bgray-300 px-3 py-1.5 text-xs font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                            Cancel
                        </button>
                        <button type="button" id="save_minutes_btn" class="inline-flex items-center gap-1.5 rounded-lg bg-success-300 px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-success-400">
                            <span id="save_minutes_spinner" class="hidden">
                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            Save Minutes
                        </button>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
