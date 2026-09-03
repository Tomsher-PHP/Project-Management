<div id="task-notes-modal" class="fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto px-4 py-6">
    <div data-task-notes-modal-overlay class="fixed inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-60"></div>

    <div class="relative flex min-h-[300px] max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-[12px] bg-white shadow-2xl dark:border dark:border-darkblack-400 dark:bg-darkblack-600">
        <div id="task-notes-modal-content" class="flex min-h-[300px] flex-1 flex-col overflow-hidden">
            <div class="flex min-h-[300px] flex-1 items-center justify-center px-6 py-10">
                <div class="text-center">
                    <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-bgray-200 border-t-success-300 dark:border-darkblack-400 dark:border-t-success-300"></div>
                    <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">Loading Notes & Files...</p>
                </div>
            </div>
        </div>
    </div>

    <template id="task-notes-loading-template">
        <div class="flex min-h-[300px] flex-1 items-center justify-center px-6 py-10">
            <div class="text-center">
                <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-bgray-200 border-t-success-300 dark:border-darkblack-400 dark:border-t-success-300"></div>
                <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">Loading Notes & Files...</p>
            </div>
        </div>
    </template>

    <template id="task-notes-error-template">
        <div class="flex min-h-[250px] flex-col items-center justify-center p-6 text-center">
            <p class="text-sm font-semibold text-red-500" data-task-notes-error-message>Unable to load notes & files.</p>
            <button type="button" data-task-notes-modal-close class="mt-4 rounded-lg bg-bgray-100 px-4 py-2 text-xs font-semibold text-bgray-700 hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                Close
            </button>
        </div>
    </template>
</div>
