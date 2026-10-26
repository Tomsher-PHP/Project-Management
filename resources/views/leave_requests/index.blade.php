@extends('layouts.master')
@section('page-content')

    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            @if (!$isPendingPage)
                @can('leave_request.create')
                    <x-button.create-button :href="route('leave-requests.create')" label="Apply Leave" />
                @endcan
            @endif
            <x-filters.button />
            <x-filters.list-search />
        </div>
    </div>
    @php
        /*
         * Employee filter
         */
        $employeeFilterOptions = collect($employees ?? [])->map(function ($employee) {
            return (object) [
                'id' => (string) $employee->id,
                'name' => $employee->name,
            ];
        });

        /*
         * Leave Type filter
         */
        $leaveTypeFilterOptions = collect($leaveTypes ?? [])->map(function ($leaveType) {
            return (object) [
                'id' => (string) $leaveType->id,
                'name' => $leaveType->name,
            ];
        });

        /*
         * Added By filter
         */
        $addedByFilterOptions = collect($users ?? [])->map(function ($user) {
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

    <!-- Filter drawer -->
    <x-filters.drawer>

        <x-filters.multi-select name="user_id" label="Employee" :options="$employeeFilterOptions" />

        <x-filters.multi-select name="leave_type_id" label="Leave Type" :options="$leaveTypeFilterOptions" />

        <x-filters.multi-select name="added_by" label="Added By" :options="$addedByFilterOptions" />

        <x-filters.select name="type" label="Day Type" :options="$dayTypeFilterOptions" />

        <x-filters.multi-select name="status" label="Status" :options="$statusFilterOptions" />

        <x-filters.input-search name="requested_from_date" label="From Date" />

        <x-filters.input-search name="requested_to_date" label="To Date" />

    </x-filters.drawer>
    <!-- Filter drawer end -->

    <!-- Validation errors -->
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Success message -->
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }} </div>
    @endif

    <!-- Warning message -->
    @if (session('warning'))
        <div class="mb-5 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
            {{ session('warning') }} </div>
    @endif

    <!-- Leave Requests -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <!-- List table -->
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full min-w-[1200px]">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <!-- # -->
                                    <th class="px-6 py-5 text-left xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                            #
                                        </span>
                                    </th>

                                    <!-- Employee -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="user.name" label="Employee" />
                                        </div>
                                    </th>

                                    <!-- Leave Type -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="leaveType.name" label="Leave Type" />
                                        </div>
                                    </th>

                                    <!-- Added By -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="addedBy.name" label="Added By" />
                                        </div>
                                    </th>

                                    <!-- Day Type -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <div class="flex w-full items-center space-x-2.5">
                                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                    Day Type
                                                </span>
                                            </div>
                                        </div>
                                    </th>

                                    <!-- Half Day -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                Half Day
                                            </span>
                                        </div>
                                    </th>

                                    <!-- From Date -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="requested_from_date" label="From Date" />
                                        </div>
                                    </th>

                                    <!-- To Date -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="requested_to_date" label="To Date" />
                                        </div>
                                    </th>

                                    <!-- Days -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="duration" label="Days" />
                                        </div>
                                    </th>

                                    <!-- Status -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="status" label="Status" />
                                        </div>
                                    </th>

                                    <!-- Actions -->
                                    <th class="px-6 py-5 xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                Actions
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startNumber = ($leaveRequests->currentPage() - 1) * $leaveRequests->perPage();
                                @endphp

                                @forelse ($leaveRequests as $leaveRequest)
                                    @php
                                        /*
                                         * ============================================
                                         * CURRENT USER
                                         * ============================================
                                         */

                                        $authUserId = (int) auth()->id();

                                        /*
                                         * Super Admin
                                         */
                                        $isSuperAdmin = (bool) auth()->user()?->is_super_admin;

                                        /*
                                         * ============================================
                                         * REQUESTER
                                         * ============================================
                                         */

                                        $isOwnRequest = (int) $leaveRequest->user_id === $authUserId;

                                        $assignedTo = $leaveRequest->assigned_to ?? [];

                                        if (is_string($assignedTo)) {
                                            $assignedTo = json_decode($assignedTo, true) ?? [];
                                        }

                                        if (!is_array($assignedTo)) {
                                            $assignedTo = [];
                                        }

                                        /*
                                         * Extract assigned user IDs.
                                         */
                                        $assignedUserIds = collect($assignedTo)
                                            ->map(function ($assignedUser) {
                                                if (is_array($assignedUser)) {
                                                    return $assignedUser['id'] ?? ($assignedUser['user_id'] ?? null);
                                                }

                                                return $assignedUser;
                                            })
                                            ->filter(fn($id) => is_numeric($id))
                                            ->map(fn($id) => (int) $id)
                                            ->values()
                                            ->all();

                                        /*
                                         * Is current user assigned to this leave?
                                         */
                                        $isAssignedToMe = in_array($authUserId, $assignedUserIds, true);

                                        /*
                                         * ============================================
                                         * MANAGEMENT PERMISSION
                                         * ============================================
                                         *
                                         * Super Admin:
                                         *     Can manage every leave.
                                         *
                                         * Normal user:
                                         *     Can manage own leave.
                                         *
                                         * Assigned user:
                                         *     Can manage the leave assigned to them.
                                         */

                                        $canManageLeave = $isSuperAdmin || $isOwnRequest || $isAssignedToMe;

                                        /*
                                         * ============================================
                                         * APPROVAL PERMISSION
                                         * ============================================
                                         *
                                         * Super Admin: Can approve/reject everything.
                                         *
                                         * Assigned user: Can approve/reject another employee's leave.
 *
 * Requester: Cannot approve/reject their own leave.
 */

