@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        @php
            $tabs = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ];

            $canApproveRejectBreakRequests = auth()->user()?->can('break_request.approve_reject');
            $showBulkActions = $selectedStatus === 'pending' && $canApproveRejectBreakRequests;
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />

                @if ($showBulkActions)
                    <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-break-request-bulk-approve disabled>
                        Bulk Approve
                    </button>

                    <button type="button" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400 disabled:cursor-not-allowed disabled:opacity-50" data-break-request-bulk-reject data-action="{{ route('break-requests.bulk-action', 'reject') }}" disabled>
                        Bulk Reject
                    </button>

                    <form method="POST" action="{{ route('break-requests.bulk-action', 'approve') }}" class="hidden" data-break-request-bulk-approve-form>
                        @csrf
                        <div data-break-request-bulk-approve-hidden-inputs></div>
                    </form>
                @endif
            </div>

            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('break-requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-200 dark:hover:bg-darkblack-500' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <section>
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                @if ($showBulkActions)
                                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-break-request-bulk-select-all>
                                    </th>
                                @endif
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested By</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested at</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Time Range</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Duration</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Description</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($breakRequests as $breakRequest)
                                @php
                                    $requestUser = $breakRequest->user;
                                    $isOwnRequest = (int) $breakRequest->user_id === (int) auth()->id();
                                    $statusClasses = $breakRequest->isApproved()
                                        ? 'bg-success-50 text-success-300'
                                        : ($breakRequest->isRejected() ? 'bg-error-50 text-error-300' : 'bg-warning-50 text-warning-300');
                                @endphp
                                <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                    @if ($showBulkActions)
                                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                            @if ($breakRequest->isPending() && ! $isOwnRequest)
                                                <input type="checkbox" value="{{ $breakRequest->id }}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-break-request-bulk-checkbox>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex min-w-[180px] items-center gap-3">
                                            <img src="{{ $requestUser?->profile_image_url ?? asset(config('assets.images.default_avatar')) }}" alt="{{ $requestUser?->name ?? 'Unknown User' }}" class="h-9 w-9 rounded-full object-cover">
                                            <div>
                                                <p class="font-semibold text-bgray-900 dark:text-white">{{ $requestUser?->name ?? 'Unknown User' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="min-w-[120px] text-sm text-bgray-700 dark:text-bgray-200">@appDate($breakRequest->work_date)</span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="min-w-[170px] text-sm text-bgray-700 dark:text-bgray-200">
                                            @appDateTime($breakRequest->created_at)
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[180px] text-sm text-bgray-700 dark:text-bgray-200">
                                            <p><span class="font-medium text-bgray-700 dark:text-bgray-50">Start:</span> @appTime($breakRequest->started_at)</p>
                                            <p class="mt-1"><span class="font-medium text-bgray-700 dark:text-bgray-50">End:</span> @appTime($breakRequest->ended_at)</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="min-w-[90px] text-sm text-bgray-700 dark:text-bgray-200">{{ formatSecondsToHMS($breakRequest->duration_seconds) }}</span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[260px] text-sm text-bgray-700 dark:text-bgray-200">
                                            {{ \Illuminate\Support\Str::limit($breakRequest->description ?? '--', 90) }}
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ ucfirst($breakRequest->status) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        @if ($breakRequest->isPending() && $canApproveRejectBreakRequests && ! $isOwnRequest)
                                            <div class="flex min-w-[180px] flex-wrap items-center gap-2">
                                                <form method="POST" action="{{ route('break-requests.action', [$breakRequest, 'approve']) }}" data-break-request-action-form data-confirm-title="Approve break work request?" data-confirm-text="This will approve the submitted break work request." data-confirm-text-button="Yes, approve">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                                                        Approve
                                                    </button>
                                                </form>

                                                <button type="button" class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400" data-break-request-reject-open data-action="{{ route('break-requests.action', [$breakRequest, 'reject']) }}" data-request-user-name="{{ $requestUser?->name ?? 'Unknown User' }}">
                                                    Reject
                                                </button>
                                            </div>
                                        @elseif ($breakRequest->isPending())
                                            <span class="text-xs text-bgray-700 dark:text-bgray-300">Waiting for approval</span>
                                        @elseif ($breakRequest->isRejected())
                                            <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-white">Rejected by {{ $breakRequest->rejectedBy?->name ?? '--' }}</p>
                                                <p>@appDateTime($breakRequest->rejected_at)</p>
                                                <p title="{{ $breakRequest->rejection_reason }}">{{ \Illuminate\Support\Str::limit($breakRequest->rejection_reason ?? '--', 45) }}</p>
                                            </div>
                                        @else
                                            <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-white">Approved by {{ $breakRequest->approvedBy?->name ?? '--' }}</p>
                                                <p>@appDateTime($breakRequest->approved_at)</p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="$showBulkActions ? 9 : 8" message="No {{ strtolower($tabs[$selectedStatus]) }} break work requests found." sub-message="There are no break work requests available for your access level." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$breakRequests" :per-page="$perPage" />
        </section>

        <x-filters.drawer>
            <input type="hidden" name="request_status" value="{{ $selectedStatus }}">
            <x-filters.input-search name="search" label="Description" />
            <x-filters.multi-select name="user_id" label="User" :options="$users" />
        </x-filters.drawer>

        <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-break-request-reject-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-break-request-reject-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                        <div>
                            <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Reject Break Work Request</h3>
                            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300" data-break-request-reject-title></p>
                        </div>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-break-request-reject-close>
                            ✕
                        </button>
                    </div>

                    <form method="POST" action="#" class="space-y-4 px-5 py-5" data-break-request-reject-form>
                        @csrf
                        <div data-break-request-reject-hidden-inputs></div>

                        <div>
                            <label for="break-request-rejection-reason" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Description <x-red-star />
                            </label>
                            <textarea id="break-request-rejection-reason" name="reason" rows="4" required class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add rejection description"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                            <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200" data-break-request-reject-close>
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400">
                                Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rejectModal = document.querySelector('[data-break-request-reject-modal]');
            const rejectForm = document.querySelector('[data-break-request-reject-form]');
            const rejectReason = document.getElementById('break-request-rejection-reason');
            const rejectTitle = document.querySelector('[data-break-request-reject-title]');
            const rejectHiddenInputs = document.querySelector('[data-break-request-reject-hidden-inputs]');
            const bulkSelectAll = document.querySelector('[data-break-request-bulk-select-all]');
            const bulkApproveButton = document.querySelector('[data-break-request-bulk-approve]');
            const bulkApproveForm = document.querySelector('[data-break-request-bulk-approve-form]');
            const bulkApproveHiddenInputs = document.querySelector('[data-break-request-bulk-approve-hidden-inputs]');
            const bulkRejectButton = document.querySelector('[data-break-request-bulk-reject]');
            const bulkCheckboxes = Array.from(document.querySelectorAll('[data-break-request-bulk-checkbox]'));

            const getSelectedRequestIds = () => bulkCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);

            const syncBulkActions = () => {
                const selectedCount = getSelectedRequestIds().length;

                bulkApproveButton?.toggleAttribute('disabled', selectedCount === 0);
                bulkRejectButton?.toggleAttribute('disabled', selectedCount === 0);

                if (bulkSelectAll) {
                    bulkSelectAll.checked = bulkCheckboxes.length > 0 && selectedCount === bulkCheckboxes.length;
                    bulkSelectAll.indeterminate = selectedCount > 0 && selectedCount < bulkCheckboxes.length;
                }
            };

            const setHiddenRequestIds = (container, requestIds = []) => {
                if (!container) {
                    return;
                }

                container.innerHTML = '';
                requestIds.forEach((requestId) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'break_request_ids[]';
                    input.value = requestId;
                    container.appendChild(input);
                });
            };

            const openRejectModal = (button) => {
                if (!rejectModal || !rejectForm) {
                    return;
                }

                rejectForm.action = button.dataset.action || '#';
                rejectForm.reset();
                setHiddenRequestIds(rejectHiddenInputs, []);

                if (rejectTitle) {
                    rejectTitle.textContent = button.dataset.requestUserName
                        ? `Requested by ${button.dataset.requestUserName}`
                        : '';
                }

                rejectModal.classList.remove('hidden');
                rejectReason?.focus();
            };

            const closeRejectModal = () => {
                rejectModal?.classList.add('hidden');
            };

            document.querySelectorAll('[data-break-request-reject-open]').forEach((button) => {
                button.addEventListener('click', () => openRejectModal(button));
            });

            document.querySelectorAll('[data-break-request-reject-close]').forEach((button) => {
                button.addEventListener('click', closeRejectModal);
            });

            bulkSelectAll?.addEventListener('change', () => {
                bulkCheckboxes.forEach((checkbox) => {
                    checkbox.checked = bulkSelectAll.checked;
                });
                syncBulkActions();
            });

            bulkCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncBulkActions);
            });

            bulkApproveButton?.addEventListener('click', async () => {
                const selectedRequestIds = getSelectedRequestIds();

                if (selectedRequestIds.length === 0) {
                    return;
                }

                const result = await Alert.confirm({
                    title: 'Approve selected break work requests?',
                    text: `This will approve ${selectedRequestIds.length} selected break work request(s).`,
                    icon: 'warning',
                    confirmText: 'Yes, approve',
                });

                if (result?.isConfirmed) {
                    setHiddenRequestIds(bulkApproveHiddenInputs, selectedRequestIds);
                    bulkApproveForm?.submit();
                }
            });

            bulkRejectButton?.addEventListener('click', () => {
                const selectedRequestIds = getSelectedRequestIds();

                if (selectedRequestIds.length === 0 || !rejectModal || !rejectForm) {
                    return;
                }

                rejectForm.action = bulkRejectButton.dataset.action || '#';
                rejectForm.reset();
                setHiddenRequestIds(rejectHiddenInputs, selectedRequestIds);

                if (rejectTitle) {
                    rejectTitle.textContent = `${selectedRequestIds.length} selected break work request(s)`;
                }

                rejectModal.classList.remove('hidden');
                rejectReason?.focus();
            });

            syncBulkActions();

            document.querySelectorAll('[data-break-request-action-form]').forEach((form) => {
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
        });
    </script>
@endpush
