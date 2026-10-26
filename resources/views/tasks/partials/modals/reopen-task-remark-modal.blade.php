
<div id="reopen-task-remark-modal"
     class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/50 p-4">

    <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Reopen Task
        </h3>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            Please explain why this completed task is being moved
            to an incomplete status.
        </p>

        <textarea
            id="reopen-task-remark-input"
            rows="4"
            class="mt-4 w-full rounded-md border border-gray-300 bg-white p-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            placeholder="Enter the reason for reopening this task..."
        ></textarea>

        <p id="reopen-task-remark-error"
           class="mt-2 hidden text-sm text-red-600">
            A remark is required to reopen this task.
        </p>

        <div class="mt-5 flex justify-end gap-3">
            <button type="button"
                    id="cancel-reopen-task"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-white">
                Cancel
            </button>

            <button type="button"
                    id="confirm-reopen-task"
                    class="inline-flex items-center justify-center rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60">
                Reopen Task
            </button>
        </div>
    </div>
</div>
