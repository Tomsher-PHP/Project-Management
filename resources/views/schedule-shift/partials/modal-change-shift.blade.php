<div id="shiftModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white dark:bg-darkblack-600 rounded-lg p-6 w-96">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Select Shift</h3>

        <select id="modalShiftSelect" class="select-subtypes w-full border rounded p-2 mb-4">
            <option value="">Select Shift</option>
            @foreach ($shifts as $shiftOption)
                <option value="{{ $shiftOption->id }}" data-subtype="{{ $shiftOption->time_from_formatted . ' - ' . $shiftOption->time_to_formatted }}">
                    {{ $shiftOption->name }}
                </option>
            @endforeach
        </select>

        <div class="flex justify-end gap-2">
            <button id="modalCancel" class="px-4 py-2 rounded bg-gray-200 dark:bg-darkblack-500 hover:bg-gray-300 dark:hover:bg-darkblack-400">Cancel</button>
            <button id="modalSave" class="px-4 py-2 rounded bg-success-300 text-white hover:bg-success-400">Save</button>
        </div>
    </div>
</div>
