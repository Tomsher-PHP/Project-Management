<div class="relative column-manager" data-report="{{ $report }}">
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300 cm-btn">
        <span class="inline-flex items-center justify-center text-current">
            <!-- Icon (columns / settings style) -->
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">

                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </span>
        <span class="text-sm font-semibold">
            Customize Columns
        </span>
    </button>

    <div
        class="cm-panel hidden absolute right-0 mt-3 w-72 rounded-xl border border-bgray-200 bg-white p-4 shadow-lg
           dark:border-darkblack-400 dark:bg-darkblack-500 z-50">
        <!-- Header Actions -->
        <div class="flex items-center justify-between mb-3">
            <button type="button"
                class="cm-select-all text-xs font-semibold text-success-400 hover:underline">
                Select All
            </button>
            <button type="button"
                class="cm-reset text-xs font-semibold text-red-500 hover:underline">
                Reset
            </button>
        </div>

        <hr class="border-bgray-200 dark:border-darkblack-400 mb-3">
        <!-- Column List -->
        <div class="max-h-60 overflow-y-auto space-y-2 pr-1">
            @foreach($columns as $key => $label)
                @if($key !== 'actions')
                <label
                    class="flex items-center gap-2 rounded-md px-2 py-1 text-sm text-bgray-700
                            hover:bg-bgray-100 dark:text-bgray-50 dark:hover:bg-darkblack-400 cursor-pointer">
                    <input
                        type="checkbox"
                        class="cm-toggle accent-success-400"
                        data-column="{{ $key }}"
                        checked>

                    <span class="select-none">
                        {{ $label }}
                    </span>
                </label>
                @endif
            @endforeach
        </div>
    </div>
</div>