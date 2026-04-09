<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Project History</h3>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                Review the top-to-bottom change flow for project status and stage.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $currentStatus['color'] }}"></span>
                Current Status: {{ $currentStatus['label'] }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $currentStage['color'] }}"></span>
                Current Stage: {{ $currentStage['label'] }}
            </span>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Status Timeline</h4>
                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Mapped from project status history in chronological order.</p>
                </div>
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                    {{ $statusHistory->count() }} {{ \Illuminate\Support\Str::plural('change', $statusHistory->count()) }}
                </span>
            </div>

            <div class="p-5">
                @if ($statusHistory->isEmpty())
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No status changes recorded yet.
                    </div>
                @else
                    <div class="space-y-0">
                        @foreach ($statusHistory as $entry)
                            <div class="relative flex gap-4 pb-6 last:pb-0">
                                @if (! $loop->last)
                                    <span class="absolute left-[9px] top-6 h-full w-px bg-bgray-200 dark:bg-darkblack-400"></span>
                                @endif

                                <span class="relative z-10 mt-1 inline-flex h-5 w-5 shrink-0 rounded-full border-4 border-white dark:border-darkblack-600" style="background-color: {{ $entry['to_color'] }}"></span>

                                <div class="min-w-0 flex-1 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-2 rounded-full bg-bgray-50 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['from_color'] }}"></span>
                                                {{ $entry['from_label'] }}
                                            </span>
                                            <span class="text-bgray-400 dark:text-bgray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h9.586L11.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L13.586 11H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['to_color'] }}"></span>
                                                {{ $entry['to_label'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-bgray-500 dark:text-bgray-300">
                                        <span>Changed by {{ $entry['changed_by'] }} at @appDateTime($entry['changed_at'])</span>
                                    </div>

                                    @if (filled($entry['remarks']))
                                        <p class="mt-3 text-sm leading-6 text-bgray-700 dark:text-bgray-100">
                                            {{ $entry['remarks'] }}
                                        </p>
                                    @endif
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
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Stage Timeline</h4>
                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Mapped from project stage history in chronological order.</p>
                </div>
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                    {{ $stageHistory->count() }} {{ \Illuminate\Support\Str::plural('change', $stageHistory->count()) }}
                </span>
            </div>

            <div class="p-5">
                @if ($stageHistory->isEmpty())
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No stage changes recorded yet.
                    </div>
                @else
                    <div class="space-y-0">
                        @foreach ($stageHistory as $entry)
                            <div class="relative flex gap-4 pb-6 last:pb-0">
                                @if (! $loop->last)
                                    <span class="absolute left-[9px] top-6 h-full w-px bg-bgray-200 dark:bg-darkblack-400"></span>
                                @endif

                                <span class="relative z-10 mt-1 inline-flex h-5 w-5 shrink-0 rounded-full border-4 border-white dark:border-darkblack-600" style="background-color: {{ $entry['to_color'] }}"></span>

                                <div class="min-w-0 flex-1 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-2 rounded-full bg-bgray-50 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['from_color'] }}"></span>
                                                {{ $entry['from_label'] }}
                                            </span>
                                            <span class="text-bgray-400 dark:text-bgray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h9.586L11.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L13.586 11H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['to_color'] }}"></span>
                                                {{ $entry['to_label'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-bgray-500 dark:text-bgray-300">
                                        <span>Changed by {{ $entry['changed_by'] }} at @appDateTime($entry['changed_at'])</span>
                                    </div>

                                    @if (filled($entry['remarks']))
                                        <p class="mt-3 text-sm leading-6 text-bgray-700 dark:text-bgray-100">
                                            {{ $entry['remarks'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
