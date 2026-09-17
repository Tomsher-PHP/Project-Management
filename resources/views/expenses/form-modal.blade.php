@php
    $payment_modes = $payment_modes ?? \App\Models\ExpensePaymentMode::active()->orderBy('sort_order')->get();
    $service_providers = $service_providers ?? \App\Models\ExpenseServiceProvider::active()->orderBy('sort_order')->get();
    $categories = $categories ?? \App\Models\ExpenseCategory::active()->orderBy('sort_order')->get();
    $customers = $customers ?? \App\Models\Customer::where('is_active', true)->orderBy('name')->get();
    $vat_payment_options = $vat_payment_options ?? \App\Models\Expense::VAT_PAYMENT_OPTIONS;
    $local_intl_options = $local_intl_options ?? \App\Models\Expense::LOCAL_INTL_OPTIONS;
@endphp

<div id="expense_modal" class="fixed inset-0 z-50 {{ $errors->any() ? '' : 'hidden' }} overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:p-6 md:p-10 flex items-center justify-center">
    <div class="relative w-full max-w-4xl rounded-[8px] bg-white shadow-xl dark:bg-darkblack-600 my-8">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <h3 id="expense_modal_title" class="text-lg font-bold text-bgray-900 dark:text-white">
                Add New Expense
            </h3>
            <button type="button" data-expense-modal-close class="rounded-lg p-1.5 text-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body / Form -->
        <form id="expense_form" method="POST" action="{{ route('expenses.store') }}" data-create-url="{{ route('expenses.store') }}">
            @csrf
            <input type="hidden" name="_method" id="expense_form_method" value="POST">
            <input type="hidden" name="expense_id" id="expense_id_input" value="">

            <div class="max-h-[75vh] overflow-y-auto p-6 space-y-1">

                <!-- Dynamic Error Container -->
                <div id="expense_form_errors" class="hidden rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-darkblack-500 dark:text-red-400 border border-red-200 dark:border-red-800">
                    <div class="font-bold mb-1">Please fix the following validation errors:</div>
                    <ul id="expense_errors_list" class="list-disc list-inside space-y-1"></ul>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-darkblack-500 dark:text-red-400 border border-red-200 dark:border-red-800">
                        <div class="font-bold mb-1">Please fix the following validation errors:</div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- SECTION 1: Payment Information -->
                <div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Payment Mode -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Payment Mode <x-red-star />
                            </label>
                            <select name="payment_mode_id" id="expense_payment_mode_id" required class="tom-select w-full">
                                <option value="">Select Payment Mode</option>
                                @foreach ($payment_modes as $mode)
                                    <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- VAT Payment -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                VAT Payment <x-red-star />
                            </label>
                            <select name="vat_payment" id="expense_vat_payment" required class="tom-select w-full">
                                <option value="">Select VAT Payment</option>
                                @foreach ($vat_payment_options as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Local / Intl Payment -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Local/Intl Payment <x-red-star />
                            </label>
                            <select name="local_intl_payment" id="expense_local_intl_payment" required class="tom-select w-full">
                                <option value="">Select Local/Intl</option>
                                @foreach ($local_intl_options as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                        <!-- Paid Date -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Paid Date <x-red-star />
                            </label>
                            <input type="text" name="paid_date" id="expense_paid_date" required class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                        </div>

                        <!-- Invoice Date -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Invoice Date
                            </label>
                            <input type="text" name="invoice_date" id="expense_invoice_date" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                        <!-- Payment Currency -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Payment Currency
                            </label>
                            <input type="text" name="payment_currency" id="expense_payment_currency" value="AED" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. AED, USD, EUR">
                        </div>

                        <!-- Other Currency -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Other Currency
                            </label>
                            <input type="text" name="other_currency" id="expense_other_currency" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Specify if applicable">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mt-4">
                        <!-- Payment Amount -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Payment Amount <x-red-star />
                            </label>
                            <input type="number" step="0.01" min="0" name="payment_amount" id="expense_payment_amount" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                        </div>

                        <!-- VAT Amount -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                VAT Amount
                            </label>
                            <input type="number" step="0.01" min="0" name="vat_amount" id="expense_vat_amount" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                        </div>

                        <!-- Bank Charges / Fees -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Bank Charges / Fees
                            </label>
                            <input type="number" step="0.01" min="0" name="bank_charges" id="expense_bank_charges" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Invoice / Provider Information -->
                <div>
                    <h4 class="text-sm font-bold text-bgray-900 dark:text-white mb-4 pb-1 border-b border-bgray-200 dark:border-darkblack-400">
                        Invoice / Provider Information
                    </h4>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Invoice # -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Invoice #
                            </label>
                            <input type="text" name="invoice_number" id="expense_invoice_number" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. INV-10023">
                        </div>

                        <!-- Service Provider -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Service Provider
                            </label>
                            <select name="service_provider_id" id="expense_service_provider_id" class="tom-select w-full">
                                <option value="">Select Service Provider</option>
                                @foreach ($service_providers as $provider)
                                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Category
                            </label>
                            <select name="category_id" id="expense_category_id" class="tom-select w-full">
                                <option value="">Select Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Service / Product -->
                    <div class="mt-4">
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Service / Product <x-red-star />
                        </label>
                        <input type="text" name="service_product" id="expense_service_product" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Describe the service or product">
                    </div>
                </div>

                <!-- SECTION 3: Customer / Comment -->
                <div>
                    <h4 class="text-sm font-bold text-bgray-900 dark:text-white mb-4 pb-1 border-b border-bgray-200 dark:border-darkblack-400">
                        Customer & Additional Details
                    </h4>

                    <div class="grid grid-cols-1 gap-4">
                        <!-- Customer Field (User facing label: Customer) -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Customer
                            </label>
                            <select name="customer_id" id="expense_customer_id" class="tom-select w-full">
                                <option value="">Select Customer</option>
                                @foreach ($customers as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->customer_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Comment -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Comment
                            </label>
                            <textarea name="comment" id="expense_comment" rows="3" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add any comments or notes..."></textarea>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                <button type="button" data-expense-modal-close class="rounded-lg border border-bgray-300 px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500 transition">
                    Cancel
                </button>
                <button type="submit" id="expense_submit_btn" class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white hover:bg-success-400 transition">
                    Save Expense
                </button>
            </div>
        </form>
    </div>
</div>
