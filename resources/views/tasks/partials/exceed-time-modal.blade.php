<div class="modal fixed inset-0 z-[80] hidden items-center justify-center overflow-y-auto" data-exceed-time-modal id="exceed_time_modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-exceed-time-modal-close></div>

    <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
        <div class="relative z-10 w-full max-w-lg transition-all duration-200">
            <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">
                            Request Estimate Time Change
                        </h3>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-exceed-time-modal-close>
                        ✕
                    </button>
                </div>

                <form action="" method="POST" class="space-y-4 overflow-y-auto px-5 py-5" data-exceed-time-form>
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Task</label>
                            <div class="rounded-lg border border-gray-200 bg-bgray-50 px-3 py-2.5 text-sm font-medium text-bgray-800 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-exceed-time-task-name>
                                --
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Current Estimated Time</label>
                            <div class="rounded-lg border border-gray-200 bg-bgray-50 px-3 py-2.5 text-sm font-medium text-bgray-800 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-exceed-time-current-estimate>
                                --
                            </div>
                        </div>

                        <div>
                            <x-forms.estimated-time-input label="New Estimated Time" name="new_estimated_time_minutes" :total-minutes="0" :show-label="false" />
                            {{-- <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">New Estimated Time <x-red-star /></label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-left text-xs font-medium uppercase tracking-[0.15em] text-bgray-700 dark:text-bgray-300">Hours</label>
                                    <input type="number" min="0" step="1" name="hours" placeholder="e.g. 10" class="w-full rounded-lg border p-2.5 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white border-gray-300 dark:border-darkblack-400" required>
                                    <p class="mt-1 hidden text-xs text-red-500" data-exceed-time-error="hours"></p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-left text-xs font-medium uppercase tracking-[0.15em] text-bgray-700 dark:text-bgray-300">Minutes</label>
                                    <input type="number" min="0" max="59" step="1" name="minutes" placeholder="e.g. 30" class="w-full rounded-lg border p-2.5 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white border-gray-300 dark:border-darkblack-400" required>
                                    <p class="mt-1 hidden text-xs text-red-500" data-exceed-time-error="minutes"></p>
                                </div>
                            </div> --}}
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Note / Reason</label>
                            <textarea name="reason" rows="3" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Describe why you need more/less time (optional)"></textarea>
                            <p class="mt-1 hidden text-xs text-red-500" data-exceed-time-error="reason"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-bgray-200 dark:border-darkblack-400">
                        <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300" data-exceed-time-modal-close>
                            Cancel
                        </button>

                        <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-exceed-time-submit>
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
