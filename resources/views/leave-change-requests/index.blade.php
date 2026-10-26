@extends('layouts.master')

@section('page-content')
    <div class="mx-auto w-full px-4 py-6 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0">

                    <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                        <tr>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Employee
                            </th>

                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Current Leave
                            </th>

                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Requested Change
                            </th>

                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Dates
                            </th>

                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Requested By
                            </th>

                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Status
                            </th>

                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-darkblack-600">
                        @forelse($changeRequests as $changeRequest)
                            <tr class="group hover:bg-bgray-100 dark:hover:bg-darkblack-500">
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    {{ $changeRequest->leaveRequest?->user?->name ?? 'Employee' }}
                                </td>

                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $changeRequest->oldLeaveType?->name ?? '—' }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $changeRequest->old_duration }}
                                        {{ $changeRequest->old_duration == 1 ? 'day' : 'days' }}
                                    </div>
                                </td>

                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $changeRequest->newLeaveType?->name ?? '—' }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $changeRequest->new_duration }}
                                        {{ $changeRequest->new_duration == 1 ? 'day' : 'days' }}
                                    </div>
                                </td>

                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    {{ $changeRequest->new_from_date?->format('d M Y') }}
                                    -
                                    {{ $changeRequest->new_to_date?->format('d M Y') }}
                                </td>

                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    {{ $changeRequest->requestedBy?->name ?? '—' }}
                                </td>

                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    @php
                                        $statusClasses = match ($changeRequest->status) {
                                            'pending' => 'bg-warning-100 text-warning-700',
                                            'approved' => 'bg-success-100 text-success-700',
                                            'rejected' => 'bg-error-100 text-error-700',
                                            'cancelled' => 'bg-gray-100 text-gray-600',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp

                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ ucfirst($changeRequest->status) }}
                                    </span>
                                </td>

                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <div class="flex items-center justify-end gap-2">

                                        {{-- View --}}
                                        <a href="{{ route('leave-change-requests.show', $changeRequest) }}" class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-200">
                                            View
                                        </a>

                                        {{-- Approve / Reject: pending requests only --}}
                                        @if ($changeRequest->status === 'pending')
                                            @can('leave-change-requests.approve')
                                                    
                                                    {{-- Approve Leave Change Request --}}
                                                    <form
                                                        method="POST"
                                                        action="{{ route('leave-change-requests.approve', $changeRequest) }}"
                                                        data-leave-change-request-action-form
                                                        data-confirm-title="Approve Leave Change Request?"
                                                        data-confirm-text="Are you sure you want to approve this leave change request?"
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

                                                    {{-- Reject Leave Change Request --}}
                                                    <form
                                                        method="POST"
                                                        action="{{ route('leave-change-requests.reject', $changeRequest) }}"
                                                        data-leave-change-request-action-form
                                                        data-confirm-title="Reject Leave Change Request?"
                                                        data-confirm-text="Are you sure you want to reject this leave change request?"
                                                        data-confirm-text-button="Yes, reject"
                                                    >
                                                        @csrf

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400"
                                                        >
                                                            Reject
                                                        </button>
                                                    </form>
                                                @endcan
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No leave change requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($changeRequests->hasPages())
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                    {{ $changeRequests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll(
            '[data-leave-change-request-action-form]'
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
