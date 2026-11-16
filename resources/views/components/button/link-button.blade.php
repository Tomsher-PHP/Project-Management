@props([
    'href' => '#',
    'label' => null,
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center gap-2 rounded-md border border-success-300 px-4 py-1.5 text-sm font-semibold transition duration-200 hover:bg-success-300 hover:text-white';
    $hasCustomTextColor = preg_match('/\btext-(?!xs\b|sm\b|base\b|lg\b|xl\b|\d+xl\b|left\b|center\b|right\b|justify\b|start\b|end\b)/', (string) $attributes->get('class')) === 1;
    $classes = $hasCustomTextColor ? $baseClasses : $baseClasses . ' text-success-400';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon instanceof \Illuminate\View\ComponentSlot)
        {{ $icon }}
    @elseif ($icon === 'import')
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3" />
        </svg>
    @elseif ($icon === 'restore')
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 8l5 4-5 4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12h-8" />
        </svg>
    @endif

    @if (filled($label))
        <span>{{ $label }}</span>
    @elseif ($slot->isNotEmpty())
        <span>{{ $slot }}</span>
    @endif
</a>

