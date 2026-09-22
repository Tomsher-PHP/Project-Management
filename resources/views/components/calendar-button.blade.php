@props(['action' => '#'])
<a
href="{{ $action }}"
{{ $attributes->merge([
'class' => 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300 group',
'title' => 'Leave Details',
]) }}>
<svg
    class="h-4 w-4"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    viewBox="0 0 24 24">

    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M7 3V5M17 3V5M4 9H20M5 5H19C20.1 5 21 5.9 21 7V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V7C3 5.9 3.9 5 5 5Z"/>

    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M8 13H10M14 13H16M8 17H10M14 17H16"/>
</svg>
</a>
