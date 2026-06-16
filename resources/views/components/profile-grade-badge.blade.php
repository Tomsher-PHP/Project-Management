@props([
    'grade' => null,
    'svg' => null,
    'name' => null,
    'color' => null,
    'size' => 'md',
    'tooltip' => true,
])

@php
    $resolvedName = $name ?? (data_get($grade, 'name') ?? 'Profile Grade');
    $resolvedSvg = filled($svg) ? $svg : data_get($grade, 'badge_svg');
    $resolvedColor = $color ?? data_get($grade, 'color');
    $safeColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $resolvedColor) ? $resolvedColor : '#22C55E';
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(\Illuminate\Support\Str::squish($resolvedName), 0, 1));

    $sizeClasses = match ($size) {
        'xs' => 'h-5 w-5 [&_svg]:h-4 [&_svg]:w-4',
        'sm' => 'h-6 w-6 [&_svg]:h-5 [&_svg]:w-5',
        'md' => 'h-8 w-8 [&_svg]:h-7 [&_svg]:w-7',
        'lg' => 'h-10 w-10 [&_svg]:h-8 [&_svg]:w-8',
        'xlg' => 'h-12 w-12 [&_svg]:h-10 [&_svg]:w-10',
        default => 'h-8 w-8 [&_svg]:h-7 [&_svg]:w-7',
    };

    $initialSizeClasses = match ($size) {
        'xs' => 'text-[9px]',
        'sm' => 'text-[10px]',
        'md' => 'text-xs',
        'lg' => 'text-sm',
        'xlg' => 'text-base',
        default => 'text-xs',
    };

    $baseClasses = 'inline-flex shrink-0 items-center justify-center';
@endphp

<span {{ $attributes->class([$baseClasses, $sizeClasses]) }} @if ($tooltip) title="{{ $resolvedName }}" @endif aria-label="Profile grade: {{ $resolvedName }}">
    @if (filled($resolvedSvg))
        {!! $resolvedSvg !!}
    @else
        <span class="flex h-full w-full items-center justify-center rounded-full font-bold text-white {{ $initialSizeClasses }}" style="background-color: {{ $safeColor }}">
            {{ $initial }}
        </span>
    @endif
</span>
