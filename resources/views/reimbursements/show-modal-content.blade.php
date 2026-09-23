<div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <!-- Employee -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Employee</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->user?->name ?: '--' }}
        </span>
    </div>

    <!-- Payment Mode -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Payment Mode</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->paymentMode?->name ?: '--' }}
        </span>
    </div>

    <!-- Paid Date -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Paid Date</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            @appDate($reimbursement->paid_date)
        </span>
    </div>

    <!-- Invoice Date -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Invoice Date</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            @appDate($reimbursement->invoice_date)
        </span>
    </div>

    <!-- VAT Payment -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">VAT Payment</span>
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $reimbursement->vat_payment === \App\Models\Reimbursement::VAT_PAYMENT_VAT ? 'bg-success-50 text-success-300 dark:bg-darkblack-500 dark:text-success-600' : 'bg-bgray-200 text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300' }}">
            {{ strtoupper($reimbursement->vat_payment) }}
        </span>
    </div>

    <!-- Local/Intl Payment -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Local/Intl Payment</span>
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $reimbursement->local_intl_payment === \App\Models\Reimbursement::LOCAL_INTL_INTERNATIONAL ? 'bg-purple-500 text-white dark:bg-darkblack-600 dark:text-purple-400' : 'bg-blue-500 text-white dark:bg-darkblack-600 dark:text-blue-400' }}">
            {{ strtoupper($reimbursement->local_intl_payment) }}
        </span>
    </div>

    <!-- Payment Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Payment Amount</span>
        <span class="font-bold text-bgray-700 dark:text-bgray-300">
            {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($reimbursement->payment_amount, 2) }}
        </span>
    </div>

    <!-- Other Currency & Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Other Amount</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($reimbursement->other_amount > 0 || filled($reimbursement->other_currency))
                {{ $reimbursement->other_currency ? $reimbursement->other_currency . ' ' : '' }}{{ number_format($reimbursement->other_amount ?? 0, 2) }}
            @else
                --
            @endif
        </span>
    </div>

    <!-- VAT Percentage -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">VAT Percentage</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->vat_percentage !== null ? number_format($reimbursement->vat_percentage, 2) . '%' : '--' }}
        </span>
    </div>

    <!-- VAT Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">VAT Amount</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($reimbursement->vat_amount > 0)
                {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($reimbursement->vat_amount, 2) }}
            @else
                --
            @endif
        </span>
    </div>

    <!-- Bank Charges / Fees -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Bank Charges / Fees</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($reimbursement->bank_charges > 0)
                {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($reimbursement->bank_charges, 2) }}
            @else
                --
            @endif
        </span>
    </div>

    <!-- Invoice # -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Invoice #</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->invoice_number ?: '--' }}
        </span>
    </div>

    <!-- Service Provider -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Service Provider</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->serviceProvider?->name ?: '--' }}
        </span>
    </div>

    <!-- Category -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Category</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->category?->name ?: '--' }}
        </span>
    </div>

    <!-- Vendor -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Vendor</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->vendor?->name ?: '--' }}
        </span>
    </div>

    <!-- Customer -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Customer</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->customer?->name ?: '--' }}
        </span>
    </div>

    <!-- Approval Status -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Approval Status</span>
        @php
            $statusClass = match ($reimbursement->approval_status) {
                \App\Models\Reimbursement::APPROVAL_STATUS_APPROVED => 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-600',
                \App\Models\Reimbursement::APPROVAL_STATUS_VERIFY => 'bg-blue-50 text-blue-500 dark:bg-darkblack-600 dark:text-blue-400',
                \App\Models\Reimbursement::APPROVAL_STATUS_REJECTED => 'bg-red-50 text-red-500 dark:bg-darkblack-600 dark:text-red-400',
                default => 'bg-amber-50 text-amber-600 dark:bg-darkblack-600 dark:text-amber-400',
            };
            $statusLabel = \App\Models\Reimbursement::APPROVAL_STATUS_OPTIONS[$reimbursement->approval_status] ?? ucfirst($reimbursement->approval_status);
        @endphp
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $statusClass }}">
            {{ $statusLabel }}
        </span>
    </div>

    <!-- Amount Reimbursed -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Amount Reimbursed</span>
        @php
            $amountStatusClass = match ($reimbursement->amount_reimbursed) {
                \App\Models\Reimbursement::AMOUNT_REIMBURSED_GIVEN => 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-600',
                default => 'bg-amber-50 text-amber-600 dark:bg-darkblack-600 dark:text-amber-400',
            };
            $amountStatusLabel = \App\Models\Reimbursement::AMOUNT_REIMBURSED_OPTIONS[$reimbursement->amount_reimbursed] ?? ucfirst($reimbursement->amount_reimbursed);
        @endphp
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $amountStatusClass }}">
            {{ $amountStatusLabel }}
        </span>
    </div>

    <!-- Reimbursement Mode -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Reimbursement Mode</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ \App\Models\Reimbursement::REIMBURSEMENT_MODE_OPTIONS[$reimbursement->reimbursement_mode] ?? ($reimbursement->reimbursement_mode ? ucfirst($reimbursement->reimbursement_mode) : '--') }}
        </span>
    </div>

    <!-- Employee Confirmation -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Employee Confirmation</span>
        @php
            $confirmClass = match ($reimbursement->employee_confirmation) {
                \App\Models\Reimbursement::EMPLOYEE_CONFIRMATION_RECEIVED => 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-600',
                default => 'bg-amber-50 text-amber-600 dark:bg-darkblack-600 dark:text-amber-400',
            };
            $confirmLabel = \App\Models\Reimbursement::EMPLOYEE_CONFIRMATION_OPTIONS[$reimbursement->employee_confirmation] ?? ucfirst($reimbursement->employee_confirmation);
        @endphp
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $confirmClass }}">
            {{ $confirmLabel }}
        </span>
    </div>

    <!-- Service / Product (Full width) -->
    <div class="md:col-span-2 rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Service / Product</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $reimbursement->service_product ?: '--' }}
        </span>
    </div>

    <!-- Comment (Full width) -->
    <div class="md:col-span-2 rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Comment</span>
        <p class="text-bgray-700 dark:text-bgray-300 whitespace-pre-line">
            {{ $reimbursement->comment ?: '--' }}
        </p>
    </div>

    <!-- Audit Metadata (Full width) -->
    <div class="md:col-span-2 grid grid-cols-2 gap-4 border-t border-bgray-200 dark:border-darkblack-400 pt-3 mt-1">
        <div>
            <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-0.5">Created By</span>
            <span class="text-xs text-bgray-700 dark:text-bgray-300 block">
                {{ $reimbursement->addedBy?->name ?: '--' }}
            </span>
            <span class="text-xs text-bgray-700 dark:text-bgray-300 block">
                @appDate($reimbursement->created_at)
            </span>
        </div>
    </div>
</div>
