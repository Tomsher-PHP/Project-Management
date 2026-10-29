@props([
    'value' => null,
    'type' => null,
])

@if (blank($value) && $value !== false && $value !== 0)
    <span class="text-bgray-600 dark:text-bgray-300">--</span>
@elseif ($type === 'date')
    <span>{{ \Carbon\Carbon::parse($value)->timezone($globalTimezone)->format($globalDateFormat) }}</span>
@elseif ($type === 'datetime')
    <span>{{ \Carbon\Carbon::parse($value)->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}</span>
@elseif (is_bool($value))
    <span>{{ $value ? 'Yes' : 'No' }}</span>
@elseif (is_array($value) || $value instanceof \Illuminate\Support\Collection || is_object($value))
@elseif ($type === 'html')
    @if (blank(trim(strip_tags((string) $value, '<img><video><audio><svg><figure><iframe><input>'))))
        <span class="text-bgray-600 dark:text-bgray-300">--</span>
    @else
        <div class="prose max-w-none text-sm text-bgray-700 dark:prose-invert dark:text-bgray-300 break-words leading-relaxed [&>p]:mb-1 [&>p:last-child]:mb-0">{!! $value !!}</div>
    @endif
@else
    <span class="break-words">{{ $value }}</span>
@endif
