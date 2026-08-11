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
    'class' => 'inline-flex shrink-0 items-center justify-center transition duration-150 ' . $sizeClasses . ' ' . ($isAgileFlow ? 'text-purple-600 dark:text-purple-400' : 'text-blue-600 dark:text-blue-400'),
]) }} title="{{ $title }}">
    @if ($isAgileFlow)
        {{-- Agile: Iterative Sprint / Cycle Loop Icon --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSizeClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
    @else
        {{-- Linear: Sequential / Direct Flow Arrow Icon --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $iconSizeClasses }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
        </svg>
    @endif
</span>
