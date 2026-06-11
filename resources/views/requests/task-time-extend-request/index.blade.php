@extends('layouts.master')

@section('page-content')
    @php
        $tabs = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        $canApproveReject = auth()->user()?->can('task_time_extend_request.approve_reject');
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <x-filters.button />
        </div>

        <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
            @foreach ($tabs as $status => $label)
                <a href="{{ route('tasks.extend-time-requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
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
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested By</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested At</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Project</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Task</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Current Estimate Time</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">New Estimated Time</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reason</span>
                            </th>
                            <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-darkblack-600">
                        @forelse ($extendRequests as $extendRequest)
                            @php
                                $requestUser = $extendRequest->user;
                                $isOwnRequest = (int) $extendRequest->user_id === (int) auth()->id();
                            @endphp
                            <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <div class="flex min-w-[180px] items-center gap-3">
                                        <x-user-avatar :user="$requestUser" :image="$requestUser?->profile_image_url" :name="$requestUser?->name ?? 'Unknown User'" size="md" />
                                        <div>
                                            <p class="font-semibold text-bgray-900 dark:text-white">{{ $requestUser?->name ?? 'Unknown User' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <span class="min-w-[170px] text-sm text-bgray-700 dark:text-bgray-300">
                                        @appDateTime($extendRequest->created_at)
                                    </span>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                        @if ($extendRequest->task?->project)
                                            <a href="{{ route('projects.edit', $extendRequest->task->project) }}" class="transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                                                {{ $extendRequest->task->project->name }}
                                            </a>
                                        @else
                                            --
                                        @endif
                                    </span>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                        @if ($extendRequest->task)
                                            <a href="{{ route('tasks.edit', $extendRequest->task) }}" class="transition duration-200 hover:text-success-400 dark:hover:text-success-300">
                                                {{ $extendRequest->task->name }}
                                            </a>
                                        @else
                                            --
                                        @endif
                                    </span>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <span class="text-sm text-bgray-700 dark:text-bgray-300">{{ $extendRequest->estimated_time_formatted }}</span>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <span class="text-sm text-bgray-700 dark:text-bgray-300 font-semibold">{{ $extendRequest->new_estimated_time_formatted }}</span>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    <div class="min-w-[200px] text-sm text-bgray-700 dark:text-bgray-300">
                                        {{ limitStringChar($extendRequest->reason ?? '--', 90) }}
                                    </div>
                                </td>
                                <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                    @if ($extendRequest->isPending() && $canApproveReject && !$isOwnRequest)
                                        <div class="flex min-w-[180px] flex-wrap items-center gap-2">
                                            <button type="button" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400" data-extend-request-approve-open data-action="{{ route('tasks.extend-time-requests.approve', $extendRequest) }}" data-details-url="{{ route('tasks.extend-time-requests.show', $extendRequest) }}">
                                                Approve
                                            </button>

                                            <button type="button" class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400" data-extend-request-reject-open data-action="{{ route('tasks.extend-time-requests.reject', $extendRequest) }}" data-request-user-name="{{ $requestUser?->name ?? 'Unknown User' }}">
                                                Reject
                                            </button>
                                        </div>
                                    @elseif ($extendRequest->isPending())
                                        <span class="text-xs text-bgray-700 dark:text-bgray-300">Waiting for approval</span>
                                    @elseif ($extendRequest->isRejected())
                                        <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                            <p class="font-semibold text-bgray-700 dark:text-white">Rejected by {{ $extendRequest->rejector?->name ?? '--' }}</p>
                                            <p class="mt-0.5">@appDateTime($extendRequest->rejected_at)</p>
                                            <p class="mt-0.5 text-bgray-500 dark:text-bgray-400" title="{{ $extendRequest->rejection_reason }}">{{ \Illuminate\Support\Str::limit($extendRequest->rejection_reason ?? '--', 45) }}</p>
                                        </div>
                                    @else
                                        <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                            <p class="font-semibold text-bgray-700 dark:text-white">Approved by {{ $extendRequest->approver?->name ?? '--' }}</p>
                                            <p class="mt-0.5">@appDateTime($extendRequest->approved_at)</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-table-no-data :col-span="8" message="No {{ strtolower($tabs[$selectedStatus]) }} task time extend requests found." sub-message="There are no task time extend requests available for your access level." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-pagination :paginator="$extendRequests" :per-page="$perPage" />
    </section>

    <x-filters.drawer>
        <input type="hidden" name="request_status" value="{{ $selectedStatus }}">
        <x-filters.input-search name="search" label="Task Name" />
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
        <x-filters.multi-select name="user_id" label="User" :options="$users" />
    </x-filters.drawer>

    <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-extend-request-reject-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-extend-request-reject-close></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Reject Task Time Extend Request</h3>
                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300" data-extend-request-reject-title></p>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-extend-request-reject-close>
                        ✕
                    </button>
                </div>

                <form method="POST" action="#" class="space-y-4 px-5 py-5" data-extend-request-reject-form>
                    @csrf

                    <div>
                        <label for="extend-request-rejection-reason" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                            Description <x-red-star />
                        </label>
                        <textarea id="extend-request-rejection-reason" name="reason" rows="4" required class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add rejection description"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-extend-request-reject-close>
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

    <!-- Approve Modal -->
    <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-extend-request-approve-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-extend-request-approve-close></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Approve Task Time Extend Request</h3>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-extend-request-approve-close>
                        ✕
                    </button>
                </div>

                <form method="POST" action="#" class="space-y-4 px-5 py-5" data-extend-request-approve-form>
                    @csrf

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="block font-medium text-bgray-600 dark:text-bgray-300">Project</span>
                            <span class="font-semibold text-bgray-900 dark:text-white" data-approve-project-name>--</span>
                        </div>
                        <div>
                            <span class="block font-medium text-bgray-600 dark:text-bgray-300">Task</span>
                            <span class="font-semibold text-bgray-900 dark:text-white" data-approve-task-name>--</span>
                        </div>
                        <div>
                            <span class="block font-medium text-bgray-600 dark:text-bgray-300">Requested By</span>
                            <span class="font-semibold text-bgray-900 dark:text-white" data-approve-user-name>--</span>
                        </div>
                        <div>
                            <span class="block font-medium text-bgray-600 dark:text-bgray-300">Current Estimate Time</span>
                            <span class="font-semibold text-bgray-900 dark:text-white" data-approve-current-estimate>--</span>
                        </div>
                    </div>

                    <div class="border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                        <span class="block text-sm font-medium text-bgray-600 dark:text-bgray-300">Reason</span>
                        <p class="mt-1 text-sm text-bgray-900 dark:text-white bg-bgray-50 dark:bg-darkblack-500 rounded-lg p-3 whitespace-pre-wrap" data-approve-reason>--</p>
                    </div>

                    <div class="border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                        <x-forms.estimated-time-input label="New Estimated Time" name="new_estimated_time_minutes" totalMinutes="0" errorKey="new_estimated_time_minutes" />
                        <span class="text-xs text-error-300 hidden" data-extend-request-approve-error="new_estimated_time_minutes"></span>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-extend-request-approve-close>
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-extend-request-approve-submit>
                            Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/modules/tasks/approve-extend-time.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rejectModal = document.querySelector('[data-extend-request-reject-modal]');
            const rejectForm = document.querySelector('[data-extend-request-reject-form]');
            const rejectReason = document.getElementById('extend-request-rejection-reason');
            const rejectTitle = document.querySelector('[data-extend-request-reject-title]');

            const openRejectModal = (button) => {
                if (!rejectModal || !rejectForm) {
                    return;
                }

                rejectForm.action = button.dataset.action || '#';
                rejectForm.reset();

                if (rejectTitle) {
                    rejectTitle.textContent = button.dataset.requestUserName ?
                        `Requested by ${button.dataset.requestUserName}` :
                        '';
                }

                rejectModal.classList.remove('hidden');
                rejectReason?.focus();
            };

            const closeRejectModal = () => {
                rejectModal?.classList.add('hidden');
            };

            document.querySelectorAll('[data-extend-request-reject-open]').forEach((button) => {
                button.addEventListener('click', () => openRejectModal(button));
            });

            document.querySelectorAll('[data-extend-request-reject-close]').forEach((button) => {
                button.addEventListener('click', closeRejectModal);
            });
        });
    </script>
@endpush