$canApproveReject = $isSuperAdmin || ($isAssignedToMe && !$isOwnRequest);

/*
 * ============================================
 * STATUS
 * ============================================
 */

$status = strtolower($leaveRequest->status ?? '');

$statusClasses = match ($status) {
    'approved' => 'text-green-700',
    'rejected' => 'text-red-700',
    'cancelled' => 'text-gray-700',
    default => 'text-red-700',
};

/*
 * ============================================
 * DAY TYPE
 * ============================================
 */

$dayType = $leaveRequest->type === 'half_day' ? 'Half Day' : 'Full Day';

/*
 * ============================================
 * HALF DAY TYPE
 * ============================================
 */

$halfDayType = $leaveRequest->type === 'half_day' ? ($leaveRequest->half_day_type ? ucfirst(str_replace('_', ' ', $leaveRequest->half_day_type)) : '-') : '-';

/*
 * ============================================
 * DATES
 * ============================================
 */

$fromDate = $leaveRequest->requested_from_date ? \Carbon\Carbon::parse($leaveRequest->requested_from_date)->format('d M Y') : '-';

$toDate = $leaveRequest->requested_to_date ? \Carbon\Carbon::parse($leaveRequest->requested_to_date)->format('d M Y') : '-';

/*
 * ============================================
 * DURATION
 * ============================================
 */

$duration = $leaveRequest->duration ?? '-';

/*
 * ============================================
 * DISPLAY STATUS
 * ============================================
 */

