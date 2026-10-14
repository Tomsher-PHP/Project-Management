
@extends('layouts.master')

@section('page-content')
    @php
        $leave = $cancellationRequest->leaveRequest;
        $user = auth()->user();

        $fromDate = $leave?->approved_from_date
            ?? $leave?->requested_from_date;

        $toDate = $leave?->approved_to_date
            ?? $leave?->requested_to_date;

        $assignedTo = $leave?->assigned_to ?? [];

        if (is_string($assignedTo)) {
            $decoded = json_decode($assignedTo, true);
            $assignedTo = is_array($decoded) ? $decoded : [];
        }

        $canReview = (bool) ($user->is_super_admin ?? false)
            || (
                $user->can('leave_request.edit')
                && is_array($assignedTo)
                && in_array(
                    (int) $user->id,
                    array_map('intval', $assignedTo),
                    true
                )
            );

        $isRequester = (int) $cancellationRequest->requested_by
            === (int) $user->id;

        $isPending = $cancellationRequest->status === 'pending';

        $statusClasses = match ($cancellationRequest->status) {
            'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'cancelled' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    @endphp

    <div>
        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('leave-cancellation-requests.index') }}"
               class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                <i class="fas fa-arrow-left"></i>
                Back to Requests
            </a>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main details --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Original leave details --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Original Leave Details
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Employee
                            </p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $leave?->user?->name ?? $cancellationRequest->requester?->name ?? 'N/A' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $leave?->user?->email ?? $cancellationRequest->requester?->email ?? '' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Leave Type
                            </p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $leave?->leaveType?->name ?? 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                From Date
                            </p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $fromDate ? \Illuminate\Support\Carbon::parse($fromDate)->format('d M Y') : 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                To Date
                            </p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $toDate ? \Illuminate\Support\Carbon::parse($toDate)->format('d M Y') : 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Approved Duration
                            </p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $leave?->approved_duration ?? $leave?->duration ?? 'N/A' }}
                                {{ is_numeric($leave?->approved_duration ?? $leave?->duration) ? 'days' : '' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Original Leave Status
                            </p>
                            <p class="mt-1">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    {{ $leave?->status === 'approved'
                                        ? 'bg-green-100 text-green-800'
                                        : ($leave?->status === 'cancelled'
                                            ? 'bg-gray-100 text-gray-700'
                                            : 'bg-amber-100 text-amber-800') }}">
                                    {{ ucfirst($leave?->status ?? 'Unknown') }}
                                </span>
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Cancellation request details --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Cancellation Request Details
                        </h2>
                    </div>

                    <div class="space-y-5 p-5">

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Request Number
                            </p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                #{{ $cancellationRequest->id }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Submitted By
                            </p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $cancellationRequest->requester?->name ?? 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Submitted On
                            </p>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $cancellationRequest->created_at?->format('d M Y, h:i A') ?? 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Cancellation Reason
                            </p>
                            <div class="mt-2 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-sm leading-6 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300">{{ $cancellationRequest->reason }}</div>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Request Status
                            </p>
                            <div class="mt-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ ucfirst($cancellationRequest->status) }}
                                </span>
                            </div>
                        </div>

                        @if ($cancellationRequest->reviewer)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Reviewed By
                                </p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ $cancellationRequest->reviewer->name }}
                                </p>
                            </div>
                        @endif

                        @if ($cancellationRequest->reviewed_at)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Reviewed On
                                </p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ $cancellationRequest->reviewed_at->format('d M Y, h:i A') }}
                                </p>
                            </div>
                        @endif

                        @if ($cancellationRequest->review_reason)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Review Remarks
                                </p>
                                <div class="mt-2 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900/50 dark:text-gray-300">{{ $cancellationRequest->review_reason }}</div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="space-y-6">

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 font-semibold text-gray-900 dark:text-white">
                        Actions
                    </h2>

                    @if ($isPending && $canReview && !$isRequester)
                        <div class="space-y-4">

                            {{-- Approve --}}
                            <form action="{{ route('leave-cancellation-requests.approve', $cancellationRequest) }}"
                                  method="POST"
                                  onsubmit="return confirm('Approve this cancellation request? The original leave will be cancelled and the balance restored.');">
                                @csrf

                                <button type="submit"
                                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-success-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-success-600">
                                    <i class="fas fa-check"></i>
                                    Approve Cancellation
                                </button>
                            </form>

                            {{-- Reject --}}
                            <form action="{{ route('leave-cancellation-requests.reject', $cancellationRequest) }}"
                                  method="POST"
                                  class="space-y-3">
                                @csrf

                                <label for="review_reason"
                                       class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Rejection Reason
                                </label>

                                <textarea
                                    id="review_reason"
                                    name="review_reason"
                                    rows="3"
                                    required
                                    maxlength="2000"
                                    class="w-full rounded-lg border border-gray-300 p-3 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Explain why this request is being rejected."
                                >{{ old('review_reason') }}</textarea>

                                <button type="submit"
                                        onclick="return confirm('Reject this cancellation request?');"
                                        class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400">
                                    <i class="fas fa-times"></i>
                                    Reject Cancellation
                                </button>
                            </form>

                        </div>

                    @elseif ($isPending && ($isRequester || $user->is_super_admin))
                        <form action="{{ route('leave-cancellation-requests.cancel', $cancellationRequest) }}"
                              method="POST"
                              onsubmit="return confirm('Withdraw this pending cancellation request?');">
                            @csrf

                            <button type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-600">
                                <i class="fas fa-times-circle"></i>
                                Withdraw Request
                            </button>
                        </form>

                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No actions are available for this request.
                        </p>
                    @endif
                </div>

                {{-- Workflow information --}}
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                    <div class="mb-2 flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-info-circle"></i>
                        Important Information
                    </div>

                    <p class="text-sm leading-6">
                        The original approved leave remains unchanged while this request is pending.
                        If the cancellation is approved, the leave will be cancelled and the eligible
                        leave balance will be restored.
                    </p>
                </div>

            </div>
        </div>
    </div>
@endsection
