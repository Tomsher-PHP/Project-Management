@php
    $activeFilters = collect(request()->except(['page', 'search_condition']))
        ->filter(function ($value) {
            return $value !== null && $value !== '';
        })
        ->count();
@endphp

<button onclick="FilterDrawer.open()" class="w-40 px-6 py-2 border border-blue-200 bg-blue-50 text-blue-700 rounded-lg text-sm hover:bg-blue-100 transition shadow-sm inline-flex items-center gap-2">
    <span>
        <svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.55169 13.5022H1.25098" stroke="#0CAF60" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M10.3623 3.80984H16.663" stroke="#0CAF60" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.94797 3.75568C5.94797 2.46002 4.88981 1.40942 3.58482 1.40942C2.27984 1.40942 1.22168 2.46002 1.22168 3.75568C1.22168 5.05133 2.27984 6.10193 3.58482 6.10193C4.88981 6.10193 5.94797 5.05133 5.94797 3.75568Z" stroke="#0CAF60" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.2214 13.4632C17.2214 12.1675 16.1641 11.1169 14.8591 11.1169C13.5533 11.1169 12.4951 12.1675 12.4951 13.4632C12.4951 14.7589 13.5533 15.8095 14.8591 15.8095C16.1641 15.8095 17.2214 14.7589 17.2214 13.4632Z" stroke="#0CAF60" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </span>
    <span class="text-base font-medium text-success-300">Filters</span>
    <span class="text-xs text-red-500">{{ $activeFilters > 0 ? "($activeFilters)" : '' }}</span>
</button>
