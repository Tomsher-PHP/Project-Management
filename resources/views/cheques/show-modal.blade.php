<div id="cheque_show_modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:p-6 md:p-10 flex items-center justify-center">
    <div class="relative w-full max-w-3xl rounded-[8px] bg-white shadow-xl dark:bg-darkblack-600 my-8">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">
                Cheque Expense Details
            </h3>
            <button type="button" data-cheque-show-modal-close class="rounded-lg p-1.5 text-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="max-h-[75vh] overflow-y-auto p-6 space-y-4">
            <div id="cheque_show_modal_content">
                <div class="flex items-center justify-center py-12 text-bgray-500">
                    <svg class="animate-spin h-8 w-8 text-success-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <button type="button" data-cheque-show-modal-close class="rounded-lg border border-bgray-300 px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500 transition">
                Close
            </button>
        </div>

    </div>
</div>
