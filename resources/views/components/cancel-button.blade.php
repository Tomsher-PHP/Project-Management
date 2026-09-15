@props(['action' => '#'])

<button
type="button"
{{ $attributes->merge([
'class' => 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-danger-300 dark:hover:bg-darkblack-400 dark:hover:text-danger-300 group',
]) }}
title="Cancel"
aria-label="Cancel"

>
<svg
    xmlns="http://www.w3.org/2000/svg"
    class="h-4 w-4"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    stroke-width="2"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M6 6l12 12M18 6L6 18"
    />
</svg>
</button>
