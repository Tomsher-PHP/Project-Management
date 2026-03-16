<div>
    <label class="text-sm font-medium text-bgray-600 dark:text-bgray-50">
        {{ $label }}
    </label>

    <select name="{{ $name }}" class="tom-select w-full" data-sort="0">
        <option value="" {{ request()->filled($name) ? '' : 'selected' }}>All</option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" {{ request($name) == (string) $value ? 'selected' : '' }}>{{ $text }}</option>
        @endforeach
    </select>
</div>
