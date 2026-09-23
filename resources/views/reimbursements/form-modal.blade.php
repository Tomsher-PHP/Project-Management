<div id="reimbursement_modal" class="fixed inset-0 z-50 {{ $errors->any() ? '' : 'hidden' }} overflow-y-auto bg-black/50 p-4 backdrop-blur-sm sm:p-6 md:p-10 flex items-center justify-center">
    <div class="relative w-full max-w-4xl rounded-[8px] bg-white shadow-xl dark:bg-darkblack-600 my-8">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <h3 id="reimbursement_modal_title" class="text-lg font-bold text-bgray-900 dark:text-white">
                Add New Reimbursement
            </h3>
            <button type="button" data-reimbursement-modal-close class="rounded-lg p-1.5 text-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body / Form -->
        <form id="reimbursement_form" method="POST" action="{{ route('reimbursements.store') }}" data-create-url="{{ route('reimbursements.store') }}" data-default-vat-percentage="{{ \App\Models\Reimbursement::DEFAULT_VAT_PERCENTAGE }}" data-vat-payment-vat="{{ \App\Models\Reimbursement::VAT_PAYMENT_VAT }}">
            @csrf
            <input type="hidden" name="_method" id="reimbursement_form_method" value="POST">
            <input type="hidden" name="reimbursement_id" id="reimbursement_id_input" value="">

            <div class="max-h-[75vh] overflow-y-auto pl-6 pr-6 pt-2 pb-6 space-y-4">

                <!-- Dynamic Error Container -->
                <div id="reimbursement_form_errors" class="hidden rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-darkblack-500 dark:text-red-400 border border-red-200 dark:border-red-800">
                    <div class="font-bold mb-1">Please fix the following validation errors:</div>
                    <ul id="reimbursement_errors_list" class="list-disc list-inside space-y-1"></ul>
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

                <!-- Row 1: Employee & Payment Mode -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Employee <x-red-star />
                        </label>
                        <select name="user_id" id="reimbursement_user_id" required class="tom-select w-full">
                            <option value="">Select Employee</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Payment Mode <x-red-star />
                        </label>
                        <select name="payment_mode_id" id="reimbursement_payment_mode_id" required class="tom-select w-full" data-default-value="{{ $default_payment_mode_id }}">
                            <option value="">Select Payment Mode</option>
                            @foreach ($payment_modes as $mode)
                                <option value="{{ $mode->id }}" {{ (string) $mode->id === (string) $default_payment_mode_id ? 'selected' : '' }}>{{ $mode->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 2: Paid Date & Invoice Date -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Paid Date <x-red-star />
                        </label>
                        <input type="text" name="paid_date" id="reimbursement_paid_date" value="{{ now()->format('Y-m-d') }}" required class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Invoice Date
                        </label>
                        <input type="text" name="invoice_date" id="reimbursement_invoice_date" value="{{ now()->format('Y-m-d') }}" class="datepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="YYYY-MM-DD">
                    </div>
                </div>

                <!-- Row 3: VAT Payment & Local/Intl Payment -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            VAT Payment <x-red-star />
                        </label>
                        <select name="vat_payment" id="reimbursement_vat_payment" required class="tom-select-no-search w-full" data-default-value="{{ \App\Models\Reimbursement::DEFAULT_VAT_PAYMENT }}">
                            <option value="">Select VAT Payment</option>
                            @foreach ($vat_payment_options as $val => $lbl)
                                <option value="{{ $val }}" {{ $val === \App\Models\Reimbursement::DEFAULT_VAT_PAYMENT ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Local/Intl Payment <x-red-star />
                        </label>
                        <select name="local_intl_payment" id="reimbursement_local_intl_payment" required class="tom-select-no-search w-full" data-default-value="{{ \App\Models\Reimbursement::DEFAULT_LOCAL_INTL_PAYMENT }}">
                            <option value="">Select Local/Intl</option>
                            @foreach ($local_intl_options as $val => $lbl)
                                <option value="{{ $val }}" {{ $val === \App\Models\Reimbursement::DEFAULT_LOCAL_INTL_PAYMENT ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 4: Payment Amount -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Payment Amount ({{ $globalCompanyCurrency ?? 'AED' }}) <x-red-star />
                    </label>
                    <input type="number" step="0.01" min="0" name="payment_amount" id="reimbursement_payment_amount" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                </div>

                <!-- Row 5: Other Currency & Other Amount -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Other Currency
                        </label>
                        <select name="other_currency" id="reimbursement_other_currency" class="tom-select-lazy w-full" data-route="{{ route('currencies.search') }}" data-placeholder="Select Currency">
                            <option value="">Select Currency</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Other Amount
                        </label>
                        <input type="number" step="0.01" min="0" name="other_amount" id="reimbursement_other_amount" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                    </div>
                </div>

                <!-- Row 6: VAT Amount & Bank Charges / Fees -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            VAT Amount
                        </label>
                        <input type="number" step="0.01" min="0" name="vat_amount" id="reimbursement_vat_amount" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Bank Charges / Fees
                        </label>
                        <input type="number" step="0.01" min="0" name="bank_charges" id="reimbursement_bank_charges" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="0.00">
                    </div>
                </div>

                <!-- Row 7: Invoice #, Service Provider, Category -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Invoice #
                        </label>
                        <input type="text" name="invoice_number" id="reimbursement_invoice_number" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. INV-10023">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Service Provider
                        </label>
                        <select name="service_provider_id" id="reimbursement_service_provider_id" class="tom-select w-full" data-default-value="{{ $default_service_provider_id }}">
                            <option value="">Select Service Provider</option>
                            @foreach ($service_providers as $provider)
                                <option value="{{ $provider->id }}" {{ (string) $provider->id === (string) $default_service_provider_id ? 'selected' : '' }}>{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Category
                        </label>
                        <select name="category_id" id="reimbursement_category_id" class="tom-select w-full" data-default-value="{{ $default_category_id }}">
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) $cat->id === (string) $default_category_id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 8: Vendor & Customer -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Vendor
                        </label>
                        <select name="vendor_id" id="reimbursement_vendor_id" class="tom-select w-full">
                            <option value="">Select Vendor</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Customer
                        </label>
                        <select name="customer_id" id="reimbursement_customer_id" class="tom-select w-full">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->customer_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 9: Reimbursement Status Controls (Protected by reimbursement.status_change) -->
                @can('reimbursement.status_change')
                    <div class="grid grid-cols-3 gap-4 border-t border-bgray-200 dark:border-darkblack-400 pt-4 mt-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Approval Status
                            </label>
                            <select name="approval_status" id="reimbursement_approval_status" class="tom-select-no-search w-full" data-default-value="{{ \App\Models\Reimbursement::DEFAULT_APPROVAL_STATUS }}">
                                @foreach ($approval_status_options as $val => $lbl)
                                    <option value="{{ $val }}" {{ $val === \App\Models\Reimbursement::DEFAULT_APPROVAL_STATUS ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Amount Reimbursed
                            </label>
                            <select name="amount_reimbursed" id="reimbursement_amount_reimbursed" class="tom-select-no-search w-full" data-default-value="{{ \App\Models\Reimbursement::DEFAULT_AMOUNT_REIMBURSED }}">
                                @foreach ($amount_reimbursed_options as $val => $lbl)
                                    <option value="{{ $val }}" {{ $val === \App\Models\Reimbursement::DEFAULT_AMOUNT_REIMBURSED ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Reimbursement Mode
                            </label>
                            <select name="reimbursement_mode" id="reimbursement_reimbursement_mode" class="tom-select-no-search w-full">
                                <option value="">Select Mode</option>
                                @foreach ($reimbursement_mode_options as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-3 gap-4 border-t border-bgray-200 dark:border-darkblack-400 pt-4 mt-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Approval Status
                            </label>
                            <div class="w-full rounded-lg border border-bgray-300 bg-bgray-100 px-4 py-2.5 text-sm font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" id="read_only_approval_status_text">
                                Pending
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Amount Reimbursed
                            </label>
                            <div class="w-full rounded-lg border border-bgray-300 bg-bgray-100 px-4 py-2.5 text-sm font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" id="read_only_amount_reimbursed_text">
                                Pending
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                                Reimbursement Mode
                            </label>
                            <div class="w-full rounded-lg border border-bgray-300 bg-bgray-100 px-4 py-2.5 text-sm font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" id="read_only_reimbursement_mode_text">
                                --
                            </div>
                        </div>
                    </div>
                @endcan

                <!-- Row 10: Employee Confirmation (Standard edit permission) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                            Employee Confirmation
                        </label>
                        <select name="employee_confirmation" id="reimbursement_employee_confirmation" class="tom-select-no-search w-full" data-default-value="{{ \App\Models\Reimbursement::DEFAULT_EMPLOYEE_CONFIRMATION }}">
                            @foreach ($employee_confirmation_options as $val => $lbl)
                                <option value="{{ $val }}" {{ $val === \App\Models\Reimbursement::DEFAULT_EMPLOYEE_CONFIRMATION ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 11: Service / Product -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Service / Product
                    </label>
                    <input type="text" name="service_product" id="reimbursement_service_product" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Describe the service or product">
                </div>

                <!-- Row 12: Comment -->
                <div>
                    <label class="mb-2 block text-sm font-semibold text-bgray-900 dark:text-white">
                        Comment
                    </label>
                    <textarea name="comment" id="reimbursement_comment" rows="3" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-medium text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add any comments or notes..."></textarea>
                </div>

            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                <button type="button" data-reimbursement-modal-close class="rounded-lg border border-bgray-300 px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500 transition">
                    Cancel
                </button>
                <button type="submit" id="reimbursement_submit_btn" class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white hover:bg-success-400 transition">
                    Save Reimbursement
                </button>
            </div>
        </form>
    </div>
</div>
