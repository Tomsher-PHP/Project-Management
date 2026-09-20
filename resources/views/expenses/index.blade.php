@extends('layouts.master')

@section('page-content')
    <!-- Top Action & Filter Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            @can('expense.create')
                <x-button.create-button type="button" id="open_create_expense_modal_btn" label="Expense" />
            @endcan

            <x-filters.button />

            <x-filters.list-search placeholder="Search expenses..." />
        </div>
    </div>

    <!-- Expenses Table Card -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1 min-w-0">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600 shadow-sm">
                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">#</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Payment Mode</th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="paid_date" label="Paid Date" />
                                    </th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="invoice_date" label="Invoice Date" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">VAT Payment</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Local/Intl Payment</th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="payment_amount" label="Payment Amount" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Other Amount</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">VAT Amount</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Bank Charges / Fees</th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="invoice_number" label="Invoice #" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Service Provider</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Category</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Vendor</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Customer</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Service / Product</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Comment</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Created</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startNumber = ($expenses->currentPage() - 1) * $expenses->perPage();
                                @endphp
                                @forelse ($expenses as $expense)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                        <!-- Row Index -->
                                        <td class="px-4 py-4 text-sm font-medium text-bgray-700 dark:text-bgray-50 whitespace-nowrap">
                                            {{ $startNumber + $loop->iteration }}
                                        </td>

                                        <!-- 1. Payment Mode -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                {{ $expense->paymentMode?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 2. Paid Date -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                @appDate($expense->paid_date)
                                            </span>
                                        </td>

                                        <!-- 3. Invoice Date -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                @appDate($expense->invoice_date)
                                            </span>
                                        </td>

                                        <!-- 4. VAT Payment -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $expense->vat_payment === \App\Models\Expense::VAT_PAYMENT_VAT ? 'bg-success-50 text-success-300 dark:bg-darkblack-500 dark:text-success-600' : 'bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                                                {{ strtoupper($expense->vat_payment) }}
                                            </span>
                                        </td>

                                        <!-- 5. Local/Intl Payment -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $expense->local_intl_payment === \App\Models\Expense::LOCAL_INTL_INTERNATIONAL ? 'bg-purple-500 text-white dark:bg-darkblack-500 dark:text-purple-500' : 'bg-blue-500 text-white dark:bg-darkblack-500 dark:text-blue-500' }}">
                                                {{ strtoupper($expense->local_intl_payment) }}
                                            </span>
                                        </td>

                                        <!-- 6. Payment Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                {{ $globalCompanyCurrency . ' ' . number_format($expense->payment_amount, 2) }}
                                            </span>
                                        </td>

                                        <!-- 8. [Other Currency] Other Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($expense->other_amount > 0 || filled($expense->other_currency))
                                                <span class="text-sm font-medium text-bgray-900 dark:text-white">
                                                    {{ $expense->other_currency ? $expense->other_currency . ' ' : '' }}{{ number_format($expense->other_amount ?? 0, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                            @endif
                                        </td>

                                        <!-- 9. VAT Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($expense->vat_amount > 0)
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ number_format($expense->vat_amount, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                            @endif
                                        </td>

                                        <!-- 10. Bank Charges / Fees -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($expense->bank_charges > 0)
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ number_format($expense->bank_charges, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                            @endif
                                        </td>

                                        <!-- 11. Invoice # -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                {{ $expense->invoice_number ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 12. Service Provider -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-800 dark:text-bgray-300">
                                                {{ $expense->serviceProvider?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 13. Category -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                {{ $expense->category?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 14. Vendor -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                {{ $expense->vendor?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 15. Customer -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                {{ $expense->customer?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 16. Service / Product -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300 line-clamp-2 max-w-xs" title="{{ $expense->service_product }}">
                                                {{ $expense->service_product ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 17. Comment -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300 line-clamp-2 max-w-xs" title="{{ $expense->comment }}">
                                                {{ $expense->comment ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- 18. Created (username and date below) -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-bgray-800 dark:text-bgray-300">
                                                    {{ $expense->addedBy?->name ?: '--' }}
                                                </span>
                                                <span class="text-xs text-bgray-700 dark:text-bgray-300">
                                                    @appDate($expense->created_at)
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-1">
                                                @can('expense.view')
                                                    <x-view-button action="javascript:void(0)" class="open-show-expense-modal-btn" data-expense-id="{{ $expense->id }}" data-show-url="{{ route('expenses.show', $expense->id) }}" title="View Expense Details" />
                                                @endcan
                                                @can('expense.edit')
                                                    <x-edit-button action="javascript:void(0)" class="open-edit-expense-modal-btn" data-expense-id="{{ $expense->id }}" data-update-url="{{ route('expenses.update', $expense->id) }}" data-fetch-url="{{ route('expenses.edit', $expense->id) }}" data-expense='@json($expense)' title="Edit Expense" />
                                                @endcan
                                                @can('expense.delete')
                                                    <x-delete-form :action="route('expenses.destroy', $expense->id)" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="19" message="No expenses found." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <x-pagination :paginator="$expenses" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>

    <!-- Filter Drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="invoice_number" label="Invoice Number" />
        <x-filters.select name="payment_mode_id" label="Payment Mode" :options="$payment_modes->pluck('name', 'id')->toArray()" />
        <x-filters.select name="service_provider_id" label="Service Provider" :options="$service_providers->pluck('name', 'id')->toArray()" />
        <x-filters.select name="category_id" label="Category" :options="$categories->pluck('name', 'id')->toArray()" />
        <x-filters.select name="vendor_id" label="Vendor" :options="$vendors->pluck('name', 'id')->toArray()" />
        <x-filters.select name="customer_id" label="Customer" :options="$customers->pluck('name', 'id')->toArray()" />
        <x-filters.select name="vat_payment" label="VAT Payment" class="tom-select-no-search w-full" :options="$vat_payment_options" />
        <x-filters.select name="local_intl_payment" label="Local / Intl" class="tom-select-no-search w-full" :options="$local_intl_options" />
    </x-filters.drawer>

    <!-- Show Details Modal -->
    @include('expenses.show-modal')

    <!-- Create/Edit Form Modal -->
    @include('expenses.form-modal')
@endsection
