@extends('layouts.master')

@section('page-content')

    @php
        /*
         * Build the calendar range from the selected month.
         *
         * Monday -> Sunday calendar
         */
        $calendarStart = $selectedDate->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);

        $calendarEnd = $selectedDate->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $totalDays = $calendarStart->diffInDays($calendarEnd) + 1;

    @endphp

    <div class="w-full">


        {{-- ========================================================= --}}
        {{-- Header --}}
        {{-- ========================================================= --}}

        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                    Attendance
                </h2>

                <p class="text-sm text-bgray-500 dark:text-bgray-300">
                    View employee attendance and leaves by month.
                </p>

            </div>


            {{-- Month Navigation --}}
            <div class="flex items-center gap-2">

                {{-- Previous Month --}}
                <a href="{{ route('attendance.index', [
                    'date' => $selectedDate->copy()->subMonth()->toDateString(),
                ]) }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    &larr;
                </a>


                {{-- Current Month --}}
                <div class="min-w-[180px] rounded-lg border border-bgray-300 bg-white px-4 py-2 text-center text-sm font-semibold text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    {{ $selectedDate->format('F Y') }}
                </div>


                {{-- Next Month --}}
                <a href="{{ route('attendance.index', [
                    'date' => $selectedDate->copy()->addMonth()->toDateString(),
                ]) }}" class="flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    &rarr;
                </a>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Messages --}}
        {{-- ========================================================= --}}

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


        {{-- ========================================================= --}}
        {{-- Leave Type Legend --}}
        {{-- ========================================================= --}}

        <div class="mb-4 rounded-xl bg-white p-4 shadow-sm dark:bg-darkblack-600">

            <div class="mb-3 text-sm font-semibold text-bgray-900 dark:text-white">
                Leave Types
            </div>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-3">

                @forelse($leaveTypes as $leaveType)
                    @php
                        $color = $leaveType->color ?: '#3B82F6';
                    @endphp

                    <div class="flex items-center gap-2">

                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $color }};"></span>

                        <span class="text-xs text-bgray-600 dark:text-bgray-300">
                            {{ $leaveType->name }}
                        </span>

                    </div>

                @empty

                    <span class="text-xs text-bgray-500 dark:text-bgray-400">
                        No active leave types found.
                    </span>
                @endforelse

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- Calendar --}}
        {{-- ========================================================= --}}

        <div class="rounded-xl bg-white shadow-sm dark:bg-darkblack-600">

            <div class="overflow-x-auto">

                <div class="min-w-[1000px]">

                    {{-- Week Days --}}
                    <div class="grid grid-cols-7 border-b border-bgray-200 dark:border-darkblack-400">

                        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            <div class="border-r border-bgray-200 px-3 py-3 text-center text-xs font-semibold uppercase text-bgray-500 last:border-r-0 dark:border-darkblack-400 dark:text-bgray-300">
                                {{ $day }}
                            </div>
                        @endforeach

                    </div>


                    {{-- Calendar Days --}}
                    <div class="grid grid-cols-7">

                        @for ($i = 0; $i < $totalDays; $i++)
                            @php

                                $date = $calendarStart->copy()->addDays($i);

                                $dateKey = $date->format('Y-m-d');

                                $isToday = $date->isToday();

                                $isSelected = $date->isSameDay($selectedDate);

                                /*
                                 * Leaves for this date.
                                 */
                                $dayLeaves = $calendarLeaves->get($dateKey, collect());

                                /*
                                 * Show maximum 3 employees directly.
                                 */
                                $visibleLeaves = $dayLeaves->take(3);

                                /*
                                 * Remaining employees.
                                 */
                                $remainingLeaves = max($dayLeaves->count() - 3, 0);

                                /*
                                 * Dates outside selected month.
                                 */
                                $isCurrentMonth = $date->month === $selectedDate->month && $date->year === $selectedDate->year;

                            @endphp


                            {{-- Calendar Cell --}}
                            <div class="
                            relative
                            min-h-[160px]
                            border-b
                            border-r
                            border-bgray-200
                            p-2
                            transition
                            dark:border-darkblack-400

                            {{ !$isCurrentMonth ? 'bg-bgray-50/60 dark:bg-darkblack-500/40' : 'hover:bg-bgray-50 dark:hover:bg-darkblack-500' }}

                            {{ $isSelected ? 'ring-2 ring-inset ring-success-500' : '' }}
                        ">

                                {{-- Date Header --}}
                                <div class="mb-2 flex items-center justify-between">

                                    {{-- Date --}}
                                    <button type="button" onclick="openDateAttendance('{{ $dateKey }}')" class="
                                    flex
                                    h-8
                                    w-8
                                    items-center
                                    justify-center
                                    rounded-full
                                    text-sm
                                    font-semibold

                                    {{ $isToday ? 'bg-success-500 text-white' : ($isCurrentMonth ? 'text-bgray-700 hover:bg-bgray-100 dark:text-white dark:hover:bg-darkblack-400' : 'text-bgray-400 dark:text-bgray-500') }}
                                ">
                                        {{ $date->day }}
                                    </button>


                                    {{-- Leave Count --}}
                                    @if ($dayLeaves->count() > 0)
                                        @php
                                            /*
                                             * Use the first leave type color
                                             * for the count badge.
                                             */
                                            $firstLeave = $dayLeaves->first();

                                            $countColor = $firstLeave?->leaveType?->color ?: '#3B82F6';
                                        @endphp

                                        <span class="rounded-full px-2 py-1 text-[10px] font-semibold" style="
                                        background-color: {{ $countColor }}20;
                                        color: {{ $countColor }};
                                    ">
                                            {{ $dayLeaves->count() }}
                                            {{ $dayLeaves->count() === 1 ? 'Leave' : 'Leaves' }}
                                        </span>
                                    @endif

                                </div>


                                {{-- Leave List --}}
                                <div class="space-y-1">

                                    @foreach ($visibleLeaves as $leave)
                                        @php
                                            $leaveColor = $leave->leaveType?->color ?: '#3B82F6';
                                            $employeeName = $leave->user->name ?? 'Employee';
                                        @endphp

                                        {{-- Employee Leave --}}
                                        <div class="rounded-md px-2 py-1.5" style="
                                        background-color: {{ $leaveColor }}15;
                                        border-left: 3px solid {{ $leaveColor }};
                                    ">

                                            <div class="flex items-center gap-1.5">

                                                {{-- Employee Initial --}}
                                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold" style="
                                                background-color: {{ $leaveColor }}30;
                                                color: {{ $leaveColor }};
                                            ">
                                                    {{ strtoupper(substr($employeeName, 0, 1)) }}
                                                </div>


                                                {{-- Employee Name --}}
                                                <div class="min-w-0">

                                                    <div class="truncate text-xs font-medium" style="color: {{ $leaveColor }};">
                                                        {{ $employeeName }}
                                                    </div>

                                                </div>

                                            </div>

                                        </div>
                                    @endforeach


                                    {{-- More Button --}}
                                    @if ($remainingLeaves > 0)
                                        <button type="button" onclick="showDayLeaves('{{ $dateKey }}')" class="w-full rounded-md bg-bgray-100 px-2 py-1.5 text-left text-xs font-semibold text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-400 dark:text-bgray-300">
                                            +{{ $remainingLeaves }} more
                                        </button>
                                    @endif

                                </div>


                                {{-- Attendance Button --}}
                                @if ($date->isToday() || $date->isFuture())
                                    @can('attendance.create')
                                        <button type="button" onclick="openDateAttendance('{{ $dateKey }}')" class="mt-3 w-full rounded-md border border-dashed border-bgray-300 px-2 py-1.5 text-[11px] font-medium text-bgray-500 transition hover:border-success-500 hover:text-success-500 dark:border-darkblack-400">
                                            + Mark Attendance
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

    {{-- ========================================================= --}}
    {{-- Leave Details Modal --}}
    {{-- ========================================================= --}}

    <div id="dayLeavesModal" class="fixed inset-0 z-[90] hidden overflow-y-auto">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeDayLeaves()"></div>

        {{-- Modal --}}
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-darkblack-600">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 id="dayLeavesTitle" class="text-lg font-semibold text-bgray-900 dark:text-white">
                            Leave Details
                        </h3>

                        <p class="text-xs text-bgray-500 dark:text-bgray-300">
                            Employees on leave
                        </p>

                    </div>

                    <button type="button" onclick="closeDayLeaves()" class="text-2xl leading-none text-bgray-500 transition hover:text-bgray-900 dark:hover:text-white">
                        &times;
                    </button>

                </div>


                {{-- Body --}}
                <div id="dayLeavesContent" class="max-h-[60vh] overflow-y-auto p-5"></div>


                {{-- Footer --}}
                <div class="border-t border-bgray-200 px-5 py-4 text-right dark:border-darkblack-400">

                    <button type="button" onclick="closeDayLeaves()" class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white dark:hover:bg-darkblack-500">
                        Close
                    </button>

                </div>

            </div>

        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- Mark Attendance / Add Leave Modal --}}
    {{-- ========================================================= --}}

    <div id="markAttendanceModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeMarkAttendance()"></div>


        {{-- Modal --}}
        <div class="relative flex min-h-full items-center justify-center p-4">

            <div class="relative z-10 w-full max-w-5xl rounded-xl bg-white shadow-xl dark:bg-darkblack-600">

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">

                    <div>

                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">
                            Mark Attendance / Add Leave
                        </h3>

                        <p class="text-xs text-bgray-500 dark:text-bgray-300">
                            Add a leave directly for an employee.
                        </p>

                    </div>


                    <button type="button" onclick="closeMarkAttendance()" class="text-2xl leading-none text-bgray-500 transition hover:text-bgray-900 dark:hover:text-white">
                        &times;
                    </button>

                </div>


                {{-- Form --}}
                <form id="markAttendanceForm" action="{{ route('leave-requests.store') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    {{-- This identifies that the request came from Mark Attendance --}}
                    <input type="hidden" name="created_from_attendance" value="1">


                    <div class="max-h-[75vh] overflow-y-auto p-6">

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">


                            {{-- ================================================= --}}
                            {{-- User --}}
                            {{-- ================================================= --}}

                            <div>

                                <label
                                    for="attendance_user_id"
                                    class="mb-2 block text-sm font-medium"
                                >
                                    User
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="user_id"
                                    id="attendance_user_id"
                                    required
                                    class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                >

                                    <option value="">
                                        Select User
                                    </option>

                                    @foreach ($attendanceUsers ?? [] as $attendanceUser)

                                        <option value="{{ $attendanceUser->id }}">
                                            {{ $attendanceUser->name }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- ================================================= --}}
                            {{-- Leave Type --}}
                            {{-- ================================================= --}}

                            <div>

                                <label for="attendance_leave_type_id" class="mb-2 block text-sm font-medium">
                                    Leave Type
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="leave_type_id" id="attendance_leave_type_id" required class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    <option value="">
                                        Select Leave Type
                                    </option>

                                    @foreach ($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" data-file-required="{{ $leaveType->is_file_upload_required ? '1' : '0' }}">
                                            {{ $leaveType->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- ================================================= --}}
                            {{-- Type --}}
                            {{-- ================================================= --}}

                            <div>

                                <label for="attendance_type" class="mb-2 block text-sm font-medium">
                                    Type
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="type" id="attendance_type" required class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    <option value="full_day" selected>
                                        Full Day
                                    </option>

                                    <option value="half_day">
                                        Half Day
                                    </option>

                                </select>

                            </div>


                            {{-- ================================================= --}}
                            {{-- Leave Period --}}
                            {{-- ================================================= --}}

                            <div id="attendance-leave-period-wrapper" class="hidden">

                                <label for="attendance_half_day_type" class="mb-2 block text-sm font-medium">
                                    Leave Period
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="half_day_type" id="attendance_half_day_type" class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                    <option value="">
                                        Select Period
                                    </option>

                                    <option value="first_half">
                                        First Half
                                    </option>

                                    <option value="second_half">
                                        Second Half
                                    </option>

                                </select>

                            </div>


                            {{-- ================================================= --}}
                            {{-- From Date --}}
                            {{-- ================================================= --}}

                            <div>

                                <label for="attendance_from_date" class="mb-2 block text-sm font-medium">
                                    From Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="date" name="requested_from_date" id="attendance_from_date" required class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                            </div>


                            {{-- ================================================= --}}
                            {{-- To Date --}}
                            {{-- ================================================= --}}

                            <div>

                                <label for="attendance_to_date" class="mb-2 block text-sm font-medium">
                                    To Date
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="date" name="requested_to_date" id="attendance_to_date" required class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                            </div>


                            {{-- ================================================= --}}
                            {{-- Duration --}}
                            {{-- ================================================= --}}

                            <div>

                                <label class="mb-2 block text-sm font-medium">
                                    Duration
                                </label>

                                <input type="text" id="attendance_duration_display" value="0.00" readonly class="w-full rounded-lg border border-bgray-200 bg-bgray-50 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <p class="mt-1 text-xs text-bgray-500">
                                    Automatically calculated.
                                </p>

                            </div>


                            {{-- ================================================= --}}
                            {{-- Leave Balance --}}
                            {{-- ================================================= --}}

                            <div id="attendance-leave-balance-info" class="hidden md:col-span-2">

                                <div id="attendance-leave-balance-success" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                                    <strong>
                                        Leave Balance:
                                    </strong>

                                    <span id="attendance-leave-balance-success-text"></span>
                                </div>


                                <div id="attendance-leave-balance-warning" class="hidden rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">

                                    <strong>
                                        Leave Balance Notice
                                    </strong>

                                    <p id="attendance-leave-balance-warning-text" class="mt-1"></p>

                                </div>

                            </div>


                            {{-- ================================================= --}}
                            {{-- Reason --}}
                            {{-- ================================================= --}}

                            <div class="md:col-span-2">

                                <label for="attendance_reason" class="mb-2 block text-sm font-medium">
                                    Reason
                                    <span class="text-red-500">*</span>
                                </label>

                                <textarea name="reason" id="attendance_reason" rows="4" required placeholder="Enter the reason for the leave..." class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>

                            </div>


                            {{-- ================================================= --}}
                            {{-- Attachment --}}
                            {{-- ================================================= --}}

                            <div id="attendance-attachment-wrapper" class="hidden md:col-span-2">

                                <label for="attendance_attachment" class="mb-2 block text-sm font-medium">
                                    Supporting Document

                                    <span id="attendance-attachment-required" class="hidden text-red-500">
                                        *
                                    </span>

                                </label>

                                <input type="file" name="attachment" id="attendance_attachment" class="w-full rounded-lg border border-bgray-200 px-4 py-3 text-sm dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">

                                <p id="attendance-attachment-help" class="mt-1 text-xs text-bgray-500">
                                    You may upload a supporting document if required.
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- ========================================================= --}}
                    {{-- Footer --}}
                    {{-- ========================================================= --}}

                    <div class="flex justify-end gap-3 border-t border-bgray-100 px-6 py-4 dark:border-darkblack-400">

                        <button type="button" onclick="closeMarkAttendance()" class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm">
                            Cancel
                        </button>

                        <button type="submit" class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white">
                            Add Leave
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- ========================================================= --}}
    {{-- JavaScript --}}
    {{-- ========================================================= --}}

    <script>
        /*
         * Leave data prepared by the controller.
         *
         * Expected structure:
         *
         * {
         *     "2026-08-01": [
         *         {
         *             employee: "John",
         *             leave_type: "Annual Leave",
         *             color: "#3B82F6",
         *             from_date: "01/08/2026",
         *             to_date: "05/08/2026"
         *         }
         *     ]
         * }
         */
        const calendarLeaves = @json($calendarLeavesForJs);


        /*
         * Show all employees on leave for a date.
         */
        function showDayLeaves(date) {
            const leaves = calendarLeaves[date] || [];

            const modal = document.getElementById('dayLeavesModal');

            const content = document.getElementById('dayLeavesContent');

            const title = document.getElementById('dayLeavesTitle');


            /*
             * Format selected date.
             */
            const formattedDate = new Date(date + 'T00:00:00')
                .toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });


            title.innerText = 'Leaves - ' + formattedDate;


            /*
             * No leaves.
             */
            if (!leaves.length) {

                content.innerHTML = `
                <div class="py-8 text-center text-sm text-bgray-500">
                    No employees are on leave.
                </div>
            `;

            } else {

                /*
                 * Create leave list.
                 */
                content.innerHTML = leaves.map(function(leave) {

                    const employeeName =
                        leave.employee || 'Employee';

                    const leaveType =
                        leave.leave_type || 'Leave';

                    const leaveColor =
                        leave.color || '#3B82F6';

                    const fromDate =
                        leave.from_date || '';

                    const toDate =
                        leave.to_date || '';


                    return `
                    <div
                        class="mb-3 rounded-lg border border-bgray-200 p-3 dark:border-darkblack-400"
                        style="border-left: 4px solid ${escapeHtml(leaveColor)};"
                    >

                        <div class="flex items-center gap-3">

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                style="
                                    background-color: ${escapeHtml(leaveColor)}30;
                                    color: ${escapeHtml(leaveColor)};
                                "
                            >
                                ${escapeHtml(
                                    employeeName.charAt(0).toUpperCase()
                                )}
                            </div>


                            <div class="min-w-0 flex-1">

                                <div
                                    class="text-sm font-semibold text-bgray-900 dark:text-white"
                                >
                                    ${escapeHtml(employeeName)}
                                </div>


                                <div
                                    class="text-xs font-medium"
                                    style="color: ${escapeHtml(leaveColor)};"
                                >
                                    ${escapeHtml(leaveType)}
                                </div>

                            </div>

                        </div>


                        <div
                            class="mt-2 text-xs text-bgray-500 dark:text-bgray-300"
                        >
                            ${escapeHtml(fromDate)}
                            ${fromDate && toDate ? ' - ' : ''}
                            ${escapeHtml(toDate)}
                        </div>

                    </div>
                `;

                }).join('');

            }


            /*
             * Open modal.
             */
            modal.classList.remove('hidden');

            document.body.classList.add('overflow-hidden');
        }


        /*
         * Close leave modal.
         */
        function closeDayLeaves() {
            const modal =
                document.getElementById('dayLeavesModal');

            modal.classList.add('hidden');

            document.body.classList.remove('overflow-hidden');
        }


        /*
         * Escape HTML.
         */
        function escapeHtml(value) {
            const div = document.createElement('div');

            div.textContent = value ?? '';

            return div.innerHTML;
        }


        /*
         * Open attendance for selected date.
         *
         * This keeps the selected date in the URL.
         */
        function openDateAttendance(date)
        {
            const modal =
                document.getElementById('markAttendanceModal');

            const fromDate =
                document.getElementById('attendance_from_date');

            const toDate =
                document.getElementById('attendance_to_date');


            /*
            * Set the selected calendar date.
            */
            if (fromDate) {
                fromDate.value = date;
            }

            if (toDate) {
                toDate.value = date;
            }


            /*
            * Open modal.
            */
            if (modal) {
                modal.classList.remove('hidden');

                document.body.classList.add('overflow-hidden');
            }


            /*
            * Calculate initial duration.
            */
            calculateAttendanceDuration();
        }


        /*
         * Close modal using ESC.
         */
        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {

                closeDayLeaves();

            }

        });

        function closeMarkAttendance()
        {
            const modal =
                document.getElementById('markAttendanceModal');

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMarkAttendance();
            }
        });

        function calculateAttendanceDuration()
        {
            const type =
                document.getElementById('attendance_type');

            const fromDate =
                document.getElementById('attendance_from_date');

            const toDate =
                document.getElementById('attendance_to_date');

            const duration =
                document.getElementById('attendance_duration_display');


            if (
                !type ||
                !fromDate ||
                !toDate ||
                !duration
            ) {
                return;
            }


            if (
                !fromDate.value ||
                !toDate.value
            ) {
                duration.value = '0.00';
                return;
            }


            const startDate =
                new Date(
                    fromDate.value + 'T00:00:00'
                );

            const endDate =
                new Date(
                    toDate.value + 'T00:00:00'
                );


            if (endDate < startDate) {
                duration.value = '0.00';
                return;
            }


            const days =
                Math.floor(
                    (
                        endDate - startDate
                    ) /
                    (
                        1000 * 60 * 60 * 24
                    )
                ) + 1;


            if (type.value === 'half_day') {

                duration.value =
                    (days * 0.5).toFixed(2);

            } else {

                duration.value =
                    days.toFixed(2);

            }
        }


        document.addEventListener('DOMContentLoaded', function () {

            const attendanceType =
                document.getElementById('attendance_type');

            const attendanceFrom =
                document.getElementById('attendance_from_date');

            const attendanceTo =
                document.getElementById('attendance_to_date');


            if (attendanceType) {

                attendanceType.addEventListener(
                    'change',
                    function () {

                        calculateAttendanceDuration();

                        toggleAttendanceLeavePeriod();

                    }
                );

            }


            if (attendanceFrom) {

                attendanceFrom.addEventListener(
                    'change',
                    calculateAttendanceDuration
                );

            }


            if (attendanceTo) {

                attendanceTo.addEventListener(
                    'change',
                    calculateAttendanceDuration
                );

            }

        });

        function toggleAttendanceLeavePeriod()
        {
            const type =
                document.getElementById('attendance_type');

            const wrapper =
                document.getElementById(
                    'attendance-leave-period-wrapper'
                );

            const period =
                document.getElementById(
                    'attendance_half_day_type'
                );


            if (
                !type ||
                !wrapper ||
                !period
            ) {
                return;
            }


            if (type.value === 'half_day') {

                wrapper.classList.remove('hidden');

                period.setAttribute(
                    'required',
                    'required'
                );

            } else {

                wrapper.classList.add('hidden');

                period.removeAttribute(
                    'required'
                );

                period.value = '';

            }
        }

    </script>

@endsection
