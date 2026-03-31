<div class="border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-bgray-500 dark:text-bgray-300">
                {{ $details['event'] === 'created' ? 'Created Details' : 'Updated Changes' }}
            </p>
            <h3 class="mt-1.5 text-xl font-semibold text-bgray-900 dark:text-white">
                {{ $details['module'] }} - {{ $details['subject'] }}
            </h3>
            <p class="mt-1.5 text-sm text-bgray-500 dark:text-bgray-300">
                {{ $details['causer'] }} | {{ $details['logged_at'] }}
            </p>
        </div>

        <button type="button" data-activity-log-close class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                <path d="M6 6l8 8M14 6l-8 8" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
            </svg>
        </button>
    </div>
</div>

<div class="overflow-y-auto px-5 py-4">
    <div class="mb-4 grid gap-3 rounded-xl bg-bgray-50 p-3 sm:grid-cols-2 dark:bg-darkblack-500">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">Subject Type</p>
            <p class="mt-1.5 text-sm font-medium text-bgray-900 dark:text-white">{{ $details['subject_type'] }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">Activity</p>
            <p class="mt-1.5 text-sm font-medium text-bgray-900 dark:text-white">{{ $details['description'] }}</p>
        </div>
    </div>

    <div class="space-y-2.5">
        @foreach ($details['rows'] as $row)
            <div class="rounded-xl border border-bgray-200 p-3.5 dark:border-darkblack-400">
                <p class="mb-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">{{ $row['field'] }}</p>

                @if ($details['event'] === 'created')
                    <div class="rounded-lg bg-bgray-50 px-3 py-2.5 text-sm font-medium text-bgray-900 dark:bg-darkblack-500 dark:text-white">
                        <x-activity-log.value :value="$row['new']" />
                    </div>
                @else
                    <div class="grid gap-2.5 md:grid-cols-2">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 px-3 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">Old Value</p>
                            <div class="text-sm font-medium text-bgray-900 dark:text-white">
                                <x-activity-log.value :value="$row['old']" />
                            </div>
                        </div>
                        <div class="rounded-lg border border-success-200 bg-success-50 px-3 py-2.5 dark:border-success-900/30 dark:bg-success-900/10">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-success-400">New Value</p>
                            <div class="text-sm font-medium text-bgray-900 dark:text-white">
                                <x-activity-log.value :value="$row['new']" />
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
