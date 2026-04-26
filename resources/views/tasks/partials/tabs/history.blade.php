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
@endphp

<div class="space-y-6">
    @php
        $currentAssigneeUser = $currentAssigneeUser ?? null;
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Status Timeline</h4>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $currentStatus['color'] }}"></span>
                        {{ $currentStatus['label'] }}
                    </span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        {{ $statusHistory->count() }} {{ \Illuminate\Support\Str::plural('change', $statusHistory->count()) }}
                    </span>
                </div>
            </div>

            <div class="max-h-[560px] min-h-[320px] overflow-y-auto p-5">
                @if ($statusHistory->isEmpty())
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No status changes recorded for this task yet.
                    </div>
                @else
                    <div class="space-y-0">
                        @foreach ($statusHistory as $entry)
                            <div class="relative flex gap-4 pb-6 last:pb-0">
                                @if (!$loop->last)
                                    <span class="absolute left-[9px] top-6 h-full w-px bg-bgray-200 dark:bg-darkblack-400"></span>
                                @endif

                                <span class="relative z-10 mt-1 inline-flex h-5 w-5 shrink-0 rounded-full border-4 border-white dark:border-darkblack-600" style="background-color: {{ $entry['to_color'] }}"></span>

                                <div class="min-w-0 flex-1 rounded-xl px-2 py-1 dark:border-darkblack-400">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex min-w-0 flex-wrap items-center gap-1">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-bgray-50 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['from_color'] }}"></span>
                                                {{ $entry['from_label'] }}
                                            </span>
                                            <span class="text-bgray-400 dark:text-bgray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h9.586L11.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L13.586 11H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['to_color'] }}"></span>
                                                {{ $entry['to_label'] }}
                                            </span>
                                        </div>

                                        <span class="text-xs text-bgray-500 dark:text-bgray-300">{{ $entry['changed_by'] }} at @appDateTime($entry['changed_at'])</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Task Time Log</h4>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        <img src="{{ $currentAssigneeUser?->profile_image_url ?? asset(config('assets.images.default_avatar')) }}" alt="{{ $currentAssigneeUser?->name ?? $currentAssignee }}" class="h-5 w-5 rounded-full object-cover">
                        <span>{{ $currentAssignee }}</span>
                    </span>
                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                        {{ $formatDuration($totalLoggedSeconds) }} total
                    </span>
                </div>
            </div>

            <div class="max-h-[560px] min-h-[320px] overflow-y-auto p-5">
                @if ($timeLogs->isEmpty())
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No timer history recorded for this task yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-bgray-200 text-xs font-semibold uppercase text-bgray-400 dark:border-darkblack-400 dark:text-bgray-300">
                                    <th class="px-3 pb-3">User</th>
                                    <th class="px-3 pb-3">Started</th>
                                    <th class="px-3 pb-3">Stopped</th>
                                    <th class="px-3 pb-3 text-right">Duration</th>
                                    <th class="px-3 pb-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400">
                                @foreach ($timeLogs as $timeLog)
                                    @php
                                        $timeLogUser = $timeLog->user;
                                        $timeLogStartedAtForInput = $timeLog->started_at?->copy()?->timezone($globalTimezone)?->format('Y-m-d H:i');
                                        $timeLogEndedAtForInput = $timeLog->ended_at?->copy()?->timezone($globalTimezone)?->format('Y-m-d H:i');
                                        $hasPendingTimeLogChangeRequest = (bool) ($timeLog->has_pending_change_request ?? false);
                                        $isDifferentUserLog = (int) ($timeLog->user_id ?? 0) !== (int) auth()->id();
                                        $isRunningLog = (bool) $timeLog->is_running;
                                        $isRejectedTask = ($task->request_status ?? null) === 'rejected';
                                        $canOpenTimeLogChangeRequest = !$hasPendingTimeLogChangeRequest && !$isDifferentUserLog && !$isRunningLog && !$isRejectedTask;
                                        $timeLogChangeRestrictionMessage = $hasPendingTimeLogChangeRequest ? 'A pending time change request already exists for this log.' : ($isDifferentUserLog ? 'You can only request changes for your own time logs.' : ($isRunningLog ? 'Stop the running timer before requesting a time change.' : ($isRejectedTask ? 'Time changes are unavailable for rejected tasks.' : 'Request time change')));
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <img src="{{ $timeLogUser?->profile_image_url ?? asset(config('assets.images.default_avatar')) }}" alt="{{ $timeLogUser?->name ?? 'Unknown User' }}" class="h-6 w-6 rounded-full object-cover">
                                                <p class="font-semibold text-bgray-900 dark:text-white">{{ $timeLogUser?->name ?? 'Unknown User' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-bgray-600 dark:text-bgray-200">@appDateTime($timeLog->started_at)</td>
                                        <td class="px-3 py-3 text-bgray-600 dark:text-bgray-200">
                                            @if ($timeLog->ended_at)
                                                @appDateTime($timeLog->ended_at)
                                            @elseif ($timeLog->is_running)
                                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-400 dark:bg-darkblack-500">Running</span>
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-bgray-900 dark:text-white">{{ $formatDuration((int) $timeLog->duration_seconds) }}</td>
                                        <td class="px-3 py-3 text-right">
                                            <button type="button" class="{{ $canOpenTimeLogChangeRequest ? 'modal-open hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300' : 'cursor-not-allowed opacity-50' }} relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 shadow-sm transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" title="{{ $timeLogChangeRestrictionMessage }}"
                                                aria-label="{{ $timeLogChangeRestrictionMessage }}" data-target="#timeLogChangeRequestModal" data-time-log-change-request-open data-task_id="{{ $task->id }}" data-task_time_log_id="{{ $timeLog->id }}" data-new_started_at="{{ $timeLogStartedAtForInput }}" data-new_ended_at="{{ $timeLogEndedAtForInput }}" data-original_started_at="{{ $timeLogStartedAtForInput }}" data-original_ended_at="{{ $timeLogEndedAtForInput }}" data-time_log_user_name="{{ $timeLogUser?->name ?? 'Unknown User' }}" @disabled(!$canOpenTimeLogChangeRequest)>
                                                @if ($hasPendingTimeLogChangeRequest)
                                                    <span class="absolute -right-1 -top-1 inline-flex h-3 w-3 rounded-full bg-warning-300 ring-2 ring-white dark:ring-darkblack-500"></span>
                                                @endif

                                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11 2L2 11L11 19.5V14C18 14 21 21.5 21 21.5C21 13 18.5 7.5 11 7.5V2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    </path>
                                                </svg>

                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div id="timeLogChangeRequestModal" class="modal modal-form fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-time-log-change-request-modal>
        <div class="modal-close fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-time-log-change-request-overlay></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-3xl">
                <div class="overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <div>
                            <h4 id="timeLogChangeRequestModalTitle" class="text-xl font-semibold text-bgray-900 dark:text-white">
                                Request Time Log Change
                            </h4>
                        </div>

                        <button type="button" class="modal-close inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" aria-label="Close time log change request modal" data-time-log-change-request-close>
                            ✕
                        </button>
                    </div>

                    <form id="timeLogChangeRequestForm" action="{{ route('tasks.time-log-change-requests.store') }}" method="POST" class="flex max-h-[80vh] flex-col" data-time-log-change-request-form>
                        @csrf
                        <input type="hidden" id="timeLogChangeRequestTaskId" name="task_id" value="{{ $task->id }}" data-time-log-change-request-task-id>
                        <input type="hidden" id="timeLogChangeRequestTaskTimeLogId" name="task_time_log_id" value="" data-time-log-change-request-time-log-id>
                        <input type="hidden" id="timeLogChangeRequestOriginalStartedAt" name="original_started_at" value="" data-time-log-change-request-original-started-at>
                        <input type="hidden" id="timeLogChangeRequestOriginalEndedAt" name="original_ended_at" value="" data-time-log-change-request-original-ended-at>

                        <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7">
                            <div class="space-y-6">

                                <div class="grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label for="timeLogChangeRequestNewStartedAt" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                            New Started At <x-red-star />
                                        </label>
                                        <input type="text" id="timeLogChangeRequestNewStartedAt" name="new_started_at" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-enable-time="true" data-time-24hr="true" data-format="Y-m-d H:i" data-time-log-change-request-started-at placeholder="Select start date and time" autocomplete="off">
                                        <p class="mt-1 hidden text-sm text-error-300" data-time-log-change-request-error-for="new_started_at"></p>
                                    </div>

                                    <div>
                                        <label for="timeLogChangeRequestNewEndedAt" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                            New Ended At <x-red-star />
                                        </label>
                                        <input type="text" id="timeLogChangeRequestNewEndedAt" name="new_ended_at" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-enable-time="true" data-time-24hr="true" data-format="Y-m-d H:i" data-time-log-change-request-ended-at placeholder="Select end date and time" autocomplete="off">
                                        <p class="mt-1 hidden text-sm text-error-300" data-time-log-change-request-error-for="new_ended_at"></p>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <label for="timeLogChangeRequestReason" class="block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                            Reason <x-red-star />
                                        </label>
                                    </div>
                                    <textarea id="timeLogChangeRequestReason" name="reason" rows="4" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Explain why this time log should be changed" data-time-log-change-request-reason></textarea>
                                    <p class="mt-1 hidden text-sm text-error-300" data-time-log-change-request-error-for="reason"></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                            <button type="button" id="timeLogChangeRequestCancelButton" class="modal-close rounded-lg border border-bgray-300 bg-white px-5 py-2 font-semibold text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-time-log-change-request-close>
                                Cancel
                            </button>
                            <button type="button" id="timeLogChangeRequestSubmitButton" class="rounded-lg bg-success-300 px-5 py-2 font-semibold text-white transition duration-200 hover:bg-success-400" data-time-log-change-request-submit>
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
            <div>
                <h4 class="text-base font-bold text-bgray-900 dark:text-white">Task Assignment History</h4>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                    {{ $assignmentHistory->count() }} {{ \Illuminate\Support\Str::plural('assignment', $assignmentHistory->count()) }}
                </span>
            </div>
        </div>

        <div class="max-h-[620px] min-h-[320px] overflow-y-auto p-5">
            @if ($assignmentHistory->isEmpty())
                <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                    No assignment history recorded for this task yet.
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($assignmentHistory as $assignment)
                        @php
                            $assignmentUser = $assignment->user;
                            $assignmentAddedBy = $assignment->addedBy;
                        @endphp
                        <article class="rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $assignmentUser?->profile_image_url ?? asset(config('assets.images.default_avatar')) }}" alt="{{ $assignmentUser?->name ?? 'Unknown User' }}" class="h-6 w-6 rounded-full object-cover">
                                        <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $assignmentUser?->name ?? 'Unknown User' }}</p>
                                    </div>
                                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                                        {{ $assignment->is_current ? 'Current assignment' : 'Previous assignment' }}
                                    </p>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $assignment->is_current ? 'bg-success-50 text-success-400 dark:bg-darkblack-500' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200' }}">
                                    {{ $assignment->is_current ? 'Active' : 'Closed' }}
                                </span>
                            </div>

                            <dl class="mt-4 space-y-2 text-xs">
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-bgray-500 dark:text-bgray-300">Assigned From</dt>
                                    <dd class="text-right font-medium text-bgray-900 dark:text-white">@appDateTime($assignment->assigned_from)</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-bgray-500 dark:text-bgray-300">Assigned To</dt>
                                    <dd class="text-right font-medium text-bgray-900 dark:text-white">
                                        @if ($assignment->assigned_to)
                                            @appDateTime($assignment->assigned_to)
                                        @else
                                            --
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-bgray-500 dark:text-bgray-300">Worked Time</dt>
                                    <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $formatDuration((int) $assignment->worked_time_seconds) }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-bgray-500 dark:text-bgray-300">Added By</dt>
                                    <dd class="flex items-center justify-end gap-1.5 text-right font-medium text-bgray-900 dark:text-white">
                                        @if ($assignmentAddedBy)
                                            <img src="{{ $assignmentAddedBy->profile_image_url }}" alt="{{ $assignmentAddedBy->name }}" class="h-4 w-4 rounded-full object-cover">
                                        @endif
                                        <span>{{ $assignmentAddedBy?->name ?? 'System' }}</span>
                                    </dd>
                                </div>
                            </dl>

                            @if (filled($assignment->handover_note))
                                <p class="mt-4 rounded-lg bg-bgray-50 px-3 py-2 text-xs leading-5 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                    {{ $assignment->handover_note }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
