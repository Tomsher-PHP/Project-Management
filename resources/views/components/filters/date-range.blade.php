<div class="flex flex-col gap-1">

    @if(isset($label))
        <label class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center gap-2">

        <input
            type="date"
            name="{{ $startName ?? 'start_date' }}"
            value="{{ request($startName ?? 'start_date') }}"
            class="w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm
                   dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50"
        >

        <span class="text-bgray-400 text-sm">to</span>

        <input
            type="date"
            name="{{ $endName ?? 'end_date' }}"
            value="{{ request($endName ?? 'end_date') }}"
            class="w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm
                   dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50"
        >

    </div>

</div>