@props([
    'filterForm' => '#filterDrawer form',
    'placeholder' => 'Search...',
])

<div class="flex min-w-0 items-stretch" data-list-search data-filter-form="{{ $filterForm }}">
    <label for="list-search-{{ $attributes->get('id', 'default') }}" class="sr-only">Search</label>
    <input id="list-search-{{ $attributes->get('id', 'default') }}" type="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}" autocomplete="off" data-list-search-input {{ $attributes->except('id')->class(['min-w-0 rounded-l-md border border-r-0 border-bgray-500 bg-white px-3 py-1.5 text-sm text-bgray-700 outline-none transition placeholder:text-bgray-500 focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-bgray-300 dark:bg-darkblack-500 dark:text-bgray-50']) }}>
    <button type="button" class="inline-flex items-center justify-center rounded-r-md border border-success-300 bg-success-300 px-3 text-white transition hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 focus:ring-offset-1" aria-label="Search" data-list-search-button>
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
        </svg>
    </button>
</div>
