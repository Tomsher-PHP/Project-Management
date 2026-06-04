@props([
    'flow' => null,
    'title' => null,
    'size' => 'md',
])

@php
    $normalizedFlow = strtolower((string) ($flow ?? 'linear'));
    $isAgileFlow = $normalizedFlow === 'agile';
    $title = $title ?? 'Project Flow: ' . ucfirst($normalizedFlow ?: 'linear');
    $sizeClasses = match ($size) {
        'sm' => 'h-5 w-5 rounded',
        'lg' => 'h-7 w-7 rounded-lg',
        default => 'h-6 w-6 rounded-md',
    };
    $iconSizeClasses = match ($size) {
        'sm' => 'h-3.5 w-3.5',
        'lg' => 'h-4.5 w-4.5',
        default => 'h-4 w-4',
    };
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex shrink-0 items-center justify-center border transition duration-150 ' . $sizeClasses . ' ' . ($isAgileFlow ? 'bg-bgray-100 border-bgray-300 text-bgray-900 dark:border-darkblack-400 dark:text-bgray-400' : 'bg-blue-50 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900/40 text-blue-500'),
]) }} title="{{ $title }}">
    @if ($isAgileFlow)
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSizeClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0114.93-3.5M20 12a8 8 0 01-14.93 3.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 6h4v4M10 18H6v-4" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSizeClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h6v6h6v6h6" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 15l3 3-3 3" />
        </svg>
    @endif
</span>
