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

<div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">
    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
        <div>
            <h3 class="text-xl font-semibold text-bgray-900 dark:text-white">Task Time Log Details</h3>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-task-log-close>
                ✕
            </button>
        </div>
    </div>

    <div class="max-h-[560px] min-h-[320px] overflow-y-auto p-5">
        @if ($timeLogs->isEmpty())
            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                No timer history recorded for this task yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-bgray-200 text-xs font-semibold uppercase text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">
                            <th class="px-3 pb-3">User</th>
                            <th class="px-3 pb-3">Started</th>
                            <th class="px-3 pb-3">Stopped</th>
                            <th class="px-3 pb-3 text-right">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400">
                        @foreach ($timeLogs as $timeLog)
                            @php
                                $timeLogUser = $timeLog->user;
                                $timeLogStartedAtForInput = $timeLog->started_at?->copy()?->timezone($globalTimezone)?->format('Y-m-d H:i:s');
                                $timeLogEndedAtForInput = $timeLog->ended_at?->copy()?->timezone($globalTimezone)?->format('Y-m-d H:i:s');
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
                                        <x-user-avatar :user="$timeLogUser" :name="$timeLogUser?->name ?? 'Unknown User'" size="xs" />
                                        <p class="font-semibold text-bgray-900 dark:text-white">
                                            {{ $timeLogUser?->name ?? 'Unknown User' }}</p>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-bgray-600 dark:text-bgray-300">@appDateTime($timeLog->started_at)</td>
                                <td class="px-3 py-3 text-bgray-600 dark:text-bgray-300">
                                    @if ($timeLog->ended_at)
                                        @appDateTime($timeLog->ended_at)
                                    @elseif ($timeLog->is_running)
                                        <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-400 dark:bg-darkblack-500">Running</span>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-bgray-900 dark:text-white">
                                    {{ $formatDuration((int) $timeLog->duration_seconds) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
