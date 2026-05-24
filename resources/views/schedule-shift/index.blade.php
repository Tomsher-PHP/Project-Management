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

                            <div class="flex items-center gap-1 rounded-md bg-bgray-50 px-2 py-1 dark:bg-darkblack-500">

                                <!-- Previous button -->
                                <button id="prevWeek" class="px-3 py-2 rounded-md text-sm font-medium text-bgray-700 transition hover:bg-white hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white">
                                    Previous
                                </button>

                                <!-- Today button -->
                                <button id="todayWeek" data-today="{{ $todayDate }}" class="px-3 py-2 rounded-md text-sm font-semibold text-success-400 transition hover:bg-white hover:text-success-500 dark:text-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-200">
                                    Today
                                </button>

                                <!-- Calendar icon button -->
                                <div class="relative">
                                    <button type="button" id="weekPickerBtn" class="flex h-10 w-10 items-center justify-center rounded-md text-bgray-600 transition hover:bg-white hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </button>

                                    <!-- Hidden input for Flatpickr -->
                                    <input type="text" class="weekPicker absolute top-0 left-0 opacity-0 w-0 h-0 pointer-events-none" value="{{ $startOfWeek->toDateString() }}">
                                </div>

                                <!-- Week range label -->
                                <span id="week-date-range" class="min-w-[190px] px-3 text-center text-base font-semibold text-bgray-800 dark:text-bgray-50">
                                    {{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M Y') }}
                                </span>

                                <!-- Next button -->
                                <button id="nextWeek" class="px-3 py-2 rounded-md text-sm font-medium text-bgray-700 transition hover:bg-white hover:text-bgray-900 dark:text-bgray-100 dark:hover:bg-darkblack-400 dark:hover:text-white">
                                    Next
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
