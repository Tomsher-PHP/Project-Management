@extends('layouts.master')

@section('page-content')

    <div>
        {{-- Warning --}}
        @if (session('warning'))
            <div
                class="mb-5 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-700">
                <strong>Leave Balance Notice:</strong>
                {{ session('warning') }}

                <div class="mt-1 text-xs">
                    The request has still been submitted and can be reviewed by the approver.
                </div>
            </div>
        @endif

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-bgray-900 dark:text-white">
                    Leave Request Details
                </h2>

                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                    View complete details of this leave request.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- Edit --}}
                @if ($leaveRequest->status === 'pending')
                    @can('leave_requests.edit')
                        @if ($leaveRequest->user_id === auth()->id())
                            <a href="{{ route('leave-requests.edit', $leaveRequest->id) }}"
                                class="rounded-lg border border-bgray-200 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white dark:hover:bg-darkblack-500">
                                Edit
                            </a>
                        @endif
                    @endcan
                @endif

                {{-- Back --}}
                {{-- <a href="{{ route('leave-requests.index') }}"
                    class="rounded-lg border border-bgray-200 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white dark:hover:bg-darkblack-500">
                    Back
                </a> --}}

            </div>
        </div>

        {{-- Status --}}
        @php
            $statusClasses = [
                'pending' => 'bg-yellow-100 text-yellow-700',
                'approved' => 'bg-green-100 text-green-700 ',
                'rejected' => 'bg-red-100 text-red-700',
                'cancelled' => 'bg-gray-100 text-gray-700',
            ];
        @endphp

        <div class="mb-6 rounded-xl bg-white p-5 shadow-sm dark:bg-darkblack-600">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-medium uppercase text-bgray-500">
                        Request Status
                    </p>

                    <span
                        class="mt-2 inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $statusClasses[$leaveRequest->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($leaveRequest->status) }}
                    </span>
                </div>

                @if ($leaveRequest->created_at)
                    <div class="text-left sm:text-right">
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Submitted On
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->created_at->format('d M Y h:i A') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Main Information --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Request Information --}}
            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600">

                <h3 class="mb-5 text-lg font-semibold text-bgray-900 dark:text-white">
                    Request Information
                </h3>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- Employee --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Employee
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->user->name ?? '-' }}
                        </p>
                    </div>


                    {{-- Leave Type --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Leave Type
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->leaveType->name ?? '-' }}
                        </p>
                    </div>


                    {{-- Day Type --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Day Type
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->type === 'half_day' ? 'Half Day' : 'Full Day' }}
                        </p>
                    </div>


                    {{-- Half Day Type --}}
                    @if ($leaveRequest->type === 'half_day')
                        <div>
                            <p class="text-xs font-medium uppercase text-bgray-500">
                                Half Day Type
                            </p>

                            <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                                @if ($leaveRequest->half_day_type === 'first_half')
                                    First Half
                                @elseif ($leaveRequest->half_day_type === 'second_half')
                                    Second Half
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    @endif


                    {{-- Requested From --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Requested From
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->requested_from_date
                                ? \Carbon\Carbon::parse($leaveRequest->requested_from_date)->format('d M Y')
                                : '-' }}
                        </p>
                    </div>


                    {{-- Requested To --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Requested To
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->requested_to_date
                                ? \Carbon\Carbon::parse($leaveRequest->requested_to_date)->format('d M Y')
                                : '-' }}
                        </p>
                    </div>


                    {{-- Requested Duration --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Requested Duration
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ number_format($leaveRequest->duration, 2) }} day(s)
                        </p>
                    </div>


                    {{-- Submitted Date --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Submitted Date
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->created_at ? $leaveRequest->created_at->format('d M Y h:i A') : '-' }}
                        </p>
                    </div>

                </div>

            </div>

            {{-- Approval Information --}}
            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600">

                <h3 class="mb-5 text-lg font-semibold text-bgray-900 dark:text-white">
                    Approval Information
                </h3>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- Approved From --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Approved From
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->approved_from_date
                                ? \Carbon\Carbon::parse($leaveRequest->approved_from_date)->format('d M Y')
                                : '-' }}
                        </p>
                    </div>


                    {{-- Approved To --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Approved To
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->approved_to_date
                                ? \Carbon\Carbon::parse($leaveRequest->approved_to_date)->format('d M Y')
                                : '-' }}
                        </p>
                    </div>


                    {{-- Approved Days --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Approved Duration
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->approved_duration !== null ? number_format($leaveRequest->approved_duration, 2) . ' day(s)' : '-' }}
                        </p>
                    </div>


                    {{-- Approved By --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Approved By
                        </p>

                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->approvedBy->name ?? '-' }}
                        </p>
                    </div>


                    {{-- Approved At --}}
                    <div>
                        <p class="text-xs font-medium uppercase text-bgray-500">
                            Approved Date
                        </p>

                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                            {{ $leaveRequest->approved_at ? $leaveRequest->approved_at->format('d M Y h:i A') : '-' }}
                        </p>
                    </div>

                    {{-- Approver Comments --}}
                    <div>
                        <label for="approver_comment">
                            Approver Comments
                        </label>
                        <p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                            {{ $leaveRequest->approver_comment ?? '-' }}
                        </p>

                    </div>

                </div>

                @if (!$leaveRequest->approved_from_date && $leaveRequest->status === 'pending')
                    <div
                        class="mt-5 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-700">
                        This leave request is awaiting approval.
                    </div>
                @endif

            </div>

            {{-- Reason --}}
            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600 lg:col-span-2">

                <h3 class="mb-4 text-lg font-semibold text-bgray-900 dark:text-white">
                    Reason
                </h3>

                <div class="rounded-lg bg-bgray-50 p-4 dark:bg-darkblack-500">
                    <p class="whitespace-pre-line text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                        {{ $leaveRequest->reason ?: 'No reason provided.' }}
                    </p>
                </div>

            </div>

            {{-- Attachment --}}
            @if ($leaveRequest->attachment)
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600 lg:col-span-2">

                    <h3 class="mb-4 text-lg font-semibold text-bgray-900 dark:text-white">
                        Attachment
                    </h3>

                    <a href="{{ asset('storage/' . $leaveRequest->attachment) }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center rounded-lg bg-bgray-100 px-4 py-2.5 text-sm font-medium text-bgray-700 hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-white dark:hover:bg-darkblack-400">

                        View Attachment

                    </a>

                </div>
            @endif

            {{-- Approver Comments --}}
            @if (isset($leaveRequest->approver_comment) && $leaveRequest->approver_comment)
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600 lg:col-span-2">

                    <h3 class="mb-4 text-lg font-semibold text-bgray-900 dark:text-white">
                        Approver Comments
                    </h3>

                    <div class="rounded-lg bg-bgray-50 p-4 dark:bg-darkblack-500">

                        <p class="whitespace-pre-line text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                            {{ $leaveRequest->approver_comment }}
                        </p>

                    </div>

                </div>
            @endif

            {{-- Rejection Information --}}
            @if ($leaveRequest->status === 'rejected')
                <div
                    class="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-900/20 lg:col-span-2">

                    <h3 class="mb-5 text-lg font-semibold text-red-700 dark:text-red-300">
                        Rejection Information
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                        {{-- Rejected By --}}
                        <div>
                            <p class="text-xs uppercase text-red-500">
                                Rejected By
                            </p>

                            <p class="mt-1 text-sm font-medium text-red-700 dark:text-red-300">
                                {{ $leaveRequest->rejectedBy->name ?? '-' }}
                            </p>
                        </div>

                        {{-- Rejected At --}}
                        <div>
                            <p class="text-xs uppercase text-red-500">
                                Rejected Date
                            </p>

                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                {{ $leaveRequest->rejected_at ? $leaveRequest->rejected_at->format('d M Y h:i A') : '-' }}
                            </p>
                        </div>

                        {{-- Rejection Reason --}}
                        <div class="md:col-span-3">

                            <p class="text-xs uppercase text-red-500">
                                Rejection Reason
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-red-700 dark:text-red-300">
                                {{ $leaveRequest->approver_comment ?: '-' }}
                            </p>

                        </div>

                    </div>

                </div>
            @endif

            {{-- Cancellation Information --}}
            @if ($leaveRequest->status === 'cancelled')
                <div
                    class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-darkblack-400 dark:bg-darkblack-500 lg:col-span-2">

                    <h3 class="mb-5 text-lg font-semibold text-bgray-900 dark:text-white">
                        Cancellation Information
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                        {{-- Cancelled By --}}
                        <div>
                            <p class="text-xs uppercase text-bgray-500">
                                Cancelled By
                            </p>

                            <p class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                                {{ $leaveRequest->cancelledBy->name ?? '-' }}
                            </p>
                        </div>


                        {{-- Cancelled At --}}
                        <div>
                            <p class="text-xs uppercase text-bgray-500">
                                Cancelled Date
                            </p>

                            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $leaveRequest->cancelled_at ? $leaveRequest->cancelled_at->format('d M Y h:i A') : '-' }}
                            </p>
                        </div>

                        {{-- Cancellation Reason --}}
                        <div class="md:col-span-3">
                            <p class="text-xs uppercase text-bgray-500">
                                Cancellation Reason
                            </p>

                            <p class="mt-1 whitespace-pre-line text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $leaveRequest->cancellation_reason ?: '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