$displayStatus = $status ? ucfirst($status) : '-';
                                    @endphp

                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                        <!-- # -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                                                {{ $startNumber + $loop->iteration }}
                                            </span>
                                        </td>

                                        <!-- Employee -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $leaveRequest->user?->name ?? '--' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Leave Type -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $leaveRequest->leaveType?->name ?? '--' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Added By -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $leaveRequest->addedBy?->name ?? '--' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Day Type -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $dayType }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Half Day -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $halfDayType }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- From Date -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $fromDate }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- To Date -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $toDate }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Days -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $duration }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="inline-flex rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] {{ $statusClasses }}">
                                                    {{ $displayStatus }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                {{-- PENDING LEAVE REQUEST --}}
                                                @if ($status === 'pending')
                                                    {{-- View --}}
                                                    @if ($canManageLeave)
                                                        @can('leave_request.view')
                                                            <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                                                View
                                                            </a>
                                                        @endcan
                                                    @endif

                                                    {{-- Edit Both employee and assigned person can edit while the leave is pending. --}}
                                                    @if ($isOwnRequest || $isAssignedToMe)
                                                        @can('leave_request.edit')
                                                            <a href="{{ route('leave-requests.edit', $leaveRequest) }}" class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                                                Edit
                                                            </a>
                                                        @endcan
                                                    @endif

                                                    {{-- Approve / Reject Only assigned person or super admin --}}
                                                    @if ($canApproveReject)
                                                        @can('leave_request.edit')
                                                            <a href="{{ route('leave-requests.edit', [
                                                                'leaveRequest' => $leaveRequest->id,
                                                                'approved_mode' => 1,
                                                                'action' => 'approve',
                                                            ]) }}" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                                                                Approve
                                                            </a>

                                                            <a href="{{ route('leave-requests.edit', [
                                                                'leaveRequest' => $leaveRequest->id,
                                                                'approved_mode' => 1,
                                                                'action' => 'reject',
                                                            ]) }}" class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400">
                                                                Reject
                                                            </a>
                                                        @endcan
                                                    @endif

                                                    {{-- Cancel Both employee and assigned person can cancel while the leave is pending. --}}
                                                    @if ($isOwnRequest || $isAssignedToMe)
                                                        @can('leave_request.cancel')
                                                            <x-cancel-button type="button" onclick="openCancelModalFromButton(this)" data-cancel-id="{{ $leaveRequest->id }}" data-cancel-employee="{{ $leaveRequest->user?->name ?? 'Employee' }}" data-cancel-leave-type="{{ $leaveRequest->leaveType?->name ?? 'Leave' }}" data-cancel-day-type="{{ $dayType }}" data-cancel-from-date="{{ $leaveRequest->requested_from_date }}" data-cancel-to-date="{{ $leaveRequest->requested_to_date }}" data-cancel-duration="{{ $leaveRequest->duration ?? '-' }}" data-cancel-status="{{ $leaveRequest->status ?? '-' }}"  />
                                                        @endcan
                                                    @endif


                                                    {{-- APPROVED LEAVE REQUEST --}}
                                                @elseif ($status === 'approved')
                                                    {{-- View Employee and assigned person can view. --}}
                                                    @if ($canManageLeave)
                                                        @can('leave_request.view')
                                                            <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                                                View
                                                            </a>
                                                        @endcan
                                                    @endif

                                                    {{-- Request Leave Change
                                                        Only the original employee can request a change.
                                                        Hide the button if a pending change request already exists. --}}
                                                    @if ($isOwnRequest)
                                                        @can('leave_change_request.create')
                                                            @php
                                                                $hasPendingLeaveChange = $leaveRequest->changeRequests()->where('status', 'pending')->exists();
                                                            @endphp

                                                            @unless ($hasPendingLeaveChange)
                                                                <a href="{{ route('leave-change-requests.create', $leaveRequest) }}" class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                                                    Request Leave Change
                                                                </a>
                                                            @endunless
                                                        @endcan
                                                    @endif

                                                    {{-- APPROVED LEAVE: Request cancellation --}}
                                                    @if ($isOwnRequest)
                                                        @can('leave_cancellation_request.create')
                                                            @php
                                                                // $hasPendingCancellation = LeaveCancellationRequest::where('leave_request_id', $leaveRequest->id)->where('status', 'pending')->exists();
                                                                $hasPendingCancellation = $leaveRequest->cancellationRequests()->where('status', 'pending')->exists();
                                                            @endphp

                                                            @unless ($hasPendingCancellation)
                                                                <button type="button" onclick="openLeaveCancellationRequestModal(this)" data-cancel-id="{{ $leaveRequest->id }}" data-cancel-employee="{{ $leaveRequest->user?->name ?? 'Employee' }}" data-cancel-leave-type="{{ $leaveRequest->leaveType?->name ?? 'Leave' }}" data-cancel-from-date="{{ $leaveRequest->approved_from_date ?? $leaveRequest->requested_from_date }}" data-cancel-to-date="{{ $leaveRequest->approved_to_date ?? $leaveRequest->requested_to_date }}" data-cancel-duration="{{ $leaveRequest->duration ?? '-' }}" data-cancel-status="{{ $leaveRequest->status ?? '-' }}" class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                                                    <i class="fas fa-calendar-times"></i>
                                                                    Request Leave Cancellation
                                                                </button>
                                                            @endunless
                                                        @endcan
                                                    @endif



                                                    {{-- REJECTED / CANCELLED --}}
                                                @else
                                                    {{-- View only --}}
                                                    @if ($canManageLeave)
                                                        @can('leave_request.view')
                                                            <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                                                View
                                                            </a>
                                                        @endcan
                                                    @endif
                                                @endif

                                            </div>
                                        </td>
                                    </tr>
                                @empty

                                    <x-table-no-data col-span="11" message="No leave requests found." />
                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    <x-pagination :paginator="$leaveRequests" :per-page="$perPage" />

                </div>

            </div>

        </section>


    </div>

    <!-- Page ends -->

    <!-- Cancel Leave Modal -->
    <div id="cancelLeaveModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="cancelLeaveModalLabel" aria-hidden="true">
        <div class="flex min-h-screen items-center justify-center px-4">

            <!-- Overlay -->
            <div class="fixed inset-0 bg-black/50" onclick="closeCancelModal()"></div>

            <!-- Modal -->
            <form id="cancelLeaveForm" method="POST" class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-darkblack-600">
                @csrf

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">

                    <h3 id="cancelLeaveModalLabel" class="text-lg font-semibold text-bgray-900 dark:text-white">
                        Cancel Leave
                    </h3>

                    <button type="button" onclick="closeCancelModal()" class="text-bgray-500 transition hover:text-bgray-700 dark:text-bgray-400 dark:hover:text-white" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                </div>

                <!-- Body -->
                <div class="px-6 py-5">
                    <div class="space-y-4">
                        <!-- Employee -->
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500">
                                Employee
                            </p>

                            <p id="cancelEmployee" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                        </div>

                        <!-- Leave Type -->
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500">
                                Leave Type
                            </p>

                            <p id="cancelLeaveType" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                        </div>

                        <!-- Day Type / Half Day -->
                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Day Type
                                </p>

                                <p id="cancelDayType" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Half Day
                                </p>

                                <p id="cancelHalfDay" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                            </div>

                        </div>

                        <!-- From Date / To Date -->
                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    From Date
                                </p>

                                <p id="cancelFromDate" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    To Date
                                </p>

                                <p id="cancelToDate" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                            </div>

                        </div>

                        <!-- Duration / Status -->
                        <div class="grid grid-cols-2 gap-4">

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Duration
                                </p>

                                <p id="cancelDuration" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                            </div>

                            <div>
                                <p class="text-xs font-medium uppercase text-bgray-500">
                                    Status
                                </p>

                                <p id="cancelStatus" class="mt-1 text-sm font-medium text-bgray-900 dark:text-white"></p>
                            </div>

                        </div>

                    </div>

                    <!-- Cancellation Reason -->
                    <div class="mt-6">
                        <label for="cancellation_reason" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                            Cancellation Reason
                            <span class="text-danger-400">*</span>
                        </label>

                        <textarea name="cancellation_reason" id="cancellation_reason" rows="4" required maxlength="2000" placeholder="Enter the reason for cancelling this leave request..." class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder:text-bgray-500"></textarea>

                        <p class="mt-1 text-xs text-bgray-500">
                            Maximum 2000 characters.
                        </p>
                    </div>

                    <!-- Confirmation -->
                    <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-800">
                        Are you sure you want to cancel this leave request?
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">

                    <button type="button" onclick="closeCancelModal()" class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500">
                        Close
                    </button>

                    <button type="submit" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-medium text-white transition hover:bg-error-600">
                        Cancel Leave
                    </button>

                </div>

            </form>
        </div>
    </div>

    {{-- Leave cancellation modal --}}
    <div id="leaveCancellationRequestModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">

        <div class="fixed inset-0 bg-black/50" onclick="closeLeaveCancellationRequestModal()"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">

                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Request Leave Cancellation
                    </h2>

                    <button type="button" onclick="closeLeaveCancellationRequestModal()" class="text-gray-500 hover:text-gray-700">
                        &times;
                    </button>
                </div>

                <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                    This request must be approved before your approved leave is cancelled.
                </div>

                <div class="mb-4 space-y-2 text-sm">
                    <p>
                        <strong>Employee:</strong>
                        <span id="cancellationRequestEmployee">--</span>
                    </p>
                    <p>
                        <strong>Leave type:</strong>
                        <span id="cancellationRequestLeaveType">--</span>
                    </p>
                    <p>
                        <strong>From:</strong>
                        <span id="cancellationRequestFromDate">--</span>
                    </p>
                    <p>
                        <strong>To:</strong>
                        <span id="cancellationRequestToDate">--</span>
                    </p>
                </div>

                <form id="leaveCancellationRequestForm" method="POST" action="">
                    @csrf

                    <label for="cancellation_request_reason" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Reason for cancellation
                    </label>

                    <textarea id="cancellation_request_reason" name="reason" rows="4" required maxlength="2000" class="w-full rounded-lg border border-gray-300 p-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Please explain why you need to cancel this leave."></textarea>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" onclick="closeLeaveCancellationRequestModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
                            Close
                        </button>

                        <button type="submit" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <!-- Cancel Modal JavaScript -->

    <script>
        function openCancelModalFromButton(button) {

            const id = button.dataset.cancelId;

            document.getElementById('cancelEmployee').textContent =
                button.dataset.cancelEmployee || '--';

            document.getElementById('cancelLeaveType').textContent =
                button.dataset.cancelLeaveType || '--';

            document.getElementById('cancelDayType').textContent =
                button.dataset.cancelDayType || '--';

            document.getElementById('cancelHalfDay').textContent =
                button.dataset.cancelHalfDay || '--';

            document.getElementById('cancelFromDate').textContent =
                button.dataset.cancelFromDate || '--';

            document.getElementById('cancelToDate').textContent =
                button.dataset.cancelToDate || '--';

            document.getElementById('cancelDuration').textContent =
                button.dataset.cancelDuration || '--';

            document.getElementById('cancelStatus').textContent =
                button.dataset.cancelStatus || '--';

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


        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {
                closeCancelModal();
            }

        });


        // Leave cancel request script

        function openLeaveCancellationRequestModal(button) {
            const id = button.dataset.cancelId;

            document.getElementById('cancellationRequestEmployee').textContent =
                button.dataset.cancelEmployee || '--';

            document.getElementById('cancellationRequestLeaveType').textContent =
                button.dataset.cancelLeaveType || '--';

            document.getElementById('cancellationRequestFromDate').textContent =
                button.dataset.cancelFromDate || '--';

            document.getElementById('cancellationRequestToDate').textContent =
                button.dataset.cancelToDate || '--';

            document.getElementById('leaveCancellationRequestForm').action =
                "{{ url('leave-cancellation-requests') }}/" + id;

            document.getElementById('leaveCancellationRequestModal')
                .classList.remove('hidden');

            document.getElementById('leaveCancellationRequestModal')
                .setAttribute('aria-hidden', 'false');

            document.body.classList.add('overflow-hidden');
        }

        function closeLeaveCancellationRequestModal() {
            document.getElementById('leaveCancellationRequestModal')
                .classList.add('hidden');

            document.getElementById('leaveCancellationRequestModal')
                .setAttribute('aria-hidden', 'true');

            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeLeaveCancellationRequestModal();
            }
        });
    </script>

@endsection
