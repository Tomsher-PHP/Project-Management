<div class="modal fixed inset-0 z-[85] hidden items-center justify-center overflow-y-auto" data-project-hosting-status-history-modal role="dialog" aria-modal="true" aria-labelledby="project-hosting-status-history-title">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-hosting-status-history-modal-close></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-3xl" style="max-width: 48rem;">
            <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                <!-- Header -->
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h4 id="project-hosting-status-history-title" class="text-xl font-semibold text-bgray-900 dark:text-white">
                            Status History
                        </h4>
                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                            Service status timeline for <span class="font-semibold text-bgray-900 dark:text-white" data-status-history-record-name>Hosting Service</span>
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-hosting-status-history-modal-close aria-label="Close">
                        ✕
                    </button>
                </div>

                <!-- Body (AJAX loaded) -->
                <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-7" data-project-hosting-status-history-body>
                    <div class="flex flex-col items-center justify-center py-16 text-center" data-project-hosting-status-history-loading>
                        <svg class="h-8 w-8 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="mt-3 text-xs font-medium text-bgray-600 dark:text-bgray-300">Loading status history...</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7 bg-bgray-50/50 dark:bg-darkblack-500/30">
                    <button type="button" class="rounded-lg border border-bgray-300 bg-white px-5 py-2 text-xs font-semibold text-bgray-700 transition duration-200 hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-status-history-modal-close>
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
