@php
    $billingCycles = $formOptions['billingCycles'] ?? [];
    $defaultCurrency = $formOptions['defaultCurrency'] ?? config('constants.currency', 'AED');
    $currencyOption = $formOptions['currencyOption'] ?? null;
    $selectedCurrency = old('currency_code', $defaultCurrency);
    $selectedCurrencyLabel = isset($currencyOption) && $currencyOption['id'] === $selectedCurrency ? $currencyOption['name'] : $selectedCurrency;
@endphp

<div class="modal fixed inset-0 z-[75] hidden items-center justify-center overflow-y-auto" data-project-hosting-renewal-modal>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-hosting-renewal-modal-close></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-2xl" style="max-width: 48rem;">
            <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                <!-- Header -->
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h4 class="text-xl font-semibold text-bgray-900 dark:text-white">
                            Renew Hosting Service
                        </h4>
                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                            Process renewal for <span class="font-semibold text-bgray-900 dark:text-white" data-renewal-record-name></span>
                            <span class="text-xs text-success-400" data-renewal-record-domain></span>
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-hosting-renewal-modal-close aria-label="Close">
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7">
                    <form id="project-hosting-renewal-form" method="POST" action="" data-project-hosting-renewal-form>
                        @csrf
                        <input type="hidden" name="hosting_id" value="" data-renewal-hosting-id>

                        <div class="space-y-5">

                            <!-- Current Expiry Date Notice (Read-only) -->
                            <div class="rounded-lg border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/50">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-400">
                                        Current Expiry Date
                                    </span>
                                    <span class="text-sm font-bold text-bgray-900 dark:text-white" data-renewal-current-expiry>
                                        —
                                    </span>
                                </div>
                            </div>

                            <!-- Renewal Date & New Expiry Date -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Renewal Date <span class="text-error-300">*</span>
                                    </label>
                                    <input type="date" id="renewal_date" name="renewal_date" required class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-renewal-field="renewal_date" value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                                    <p data-renewal-error="renewal_date" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        New Expiry Date <span class="text-error-300">*</span>
                                    </label>
                                    <input type="date" id="renewal_new_expiry_date" name="new_expiry_date" required class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-renewal-field="new_expiry_date" autocomplete="off">
                                    <p data-renewal-error="new_expiry_date" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Optional New Start Date & Billing Cycle -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        New Start Date <span class="text-xs text-bgray-500">(Optional)</span>
                                    </label>
                                    <input type="date" id="renewal_new_start_date" name="new_start_date" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-renewal-field="new_start_date" autocomplete="off">
                                    <p data-renewal-error="new_start_date" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Billing Cycle
                                    </label>
                                    <select id="renewal_billing_cycle" name="hosting_billing_cycle_id" class="tom-select w-full" data-renewal-field="hosting_billing_cycle_id">
                                        <option value="">Select Billing Cycle</option>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                    <p data-renewal-error="hosting_billing_cycle_id" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Billing Amount, Currency & Reference -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Billing Amount
                                    </label>
                                    <input type="number" step="0.01" min="0" max="9999999999.99" id="renewal_billing_amount" name="billing_amount" placeholder="0.00" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-renewal-field="billing_amount">
                                    <p data-renewal-error="billing_amount" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Currency
                                    </label>
                                    <select id="renewal_currency_code" name="currency_code" class="tom-select w-full" data-renewal-field="currency_code">
                                        <option value="{{ $selectedCurrency }}">{{ $selectedCurrencyLabel }}</option>
                                    </select>
                                    <p data-renewal-error="currency_code" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Reference / Invoice #
                                    </label>
                                    <input type="text" id="renewal_reference" name="reference" maxlength="255" placeholder="e.g. INV-2026-001" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-renewal-field="reference">
                                    <p data-renewal-error="reference" class="mt-1 hidden text-xs text-error-300"></p>
                                </div>
                            </div>

                            <!-- Renewal Notes -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                    Renewal Notes
                                </label>
                                <textarea id="renewal_notes" name="notes" rows="3" placeholder="Optional notes about this renewal transaction..." class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-renewal-field="notes"></textarea>
                                <p data-renewal-error="notes" class="mt-1 hidden text-xs text-error-300"></p>
                            </div>

                        </div>

                        <!-- Footer -->
                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-bgray-200 pt-5 dark:border-darkblack-400">
                            <button type="button" class="rounded-lg border border-bgray-300 bg-white px-5 py-2.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-renewal-modal-close>
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-project-hosting-renewal-submit>
                                <span>Confirm Renewal</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
