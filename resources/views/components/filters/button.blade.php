@php
    $activeFilters = collect(request()->except(['page', 'search_condition', 'name_condition', 'sort_by', 'sort_dir', 'per_page', 'request_status', 'read_status']))
        ->filter(function ($value) {
            return $value !== null && $value !== '';
        })
        ->count();
    $hasActiveFilters = $activeFilters > 0;

    $classes = $hasActiveFilters ? 'border-success-300 bg-success-50/80 text-success-400 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300' : 'border-bgray-500 bg-white text-bgray-700 dark:border-bgray-300 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:bg-darkblack-400';

    $classes .= 'ml-auto inline-flex items-center gap-1 rounded-md border px-2 py-1.5 text-sm font-semibold shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:hover:border-success-300 dark:hover:text-success-300';
@endphp

@props([
    'label' => null,
])

<button type="button" onclick="FilterDrawer.open()" aria-label="Open filters" class="{{ $classes }}">
    <span class="inline-flex items-center justify-center text-current">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8v5.25a1.5 1.5 0 0 1-.879 1.365l-3 1.364A.75.75 0 0 1 9 19.796V12.5l-6-8Z" />
        </svg>
    </span>

    @if ($label)
        <span class="text-sm font-semibold">{{ $label }}</span>
    @endif

    @if ($hasActiveFilters)
        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-success-300 px-1.5 text-[11px] font-bold text-white dark:bg-success-300 dark:text-white">
            {{ $activeFilters }}
        </span>
    @endif
</button>
