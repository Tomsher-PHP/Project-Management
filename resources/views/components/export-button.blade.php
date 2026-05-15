<a
    href="{{ $href }}"
    class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300"
    aria-label="Export report"
>
    <span class="inline-flex items-center justify-center text-current">
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
                d="M12 16V4m0 12l-4-4m4 4l4-4"
            />

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4 20h16"
            />
        </svg>
    </span>

    <span class="text-sm font-semibold">
        {{ $label ?? 'Export Excel' }}
    </span>
</a>