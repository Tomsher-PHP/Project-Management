@php
    $activeFilters = collect(request()->except(['page', 'search_condition', 'name_condition', 'sort_by', 'sort_dir', 'per_page', 'request_status']))
        ->filter(function ($value) {
            return $value !== null && $value !== '';
        })
        ->count();
    $hasActiveFilters = $activeFilters > 0;
@endphp

<button
    type="button"
    onclick="FilterDrawer.open()"
    class="{{ $hasActiveFilters
        ? 'border-success-200 bg-success-50/80 text-success-400 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300'
        : 'border-bgray-200 bg-white text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:bg-darkblack-400' }} ml-auto inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:hover:border-success-300 dark:hover:text-success-300"
    aria-label="Open filters"
>
    <span class="inline-flex items-center justify-center text-current">
        <svg class="h-4 w-4" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M7.55169 13.5022H1.25098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M10.3623 3.80984H16.663" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.94797 3.75568C5.94797 2.46002 4.88981 1.40942 3.58482 1.40942C2.27984 1.40942 1.22168 2.46002 1.22168 3.75568C1.22168 5.05133 2.27984 6.10193 3.58482 6.10193C4.88981 6.10193 5.94797 5.05133 5.94797 3.75568Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.2214 13.4632C17.2214 12.1675 16.1641 11.1169 14.8591 11.1169C13.5533 11.1169 12.4951 12.1675 12.4951 13.4632C12.4951 14.7589 13.5533 15.8095 14.8591 15.8095C16.1641 15.8095 17.2214 14.7589 17.2214 13.4632Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </span>

    <span class="text-sm font-semibold">Filters</span>

    @if ($hasActiveFilters)
        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-success-300 px-1.5 text-[11px] font-bold text-white dark:bg-success-300 dark:text-white">
            {{ $activeFilters }}
        </span>
    @endif
</button>
