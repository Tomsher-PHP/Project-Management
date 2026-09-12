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
            <div class="flex flex-wrap items-center justify-center gap-1.5 rounded-lg border border-bgray-300 bg-white p-1 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 sm:ml-auto">
                <!-- Previous Month -->
                <a href="{{ route('meetings.index', array_merge(request()->except('calendar_date'), ['calendar_date' => $selectedDate->copy()->subMonth()->startOfMonth()->toDateString()])) }}" class="flex h-9 w-9 items-center justify-center rounded-md text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400" title="Previous Month" aria-label="Previous Month">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>

                <!-- Month Picker Section -->
                <div class="relative flex items-center gap-1 cursor-pointer" id="meeting_month_picker_wrapper">
                    <button type="button" id="meeting_month_picker_btn" class="flex h-9 w-9 items-center justify-center rounded-md text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400" title="Select Month" aria-label="Select Month">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                        </svg>
                    </button>

                    <span class="min-w-[130px] px-1 text-center text-sm font-bold text-bgray-800 dark:text-bgray-50" id="meeting_month_label">
                        {{ $selectedDate->format('F Y') }}
                    </span>

                    <input type="text" id="meeting_month_picker" value="{{ $selectedDate->format('Y-m') }}" class="monthpicker absolute left-0 top-0 h-0 w-0 opacity-0 pointer-events-none" data-open-to-date="{{ $selectedDate->format('Y-m-d') }}" aria-label="Select meeting month" readonly>
                </div>

                <!-- Next Month -->
                <a href="{{ route('meetings.index', array_merge(request()->except('calendar_date'), ['calendar_date' => $selectedDate->copy()->addMonth()->startOfMonth()->toDateString()])) }}" class="flex h-9 w-9 items-center justify-center rounded-md text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400" title="Next Month" aria-label="Next Month">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>

                <div class="mx-1 h-5 w-px bg-bgray-300 dark:bg-darkblack-400"></div>

                <!-- Today Button -->
                @php
                    $todayDateStr = today()->toDateString();
                    $isCurrentSelectedTodayMonth = $selectedDate->isSameMonth(today());
                @endphp
                <a href="{{ route('meetings.index', array_merge(request()->except('calendar_date'), ['calendar_date' => $todayDateStr])) }}" class="rounded-md px-3 py-1.5 text-sm font-semibold transition {{ $isCurrentSelectedTodayMonth ? 'bg-success-50 text-success-600 hover:bg-success-100 dark:bg-success-300 dark:text-bgray-900' : 'text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400' }}" title="Today">
                    Today
                </a>
            </div>
        </div>

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
                                        @endphp
                                        <div class="group relative flex items-center justify-between rounded-md px-2 py-1.5 transition hover:opacity-90 cursor-pointer" style="background-color: {{ $mColor }}15; border-left: 3px solid {{ $mColor }};">
                                            <button type="button" class="preview-meeting-btn min-w-0 flex-1 text-left block cursor-pointer" data-id="{{ $m->id }}">
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
                                            @can('meeting.edit')
                                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 ml-1 flex-shrink-0">
                                                    <x-edit-button action="javascript:void(0)" class="edit-meeting-btn !h-5 !w-5 !p-0 border-none bg-transparent hover:bg-bgray-200 dark:hover:bg-darkblack-400" icon-class="h-3.5 w-3.5" data-url="{{ route('meetings.edit', $m->id) }}" data-update-url="{{ route('meetings.update', $m->id) }}" data-id="{{ $m->id }}" title="Edit Meeting" />
                                                </div>
                                            @endcan
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

    <!-- Meeting Preview Drawer -->
    @include('meetings.partials.preview-drawer')

    <!-- Filter Drawer -->
    <x-filters.drawer>
        <input type="hidden" name="calendar_date" value="{{ $selectedDate->toDateString() }}">
        <x-filters.input-search name="search" label="Search" />

        <div>
            <label class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">Project</label>
            <select name="project_id" class="tom-select-lazy w-full" data-route="{{ route('projects.search') }}" data-sort="0">
                <option value="">All Projects</option>
                @if (request('project_id') && ($filterProj = \App\Models\Project::find(request('project_id'))))
                    <option value="{{ $filterProj->id }}" selected>{{ $filterProj->name }}</option>
                @endif
            </select>
        </div>

        <x-filters.multi-select name="meeting_type_id" label="Meeting Type" :options="$meetingTypes" />
        <x-filters.multi-select name="meeting_location_id" label="Location" :options="$meetingLocations" />
        <x-filters.multi-select name="meeting_status_id" label="Status" :options="$meetingStatuses" />
        <x-filters.multi-select name="organizer_id" label="Organizer" :options="$users" />
    </x-filters.drawer>
@endsection

@push('scripts')
    <script>
        window.calendarMeetings = @json($calendarMeetingsForJs);
    </script>
    @vite(['resources/js/modules/meetings/meetings.js', 'resources/js/modules/meetings/meeting-form.js'])
@endpush
