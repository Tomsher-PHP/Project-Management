@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        @php
            $formatDuration = function (?int $seconds): string {
                $totalSeconds = max(0, (int) ($seconds ?? 0));
                $hours = intdiv($totalSeconds, 3600);
                $minutes = intdiv($totalSeconds % 3600, 60);
                $remainingSeconds = $totalSeconds % 60;

                if ($hours > 0) {
                    return sprintf('%dh %02dm', $hours, $minutes);
                }

                if ($minutes > 0) {
                    return sprintf('%dm %02ds', $minutes, $remainingSeconds);
                }

                return sprintf('%ds', $remainingSeconds);
            };

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
                    <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-time-log-change-request-bulk-approve disabled>
                        Bulk Approve
                    </button>

                    <button type="button" class="rounded-lg bg-error-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-error-400 disabled:cursor-not-allowed disabled:opacity-50" data-time-log-change-request-bulk-reject data-action="{{ route('tasks.time-log-change-requests.bulk-action', 'reject') }}" disabled>
                        Bulk Reject
                    </button>

                    <form method="POST" action="{{ route('tasks.time-log-change-requests.bulk-action', 'approve') }}" class="hidden" data-time-log-change-request-bulk-approve-form>
                        @csrf
                        <div data-time-log-change-request-bulk-approve-hidden-inputs></div>
                    </form>
                @endif
            </div>

            <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
                @foreach ($tabs as $status => $label)
                    <a href="{{ route('tasks.time-log-change-requests.index', array_merge(request()->except(['page', 'status']), ['request_status' => $status])) }}" class="px-4 py-2 text-sm font-semibold transition {{ $selectedStatus === $status ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-200 dark:hover:bg-darkblack-500' }}">
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
                                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-time-log-change-request-bulk-select-all>
                                    </th>
                                @endif
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested By</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Task</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Current Log</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Requested Change</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reason</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($changeRequests as $changeRequest)
                                @php
                                    $requestUser = $changeRequest->user;
                                    $timeLog = $changeRequest->timeLog;
                                    $task = $timeLog?->task;
                                    $isStartChanged = optional($changeRequest->old_started_at)?->equalTo($changeRequest->new_started_at) === false;
                                    $isEndChanged = optional($changeRequest->old_ended_at)?->equalTo($changeRequest->new_ended_at) === false;
                                    $statusClasses = $changeRequest->status === 'approved' ? 'bg-success-50 text-success-300' : ($changeRequest->status === 'rejected' ? 'bg-error-50 text-error-300' : 'bg-warning-50 text-warning-300');
                                @endphp
                                <tr class="group hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                    @if ($selectedStatus === 'pending')
                                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                            <input type="checkbox" value="{{ $changeRequest->id }}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-time-log-change-request-bulk-checkbox>
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
                                        <div class="min-w-[220px]">
                                            @if ($task)
                                                <a href="{{ route('tasks.edit', $task) }}" class="font-semibold text-bgray-900 transition hover:text-success-300 dark:text-white dark:hover:text-success-300">
                                                    {{ $task->name }}
                                                </a>
                                            @else
                                                <p class="font-semibold text-bgray-900 dark:text-white">--</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[220px] text-sm text-bgray-600 dark:text-bgray-200">
                                            <p><span class="font-medium text-bgray-700 dark:text-bgray-50">Start:</span> @appDateTime($changeRequest->old_started_at)</p>
                                            <p class="mt-1"><span class="font-medium text-bgray-700 dark:text-bgray-50">End:</span> @appDateTime($changeRequest->old_ended_at)</p>
                                            <p class="mt-2 text-xs font-medium text-bgray-500 dark:text-bgray-300">
                                                Duration: {{ $formatDuration($timeLog?->duration_seconds) }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[220px] text-sm text-bgray-600 dark:text-bgray-200">
                                            <p>
                                                <span class="font-medium text-bgray-700 dark:text-bgray-50">Start:</span>
                                                <span class="{{ $isStartChanged ? 'font-semibold text-bgray-900 dark:text-white' : '' }}">@appDateTime($changeRequest->new_started_at)</span>
                                            </p>
                                            <p class="mt-1">
                                                <span class="font-medium text-bgray-700 dark:text-bgray-50">End:</span>
                                                <span class="{{ $isEndChanged ? 'font-semibold text-bgray-900 dark:text-white' : '' }}">@appDateTime($changeRequest->new_ended_at)</span>
                                            </p>
                                            <p class="mt-2 text-xs font-medium text-bgray-500 dark:text-bgray-300">
                                                Duration: {{ $formatDuration($changeRequest->new_duration) }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        <div class="min-w-[240px] text-sm text-bgray-600 dark:text-bgray-200">
                                            {{ \Illuminate\Support\Str::limit($changeRequest->reason ?? '--', 90) }}
                                        </div>
                                    </td>
                                    <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                                        @if ($changeRequest->isPending())
                                            <div class="flex min-w-[190px] flex-wrap items-center gap-2">
                                                <form method="POST" action="{{ route('tasks.time-log-change-requests.action', [$changeRequest, 'approve']) }}" data-time-log-change-request-action-form data-confirm-title="Approve time log change request?" data-confirm-text="This will update the original task time log with the requested time range." data-confirm-text-button="Yes, approve">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                                                        Approve
                                                    </button>
                                                </form>

                                                <button type="button" class="rounded-lg bg-error-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-error-400" data-time-log-change-request-reject-open data-action="{{ route('tasks.time-log-change-requests.action', [$changeRequest, 'reject']) }}" data-task-name="{{ $task?->name ?? 'Unknown Task' }}" data-request-user-name="{{ $requestUser?->name ?? 'Unknown User' }}">
                                                    Reject
                                                </button>
                                            </div>
                                        @elseif ($changeRequest->isRejected())
                                            <div class="min-w-[220px] text-xs text-bgray-700 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-white">Rejected by {{ $changeRequest->rejector?->name ?? '--' }}</p>
                                                <p>{{ $changeRequest->rejected_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}</p>
                                                <p title="{{ $changeRequest->rejection_reason }}">{{ \Illuminate\Support\Str::limit($changeRequest->rejection_reason ?? '--', 45) }}</p>
                                            </div>
                                        @else
                                            <div class="min-w-[200px] text-xs text-bgray-700 dark:text-bgray-300">
                                                <p class="font-semibold text-bgray-700 dark:text-white">Approved by {{ $changeRequest->approver?->name ?? '--' }}</p>
                                                <p>{{ $changeRequest->approved_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}</p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-table-no-data :col-span="$selectedStatus === 'pending' ? 8 : 7" message="No {{ strtolower($tabs[$selectedStatus]) }} time log change requests found." sub-message="There are no requests available for your access level." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$changeRequests" :per-page="$perPage" />
        </section>

        <x-filters.drawer>
            <input type="hidden" name="request_status" value="{{ $selectedStatus }}">
            <x-filters.multi-select name="user_id" label="Users" :options="$users" />
        </x-filters.drawer>

        <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-time-log-change-request-reject-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-time-log-change-request-reject-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                        <div>
                            <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Reject Time Log Change Request</h3>
                            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300" data-time-log-change-request-reject-task-name></p>
                        </div>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-time-log-change-request-reject-close>
                            ✕
                        </button>
                    </div>

                    <form method="POST" action="#" class="space-y-4 px-5 py-5" data-time-log-change-request-reject-form>
                        @csrf
                        <div data-time-log-change-request-reject-hidden-inputs></div>

                        <div>
                            <label for="time-log-change-request-rejection-reason" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Description <x-red-star />
                            </label>
                            <textarea id="time-log-change-request-rejection-reason" name="reason" rows="4" required class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add rejection description"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-bgray-100 pt-4 dark:border-darkblack-400">
                            <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200" data-time-log-change-request-reject-close>
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
    @vite('resources/js/modules/tasks/time-log-change-request.js')
@endpush
