<div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <!-- Cheque # -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Cheque #</span>
        <span class="font-bold text-bgray-900 dark:text-white">
            {{ $cheque->cheque_number }}
        </span>
    </div>

    <!-- Amount -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Amount</span>
        <span class="font-bold text-bgray-900 dark:text-white">
            {{ ($globalCompanyCurrency ?? 'AED') . ' ' . number_format($cheque->amount, 2) }}
        </span>
    </div>

    <!-- Cheque Date -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Cheque Date</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @appDate($cheque->cheque_date)
        </span>
    </div>

    <!-- Cheque Given -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Cheque Given</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @appDate($cheque->cheque_given)
        </span>
    </div>

    <!-- Cheque To -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Cheque To</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            {{ $cheque->cheque_to }}
        </span>
    </div>

    <!-- Cheque Status -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Cheque Status</span>
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $cheque->cheque_status === \App\Models\Cheque::STATUS_DEBITED ? 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-600' : 'bg-warning-50 text-warning-400 dark:bg-darkblack-500 dark:text-warning-600' }}">
            {{ strtoupper($cheque->cheque_status) }}
        </span>
    </div>

    <!-- Debited Date -->
    <div class="rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Debited Date</span>
        <span class="font-medium text-bgray-700 dark:text-bgray-300">
            @if ($cheque->debited_date)
                @appDate($cheque->debited_date)
            @else
                --
            @endif
        </span>
    </div>

    <!-- Purpose (Full width or span) -->
    <div class="md:col-span-2 rounded-lg bg-bgray-50 p-3.5 dark:bg-darkblack-500">
        <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-1">Purpose</span>
        <p class="text-bgray-700 dark:text-bgray-300 whitespace-pre-line">
            {{ $cheque->purpose }}
        </p>
    </div>

    <!-- Audit Metadata (Full width) -->
    <div class="md:col-span-2 grid grid-cols-2 gap-4 border-t border-bgray-200 dark:border-darkblack-400 pt-3 mt-1">
        <div>
            <span class="block text-xs font-medium text-bgray-900 dark:text-bgray-50 mb-0.5">Created By</span>
            <span class="text-xs text-bgray-700 dark:text-bgray-300 block">
                {{ $cheque->addedBy?->name ?: '--' }}
            </span>
            <span class="text-xs text-bgray-700 dark:text-bgray-300 block">
                @appDate($cheque->created_at)
            </span>
        </div>
    </div>
</div>
