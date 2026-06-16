@props([
    'grade' => null,
    'svg' => null,
    'name' => null,
    'color' => null,
    'size' => 'md',
    'tooltip' => true,
])

@php
    $resolvedName = $name ?? (data_get($grade, 'name') ?? 'New');
    $resolvedSvg = filled($svg) ? $svg : data_get($grade, 'badge_svg');
    $resolvedColor = $color ?? data_get($grade, 'color');
    $safeColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $resolvedColor) ? $resolvedColor : '#22C55E';
    $sizeClasses = match ($size) {
        'xs' => 'h-5 w-5 [&_svg]:h-4 [&_svg]:w-4',
        'sm' => 'h-6 w-6 [&_svg]:h-5 [&_svg]:w-5',
        'md' => 'h-8 w-8 [&_svg]:h-7 [&_svg]:w-7',
        'lg' => 'h-10 w-10 [&_svg]:h-8 [&_svg]:w-8',
        'xlg' => 'h-12 w-12 [&_svg]:h-10 [&_svg]:w-10',
        default => 'h-8 w-8 [&_svg]:h-7 [&_svg]:w-7',
    };

    $baseClasses = 'inline-flex shrink-0 items-center justify-center';
@endphp

<span {{ $attributes->class([$baseClasses, $sizeClasses]) }} @if ($tooltip) title="Profile grade: {{ $resolvedName }}" @endif aria-label="Profile grade: {{ $resolvedName }}">
    @if (filled($resolvedSvg))
        {!! $resolvedSvg !!}
    @else
        <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <defs>
                <linearGradient id="profile-grade-fallback-star" x1="3" y1="2" x2="17" y2="18" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#BBF7D0" />
                    <stop offset=".48" stop-color="#22C55E" />
                    <stop offset="1" stop-color="{{ $safeColor }}" />
                </linearGradient>
            </defs>
            <path fill="url(#profile-grade-fallback-star)" d="m10 1.6 2.47 5.01 5.53.8-4 3.9.94 5.51L10 14.22l-4.94 2.6L6 11.31l-4-3.9 5.53-.8L10 1.6Z" />
            <path fill="#ECFDF5" fill-opacity=".68" d="m10 3.65 1.12 2.27-2.9 5.85.54-3.13-2.28-2.22 3.15-.46L10 3.65Z" />
        </svg>
    @endif
</span>
