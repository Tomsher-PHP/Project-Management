@extends('layouts.master')

@section('page-content')
    <div class="w-full">

        <!-- Top Action & Filter Bar -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can('meeting.create')
                <x-button.create-button type="button" id="open_create_meeting_modal_btn" label="Meeting" />
            @endcan

            <x-filters.button />

            <x-filters.list-search placeholder="Search meetings..." />

            <!-- Month Navigation (Right Aligned) -->
            <div class="flex items-center gap-2 sm:ml-auto">
                <!-- Previous Month -->
                <a href="{{ route('meetings.index', array_merge(request()->except('calendar_date'), ['calendar_date' => $selectedDate->copy()->subMonth()->toDateString()])) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" title="Previous Month">
                    &larr;
                </a>

                <!-- Current Month -->
                <div class="min-w-[150px] rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-center text-sm font-semibold text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    {{ $selectedDate->format('F Y') }}
                </div>

                <!-- Next Month -->
                <a href="{{ route('meetings.index', array_merge(request()->except('calendar_date'), ['calendar_date' => $selectedDate->copy()->addMonth()->toDateString()])) }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" title="Next Month">
                    &rarr;
                </a>
            </div>
        </div>

        <!-- Filter Drawer -->
        <x-filters.drawer>
            <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">

            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or location..." class="w-full rounded-lg border border-bgray-300 p-2.5 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Project</label>
                <select name="project_id" class="tom-select-lazy w-full" data-route="{{ route('projects.search') }}" data-sort="0">
                    <option value="">All Projects</option>
                    @if (request('project_id') && ($filterProj = \App\Models\Project::find(request('project_id'))))
                        <option value="{{ $filterProj->id }}" selected>{{ $filterProj->name }}</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Meeting Type</label>
                <select name="meeting_type_id" class="tom-select w-full">
                    <option value="">All Types</option>
                    @foreach ($meetingTypes as $type)
                        <option value="{{ $type->id }}" {{ request('meeting_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Location</label>
                <select name="meeting_location_id" class="tom-select w-full">
                    <option value="">All Locations</option>
                    @foreach ($meetingLocations as $loc)
                        <option value="{{ $loc->id }}" {{ request('meeting_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Status</label>
                <select name="meeting_status_id" class="tom-select w-full">
                    <option value="">All Statuses</option>
                    @foreach ($meetingStatuses as $status)
                        <option value="{{ $status->id }}" {{ request('meeting_status_id') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Organizer</label>
                <select name="organizer_id" class="tom-select w-full">
                    <option value="">All Organizers</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ request('organizer_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </x-filters.drawer>


        <!-- Legend Card -->
        <div class="mb-4 rounded-xl bg-white p-4 shadow-sm dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
                @forelse($meetingTypes as $mt)
                    @php
                        $color = $mt->color ?: '#3B82F6';
                    @endphp
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $color }};"></span>
                        <span class="text-xs text-bgray-900 dark:text-bgray-300">{{ $mt->name }}</span>
                    </div>
                @empty
                    <span class="text-xs text-bgray-600 dark:text-bgray-400">No active meeting types found.</span>
                @endforelse
            </div>
        </div>

        <!-- Attendance-Style Calendar Grid -->
        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600">
            <div class="overflow-x-auto">
                <div class="min-w-[1000px]">

                    <!-- Week Days Header -->
                    <div class="grid grid-cols-7 border-b border-bgray-200 dark:border-darkblack-400">
                        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            <div class="border-r border-bgray-200 px-3 py-3 text-center text-xs font-semibold uppercase text-bgray-700 last:border-r-0 dark:border-darkblack-400 dark:text-bgray-300">
                                {{ $day }}
                            </div>
                        @endforeach
                    </div>

                    <!-- Calendar Days Grid -->
                    <div class="grid grid-cols-7">
                        @for ($i = 0; $i < $totalDays; $i++)
                            @php
                                $date = $calendarStart->copy()->addDays($i);
                                $dateKey = $date->format('Y-m-d');
                                $isToday = $date->isToday();
                                $dayMeetings = $meetingsByDate->get($dateKey, collect())->sortBy('start_at');
                                $visibleMeetings = $dayMeetings->take(3);
                                $remainingMeetings = max($dayMeetings->count() - 3, 0);
                                $isCurrentMonth = $date->month === $selectedDate->month && $date->year === $selectedDate->year;
                            @endphp

                            <!-- Day Cell -->
                            <div class="relative min-h-[160px] border-b border-r border-bgray-200 p-2 transition dark:border-darkblack-400 {{ !$isCurrentMonth ? 'bg-bgray-50/60 dark:bg-darkblack-500/40' : 'hover:bg-bgray-50 dark:hover:bg-darkblack-500' }}">

                                <!-- Date Header -->
                                <div class="mb-2 flex items-center justify-between">
                                    <!-- Date Number -->
                                    <button type="button" onclick="openCreateMeetingForDate('{{ $dateKey }}')" class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold {{ $isToday ? 'bg-success-300 text-white' : ($isCurrentMonth ? 'text-bgray-700 hover:bg-bgray-100 dark:text-white dark:hover:bg-darkblack-400' : 'text-bgray-400 dark:text-bgray-500') }}">
                                        {{ $date->day }}
                                    </button>

                                    <!-- Meeting Count Badge -->
                                    @if ($dayMeetings->count() > 0)
                                        @php
                                            $firstMeeting = $dayMeetings->first();
                                            $countColor = $firstMeeting?->meetingType?->color ?: '#3B82F6';
                                        @endphp
                                        <span class="rounded-full px-2 py-1 text-[10px] font-semibold" style="background-color: {{ $countColor }}20; color: {{ $countColor }};">
                                            {{ $dayMeetings->count() }}
                                            {{ Str::plural('Meeting', $dayMeetings->count()) }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Meetings List -->
                                <div class="space-y-1">
                                    @foreach ($visibleMeetings as $m)
                                        @php
                                            $mColor = $m->meetingType?->color ?: '#3B82F6';
                                            $isFutureMeeting = $m->start_at && ! $m->start_at->isPast();
                                        @endphp
                                        <div class="group relative flex items-center justify-between rounded-md px-2 py-1.5 transition hover:opacity-90 cursor-pointer" style="background-color: {{ $mColor }}15; border-left: 3px solid {{ $mColor }};">
                                            <button type="button" class="edit-meeting-btn min-w-0 flex-1 text-left block cursor-pointer" data-url="{{ route('meetings.edit', $m->id) }}" data-update-url="{{ route('meetings.update', $m->id) }}" data-id="{{ $m->id }}">
                                                <div class="flex items-center gap-1.5">
                                                    <x-user-avatar :user="$m->organizer" size="xs" />
                                                    <div class="min-w-0 flex-1">
                                                        <div class="truncate text-xs font-medium" style="color: {{ $mColor }};">
                                                            {{ $m->title }}
                                                        </div>
                                                        <div class="text-[11px] text-bgray-700 dark:text-bgray-300">
                                                            {{ $m->start_at->format($globalTimeFormat) }} to {{ $m->end_at->format($globalTimeFormat) }} ({{ $m->start_at->diffForHumans($m->end_at, true) }})
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                            @if ($isFutureMeeting)
                                                @can('meeting.delete')
                                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 ml-1 flex-shrink-0" onclick="event.stopPropagation();">
                                                        <x-delete-form :action="route('meetings.destroy', $m->id)" ajax confirm-title="Delete Meeting" confirm-message="Are you sure you want to delete this meeting?" class="!h-6 !w-6 !p-0 border-none bg-transparent hover:bg-red-100 dark:hover:bg-red-900/40 text-red-500" title="Delete Meeting" />
                                                    </div>
                                                @endcan
                                            @endif
                                        </div>
                                    @endforeach

                                    <!-- More Button -->
                                    @if ($remainingMeetings > 0)
                                        <button type="button" onclick="showDayMeetings('{{ $dateKey }}')" class="w-full rounded-md bg-bgray-100 px-2 py-1.5 text-left text-xs font-semibold text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-400 dark:text-bgray-300">
                                            +{{ $remainingMeetings }} more
                                        </button>
                                    @endif
                                </div>

                                <!-- Action Button on Today / Future Dates -->
                                @if ($date->isToday() || $date->isFuture())
                                    @can('meeting.create')
                                        <button type="button" onclick="openCreateMeetingForDate('{{ $dateKey }}')" class="mt-3 w-full rounded-md border border-dashed border-bgray-300 px-2 py-1.5 text-[11px] font-medium text-bgray-500 transition hover:border-success-500 hover:text-success-500 dark:border-darkblack-400">
                                            + Add
                                        </button>
                                    @endcan
                                @endif

                            </div>
                        @endfor
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Day Meetings Modal -->
    <div id="dayMeetingsModal" class="fixed inset-0 z-[90] hidden overflow-y-auto" data-day-meetings-url="{{ route('meetings.day-meetings') }}">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeDayMeetings()"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 id="dayMeetingsTitle" class="text-lg font-semibold text-bgray-900 dark:text-white">
                            Meeting Details
                        </h3>
                    </div>
                    <button type="button" onclick="closeDayMeetings()" class="text-2xl leading-none text-bgray-500 transition hover:text-bgray-900 dark:hover:text-white">
                        &times;
                    </button>
                </div>
                <div id="dayMeetingsContent" class="max-h-[60vh] overflow-y-auto p-5 space-y-2"></div>
                <div class="border-t border-bgray-200 px-5 py-4 text-right dark:border-darkblack-400">
                    <button type="button" onclick="closeDayMeetings()" class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white dark:hover:bg-darkblack-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Reusable Form Modal -->
    @include('meetings.form-modal')
@endsection

@push('scripts')
    <script>
        window.calendarMeetings = @json($calendarMeetingsForJs);
    </script>
    @vite(['resources/js/modules/meetings/meetings.js', 'resources/js/modules/meetings/meeting-form.js'])
@endpush
