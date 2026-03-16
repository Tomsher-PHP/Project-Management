@php
    $activeFilters = collect(request()->except(['page', 'search_condition']))
        ->filter(function ($value) {
            return $value !== null && $value !== '';
        })
        ->count();
@endphp

<button onclick="FilterDrawer.open()" class="px-4 py-2 border rounded-md text-sm">
    Filters {{ $activeFilters > 0 ? "($activeFilters)" : '' }}
</button>
