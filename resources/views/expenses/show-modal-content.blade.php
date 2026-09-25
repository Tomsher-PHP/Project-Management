<div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <!-- Payment Mode -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Payment Mode</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $expense->paymentMode?->name ?: '--' }}
        </span>
    </div>

    <!-- Paid Date -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Paid Date</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            @appDate($expense->paid_date)
        </span>
    </div>

    <!-- Invoice Date -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Invoice Date</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            @appDate($expense->invoice_date)
        </span>
    </div>

    <!-- VAT Payment -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">VAT Payment</span>
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $expense->vat_payment === \App\Models\Expense::VAT_PAYMENT_VAT ? 'bg-success-50 text-success-300 dark:bg-darkblack-500 dark:text-success-600' : 'bg-bgray-200 text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300' }}">
            {{ strtoupper($expense->vat_payment) }}
        </span>
    </div>

    <!-- Local/Intl Payment -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Local/Intl Payment</span>
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $expense->local_intl_payment === \App\Models\Expense::LOCAL_INTL_INTERNATIONAL ? 'bg-purple-500 text-white dark:bg-darkblack-600 dark:text-purple-400' : 'bg-blue-500 text-white dark:bg-darkblack-600 dark:text-blue-400' }}">
            {{ strtoupper($expense->local_intl_payment) }}
        </span>
    </div>

    <!-- Payment Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Payment Amount</span>
        <span class="font-bold text-bgray-700 dark:text-bgray-300">
            {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($expense->payment_amount, 2) }}
        </span>
    </div>

    <!-- Other Currency & Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Other Amount</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($expense->other_amount > 0 || filled($expense->other_currency))
                {{ $expense->other_currency ? $expense->other_currency . ' ' : '' }}{{ number_format($expense->other_amount ?? 0, 2) }}
            @else
                --
            @endif
        </span>
    </div>

    <!-- VAT Percentage -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">VAT Percentage</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $expense->vat_percentage !== null ? number_format($expense->vat_percentage, 2) . '%' : '--' }}
        </span>
    </div>

    <!-- VAT Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">VAT Amount</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($expense->vat_amount > 0)
                {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($expense->vat_amount, 2) }}
            @else
                --
            @endif
        </span>
    </div>

    <!-- Bank Charges / Fees -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Bank Charges / Fees</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($expense->bank_charges > 0)
                {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($expense->bank_charges, 2) }}
            @else
                --
            @endif
        </span>
    </div>

    <!-- Invoice # -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Invoice #</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $expense->invoice_number ?: '--' }}
        </span>
    </div>

    <!-- Service Provider -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Service Provider</span>
        <span class="font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $expense->serviceProvider?->name ?: '--' }}
        </span>
    </div>

    <!-- Category -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Category</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $expense->category?->name ?: '--' }}
        </span>
    </div>

    <!-- Vendor -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Vendor</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $expense->vendor?->name ?: '--' }}
        </span>
    </div>

    <!-- Customer -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Customer</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $expense->customer?->name ?: '--' }}
        </span>
    </div>

    <!-- Service / Product (Full width) -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Service / Product</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $expense->service_product ?: '--' }}
        </span>
    </div>

    <!-- Comment (Full width) -->
    <div class="md:col-span-2 rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Comment</span>
        <p class="text-bgray-700 dark:text-bgray-300 whitespace-pre-line">
            {{ $expense->comment ?: '--' }}
        </p>
    </div>

    <!-- Audit Metadata (Full width) -->
    <div class="md:col-span-2 grid grid-cols-2 gap-4 border-t border-bgray-200 dark:border-darkblack-400 pt-3 mt-1">
        <div>
            <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-0.5">Created By</span>
            <span class="text-xs text-bgray-700 dark:text-bgray-300 block">
                {{ $expense->addedBy?->name ?: '--' }}
            </span>
            <span class="text-xs text-bgray-700 dark:text-bgray-300 block">
                @appDate($expense->created_at)
            </span>
        </div>
    </div>
</div>
