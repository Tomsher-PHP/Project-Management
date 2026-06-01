<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-bgray-700 dark:text-bgray-50">
        {{ $label }}
    </label>

    <input type="text" name="{{ $name }}" value="{{ request($name) }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" placeholder="Search {{ $label }}">
</div>
