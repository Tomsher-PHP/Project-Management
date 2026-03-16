<div>
    <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </label>

    <select name="{{ $name }}[]" class="tom-select-multiple w-full" multiple data-sort="0">
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" {{ in_array((string) $value, request($name, [])) ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>
</div>
