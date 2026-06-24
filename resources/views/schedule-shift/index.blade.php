@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->

    @can('schedule_shift.create')
        <a href="{{ route('schedule.shift.create') }}" id="schedule-shift-btn" class="inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>

            <span>Schedule Shift</span>
        </a>
    @endcan

    <!-- write your code here-->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <!--list table-->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex flex-col space-y-5">

                    <!-- Header: Prev | Calendar Icon | Week Range | Next -->
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center">
                            <select id="teamFilterSelect" class="tom-select min-w-[240px]" data-sort="0">
                                <option value="">All Team</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected($selectedTeamFilter === (string) $team->id)>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                                <option value="{{ $notInTeamFilter }}" @selected($selectedTeamFilter === $notInTeamFilter)>Not in a Team</option>
                            </select>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-1.5 rounded-lg border border-bgray-300 bg-white p-1 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">

                            <!-- Previous button -->
                            <button id="prevWeek" class="flex h-9 w-9 items-center justify-center rounded-md text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" aria-label="Previous week">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <!-- Week Picker (Calendar Icon + Label) -->
                            <div class="relative flex items-center gap-1">
                                <!-- Calendar icon button -->
                                <button type="button" id="weekPickerBtn" class="flex h-9 w-9 items-center justify-center rounded-md text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" aria-label="Open calendar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                                    </svg>
                                </button>

                                <!-- Week range label -->
                                <span id="week-date-range" class="min-w-[130px] px-1 text-center text-sm font-bold text-bgray-800 dark:text-bgray-50">
                                    {{ $startOfWeek->format($globalDateFormat) }} - {{ $endOfWeek->format($globalDateFormat) }}
                                </span>

                                <!-- Hidden input for Flatpickr -->
                                <input type="text" class="weekPicker absolute top-0 left-0 opacity-0 w-0 h-0 pointer-events-none" value="{{ $startOfWeek->toDateString() }}">
                            </div>

                            <!-- Next button -->
                            <button id="nextWeek" class="flex h-9 w-9 items-center justify-center rounded-md text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" aria-label="Next week">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div class="mx-1 h-5 w-px bg-bgray-300 dark:bg-darkblack-400"></div>

                            <!-- Today button -->
                            @php
                                $isTodayInWeek = \Carbon\Carbon::parse($startOfWeek)->lte(\Carbon\Carbon::parse($todayDate)) && \Carbon\Carbon::parse($endOfWeek)->gte(\Carbon\Carbon::parse($todayDate));
                            @endphp
                            <button id="todayWeek" data-today="{{ $todayDate }}" class="rounded-md px-3 py-1.5 text-sm font-semibold transition {{ $isTodayInWeek ? 'bg-success-50 text-success-600 hover:bg-success-100 dark:bg-success-500/10 dark:text-success-400 dark:hover:bg-success-500/20' : 'text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white' }}">
                                Today
                            </button>

                        </div>
                    </div>

                    <div id="schedule-table">
                        @include('schedule-shift.partials.schedule-table')
                    </div>

                </div>
            </div>
        </section>
    </div>

    <!-- Modal for changing shift -->
    @include('schedule-shift.partials.modal-change-shift')

    <!-- write your code here-->
    <!-- Page ends -->
@endsection

@push('scripts')
    @vite('resources/js/modules/schedule-shift.js')
    <script>
        let currentWeek = "{{ $startOfWeek->toDateString() }}";
    </script>
@endpush
