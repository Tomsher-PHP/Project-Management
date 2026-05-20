@props([
    'user' => null,
    'image' => null,
    'name' => null,
    'size' => 'md',
])

@php
    $resolvedName = $name ?? data_get($user, 'name') ?? 'User';
    $hasUserImage = (bool) data_get($user, 'hasProfileImage', false);
    $resolvedImage = filled($image) ? $image : ($hasUserImage ? data_get($user, 'profileImageUrl') : null);
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(\Illuminate\Support\Str::squish($resolvedName), 0, 2));

    $sizeClasses = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
        'xlg' => 'h-16 w-16 text-xl',
        default => 'h-10 w-10 text-sm',
    };

    $baseClasses = 'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-success-50 font-bold text-success-400 dark:bg-darkblack-500 dark:text-success-300';
@endphp

<div {{ $attributes->merge(['class' => $baseClasses . ' ' . $sizeClasses]) }}>
    @if (filled($resolvedImage))
        <img src="{{ $resolvedImage }}" alt="{{ $resolvedName }}" class="h-full w-full rounded-full object-cover">
    @else
        <span>{{ $initial }}</span>
    @endif
</div>
