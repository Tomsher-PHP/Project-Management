@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'label' => 'Create',
])

@php
    $classes = 'inline-flex items-center gap-1 rounded-md border border-bgray-500 bg-white px-2 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @isset($icon)
            {{ $icon }}
        @endisset
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ $label }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @isset($icon)
            {{ $icon }}
        @endisset
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ $label }}</span>
    </button>
@endif
