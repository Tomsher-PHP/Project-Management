@props([
    'count' => null,
    'max' => 99,
])

@php
    $rawCount = $count ?? trim((string) $slot);
    $numericCount = is_numeric($rawCount) ? (int) $rawCount : null;
    $displayCount = ($numericCount !== null && $numericCount > $max) ? "{$max}+" : ($numericCount ?? $rawCount);
@endphp

<span {{ $attributes->merge([
    'class' => 'rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white',
    'data-count' => $numericCount ?? $rawCount,
]) }}>{{ $displayCount }}</span>

