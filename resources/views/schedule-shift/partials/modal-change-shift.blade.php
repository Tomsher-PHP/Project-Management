<div id="shiftModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur-sm z-50">
    <div class="bg-white dark:bg-darkblack-600 rounded-xl p-6 w-96 shadow-xl border border-gray-200 dark:border-darkblack-500">

        <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white">
            Assign Shift
        </h3>

        <div class="text-sm text-gray-600 dark:text-gray-300 mb-4">
            <div><strong>User:</strong> <span id="modalUserName"></span></div>
            <div><strong>Date:</strong> <span id="modalDate"></span></div>
        </div>

        <select id="modalShiftSelect" class="tom-select w-full border rounded p-2 mb-4">
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
