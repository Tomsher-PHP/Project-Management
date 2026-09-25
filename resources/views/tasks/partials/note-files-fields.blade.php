@php
    $noteInputId = $noteInputId ?? 'task_create_note_input';
    $noteEditorId = $noteEditorId ?? 'task_create_note_editor';
    $attachmentsInputId = $attachmentsInputId ?? 'task_create_attachments_input';
    $selectedFilesContainerId = $selectedFilesContainerId ?? 'task_create_selected_files';
    $errorPrefix = $errorPrefix ?? 'task-create';
    $sectionTitle = $sectionTitle ?? 'Note & Files';
    $showTitle = $showTitle ?? true;
@endphp

@can('task.add_notes_files')
    <div class="rounded-[8px] border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
        @if ($showTitle)
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-700 dark:text-bgray-300">{{ $sectionTitle }}</p>
        @endif

        <div class="{{ $showTitle ? 'mt-4' : '' }} space-y-4">
            <div>
                <input type="hidden" name="note" id="{{ $noteInputId }}">
                <div class="custom-quill-wrapper rounded-lg border border-gray-300 dark:border-darkblack-400 overflow-hidden">
                    <div id="{{ $noteEditorId }}" class="h-36 bg-white dark:bg-darkblack-500 dark:text-white"></div>
                </div>
                <p class="mt-1 hidden text-xs text-red-500" data-{{ $errorPrefix }}-error="note"></p>
            </div>

            <div>
                <input type="file" id="{{ $attachmentsInputId }}" name="attachments[]" multiple class="block w-full rounded-lg border border-bgray-300 bg-white px-3 py-2.5 text-sm text-bgray-700 file:mr-3 file:rounded-md file:border-0 file:bg-success-50 file:px-3 file:py-1.5 file:font-medium file:text-success-400 hover:file:bg-success-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" accept=".pdf,.xls,.xlsx,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                <p class="mt-2 text-xs text-bgray-600 dark:text-bgray-300">
                    Allowed types: pdf, xls, xlsx, doc, docx, ppt, pptx, jpg, jpeg, png. Max file size: 1GB per file.
                </p>
                <p class="mt-1 hidden text-xs text-red-500" data-{{ $errorPrefix }}-error="attachments"></p>

                <div id="{{ $selectedFilesContainerId }}" class="mt-3 space-y-2"></div>
            </div>
        </div>
    </div>
@else
    <div class="rounded-[8px] border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-700 dark:text-bgray-300">Note & Files</p>
        <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">You do not have permission to add notes and files to tasks.</p>
    </div>
@endcan
