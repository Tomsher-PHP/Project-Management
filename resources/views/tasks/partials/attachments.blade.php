@php
    $canCreate = auth()->user()->can('update', $task);
    $canRemove = auth()->user()->can('delete', $task);
@endphp

<div class="w-full border-b border-bgray-200 pb-6 dark:border-darkblack-400">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Notes & Files</h3>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                Keep task-specific notes, updates, and related files together.
            </p>
        </div>

        @if ($canCreate)
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400"
                data-task-note-modal-open
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Note & Files</span>
            </button>
        @endif
    </div>

    <div class="mt-6">
        @if (! $canCreate)
            <p class="rounded-lg border border-dashed border-bgray-300 px-4 py-3 text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                You have view-only access to task notes.
            </p>
        @endif
    </div>

    @if ($canCreate)
        <div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-task-note-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-task-note-modal-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-3xl">
                    <div class="overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600">
                        <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                            <div>
                                <h4 class="text-xl font-semibold text-bgray-900 dark:text-white">Add Note & Files</h4>
                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                                    Capture a task update and attach supporting files in one place.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300"
                                data-task-note-modal-close
                            >
                                ✕
                            </button>
                        </div>

                        <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7">
                            <div class="space-y-6">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Notes
                                    </label>

                                    <div class="custom-quill">
                                        <div class="h-60 min-h-[100px] rounded-b-lg bg-white dark:bg-darkblack-500" id="task-note"></div>
                                    </div>
                                </div>

                                <div>
                                    <label for="task-note-attachments-input" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Files
                                    </label>

                                    <input type="file" id="task-note-attachments-input" multiple class="block w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-700 file:mr-4 file:rounded-md file:border-0 file:bg-success-50 file:px-4 file:py-2 file:font-medium file:text-success-400 hover:file:bg-success-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" accept=".pdf,.xls,.xlsx,.doc,.docx,.jpg,.jpeg,.png">
                                    <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                                        You can attach multiple files. Allowed types: pdf, xls, xlsx, doc, docx, jpg, jpeg, png. Max file size: 5MB.
                                    </p>
                                    <div id="selected-task-note-files" class="mt-3 flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                            <button
                                type="button"
                                class="rounded-lg border border-bgray-300 bg-white px-5 py-2 font-semibold text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white"
                                data-task-note-modal-close
                            >
                                Cancel
                            </button>
                            <button type="button" id="saveTaskNote" class="rounded-lg bg-success-300 px-5 py-2 font-semibold text-white transition duration-200 hover:bg-success-400">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="w-full pt-6">
    @include('tasks.partials.task-notes-list', ['task' => $task, 'taskNotes' => $taskNotes, 'canRemove' => $canRemove])
</div>
