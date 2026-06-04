@props([
    'label' => 'Estimated Time',
    'name' => 'estimated_time_minutes',
    'totalMinutes' => 0,
    'errorKey' => null,
    'inputAction' => null,
    'hoursPlaceholder' => 'e.g. 10',
    'minutesPlaceholder' => 'e.g. 30',
    'panel' => false,
    'helpText' => 'Enter time naturally. We’ll convert it automatically for calculation.',
    'showLabel' => true,
    'disabled' => false,
])

@php
    $errorKey = $errorKey ?? $name;
    $normalizedTotalMinutes = max(0, (int) ($totalMinutes ?? 0));
    $hours = intdiv($normalizedTotalMinutes, 60);
    $minutes = $normalizedTotalMinutes % 60;
    $inputClasses = $errors->has($errorKey) ? 'border-b-alertsErrorBase' : 'border-gray-300 dark:border-darkblack-400';
    $inputEvents = $inputAction ? 'x-on:input="' . $inputAction . '" x-on:change="' . $inputAction . '"' : '';
@endphp

<div class="flex flex-col gap-2" data-estimated-time>
    @if ($showLabel)
        <label class="text-base font-medium text-bgray-600 dark:text-bgray-50">
            {{ $label }}
        </label>
    @endif

    <input type="hidden" name="{{ $name }}" value="{{ $normalizedTotalMinutes }}" data-estimated-total-minutes>

    <div class="{{ $panel ? 'rounded-2xl border border-bgray-200 bg-bgray-50/80 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/70' : '' }}">
        <div class="grid grid-cols-2 gap-3">
            <div class="{{ $panel ? 'rounded-xl border border-bgray-200 bg-white p-3 dark:border-darkblack-400 dark:bg-darkblack-600' : '' }}">
                <label class="mb-2 block text-left text-xs font-medium uppercase tracking-[0.15em] text-bgray-700 dark:text-bgray-300">Hours</label>
                <input type="number" min="0" step="1" value="{{ $hours }}" placeholder="{{ $hoursPlaceholder }}" data-estimated-hours class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white {{ $inputClasses }}" @disabled($disabled) {!! $inputEvents !!}>
            </div>

            <div class="{{ $panel ? 'rounded-xl border border-bgray-200 bg-white p-3 dark:border-darkblack-400 dark:bg-darkblack-600' : '' }}">
                <label class="mb-2 block text-left text-xs font-medium uppercase tracking-[0.15em] text-bgray-700 dark:text-bgray-300">Minutes</label>
                <input type="number" min="0" step="1" value="{{ $minutes }}" placeholder="{{ $minutesPlaceholder }}" data-estimated-extra-minutes class="w-full rounded-lg border p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white {{ $inputClasses }}" @disabled($disabled) {!! $inputEvents !!}>
            </div>
        </div>

        @if ($helpText)
            <p class="mt-3 text-xs text-bgray-700 dark:text-bgray-300">{{ $helpText }}</p>
        @endif
    </div>

    @if ($errors->has($errorKey))
        <p class="text-sm text-error-300">{{ $errors->first($errorKey) }}</p>
    @endif
</div>
