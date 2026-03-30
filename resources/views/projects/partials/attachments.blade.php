@php
    $canCreate = auth()->user()->can('project.add_notes_files');
    $canRemove = auth()->user()->can('project.remove_notes_files');
@endphp

<div class="w-full border-b border-bgray-200 pb-6 dark:border-darkblack-400">
    <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Notes & Files</h3>

    <div class="mt-6 space-y-6">
        @if ($canCreate)
            <div>
                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                    Notes
                </label>

                <div class="custom-quill">
                    <div class="h-60 min-h-[100px] rounded-b-lg bg-white dark:bg-darkblack-500" id="project-note"></div>
                </div>
            </div>

            <div>
                <label for="note-attachments-input" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                    Files
                </label>

                    <input
                    type="file"
                    id="note-attachments-input"
                    multiple
                    class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-success-50 file:px-4 file:py-2 file:font-medium file:text-success-400 hover:file:bg-success-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                    accept=".pdf,.xls,.xlsx,.doc,.docx,.jpg,.jpeg,.png"
                >
                <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                    You can attach multiple files. Allowed types: pdf, xls, xlsx, doc, docx, jpg, jpeg, png. Max file size: 5MB.
                </p>
                <div id="selected-note-files" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            <div>
                <button type="button" id="saveProjectNote" class="rounded-lg bg-success-300 px-5 py-2 font-semibold text-white hover:bg-success-400">
                    Save
                </button>
            </div>
        @else
            <p class="rounded-lg border border-dashed border-bgray-300 px-4 py-3 text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                You have view-only access to project notes.
            </p>
        @endif
    </div>
</div>

<div class="w-full pt-6">
    @include('projects.partials.project-notes-list', ['projectNotes' => $projectNotes, 'canRemove' => $canRemove])
</div>
