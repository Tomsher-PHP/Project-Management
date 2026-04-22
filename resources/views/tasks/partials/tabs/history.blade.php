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

    <div class="grid gap-6 xl:grid-cols-2">
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
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-bgray-200 text-xs font-semibold uppercase text-bgray-400 dark:border-darkblack-400 dark:text-bgray-300">
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
                                        <td class="px-3 py-3 text-right font-semibold text-bgray-900 dark:text-white">{{ $formatDuration((int) $timeLog->duration_seconds) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
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
