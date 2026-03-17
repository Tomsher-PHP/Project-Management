@props(['column', 'label'])

@php
    $currentSort = request('sort_by');
    $currentDir = request('sort_dir', 'asc');

    $isActive = $currentSort === $column;

    $nextDir = $isActive && $currentDir === 'asc' ? 'desc' : 'asc';

    // Change color when active
    $iconColor = $isActive ? '#2563EB' : '#718096'; // blue when active, gray default
@endphp

<a href="{{ request()->fullUrlWithQuery([
    'sort_by' => $column,
    'sort_dir' => $nextDir,
]) }}" class="flex w-full items-center space-x-2.5 cursor-pointer">

    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </span>

    <span>
        <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">

            <path d="M10.332 1.31567V13.3157" stroke="{{ $iconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />

            <path d="M5.66602 11.3157L3.66602 13.3157L1.66602 11.3157" stroke="{{ $iconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />

            <path d="M3.66602 13.3157V1.31567" stroke="{{ $iconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />

            <path d="M12.332 3.31567L10.332 1.31567L8.33203 3.31567" stroke="{{ $iconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
</a>
