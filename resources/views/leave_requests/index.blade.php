@extends('layouts.master')

@section('page-content')

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">

        {{-- Page Header --}}
        <div class="mb-6 flex flex-wrap items-center gap-3">

            @if (!$isPendingPage)
                @can('leave_request.create')
                    <x-button.create-button
                        type="button"
                        onclick="window.location.href='{{ route('leave-requests.create') }}'"
                        title="Apply for leave"
                        label="Apply Leave"
                    />
                @endcan
            @endif

            {{-- Filters --}}
            <x-filters.button />

            {{-- Search --}}
            <x-filters.list-search />

        </div>

        {{-- Filter Options --}}
        @php

            /*
             * Employee filter
             */
            $employeeFilterOptions = collect($employees ?? [])
                ->map(function ($employee) {
                    return (object) [
                        'id' => (string) $employee->id,
                        'name' => $employee->name,
                    ];
                });

            /*
             * Leave Type filter
             */
            $leaveTypeFilterOptions = collect($leaveTypes ?? [])
                ->map(function ($leaveType) {
                    return (object) [
                        'id' => (string) $leaveType->id,
                        'name' => $leaveType->name,
                    ];
                });

            /*
             * Added By filter
             */
            $addedByFilterOptions = collect($users ?? [])
                ->map(function ($user) {
                    return (object) [
                        'id' => (string) $user->id,
                        'name' => $user->name,
                    ];
                });

            /*
             * Day Type filter
             */
            $dayTypeFilterOptions = [
                'full_day' => 'Full Day',
                'half_day' => 'Half Day',
            ];

            /*
             * Status filter
             */
            $statusFilterOptions = collect([
                (object) [
                    'id' => 'pending',
                    'name' => 'Pending',
                ],
                (object) [
                    'id' => 'approved',
                    'name' => 'Approved',
                ],
                (object) [
                    'id' => 'rejected',
                    'name' => 'Rejected',
                ],
                (object) [
                    'id' => 'cancelled',
                    'name' => 'Cancelled',
                ],
            ]);

        @endphp

        {{-- Filter Drawer --}}
        <x-filters.drawer>

            {{-- Employee --}}
            <x-filters.multi-select
                name="user_id"
                label="Employee"
                :options="$employeeFilterOptions"
            />

            {{-- Leave Type --}}
            <x-filters.multi-select
                name="leave_type_id"
                label="Leave Type"
                :options="$leaveTypeFilterOptions"
            />

            {{-- Added By --}}
            <x-filters.multi-select
                name="added_by"
                label="Added By"
                :options="$addedByFilterOptions"
            />

            {{-- Day Type --}}
            <x-filters.select
                name="type"
                label="Day Type"
                :options="$dayTypeFilterOptions"
            />

            {{-- Status --}}
            <x-filters.multi-select
                name="status"
                label="Status"
                :options="$statusFilterOptions"
            />

            {{-- From Date --}}
            <x-filters.input-search
                name="requested_from_date"
                label="From Date"
            />

            {{-- To Date --}}
            <x-filters.input-search
                name="requested_to_date"
                label="To Date"
            />

        </x-filters.drawer>


        {{-- Errors --}}
        @if ($errors->any())
            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">

                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif


        {{-- Success Message --}}
        @if (session('success'))
            <div
                class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">

                {{ session('success') }}

            </div>
        @endif


        {{-- Warning Message --}}
        @if (session('warning'))
            <div
                class="mb-5 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">

                {{ session('warning') }}

            </div>
        @endif


        {{-- Leave Requests Table --}}
        <div
            class="overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1200px]">

                    <thead>
                        <tr class="border-b border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500">

                            {{-- Employee --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="user.name"
                                    label="Employee"
                                />
                            </th>

                            {{-- Leave Type --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="leaveType.name"
                                    label="Leave Type"
                                />
                            </th>

                            {{-- Added By --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="addedBy.name"
                                    label="Added By"
                                />
                            </th>

                            {{-- Day Type --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                Day Type
                            </th>

                            {{-- Half Day --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                Half Day
                            </th>

                            {{-- From Date --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="requested_from_date"
                                    label="From Date"
                                />
                            </th>

                            {{-- To Date --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="requested_to_date"
                                    label="To Date"
                                />
                            </th>

                            {{-- Days --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="duration"
                                    label="Days"
                                />
                            </th>

                            {{-- Status --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                <x-sorting.sortable-column
                                    column="status"
                                    label="Status"
                                />
                            </th>

                            {{-- Action --}}
                            <th class="px-4 py-3 text-left text-sm font-medium text-bgray-600 dark:text-bgray-300">
                                Action
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($leaveRequests as $leaveRequest)

                            <tr
                                class="border-b border-bgray-200 last:border-0 hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500">

                                {{-- Employee --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->user?->name ?? '-' }}
                                </td>

                                {{-- Leave Type --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->leaveType?->name ?? '-' }}
                                </td>

                                {{-- Added By --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->addedBy?->name ?? '-' }}
                                </td>

                                {{-- Day Type --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">

                                    @if ($leaveRequest->type === 'half_day')
                                        Half Day
                                    @else
                                        Full Day
                                    @endif

                                </td>

                                {{-- Half Day --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">

                                    @if ($leaveRequest->type === 'half_day')
                                        {{ ucfirst(str_replace('_', ' ', $leaveRequest->half_day_type ?? '-')) }}
                                    @else
                                        -
                                    @endif

                                </td>

                                {{-- From Date --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->requested_from_date
                                        ? \Carbon\Carbon::parse($leaveRequest->requested_from_date)->format('d M Y')
                                        : '-' }}
                                </td>

                                {{-- To Date --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->requested_to_date
                                        ? \Carbon\Carbon::parse($leaveRequest->requested_to_date)->format('d M Y')
                                        : '-' }}
                                </td>

                                {{-- Duration --}}
                                <td class="px-4 py-4 text-sm text-bgray-900 dark:text-white">
                                    {{ $leaveRequest->duration ?? '-' }}
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-4 text-sm">

                                    @php
                                        $status = strtolower($leaveRequest->status ?? '');

                                        $statusClasses = match ($status) {
                                            'approved' =>
                                                'bg-green-100 text-green-700',
                                            'rejected' =>
                                                'bg-red-100 text-red-700',
                                            'cancelled' =>
                                                'bg-gray-100 text-gray-700',
                                            default =>
                                                'bg-yellow-100 text-yellow-700',
                                        };
                                    @endphp

                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                        {{ ucfirst($status ?: '-') }}
                                    </span>

                                </td>

                                {{-- Action --}}
                                <td class="px-4 py-4">

                                    <div class="flex items-center gap-2">

                                        @if ($isPendingPage)

                                            @if ($leaveRequest->user_id === auth()->id())

                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">
                                                    Waiting for approval
                                                </span>

                                            @else

                                                {{-- Approve --}}
                                                @can('leave_request.edit')
                                                    <a
                                                        href="{{ route('leave-requests.edit', [
                                                            'leaveRequest' => $leaveRequest->id,
                                                            'approved_mode' => 1,
                                                            'action' => 'approve',
                                                        ]) }}"
                                                        class="inline-flex items-center rounded-lg bg-green-100 px-3 py-2 text-xs font-medium text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50">
                                                        Approve
                                                    </a>
                                                @endcan

                                                {{-- Reject --}}
                                                @can('leave_request.edit')
                                                    <a
                                                        href="{{ route('leave-requests.edit', [
                                                            'leaveRequest' => $leaveRequest->id,
                                                            'approved_mode' => 1,
                                                            'action' => 'reject',
                                                        ]) }}"
                                                        class="inline-flex items-center rounded-lg bg-red-100 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50">
                                                        Reject
                                                    </a>
                                                @endcan

                                            @endif

                                            {{-- View --}}
                                            @can('leave_request.view')
                                                <x-view-button
                                                    :href="route('leave-requests.show', $leaveRequest->id)"
                                                />
                                            @endcan

                                        @else

                                            {{-- View --}}
                                            @can('leave_request.view')
                                                <x-view-button
                                                    :href="route('leave-requests.show', $leaveRequest->id)"
                                                />
                                            @endcan

                                            {{-- Edit --}}
                                            @if ($leaveRequest->user_id === auth()->id())
                                                @can('leave_request.edit')

                                                    <x-edit-button
                                                        :href="route('leave-requests.edit', $leaveRequest->id)"
                                                    />

                                                @endcan
                                            @endif

                                            {{-- Cancel --}}
                                            @if (
                                                $leaveRequest->user_id === auth()->id() &&
                                                in_array(strtolower($leaveRequest->status), ['pending', 'approved'])
                                            )

                                                @can('leave_request.cancel')

                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-lg bg-red-100 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
                                                        onclick="openCancelModal(
                                                            '{{ $leaveRequest->id }}',
                                                            '{{ addslashes($leaveRequest->user?->name ?? '-') }}',
                                                            '{{ addslashes($leaveRequest->leaveType?->name ?? '-') }}',
                                                            '{{ $leaveRequest->type === 'half_day' ? 'Half Day' : 'Full Day' }}',
                                                            '{{ addslashes($leaveRequest->half_day_type ?? '-') }}',
                                                            '{{ $leaveRequest->requested_from_date ? \Carbon\Carbon::parse($leaveRequest->requested_from_date)->format('d M Y') : '-' }}',
                                                            '{{ $leaveRequest->requested_to_date ? \Carbon\Carbon::parse($leaveRequest->requested_to_date)->format('d M Y') : '-' }}',
                                                            '{{ $leaveRequest->duration ?? '-' }}',
                                                            '{{ ucfirst($leaveRequest->status ?? '-') }}'
                                                        )"
                                                    >
                                                        Cancel
                                                    </button>

                                                @endcan

                                            @endif

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="11"
                                    class="px-4 py-10 text-center text-sm text-bgray-500 dark:text-bgray-400">
                                    No leave requests found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- Pagination --}}
        @if ($leaveRequests->hasPages())

            <div class="mt-5">
                {{ $leaveRequests->links() }}
            </div>

        @endif

    </div>


    {{-- Cancel Leave Modal --}}
    <div
        id="cancelLeaveModal"
        class="fixed inset-0 z-50 hidden overflow-y-auto"
        aria-labelledby="cancelLeaveModalLabel"
        aria-hidden="true">

        <div class="flex min-h-screen items-center justify-center px-4">

            {{-- Overlay --}}
            <div
                class="fixed inset-0 bg-black/50"
                onclick="closeCancelModal()">
            </div>

            {{-- Modal --}}
            <div
                class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-darkblack-600">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">

                    <h3
                        id="cancelLeaveModalLabel"
                        class="text-lg font-semibold text-bgray-900 dark:text-white">
                        Cancel Leave
                    </h3>

                    <button
                        type="button"
                        onclick="closeCancelModal()"
                        class="text-bgray-500 hover:text-bgray-700 dark:text-bgray-400 dark:hover:text-white">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                </div>


                {{-- Body --}}
                <div class="px-6 py-5">

                    <div class="space-y-4">

                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500">
                                Employee
                            </p>
                            <p
                                id="cancelEmployee"
                                class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500">
                                Leave Type
                            </p>
                            <p
                                id="cancelLeaveType"
                                class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Day Type
                                </p>

                                <p
                                    id="cancelDayType"
                                    class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Half Day
                                </p>

                                <p
                                    id="cancelHalfDay"
                                    class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                </p>
                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    From Date
                                </p>

                                <p
                                    id="cancelFromDate"
                                    class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    To Date
                                </p>

                                <p
                                    id="cancelToDate"
                                    class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                </p>
                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Duration
                                </p>

                                <p
                                    id="cancelDuration"
                                    class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Status
                                </p>

                                <p
                                    id="cancelStatus"
                                    class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                </p>
                            </div>

                        </div>

                    </div>


                    {{-- Confirmation --}}
                    <div
                        class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">

                        Are you sure you want to cancel this leave request?

                    </div>

                </div>


                {{-- Footer --}}
                <div
                    class="flex justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">

                    <button
                        type="button"
                        onclick="closeCancelModal()"
                        class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500">
                        Close
                    </button>

                    <form
                        id="cancelLeaveForm"
                        method="POST">

                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Cancel Leave
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>


    {{-- Cancel Modal JavaScript --}}
    <script>

        function openCancelModal(
            id,
            employee,
            leaveType,
            dayType,
            halfDay,
            fromDate,
            toDate,
            duration,
            status
        ) {

            document.getElementById('cancelEmployee').textContent = employee;
            document.getElementById('cancelLeaveType').textContent = leaveType;
            document.getElementById('cancelDayType').textContent = dayType;
            document.getElementById('cancelHalfDay').textContent = halfDay;
            document.getElementById('cancelFromDate').textContent = fromDate;
            document.getElementById('cancelToDate').textContent = toDate;
            document.getElementById('cancelDuration').textContent = duration;
            document.getElementById('cancelStatus').textContent = status;

            document.getElementById('cancelLeaveForm').action =
                "{{ url('leave-requests') }}/" + id + "/cancel";

            document
                .getElementById('cancelLeaveModal')
                .classList
                .remove('hidden');

            document
                .getElementById('cancelLeaveModal')
                .setAttribute('aria-hidden', 'false');

            document.body.classList.add('overflow-hidden');
        }


        function closeCancelModal() {

            document
                .getElementById('cancelLeaveModal')
                .classList
                .add('hidden');

            document
                .getElementById('cancelLeaveModal')
                .setAttribute('aria-hidden', 'true');

            document.body.classList.remove('overflow-hidden');
        }


        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape') {
                closeCancelModal();
            }

        });

    </script>

@endsection
