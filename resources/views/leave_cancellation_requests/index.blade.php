
@extends('layouts.master')

@section('page-content')
    <div class="mx-auto w-full px-4 py-6 sm:px-6 lg:px-8">

        {{-- Page header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <a href="{{ route('leave-requests.index') }}"
               class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                <i class="fas fa-arrow-left"></i>
                Back to Leave Management
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

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Requests table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                #
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Employee
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Leave Type
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Leave Dates
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Reason
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Requested On
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                        @forelse ($cancellationRequests as $item)
                            @php
                                $leave = $item->leaveRequest;

                                $statusClasses = match ($item->status) {
                                    'pending' => 'inline-flex rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-red-700',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    'cancelled' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                };

                                $fromDate = $leave?->approved_from_date
                                    ?? $leave?->requested_from_date;

                                $toDate = $leave?->approved_to_date
                                    ?? $leave?->requested_to_date;
                            @endphp

                            <tr class="group hover:bg-bgray-100 dark:hover:bg-darkblack-500">

                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                    {{ $cancellationRequests->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $leave?->user?->name ?? $item->requester?->name ?? 'Employee' }}
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $item->requester?->email ?? '' }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $leave?->leaveType?->name ?? 'N/A' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div>
                                        {{ $fromDate ? \Illuminate\Support\Carbon::parse($fromDate)->format('d M Y') : 'N/A' }}
                                    </div>

                                    @if ($toDate)
                                        <div class="mt-1 text-xs text-gray-500">
                                            to {{ \Illuminate\Support\Carbon::parse($toDate)->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="max-w-xs px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span title="{{ $item->reason }}">
                                        {{ \Illuminate\Support\Str::limit($item->reason, 65) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                    {{ $item->created_at?->format('d M Y, h:i A') ?? 'N/A' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                        {{-- View --}}
                                        <a href="{{ route('leave-cancellation-requests.show', $item) }}"
                                        class="rounded-lg  px-3 py-2 text-xs font-semibold transition 0 border border-bgray-400 bg-white text-bgray-700 shadow-sm">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </a>

                                        {{-- Approve / Reject: pending requests only --}}
                                        @if ($item->status === 'pending')
                                            @php
                                                $isSuperAdmin = (bool) auth()->user()->is_super_admin;

                                                $assignedTo = $item->leaveRequest->assigned_to ?? [];

                                                if (is_string($assignedTo)) {
                                                    $assignedTo = json_decode($assignedTo, true) ?? [];
                                                }

                                                $isAssignedReviewer = in_array(
                                                    (int) auth()->id(),
                                                    array_map('intval', (array) $assignedTo),
                                                    true
                                                );

                                                $isRequester = (int) $item->requested_by === (int) auth()->id();

                                                $canReview = ($isSuperAdmin || $isAssignedReviewer)
                                                    && ! $isRequester;
                                            @endphp

                                            @if ($canReview)
                                                

                                                 {{-- Approve Leave Cancel Request --}}
                                                <form
                                                    method="POST"
                                                    action="{{ route('leave-cancellation-requests.approve', $item) }}"
                                                    data-leave-change-request-action-form
                                                    data-confirm-title="Approve Leave Cancel Request?"
                                                    data-confirm-text="Are you sure you want to approve this leave cancel request? Note:Approve this cancellation request? The original approved leave will be cancelled and its leave balance restored."
                                                    data-confirm-text-button="Yes, approve"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400"
                                                    >
                                                        Approve
                                                    </button>
                                                </form>

                                                {{-- Reject Leave cancel Request --}}
                                                <a href="{{ route('leave-cancellation-requests.show', $item) }}"
                                                class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700">
                                                    <i class="fas fa-times"></i>
                                                    Reject
                                                </a>
                                            @endif
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <div class="mb-3 text-3xl text-gray-300">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>

                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        No leave cancellation requests found.
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Submitted requests will appear here.
                                    </p>
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($cancellationRequests->hasPages())
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                    {{ $cancellationRequests->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll(
            '[data-leave-cancel-request-action-form]'
        ).forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const result = await Alert.confirm({
                    title: form.dataset.confirmTitle || 'Are you sure?',
                    text: form.dataset.confirmText || 'Please confirm this action.',
                    icon: form.dataset.confirmIcon || 'warning',
                    confirmText: form.dataset.confirmTextButton || 'Yes',
                });

                if (result?.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
