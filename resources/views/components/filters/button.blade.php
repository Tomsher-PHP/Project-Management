@php
    $activeFilters = collect(request()->except(['page', 'search_condition', 'name_condition', 'sort_by', 'sort_dir', 'per_page', 'request_status']))
        ->filter(function ($value) {
            return $value !== null && $value !== '';
        })
        ->count();
    $hasActiveFilters = $activeFilters > 0;
@endphp

<button type="button" onclick="FilterDrawer.open()" class="{{ $hasActiveFilters ? 'border-success-200 bg-success-50/80 text-success-400 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300' : 'border-bgray-500 bg-white text-bgray-700 dark:border-bgray-300 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:bg-darkblack-400' }} ml-auto inline-flex items-center gap-1 rounded-lg border px-2 py-2 text-sm font-semibold shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:hover:border-success-300 dark:hover:text-success-300" aria-label="Open filters">
    <span class="inline-flex items-center justify-center text-current">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8v5.25a1.5 1.5 0 0 1-.879 1.365l-3 1.364A.75.75 0 0 1 9 19.796V12.5l-6-8Z" />
        </svg>
    </span>

    <span class="text-sm font-semibold">Filters</span>

    @if ($hasActiveFilters)
        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-success-300 px-1.5 text-[11px] font-bold text-white dark:bg-success-300 dark:text-white">
            {{ $activeFilters }}
        </span>
    @endif
</button>
