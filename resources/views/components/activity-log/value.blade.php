@props([
    'value' => null,
    'type' => null,
])

@if (blank($value) && $value !== false && $value !== 0)
    <span class="text-bgray-400 dark:text-bgray-700">--</span>
@elseif ($type === 'date')
    <span>{{ \Carbon\Carbon::parse($value)->timezone($globalTimezone)->format($globalDateFormat) }}</span>
@elseif ($type === 'datetime')
    <span>{{ \Carbon\Carbon::parse($value)->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}</span>
@elseif (is_bool($value))
    <span>{{ $value ? 'Yes' : 'No' }}</span>
@elseif (is_array($value) || $value instanceof \Illuminate\Support\Collection || is_object($value))
    <pre class="whitespace-pre-wrap break-words text-xs leading-5 text-bgray-700 dark:text-bgray-300">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
@else
    <span class="break-words">{{ $value }}</span>
@endif
