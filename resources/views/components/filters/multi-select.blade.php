<div>
    <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </label>

    <select name="{{ $name }}[]" class="tom-select-multiple w-full" multiple data-sort="0">
        <option value="">Select {{ $label }}</option>
        @foreach ($options as $value)
            <option value="{{ $value->id }}" {{ in_array((string) $value->id, request($name, [])) ? 'selected' : '' }}>
                {{ $value->name }}
            </option>
        @endforeach
    </select>
</div>
