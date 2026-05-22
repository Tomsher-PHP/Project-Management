<div class="modal fixed inset-0 z-[80] hidden items-center justify-center overflow-y-auto" data-break-work-request-modal role="dialog" aria-modal="true" aria-hidden="true">
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-break-work-request-close></div>

    <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
        <div class="relative z-10 w-full max-w-lg transition-all duration-200">
            <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Request Break Time as Work</h3>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-break-work-request-close>
                        ✕
                    </button>
                </div>

                <form action="{{ route('break-work-requests.store') }}" method="POST" class="space-y-4 overflow-y-auto px-5 py-5" data-break-work-request-form data-store-url="{{ route('break-work-requests.store') }}">
                    @csrf

                    <input type="hidden" name="work_date" value="" data-break-work-request-work-date>
                    <input type="hidden" name="original_break_start" value="" data-break-work-request-original-start>
                    <input type="hidden" name="original_break_end" value="" data-break-work-request-original-end>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Date</label>
                            <div class="rounded-lg border border-gray-200 bg-bgray-50 px-3 py-2.5 text-sm font-medium text-bgray-800 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100" data-break-work-request-date-label>
                                --
                            </div>
                            <p class="mt-1 hidden text-xs text-red-500" data-break-work-request-error="work_date"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Start Time <x-red-star /></label>
                            <input type="text" name="start_time" value="" class="timepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="24" data-enable-seconds="true" data-break-work-request-time data-break-work-request-start-time placeholder="HH:MM:SS" autocomplete="off">
                            <p class="mt-1 hidden text-xs text-red-500" data-break-work-request-error="start_time"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">End Time <x-red-star /></label>
                            <input type="text" name="end_time" value="" class="timepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="24" data-enable-seconds="true" data-break-work-request-time data-break-work-request-end-time placeholder="HH:MM:SS" autocomplete="off">
                            <p class="mt-1 hidden text-xs text-red-500" data-break-work-request-error="end_time"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Description <x-red-star /></label>
                            <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-break-work-request-description placeholder="Describe the work completed during this break" required></textarea>
                            <p class="mt-1 hidden text-xs text-red-500" data-break-work-request-error="description"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-darkblack-300" data-break-work-request-close>
                            Cancel
                        </button>

                        <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-break-work-request-submit>
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
