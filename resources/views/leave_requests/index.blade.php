@extends('layouts.master')

@section('page-content')

    <div class="w-full">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            {{-- Apply Leave --}}
            @if (!$isPendingPage)

                @can('leave_request.create')

                    <a href="{{ route('leave-requests.create') }}"
                        class="inline-flex items-center gap-1 rounded-md border border-bgray-500 bg-white px-2 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4v16m8-8H4" />

                        </svg>

                        Apply Leave

                    </a>

                @endcan

            @endif

        </div>


        {{-- Success Message --}}
        @if (session('success'))

            <div
                class="mb-5 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">

                {{ session('success') }}

            </div>

        @endif


        {{-- Error Message --}}
        @if (session('error'))

            <div
                class="mb-5 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700">

                {{ session('error') }}

            </div>

        @endif


        {{-- Warning Message --}}
        @if (session('warning'))

            <div
                class="mb-5 rounded-lg bg-yellow-100 px-4 py-3 text-sm text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">

                {{ session('warning') }}

            </div>

        @endif


        {{-- LIST VIEW --}}
        <div
            class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="overflow-x-auto">

                <table class="min-w-full border-separate border-spacing-0">

                    <thead class="bg-bgray-50/80 dark:bg-darkblack-500">

                        <tr class="border-b border-bgray-200 dark:border-darkblack-400">

                            {{-- Employee --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="user.name"
                                    label="Employee"
                                />

                            </th>


                            {{-- Leave Type --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="leaveType.name"
                                    label="Leave Type"
                                />

                            </th>


                            {{-- Added By --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="addedBy.name"
                                    label="Added By"
                                />

                            </th>


                            {{-- Day Type --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                Day Type

                            </th>


                            {{-- Half Day Type --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                Half Day

                            </th>


                            {{-- From Date --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="requested_from_date"
                                    label="From Date"
                                />

                            </th>


                            {{-- To Date --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="requested_to_date"
                                    label="To Date"
                                />

                            </th>


                            {{-- Days --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="duration"
                                    label="Days"
                                />

                            </th>


                            {{-- Status --}}
                            <th class="px-5 py-4 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <x-sorting.sortable-column
                                    column="status"
                                    label="Status"
                                />

                            </th>


                            {{-- Action --}}
                            <th class="px-5 py-4 text-center text-sm font-semibold text-bgray-600 dark:text-bgray-200">

                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    Action
                                </span>

                            </th>

                        </tr>

                    </thead>


                    <tbody class="bg-white dark:bg-darkblack-600">

                        @forelse ($leaveRequests as $leaveRequest)

                            @php

                                /*
                                 * ==========================================================
                                 * Cancel Modal Data
                                 * ==========================================================
                                 */

                                $cancelEmployeeName = '-';

                                if ($leaveRequest->user) {
                                    $cancelEmployeeName = $leaveRequest->user->name ?? '-';
                                }


                                $cancelLeaveType = '-';

                                if ($leaveRequest->leaveType) {
                                    $cancelLeaveType = $leaveRequest->leaveType->name ?? '-';
                                }


                                /*
                                 * Full Day / Half Day
                                 */
                                if ($leaveRequest->type === 'half_day') {
                                    $cancelDayType = 'Half Day';
                                } else {
                                    $cancelDayType = 'Full Day';
                                }


                                /*
                                 * Half Day Type
                                 */
                                $cancelHalfDayType = '';

                                if ($leaveRequest->half_day_type === 'first_half') {

                                    $cancelHalfDayType = 'First Half';

                                } elseif ($leaveRequest->half_day_type === 'second_half') {

                                    $cancelHalfDayType = 'Second Half';

                                }


                                /*
                                 * Dates
                                 *
                                 * For approved leave use approved dates.
                                 * For pending leave use requested dates.
                                 */
                                $cancelFromDate = '-';
                                $cancelToDate = '-';

                                if (
                                    $leaveRequest->status === 'approved' &&
                                    $leaveRequest->approved_from_date
                                ) {

                                    $cancelFromDate =
                                        \Carbon\Carbon::parse(
                                            $leaveRequest->approved_from_date
                                        )->format('d/m/Y');

                                } elseif ($leaveRequest->requested_from_date) {

                                    $cancelFromDate =
                                        \Carbon\Carbon::parse(
                                            $leaveRequest->requested_from_date
                                        )->format('d/m/Y');

                                }


                                if (
                                    $leaveRequest->status === 'approved' &&
                                    $leaveRequest->approved_to_date
                                ) {

                                    $cancelToDate =
                                        \Carbon\Carbon::parse(
                                            $leaveRequest->approved_to_date
                                        )->format('d/m/Y');

                                } elseif ($leaveRequest->requested_to_date) {

                                    $cancelToDate =
                                        \Carbon\Carbon::parse(
                                            $leaveRequest->requested_to_date
                                        )->format('d/m/Y');

                                }


                                /*
                                 * Duration
                                 */
                                if ($leaveRequest->status === 'approved') {

                                    if ($leaveRequest->approved_duration !== null) {
                                        $cancelDuration =
                                            $leaveRequest->approved_duration;
                                    } else {
                                        $cancelDuration =
                                            $leaveRequest->duration;
                                    }

                                } else {

                                    $cancelDuration =
                                        $leaveRequest->duration;

                                }


                                /*
                                 * Status
                                 */
                                $cancelStatus =
                                    ucfirst($leaveRequest->status);

                            @endphp


                            <tr class="border-b border-bgray-100 dark:border-darkblack-400">

                                {{-- Employee --}}
                                <td class="px-5 py-4">

                                    <div class="font-medium text-bgray-900 dark:text-white">

                                        {{ $leaveRequest->user->name ?? '-' }}

                                    </div>

                                </td>


                                {{-- Leave Type --}}
                                <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">

                                    {{ $leaveRequest->leaveType->name ?? '-' }}

                                </td>


                                {{-- Added By --}}
                                <td class="px-5 py-4">

                                    <div class="font-medium text-bgray-900 dark:text-white">

                                        {{ $leaveRequest->addedBy->name ?? '-' }}

                                    </div>

                                </td>


                                {{-- Day Type --}}
                                <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">

                                    @if ($leaveRequest->type === 'half_day')

                                        Half Day

                                    @else

                                        Full Day

                                    @endif

                                </td>


                                {{-- Half Day Type --}}
                                <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">

                                    @if ($leaveRequest->type === 'half_day')

                                        @if ($leaveRequest->half_day_type === 'first_half')

                                            <span class="font-medium">
                                                First Half
                                            </span>

                                        @elseif ($leaveRequest->half_day_type === 'second_half')

                                            <span class="font-medium">
                                                Second Half
                                            </span>

                                        @else

                                            -

                                        @endif

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- From Date --}}
                                <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">

                                    {{ $leaveRequest->requested_from_date
                                        ? \Carbon\Carbon::parse($leaveRequest->requested_from_date)->format('d M Y')
                                        : '-' }}

                                </td>


                                {{-- To Date --}}
                                <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">

                                    {{ $leaveRequest->requested_to_date
                                        ? \Carbon\Carbon::parse($leaveRequest->requested_to_date)->format('d M Y')
                                        : '-' }}

                                </td>


                                {{-- Days --}}
                                <td class="px-5 py-4 text-sm text-bgray-600 dark:text-bgray-300">

                                    {{ number_format($leaveRequest->duration, 2) }}

                                </td>


                                {{-- Status --}}
                                <td class="px-5 py-4">

                                    @php

                                        $statusClasses = [
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'approved' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                            'cancelled' => 'bg-gray-100 text-gray-700',
                                        ];

                                    @endphp


                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses[$leaveRequest->status] ?? 'bg-gray-100 text-gray-700' }}">

                                        {{ ucfirst($leaveRequest->status) }}

                                    </span>

                                </td>


                                {{-- Actions --}}
                                <td class="px-6 py-5 xl:w-[260px] xl:px-0">

                                    <div class="flex w-full flex-wrap items-center justify-center gap-2">


                                        {{-- ======================================================
                                             PENDING PAGE
                                        ======================================================= --}}
                                        @if ($isPendingPage && $leaveRequest->status === 'pending')

                                            @if ($leaveRequest->user_id === auth()->id())

                                                <span class="text-sm text-gray-500">

                                                    Waiting for approval

                                                </span>

                                            @else

                                                {{-- Approve --}}
                                                @can('leave_request.approve')

                                                    <a
                                                        href="{{ route('leave-requests.edit', [
                                                            'leaveRequest' => $leaveRequest->id,
                                                            'approved_mode' => 1,
                                                            'action' => 'approve',
                                                        ]) }}"
                                                        class="inline-flex items-center rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400"
                                                    >

                                                        Approve

                                                    </a>

                                                @endcan


                                                {{-- Reject --}}
                                                @can('leave_request.reject')

                                                    <a
                                                        href="{{ route('leave-requests.edit', [
                                                            'leaveRequest' => $leaveRequest->id,
                                                            'approved_mode' => 1,
                                                            'action' => 'reject',
                                                        ]) }}"
                                                        class="inline-flex items-center rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400"
                                                    >

                                                        Reject

                                                    </a>

                                                @endcan


                                                {{-- View --}}
                                                @can('leave_request.view')

                                                    <x-view-button
                                                        :action="route('leave-requests.show', $leaveRequest->id)"
                                                    />

                                                @endcan

                                            @endif


                                        {{-- ======================================================
                                             NORMAL PAGE
                                        ======================================================= --}}
                                        @else

                                            {{-- View --}}
                                            @can('leave_request.view')

                                                <x-view-button
                                                    :action="route('leave-requests.show', $leaveRequest->id)"
                                                />

                                            @endcan


                                            {{-- ==================================================
                                                 Edit

                                                 The edit icon is always visible for the
                                                 employee's own request.

                                                 The edit form/controller will decide:
                                                 - Pending + added by employee:
                                                   full edit
                                                 - Approved:
                                                   reason + attachment only
                                                 - Added by another person:
                                                   reason + attachment only
                                            =================================================== --}}
                                            @can('leave_request.edit')

                                                @if ($leaveRequest->user_id === auth()->id())

                                                    <x-edit-button
                                                        :action="route('leave-requests.edit', $leaveRequest->id)"
                                                    />

                                                @endif

                                            @endcan


                                            {{-- ==================================================
                                                 Cancel
                                            =================================================== --}}
                                            @can('leave_request.cancel')

                                                @if (
                                                    $leaveRequest->user_id === auth()->id() &&
                                                    in_array(
                                                        $leaveRequest->status,
                                                        ['pending', 'approved'],
                                                        true
                                                    )
                                                )

                                                    <button
                                                        type="button"
                                                        onclick="openCancelLeaveModal(this)"

                                                        data-id="{{ $leaveRequest->id }}"

                                                        data-employee="{{ $cancelEmployeeName }}"

                                                        data-leave-type="{{ $cancelLeaveType }}"

                                                        data-type="{{ $cancelDayType }}"

                                                        data-half-day-type="{{ $cancelHalfDayType }}"

                                                        data-from-date="{{ $cancelFromDate }}"

                                                        data-to-date="{{ $cancelToDate }}"

                                                        data-duration="{{ $cancelDuration }}"

                                                        data-status="{{ $cancelStatus }}"

                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-orange-200 bg-orange-50 text-orange-600 transition hover:bg-orange-100"

                                                        title="Cancel Request"
                                                    >

                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >

                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M6 18L18 6M6 6l12 12"
                                                            />

                                                        </svg>

                                                    </button>

                                                @endif

                                            @endcan

                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="px-5 py-10 text-center text-sm text-bgray-500"
                                >

                                    @if ($isPendingPage)

                                        No pending leave requests found.

                                    @else

                                        No leave requests found.

                                    @endif

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if ($leaveRequests->hasPages())

                <div class="border-t border-bgray-200 px-5 py-4 dark:border-darkblack-400">

                    {{ $leaveRequests->links() }}

                </div>

            @endif

        </div>

    </div>


    {{-- ==============================================================
         Cancel Leave Modal
    ============================================================== --}}
    <div
        id="cancelLeaveModal"
        class="fixed inset-0 z-[9999] hidden overflow-y-auto"
        aria-labelledby="cancelLeaveModalTitle"
        aria-modal="true"
        role="dialog"
    >

        {{-- Background --}}
        <div
            class="fixed inset-0 bg-black/50"
            onclick="closeCancelLeaveModal()"
        ></div>


        {{-- Modal Wrapper --}}
        <div class="relative flex min-h-full items-center justify-center p-4">

            {{-- Modal --}}
            <div
                class="relative w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-darkblack-600"
                onclick="event.stopPropagation()"
            >

                {{-- Header --}}
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-darkblack-400"
                >

                    <div>

                        <h2
                            id="cancelLeaveModalTitle"
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >

                            Cancel Leave Request

                        </h2>


                        <p class="mt-1 text-sm text-gray-500 dark:text-bgray-300">

                            Please review the leave details and provide a cancellation reason.

                        </p>

                    </div>


                    {{-- Close --}}
                    <button
                        type="button"
                        onclick="closeCancelLeaveModal()"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-darkblack-500"
                        aria-label="Close"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />

                        </svg>

                    </button>

                </div>


                {{-- Body --}}
                <div class="px-6 py-5">

                    {{-- Leave Details --}}
                    <div
                        class="mb-5 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200 dark:bg-darkblack-500 dark:ring-darkblack-400"
                    >

                        <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white">

                            Leave Details

                        </h3>


                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            {{-- Employee --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    Employee

                                </span>


                                <span
                                    id="cancel-leave-employee"
                                    class="mt-1 block text-sm font-semibold text-gray-900 dark:text-white"
                                >

                                    -

                                </span>

                            </div>


                            {{-- Leave Type --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    Leave Type

                                </span>


                                <span
                                    id="cancel-leave-type"
                                    class="mt-1 block text-sm font-semibold text-gray-900 dark:text-white"
                                >

                                    -

                                </span>

                            </div>


                            {{-- Type --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    Type

                                </span>


                                <span
                                    id="cancel-leave-day-type"
                                    class="mt-1 block text-sm text-gray-900 dark:text-white"
                                >

                                    -

                                </span>

                            </div>


                            {{-- Status --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    Status

                                </span>


                                <span
                                    id="cancel-leave-status"
                                    class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                >

                                    -

                                </span>

                            </div>


                            {{-- From Date --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    From Date

                                </span>


                                <span
                                    id="cancel-leave-from"
                                    class="mt-1 block text-sm text-gray-900 dark:text-white"
                                >

                                    -

                                </span>

                            </div>


                            {{-- To Date --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    To Date

                                </span>


                                <span
                                    id="cancel-leave-to"
                                    class="mt-1 block text-sm text-gray-900 dark:text-white"
                                >

                                    -

                                </span>

                            </div>


                            {{-- Duration --}}
                            <div>

                                <span
                                    class="block text-xs font-medium uppercase text-gray-500 dark:text-bgray-400"
                                >

                                    Duration

                                </span>


                                <span
                                    id="cancel-leave-duration"
                                    class="mt-1 block text-sm font-semibold text-gray-900 dark:text-white"
                                >

                                    -

                                </span>

                            </div>

                        </div>

                    </div>


                    {{-- Cancellation Form --}}
                    <form
                        id="cancelLeaveForm"
                        method="POST"
                        action=""
                    >

                        @csrf


                        {{-- Cancellation Reason --}}
                        <div>

                            <label
                                for="cancellation_reason"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-bgray-200"
                            >

                                Cancellation Reason

                                <span class="text-red-500">*</span>

                            </label>


                            <textarea
                                id="cancellation_reason"
                                name="cancellation_reason"
                                rows="4"
                                maxlength="2000"
                                required
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                                placeholder="Please enter the reason for cancelling this leave request..."
                            ></textarea>


                            <p class="mt-1 text-xs text-gray-500 dark:text-bgray-400">

                                Maximum 2000 characters.

                            </p>

                        </div>


                        {{-- Buttons --}}
                        <div class="mt-6 flex justify-end gap-3">

                            {{-- Close --}}
                            <button
                                type="button"
                                onclick="closeCancelLeaveModal()"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:bg-darkblack-400"
                            >

                                Close

                            </button>


                            {{-- Cancel Leave --}}
                            <button
                                type="submit"
                                id="cancelLeaveSubmitButton"
                                class="rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-700"
                            >

                                Cancel Leave

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

@endsection


@push('scripts')

<script>

    /*
     * ==============================================================
     * Cancel Leave Modal
     * ==============================================================
     */


    /**
     * Open Cancel Leave Modal
     */
    function openCancelLeaveModal(button) {

        const modal =
            document.getElementById('cancelLeaveModal');

        const form =
            document.getElementById('cancelLeaveForm');

        const reasonInput =
            document.getElementById('cancellation_reason');


        if (!modal || !form) {
            return;
        }


        /*
         * Employee
         */
        const employeeElement =
            document.getElementById('cancel-leave-employee');

        if (employeeElement) {

            employeeElement.textContent =
                button.dataset.employee || '-';

        }


        /*
         * Leave Type
         */
        const leaveTypeElement =
            document.getElementById('cancel-leave-type');

        if (leaveTypeElement) {

            leaveTypeElement.textContent =
                button.dataset.leaveType || '-';

        }


        /*
         * Day Type
         */
        let dayType =
            button.dataset.type || '-';


        if (
            button.dataset.type === 'Half Day' &&
            button.dataset.halfDayType
        ) {

            dayType =
                dayType +
                ' (' +
                button.dataset.halfDayType +
                ')';

        }


        const dayTypeElement =
            document.getElementById('cancel-leave-day-type');

        if (dayTypeElement) {

            dayTypeElement.textContent =
                dayType;

        }


        /*
         * Status
         */
        const statusElement =
            document.getElementById('cancel-leave-status');


        if (statusElement) {

            const status =
                button.dataset.status || '-';

            statusElement.textContent =
                status;


            /*
             * Reset classes.
             */
            statusElement.className =
                'mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold';


            /*
             * Status colour.
             */
            if (status.toLowerCase() === 'approved') {

                statusElement.classList.add(
                    'bg-green-100',
                    'text-green-800'
                );

            } else if (status.toLowerCase() === 'pending') {

                statusElement.classList.add(
                    'bg-yellow-100',
                    'text-yellow-800'
                );

            } else {

                statusElement.classList.add(
                    'bg-gray-100',
                    'text-gray-800'
                );

            }

        }


        /*
         * From Date
         */
        const fromDateElement =
            document.getElementById('cancel-leave-from');

        if (fromDateElement) {

            fromDateElement.textContent =
                button.dataset.fromDate || '-';

        }


        /*
         * To Date
         */
        const toDateElement =
            document.getElementById('cancel-leave-to');

        if (toDateElement) {

            toDateElement.textContent =
                button.dataset.toDate || '-';

        }


        /*
         * Duration
         */
        const durationElement =
            document.getElementById('cancel-leave-duration');


        if (durationElement) {

            const duration =
                Number(button.dataset.duration || 0);

            durationElement.textContent =
                duration.toFixed(2) + ' days';

        }


        /*
         * Set form action.
         */
        form.action =
            "{{ url('leave-requests') }}/" +
            button.dataset.id +
            "/cancel";


        /*
         * Clear previous reason.
         */
        if (reasonInput) {

            reasonInput.value = '';

        }


        /*
         * Enable submit button.
         */
        const submitButton =
            document.getElementById('cancelLeaveSubmitButton');


        if (submitButton) {

            submitButton.disabled =
                false;

            submitButton.textContent =
                'Cancel Leave';

        }


        /*
         * Open modal.
         */
        modal.classList.remove('hidden');

        document.body.classList.add('overflow-hidden');


        /*
         * Focus reason field.
         */
        setTimeout(function () {

            if (reasonInput) {

                reasonInput.focus();

            }

        }, 100);

    }


    /**
     * Close Cancel Leave Modal
     */
    function closeCancelLeaveModal() {

        const modal =
            document.getElementById('cancelLeaveModal');


        if (!modal) {
            return;
        }


        /*
         * Hide modal.
         */
        modal.classList.add('hidden');


        /*
         * Restore scrolling.
         */
        document.body.classList.remove('overflow-hidden');


        /*
         * Clear reason.
         */
        const reasonInput =
            document.getElementById('cancellation_reason');


        if (reasonInput) {

            reasonInput.value = '';

        }

    }


    /**
     * Close modal with Escape key.
     */
    document.addEventListener('keydown', function (event) {

        if (event.key !== 'Escape') {
            return;
        }


        const modal =
            document.getElementById('cancelLeaveModal');


        if (
            modal &&
            !modal.classList.contains('hidden')
        ) {

            closeCancelLeaveModal();

        }

    });


    /**
     * Prevent double submission.
     */
    document.addEventListener('submit', function (event) {

        if (
            event.target.id !== 'cancelLeaveForm'
        ) {
            return;
        }


        const submitButton =
            document.getElementById('cancelLeaveSubmitButton');


        if (!submitButton) {
            return;
        }


        submitButton.disabled =
            true;

        submitButton.textContent =
            'Cancelling...';

    });

</script>

@endpush
