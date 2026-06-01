<div class="flex flex-col gap-1">
    @if (isset($label))
        <label class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center gap-2">
        <input type="text" name="{{ $startName ?? 'start_date' }}" value="{{ request($startName ?? 'start_date') }}" class="datepicker w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-format="Y-m-d" placeholder="Select start date" autocomplete="off">

        <span class="text-sm text-bgray-700 dark:text-bgray-300">to</span>

        <input type="text" name="{{ $endName ?? 'end_date' }}" value="{{ request($endName ?? 'end_date') }}" class="datepicker w-full rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-format="Y-m-d" placeholder="Select end date" autocomplete="off">
    </div>
</div>
