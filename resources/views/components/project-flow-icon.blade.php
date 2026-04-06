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
        'sm' => 'h-3 w-3',
        'lg' => 'h-4 w-4',
        default => 'h-3.5 w-3.5',
    };
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex shrink-0 items-center justify-center border border-bgray-200 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100 '
        . $sizeClasses . ' '
        . ($isAgileFlow ? 'bg-success-50' : 'bg-bgray-100'),
]) }} title="{{ $title }}">
    @if ($isAgileFlow)
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSizeClasses }} text-success-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h4m0 0v4m0-4l-6 6" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17h4m-4 0v-4m0 4l10-10" opacity=".45" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSizeClasses }} text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 8l4 4-4 4" />
        </svg>
    @endif
</span>
