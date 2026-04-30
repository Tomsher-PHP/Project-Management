<div id="project-payment-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" data-default-date="{{ now(config('constants.timezone'))->toDateString() }}">
    <div class="absolute inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-payment-modal-close></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-2xl">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 class="text-2xl font-semibold text-bgray-900 dark:text-white">Project Payment Status</h3>
                        <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                            Add the latest payment details to refresh this project's payment status.
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-payment-modal-close>
                        ✕
                    </button>
                </div>

                <form class="flex flex-col" data-project-payment-form>
                    <div class="grid gap-5 px-6 py-6 sm:grid-cols-2 sm:px-7">
                        <div>
                            <label for="project_payment_amount" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Amount</label>
                            <input type="number" step="0.01" min="0" id="project_payment_amount" name="amount" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Enter amount">
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="amount"></p>
                        </div>

                        <div>
                            <label for="project_payment_paid_date" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Paid Date</label>
                            <input type="text" id="project_payment_paid_date" name="paid_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="{{ $globalDateFormat }}" placeholder="Select paid date" autocomplete="off">
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="paid_date"></p>
                        </div>

                        <div>
                            <label for="project_payment_coverage_start_date" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Coverage Start Date <x-red-star /></label>
                            <input type="text" id="project_payment_coverage_start_date" name="coverage_start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="{{ $globalDateFormat }}" placeholder="Select start date" autocomplete="off" required>
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="coverage_start_date"></p>
                        </div>

                        <div>
                            <label for="project_payment_coverage_end_date" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Coverage End Date <x-red-star /></label>
                            <input type="text" id="project_payment_coverage_end_date" name="coverage_end_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="{{ $globalDateFormat }}" placeholder="Select end date" autocomplete="off" required>
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="coverage_end_date"></p>
                        </div>

                        {{-- <div>
                            <label for="project_payment_method" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Payment Method</label>
                            <input type="text" id="project_payment_method" name="payment_method" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Cash, bank transfer, card">
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="payment_method"></p>
                        </div> --}}

                        {{-- <div>
                            <label for="project_payment_reference" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Reference</label>
                            <input type="text" id="project_payment_reference" name="reference" maxlength="150" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Transaction ID or note">
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="reference"></p>
                        </div> --}}

                        <div class="sm:col-span-2">
                            <label for="project_payment_notes" class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Notes</label>
                            <textarea id="project_payment_notes" name="notes" rows="4" maxlength="500" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add any payment details or remarks"></textarea>
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-payment-error-for="notes"></p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="rounded-lg border border-bgray-300 bg-white px-6 py-3 text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-payment-modal-close>
                            Cancel
                        </button>

                        <button type="submit" class="rounded-lg bg-success-300 px-6 py-3 text-white transition duration-200 hover:bg-success-400" data-project-payment-submit>
                            Save Payment Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
