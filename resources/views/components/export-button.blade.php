@props([
    'action' => '#',
    'method' => 'GET',
    'label' => 'Export Excel',
    'show' => true,
    'params' => [],
    'hiddenFields' => [],
    'buttonClass' => '',
    'ariaLabel' => 'Export report',
])

@php
    $buttonClasses = 'inline-flex items-center gap-2 rounded-lg border border-bgray-500 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300';
    $normalizedMethod = strtoupper($method);
    $formMethod = in_array($normalizedMethod, ['GET', 'POST'], true) ? $normalizedMethod : 'POST';

    $renderHiddenInput = function ($name, $value) use (&$renderHiddenInput) {
        if (is_array($value)) {
            $isList = array_is_list($value);

            foreach ($value as $nestedKey => $nestedValue) {
                $nextName = $name . ($isList ? '[]' : '[' . $nestedKey . ']');
                $renderHiddenInput($nextName, $nestedValue);
            }

            return;
        }

        echo '<input type="hidden" name="' . e($name) . '" value="' . e((string) $value) . '">';
    };
@endphp

@if ($show)
    <form method="{{ $formMethod }}" action="{{ $action }}" {{ $attributes->class(['inline-flex']) }}>
        @if ($formMethod !== 'GET')
            @csrf
        @endif

        @if (!in_array($normalizedMethod, ['GET', 'POST'], true))
            @method($normalizedMethod)
        @endif

        @foreach ($params as $key => $value)
            @php
                $renderHiddenInput($key, $value);
            @endphp
        @endforeach

        @foreach ($hiddenFields as $key => $value)
            @php
                $renderHiddenInput($key, $value);
            @endphp
        @endforeach

        {{ $slot }}

        <button type="submit" class="{{ trim($buttonClasses . ' ' . $buttonClass) }}" aria-label="{{ $ariaLabel }}">
            <span class="inline-flex items-center justify-center text-current">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                </svg>
            </span>

            <span class="text-sm font-semibold">
                {{ $label }}
            </span>
        </button>
    </form>
@endif
