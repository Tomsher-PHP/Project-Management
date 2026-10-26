@props([
    'type' => 'button',
    'title' => 'Refresh',
    'ariaLabel' => 'Refresh',
])

@php
    $classes = 'inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-400 bg-white text-[#111653] shadow-[var(--workspace-soft-shadow)] transition duration-200 hover:border-[#d7e3f6] hover:bg-[#fbfdff] hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300 disabled:cursor-not-allowed disabled:opacity-50';
@endphp

<button type="{{ $type }}" title="{{ $title }}" aria-label="{{ $ariaLabel }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
        {{ $icon }}
    @else
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" data-refresh-icon>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
    @endisset
</button>
