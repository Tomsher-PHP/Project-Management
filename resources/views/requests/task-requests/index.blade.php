@extends('layouts.master')

@section('page-content')
        @php
            $tabs = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ];
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />

                @if ($selectedStatus === 'pending')
                    <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-task-request-bulk-approve disabled>
                        Bulk Approve
                    </button>

                    <button type="button" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400 disabled:cursor-not-allowed disabled:opacity-50" data-task-request-bulk-reject data-action="{{ route('tasks.requests.bulk-action', 'reject') }}" disabled>
                        Bulk Reject
                    </button>

                    <form method="POST" action="{{ route('tasks.requests.bulk-action', 'approve') }}" class="hidden" data-task-request-bulk-approve-form>
                        @csrf
                        <div data-task-request-bulk-approve-hidden-inputs></div>
                    </form>
                @endif
            </div>

            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('tasks.requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
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
                                @if ($selectedStatus === 'pending')
                                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-task-request-bulk-select-all>
                                    </th>
                                @endif
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="name" label="Task" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="project.name" label="Project" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="currentAssignee.name" label="Requested By" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="due_date_time" label="Due Date" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($tasks as $task)
                                <tr class="group {{ config('assets.classes.table_row_hover') }}">
                                    @if ($selectedStatus === 'pending')
                                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                            @if ($task->request_status === 'pending' && ! $task->is_self_requested)
                                                <input type="checkbox" value="{{ $task->id }}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-task-request-bulk-checkbox>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[220px]">
                                            <a href="{{ route('tasks.edit', $task) }}" class="font-semibold text-bgray-900 transition hover:text-success-300 dark:text-white dark:hover:text-success-300">
                                                {{ $task->name }}
                                            </a>
                                            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">{{ $task->code }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[180px]">
                                            @if ($task->project && auth()->user()?->can('project.view'))
                                                <a href="{{ route('projects.edit', $task->project_id) }}" class="text-sm font-medium text-bgray-700 transition hover:text-success-300 dark:text-bgray-300 dark:hover:text-success-300">
                                                    {{ $task->project->name }}
                                                </a>
                                            @elseif ($task->project)
                                                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">{{ $task->project->name }}</p>
                                            @else
                                                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">--</p>
                                            @endif
                                            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">{{ $task->projectMilestone?->name ?? 'No milestone' }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="flex min-w-[180px] items-center gap-3">
                                            <x-user-avatar :user="$task->currentAssignee" :image="$task->currentAssignee?->profile_image_url" :name="$task->currentAssignee?->name ?? '--'" size="md" />
                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">{{ $task->currentAssignee?->name ?? '--' }}</span>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $task->request_status === 'pending' ? 'bg-warning-50 text-warning-300' : ($task->request_status === 'approved' ? 'bg-success-50 text-success-300' : 'bg-error-50 text-error-300') }}">
                                            {{ ucfirst($task->request_status) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-600 dark:text-bgray-300">@appDateTime($task->due_date_time)</span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        @if ($task->request_status === 'pending' && ! $task->is_self_requested)
                                            <div class="flex min-w-[180px] flex-wrap items-center gap-2">
                                                <form method="POST" action="{{ route('tasks.requests.action', [$task, 'approve']) }}" data-task-request-action-form data-confirm-title="Approve task request?" data-confirm-text="This will approve the requested user's work logs for this task." data-confirm-text-button="Yes, approve">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                                                        Approve
                                                    </button>
                                                </form>

                                                <button type="button" class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400" data-task-request-reject-open data-action="{{ route('tasks.requests.action', [$task, 'reject']) }}" data-task-name="{{ $task->name }}">
                                                    Reject
                                                </button>
                                            </div>
                                        @elseif ($task->request_status === 'pending')
                                            <span class="text-xs text-bgray-700 dark:text-bgray-300">Waiting for approval</span>
                                        @elseif ($task->request_status === 'rejected')
                                            <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-white">Rejected by {{ $task->rejectedBy?->name ?? '--' }}</p>
                                                <p>{{ $task->rejected_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}</p>
                                                <p title="{{ $task->rejection_reason }}">{{ \Illuminate\Support\Str::limit($task->rejection_reason ?? '--', 45) }}</p>
                                            </div>
                                        @else
                                            <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-white">Approved by {{ $task->approvedBy?->name ?? '--' }}</p>
                                                <p>{{ $task->approved_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}</p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="$selectedStatus === 'pending' ? 7 : 6" message="No {{ strtolower($tabs[$selectedStatus]) }} task requests found." sub-message="There are no task requests to display for this tab." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$tasks" :per-page="$perPage" />
        </section>

        <x-filters.drawer>
            <input type="hidden" name="request_status" value="{{ $selectedStatus }}">
            <x-filters.input-search name="search" label="Task" />
            <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
            <x-filters.multi-select name="current_assignee_id" label="User" :options="$users" />
        </x-filters.drawer>

        <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-task-request-reject-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-task-request-reject-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                        <div>
                            <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Reject Task Request</h3>
                            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300" data-task-request-reject-task-name></p>
                        </div>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-task-request-reject-close>
                            ✕
                        </button>
                    </div>

                    <form method="POST" action="#" class="space-y-4 px-5 py-5" data-task-request-reject-form>
                        @csrf
                        <div data-task-request-reject-hidden-inputs></div>

                        <div>
                            <label for="task-request-rejection-reason" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                Description <x-red-star />
                            </label>
                            <textarea id="task-request-rejection-reason" name="reason" rows="4" required class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add rejection description"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                            <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-task-request-reject-close>
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rejectModal = document.querySelector('[data-task-request-reject-modal]');
            const rejectForm = document.querySelector('[data-task-request-reject-form]');
            const rejectReason = document.getElementById('task-request-rejection-reason');
            const rejectTaskName = document.querySelector('[data-task-request-reject-task-name]');
            const rejectHiddenInputs = document.querySelector('[data-task-request-reject-hidden-inputs]');
            const bulkSelectAll = document.querySelector('[data-task-request-bulk-select-all]');
            const bulkApproveButton = document.querySelector('[data-task-request-bulk-approve]');
            const bulkApproveForm = document.querySelector('[data-task-request-bulk-approve-form]');
            const bulkApproveHiddenInputs = document.querySelector('[data-task-request-bulk-approve-hidden-inputs]');
            const bulkRejectButton = document.querySelector('[data-task-request-bulk-reject]');
            const bulkCheckboxes = Array.from(document.querySelectorAll('[data-task-request-bulk-checkbox]'));

            const getSelectedTaskIds = () => bulkCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);

            const syncBulkActions = () => {
                const selectedCount = getSelectedTaskIds().length;

                bulkApproveButton?.toggleAttribute('disabled', selectedCount === 0);
                bulkRejectButton?.toggleAttribute('disabled', selectedCount === 0);

                if (bulkSelectAll) {
                    bulkSelectAll.checked = bulkCheckboxes.length > 0 && selectedCount === bulkCheckboxes.length;
                    bulkSelectAll.indeterminate = selectedCount > 0 && selectedCount < bulkCheckboxes.length;
                }
            };

            const setHiddenTaskIds = (container, taskIds = []) => {
                if (!container) {
                    return;
                }

                container.innerHTML = '';
                taskIds.forEach((taskId) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'task_ids[]';
                    input.value = taskId;
                    container.appendChild(input);
                });
            };

            const openRejectModal = (button) => {
                if (!rejectModal || !rejectForm) {
                    return;
                }

                rejectForm.action = button.dataset.action || '#';
                rejectForm.reset();
                setHiddenTaskIds(rejectHiddenInputs, []);

                if (rejectTaskName) {
                    rejectTaskName.textContent = button.dataset.taskName ? `Task: ${button.dataset.taskName}` : '';
                }

                rejectModal.classList.remove('hidden');
                rejectReason?.focus();
            };

            const closeRejectModal = () => {
                rejectModal?.classList.add('hidden');
            };

            document.querySelectorAll('[data-task-request-reject-open]').forEach((button) => {
                button.addEventListener('click', () => openRejectModal(button));
            });

            document.querySelectorAll('[data-task-request-reject-close]').forEach((button) => {
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
                const selectedTaskIds = getSelectedTaskIds();

                if (selectedTaskIds.length === 0) {
                    return;
                }

                const result = await Alert.confirm({
                    title: 'Approve selected task requests?',
                    text: `This will approve ${selectedTaskIds.length} selected task request(s).`,
                    icon: 'warning',
                    confirmText: 'Yes, approve',
                });

                if (result?.isConfirmed) {
                    setHiddenTaskIds(bulkApproveHiddenInputs, selectedTaskIds);
                    bulkApproveForm?.submit();
                }
            });

            bulkRejectButton?.addEventListener('click', () => {
                const selectedTaskIds = getSelectedTaskIds();

                if (selectedTaskIds.length === 0 || !rejectModal || !rejectForm) {
                    return;
                }

                rejectForm.action = bulkRejectButton.dataset.action || '#';
                rejectForm.reset();
                setHiddenTaskIds(rejectHiddenInputs, selectedTaskIds);

                if (rejectTaskName) {
                    rejectTaskName.textContent = `${selectedTaskIds.length} selected task request(s)`;
                }

                rejectModal.classList.remove('hidden');
                rejectReason?.focus();
            });

            syncBulkActions();

            document.querySelectorAll('[data-task-request-action-form]').forEach((form) => {
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
