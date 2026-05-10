@php
    $selectedValues = collect(request($name, []))
        ->flatten()
        ->filter(fn($value) => filled($value))
        ->map(fn($value) => (string) $value)
        ->all();
@endphp

<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </label>
    <select name="{{ $name }}[]" class="tom-select-multiple w-full" multiple data-sort="0">
        <option value="">Select {{ $label }}</option>
        @foreach ($options as $value)
            <option value="{{ $value->id }}" {{ in_array((string) $value->id, $selectedValues, true) ? 'selected' : '' }}>
                {{ $value->name }}
            </option>
        @endforeach
    </select>
</div>
