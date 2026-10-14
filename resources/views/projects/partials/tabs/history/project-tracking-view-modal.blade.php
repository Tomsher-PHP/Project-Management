<!-- Project Tracking View Modal -->
<div class="modal fixed inset-0 z-[80] hidden items-center justify-center overflow-y-auto" data-project-tracking-view-modal>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-tracking-view-modal-close></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-3xl">
            <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                <!-- Header -->
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h4 class="text-xl font-semibold text-bgray-900 dark:text-white">
                            Project Tracking
                        </h4>

                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                            Tracking details
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-bgray-500 transition hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white" data-project-tracking-view-modal-close aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7" data-project-tracking-view-content>
                    <div class="flex items-center justify-center py-12">
                        <span class="text-sm text-bgray-500 dark:text-bgray-400">
                            Loading...
                        </span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <button type="button" class="rounded-lg border border-bgray-300 bg-white px-5 py-2.5 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-tracking-view-modal-close>
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
