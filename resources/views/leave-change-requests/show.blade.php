@extends('layouts.master')

@section('page-content')
    <div>
        <div class="mb-6 flex items-center justify-between">
            <a
                href="{{ route('leave-change-requests.index') }}"
                class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm flex items-center">
                
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg> <span>Back</span>
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Status --}}
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase text-gray-500">
                        Status
                    </p>

                    @php
                        $statusClasses = match($leaveChangeRequest->status) {
                            'pending' => 'bg-warning-100 text-warning-700',
                            'approved' => 'bg-success-100 text-success-700',
                            'rejected' => 'bg-error-100 text-error-700',
                            'cancelled' => 'bg-gray-100 text-gray-600',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp

                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $statusClasses }}">
                        {{ ucfirst($leaveChangeRequest->status) }}
                    </span>
                </div>

                <div class="text-right">
                    <p class="text-xs text-gray-500">
                        Requested on
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $leaveChangeRequest->created_at?->format('d M Y, h:i A') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Employee --}}
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-5 text-base font-semibold text-gray-900 dark:text-white">
                Employee
            </h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Employee
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $leaveChangeRequest->leaveRequest?->user?->name ?? 'Employee' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Requested By
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $leaveChangeRequest->requestedBy?->name ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Original Leave Status
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ ucfirst($leaveChangeRequest->leaveRequest?->status ?? '—') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Comparison --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Original --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-5 text-base font-semibold text-gray-900 dark:text-white">
                    Current Approved Leave
                </h2>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            Leave Type
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->oldLeaveType?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            From
                        </dt>

                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->old_from_date?->format('d M Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            To
                        </dt>

                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->old_to_date?->format('d M Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            Duration
                        </dt>

                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->old_duration }}
                            {{ $leaveChangeRequest->old_duration == 1 ? 'day' : 'days' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Requested --}}
            <div class="rounded-xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-800 dark:bg-primary-900/20">
                <h2 class="mb-5 text-base font-semibold text-gray-900 dark:text-white">
                    Requested Change
                </h2>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            Leave Type
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->newLeaveType?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            From
                        </dt>

                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->new_from_date?->format('d M Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            To
                        </dt>

                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->new_to_date?->format('d M Y') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500">
                            Duration
                        </dt>

                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $leaveChangeRequest->new_duration }}
                            {{ $leaveChangeRequest->new_duration == 1 ? 'day' : 'days' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Reason --}}
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
                Reason for Change
            </h2>

            <div class="rounded-lg bg-gray-50 p-4 text-sm leading-6 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                {!! nl2br(e($leaveChangeRequest->reason)) !!}
            </div>
        </div>

        {{-- Rejection Reason --}}
        @if($leaveChangeRequest->status === 'rejected')
            <div class="mb-6 rounded-xl border border-error-200 bg-error-50 p-6 dark:border-error-800 dark:bg-error-900/20">
                <h2 class="mb-3 text-base font-semibold text-error-700">
                    Rejection Reason
                </h2>

                <p class="text-sm leading-6 text-error-700">
                    {{ $leaveChangeRequest->rejection_reason }}
                </p>
            </div>
        @endif

        {{-- Approval information --}}
        @if($leaveChangeRequest->status === 'approved')
            <div class="mb-6 rounded-xl border border-success-200 bg-success-50 p-6 dark:border-success-800 dark:bg-success-900/20">
                <h2 class="mb-3 text-base font-semibold text-success-700">
                    Approval Details
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-success-600">
                            Approved By
                        </p>

                        <p class="mt-1 text-sm font-semibold text-success-800">
                            {{ $leaveChangeRequest->approvedBy?->name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-success-600">
                            Approved At
                        </p>

                        <p class="mt-1 text-sm font-semibold text-success-800">
                            {{ $leaveChangeRequest->approved_at?->format('d M Y, h:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        @if($leaveChangeRequest->status === 'pending')
            <div class="flex flex-wrap items-center justify-end gap-3">
                @can('leave_change_request.cancel')
                    @if(
                        auth()->user()->is_super_admin ||
                        auth()->id() === $leaveChangeRequest->requested_by
                    )
                        <form
                            method="POST"
                            action="{{ route('leave-change-requests.cancel', $leaveChangeRequest) }}"
                            data-confirm-title="Cancel leave change request?"
                            data-confirm-text="This will cancel your pending leave change request."
                            data-confirm-text-button="Yes, cancel"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg bg-error-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-error-400"
                            >
                                Cancel Request
                            </button>
                        </form>
                    @endif
                @endcan

                @can('leave_change_request.reject')
                    <button
                        type="button"
                        onclick="document.getElementById('rejectLeaveChangeModal').classList.remove('hidden')"
                        class="rounded-lg bg-error-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-error-400"
                    >
                        Reject
                    </button>
                @endcan

                @can('leave_change_request.approve')
                    <form
                        method="POST"
                        action="{{ route('leave-change-requests.approve', $leaveChangeRequest) }}"
                        data-confirm-title="Approve leave change request?"
                        data-confirm-text="This will update the approved leave and adjust the leave balances."
                        data-confirm-text-button="Yes, approve"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400"
                        >
                            Approve
                        </button>
                    </form>
                @endcan
            </div>
        @endif
    </div>

    {{-- Reject Modal --}}
    @if($leaveChangeRequest->status === 'pending')
        @can('leave_change_request.reject')
            <div
                id="rejectLeaveChangeModal"
                class="fixed inset-0 z-50 hidden overflow-y-auto"
            >
                <div class="flex min-h-screen items-center justify-center px-4">

                    <div
                        class="fixed inset-0 bg-black/50"
                        onclick="document.getElementById('rejectLeaveChangeModal').classList.add('hidden')"
                    ></div>

                    <div class="relative z-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">

                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Reject Leave Change Request
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Please provide a reason for rejecting this request.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('leave-change-requests.reject', $leaveChangeRequest) }}"
                            class="mt-5"
                        >
                            @csrf

                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Rejection Reason
                            </label>

                            <textarea
                                name="rejection_reason"
                                rows="5"
                                required
                                maxlength="2000"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Enter rejection reason..."
                            ></textarea>

                            <div class="mt-5 flex justify-end gap-3">

                                <button
                                    type="button"
                                    onclick="document.getElementById('rejectLeaveChangeModal').classList.add('hidden')"
                                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="rounded-lg bg-error-300 px-4 py-2.5 text-sm font-semibold text-white hover:bg-error-400"
                                >
                                    Reject Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif
@endsection
