@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        @php
            $tabs = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ];
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            
            <x-filters.button />

            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('tasks.requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-200 dark:hover:bg-darkblack-500' }}">
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
                                    <x-sorting.sortable-column column="due_date" label="Due Date" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($tasks as $task)
                                <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[220px]">
                                            <a href="{{ route('tasks.edit', $task) }}" class="font-semibold text-bgray-900 transition hover:text-success-300 dark:text-white dark:hover:text-success-300">
                                                {{ $task->name }}
                                            </a>
                                            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">{{ $task->code }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[180px]">
                                            @if ($task->project && auth()->user()?->can('project.view'))
                                                <a href="{{ route('projects.edit', $task->project_id) }}" class="text-sm font-medium text-bgray-700 transition hover:text-success-300 dark:text-bgray-100 dark:hover:text-success-300">
                                                    {{ $task->project->name }}
                                                </a>
                                            @elseif ($task->project)
                                                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-100">{{ $task->project->name }}</p>
                                            @else
                                                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-100">--</p>
                                            @endif
                                            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">{{ $task->projectModule?->name ?? 'No module' }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-100">{{ $task->currentAssignee?->name ?? '--' }}</span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $task->request_status === 'pending' ? 'bg-warning-50 text-warning-300' : ($task->request_status === 'approved' ? 'bg-success-50 text-success-300' : 'bg-error-50 text-error-300') }}">
                                            {{ ucfirst($task->request_status) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <span class="text-sm text-bgray-600 dark:text-bgray-200">{{ $task->due_date?->format($globalDateFormat) ?? '--' }}</span>
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
                                            <span class="text-xs text-bgray-500 dark:text-bgray-300">Waiting for approval</span>
                                        @elseif ($task->request_status === 'rejected')
                                            <div class="min-w-[220px] text-xs text-bgray-500 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-bgray-100">Rejected by {{ $task->rejectedBy?->name ?? '--' }}</p>
                                                <p>{{ $task->rejected_at?->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}</p>
                                                <p title="{{ $task->rejection_reason }}">Description: {{ \Illuminate\Support\Str::limit($task->rejection_reason ?? '--', 25) }}</p>
                                            </div>
                                        @else
                                            <div class="min-w-[220px] text-xs text-bgray-500 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-bgray-100">Approved by {{ $task->approvedBy?->name ?? '--' }}</p>
                                                <p>{{ $task->approved_at?->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}</p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data col-span="6" message="No {{ strtolower($tabs[$selectedStatus]) }} task requests found." sub-message="There are no task requests to display for this tab." />
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
                            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300" data-task-request-reject-task-name></p>
                        </div>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-task-request-reject-close>
                            ✕
                        </button>
                    </div>

                    <form method="POST" action="#" class="space-y-4 px-5 py-5" data-task-request-reject-form>
                        @csrf

                        <div>
                            <label for="task-request-rejection-reason" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Description <x-red-star />
                            </label>
                            <textarea id="task-request-rejection-reason" name="reason" rows="4" required class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add rejection description"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                            <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200" data-task-request-reject-close>
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
            const rejectModal = document.querySelector('[data-task-request-reject-modal]');
            const rejectForm = document.querySelector('[data-task-request-reject-form]');
            const rejectReason = document.getElementById('task-request-rejection-reason');
            const rejectTaskName = document.querySelector('[data-task-request-reject-task-name]');

            const openRejectModal = (button) => {
                if (!rejectModal || !rejectForm) {
                    return;
                }

                rejectForm.action = button.dataset.action || '#';
                rejectForm.reset();

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
