
<div id="reopen-task-remark-modal"
     class="modal fixed inset-0 z-[10000] hidden items-center justify-center overflow-y-auto">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <!-- Modal wrapper -->
    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-lg">

            <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                <!-- Header -->
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">
                        Reopen Task
                    </h3>

                    <button type="button"
                            id="close-reopen-task"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300">
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="px-5 py-5">
                    <div>
                        <label for="reopen-task-remark-input"
                               class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                            Reason for reopening <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            id="reopen-task-remark-input"
                            rows="4"
                            class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-700 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder:text-bgray-500"
                            placeholder="Enter the reason for reopening this task..."
                        ></textarea>

                        <p id="reopen-task-remark-error"
                           class="mt-2 hidden text-xs text-red-500">
                            A remark is required to reopen this task.
                        </p>
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-5 py-4 dark:border-darkblack-400">

                    <button type="button"
                            id="cancel-reopen-task"
                            class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300">
                        Cancel
                    </button>

                    <button type="button"
                            id="confirm-reopen-task"
                            class="inline-flex items-center justify-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60">
                        Reopen Task
                    </button>

                </div>

            </div>
        </div>
    </div>
</div>
