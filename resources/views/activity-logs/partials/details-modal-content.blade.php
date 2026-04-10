<div class="flex shrink-0 items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-success-400">Activity Details</p>
        <p class="mt-2 text-md font-bold text-bgray-900 dark:text-white">
            {{ $details['description'] }}
        </p>
        <p class="mt-2 text-sm font-medium text-bgray-900 dark:text-white">
            {{ $details['causer'] }} changed at {{ $details['logged_at']?->timezone($globalTimezone)?->format($globalDateFormat . ' ' . $globalTimeFormat) ?? '--' }}
        </p>
    </div>

    <button type="button" data-activity-log-close class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-bgray-100 text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
            <path d="M6 6l8 8M14 6l-8 8" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
        </svg>
    </button>
</div>

<div class="min-h-0 flex-1 overflow-y-auto px-6 pt-3 pb-6">
    <x-activity-log.change-list :rows="$details['rows']" :event="$details['event']" />
</div>
