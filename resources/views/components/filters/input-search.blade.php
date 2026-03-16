<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </label>

    <div class="flex mt-1">
        <select name="{{ $name }}_condition" class="w-[140px] rounded-lg border border-gray-300 p-1 focus:border-success-300 focus:ring-0
                   bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            <option value="all" {{ request($name . '_condition') == 'all' ? 'selected' : '' }}>All</option>
            <option value="starts_with" {{ request($name . '_condition') == 'starts_with' ? 'selected' : '' }}>Starts With
            </option>
            <option value="ends_with" {{ request($name . '_condition') == 'ends_with' ? 'selected' : '' }}>Ends With
            </option>
            <option value="contains" {{ request($name . '_condition') == 'contains' ? 'selected' : '' }}>Contains</option>
            <option value="not_contains" {{ request($name . '_condition') == 'not_contains' ? 'selected' : '' }}>Not Contains</option>
        </select>

        <input type="text" name="{{ $name }}" value="{{ request($name) }}" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0
                    bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400" placeholder="Search {{ $label }}">
    </div>
</div>
