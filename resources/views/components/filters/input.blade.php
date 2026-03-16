<div>
    <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </label>

    <input type="text" name="{{ $name }}" value="{{ request($name) }}" class="w-full mt-1 border rounded-md px-3 py-2">
</div>
