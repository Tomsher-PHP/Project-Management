@php
    $rowIndex = $index ?? 0;
    $rowFieldName = $fieldName ?? 'emergency_contacts';
    $rowIdPrefix = $idPrefix ?? 'modal_contact';
    $rowContact = $contact ?? [];
    $rowNumberPlaceholder = $numberPlaceholder ?? 'e.g. +971501234567';
    $rowRelationPlaceholder = $relationPlaceholder ?? 'e.g. Father, Spouse';
@endphp

<div class="modal-contact-row border border-gray-200 dark:border-darkblack-400 rounded-lg p-4" data-index="{{ $rowIndex }}">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
        <div class="col-contact-name md:col-span-4 flex flex-col gap-2">
            <label for="{{ $rowIdPrefix }}_name_{{ $rowIndex }}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Contact Name
            </label>
            <input type="text"
                   id="{{ $rowIdPrefix }}_name_{{ $rowIndex }}"
                   name="{{ $rowFieldName }}[{{ $rowIndex }}][name]"
                   value="{{ $rowContact['name'] ?? '' }}"
                   placeholder="Enter contact name"
                   class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div class="col-contact-number md:col-span-4 flex flex-col gap-2">
            <label for="{{ $rowIdPrefix }}_number_{{ $rowIndex }}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Contact Number
            </label>
            <input type="text"
                   id="{{ $rowIdPrefix }}_number_{{ $rowIndex }}"
                   name="{{ $rowFieldName }}[{{ $rowIndex }}][number]"
                   value="{{ $rowContact['number'] ?? '' }}"
                   placeholder="{{ $rowNumberPlaceholder }}"
                   class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div class="col-contact-relation md:col-span-3 flex flex-col gap-2">
            <label for="{{ $rowIdPrefix }}_relation_{{ $rowIndex }}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                Relation
            </label>
            <input type="text"
                   id="{{ $rowIdPrefix }}_relation_{{ $rowIndex }}"
                   name="{{ $rowFieldName }}[{{ $rowIndex }}][relation]"
                   value="{{ $rowContact['relation'] ?? '' }}"
                   placeholder="{{ $rowRelationPlaceholder }}"
                   class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div class="md:col-span-1 flex items-center justify-end md:justify-center pt-8">
            <button type="button" class="btn-remove-contact inline-flex h-[42px] w-[42px] items-center justify-center rounded-lg border border-error-200 bg-error-50 text-error-300 hover:bg-error-100 hover:border-error-300 transition" title="Remove Contact" aria-label="Remove contact">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</div>
