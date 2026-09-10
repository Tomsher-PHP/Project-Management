@extends('layouts.master')

@section('page-content')
    <div class="w-full">

        <!-- Page Header -->
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                    Meetings
                </h2>
                <p class="text-sm text-bgray-500 dark:text-bgray-300">
                    Manage and track project and team meetings.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Month Navigation -->
                <div class="flex items-center gap-2">
                    <!-- Previous Month -->
                    <a href="{{ route('meetings.index', array_merge(request()->except('date'), ['date' => $selectedDate->copy()->subMonth()->toDateString()])) }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        &larr;
                    </a>

                    <!-- Current Month -->
                    <div class="min-w-[180px] rounded-lg border border-bgray-300 bg-white px-4 py-2 text-center text-sm font-semibold text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        {{ $selectedDate->format('F Y') }}
                    </div>

                    <!-- Next Month -->
                    <a href="{{ route('meetings.index', array_merge(request()->except('date'), ['date' => $selectedDate->copy()->addMonth()->toDateString()])) }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        &rarr;
                    </a>
                </div>

                @can('meeting.create')
                    <div>
                        <button type="button" id="open_create_meeting_modal_btn" class="flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Meeting
                        </button>
                    </div>
                @endcan
            </div>
        </div>

        <!-- Flash Alerts -->
        @if (session('success'))
            <div class="mb-5 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filters Card -->
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm dark:bg-darkblack-600">
            <form method="GET" action="{{ route('meetings.index') }}" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">

                <!-- Search -->
                <div class="min-w-[200px] flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or location..." class="w-full rounded-lg border border-bgray-300 px-3.5 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>

                <!-- Project Filter -->
                <div class="min-w-[160px]">
                    <select name="project_id" class="w-full rounded-lg border border-bgray-300 px-3 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Meeting Type Filter -->
                <div class="min-w-[140px]">
                    <select name="meeting_type_id" class="w-full rounded-lg border border-bgray-300 px-3 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <option value="">All Types</option>
                        @foreach ($meetingTypes as $type)
                            <option value="{{ $type->id }}" {{ request('meeting_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="min-w-[140px]">
                    <select name="meeting_status_id" class="w-full rounded-lg border border-bgray-300 px-3 py-2 text-xs font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach ($meetingStatuses as $status)
                            <option value="{{ $status->id }}" {{ request('meeting_status_id') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-lg bg-bgray-900 px-4 py-2 text-xs font-semibold text-white hover:bg-bgray-800 dark:bg-darkblack-500 dark:hover:bg-darkblack-400 transition">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'project_id', 'meeting_type_id', 'meeting_status_id']))
                        <a href="{{ route('meetings.index', ['date' => $selectedDate->toDateString()]) }}" class="rounded-lg border border-bgray-300 px-3 py-2 text-xs font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Legend Card -->
        <div class="mb-4 rounded-xl bg-white p-4 shadow-sm dark:bg-darkblack-600">
            <div class="mb-3 text-sm font-semibold text-bgray-900 dark:text-white">
                Meeting Statuses
            </div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
                @forelse($meetingStatuses as $st)
                    @php
                        $color = $st->color ?: '#3B82F6';
                    @endphp
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $color }};"></span>
                        <span class="text-xs text-bgray-600 dark:text-bgray-300">{{ $st->name }}</span>
                    </div>
                @empty
                    <span class="text-xs text-bgray-600 dark:text-bgray-400">No active meeting statuses found.</span>
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
                                            $countColor = $firstMeeting?->meetingStatus?->color ?: ($firstMeeting?->meetingType?->color ?: '#3B82F6');
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
                                            $mColor = $m->meetingStatus?->color ?: ($m->meetingType?->color ?: '#3B82F6');
                                            $organizerName = $m->organizer?->name ?? 'User';
                                        @endphp
                                        <a href="{{ route('meetings.show', $m->id) }}" class="block rounded-md px-2 py-1.5 transition hover:opacity-90 cursor-pointer" style="background-color: {{ $mColor }}15; border-left: 3px solid {{ $mColor }};">
                                            <div class="flex items-center gap-1.5">
                                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold" style="background-color: {{ $mColor }}30; color: {{ $mColor }};">
                                                    {{ strtoupper(substr($organizerName, 0, 1)) }}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-xs font-medium" style="color: {{ $mColor }};">
                                                        {{ $m->title }}
                                                    </div>
                                                    <div class="text-[10px] text-bgray-700 dark:text-bgray-400">
                                                        {{ $m->start_at->format('H:i') }} - {{ $m->end_at->format('H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
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
    <div id="dayMeetingsModal" class="fixed inset-0 z-[90] hidden overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeDayMeetings()"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 id="dayMeetingsTitle" class="text-lg font-semibold text-bgray-900 dark:text-white">
                            Meeting Details
                        </h3>
                        <p class="text-xs text-bgray-500 dark:text-bgray-300">
                            Meetings scheduled for date
                        </p>
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
