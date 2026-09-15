<div id="reschedule_modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:p-6 md:p-10 flex items-center justify-center">
    <div class="relative w-full max-w-lg rounded-[8px] bg-white shadow-xl dark:bg-darkblack-600">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white flex items-center gap-2">
                <svg class="h-5 w-5 text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Reschedule Meeting
            </h3>
            <button type="button" data-reschedule-modal-close class="rounded-lg p-1.5 text-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-4">

            <!-- Current Meeting Date & Time Display -->
            <div class="rounded-lg border border-bgray-200 bg-bgray-50/50 p-3.5 dark:border-darkblack-400 dark:bg-darkblack-500/50">
                <span class="block text-xs font-semibold text-bgray-700 dark:text-bgray-300">Current:</span>
                <span id="reschedule_modal_current_time" class="text-sm font-bold text-bgray-900 dark:text-white">
                    --
                </span>
            </div>

            <!-- New Start Date & Time Input -->
            <div>
                <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                    New Start Date & Time <x-red-star />
                </label>
                <input type="text" id="reschedule_new_start_at" required data-enable-time="true" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD HH:MM">
                <p id="reschedule_start_at_error" class="mt-1 text-xs text-red-500 hidden"></p>
            </div>

            <!-- Reason Input -->
            <div>
                <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                    Reason <x-red-star />
                </label>
                <textarea id="reschedule_reason_input" required rows="3" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Please enter reason for rescheduling..."></textarea>
                <p id="reschedule_reason_error" class="mt-1 text-xs text-red-500 hidden"></p>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <button type="button" data-reschedule-modal-close class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                Cancel
            </button>
            <button type="button" id="reschedule_continue_btn" class="rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                Continue
            </button>
        </div>

    </div>
</div>
