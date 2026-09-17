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
                                    <th class="px-4 py-4 text-left font-medium text-bgray-600 dark:text-bgray-50 text-sm">#</th>
                                    <th class="px-4 py-4 text-left">
                                        <x-sorting.sortable-column column="paid_date" label="Paid / Invoice Date" />
                                    </th>
                                    <th class="px-4 py-4 text-left">
                                        <x-sorting.sortable-column column="invoice_number" label="Invoice # & Service" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-600 dark:text-bgray-50 text-sm">Provider & Category</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-600 dark:text-bgray-50 text-sm">Customer</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-600 dark:text-bgray-50 text-sm">Payment Mode & Type</th>
                                    <th class="px-4 py-4 text-left">
                                        <x-sorting.sortable-column column="payment_amount" label="Amount" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-600 dark:text-bgray-50 text-sm">Comment</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-600 dark:text-bgray-50 text-sm">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startNumber = ($expenses->currentPage() - 1) * $expenses->perPage();
                                @endphp
                                @forelse ($expenses as $expense)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                        <!-- Row Index -->
                                        <td class="px-4 py-4 text-sm font-medium text-bgray-600 dark:text-bgray-50">
                                            {{ $startNumber + $loop->iteration }}
                                        </td>

                                        <!-- Dates -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                    {{ $expense->paid_date ? $expense->paid_date->format('d M Y') : '--' }}
                                                </span>
                                                @if ($expense->invoice_date)
                                                    <span class="text-xs text-bgray-500 dark:text-bgray-400">
                                                        Inv Date: {{ $expense->invoice_date->format('d M Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Invoice # & Service/Product -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                    {{ $expense->invoice_number ?: '--' }}
                                                </span>
                                                @if (filled($expense->service_product))
                                                    <span class="text-xs text-bgray-600 dark:text-bgray-300 line-clamp-1" title="{{ $expense->service_product }}">
                                                        {{ $expense->service_product }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Provider & Category -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-bgray-800 dark:text-bgray-100">
                                                    {{ $expense->serviceProvider?->name ?: '--' }}
                                                </span>
                                                <span class="text-xs text-bgray-500 dark:text-bgray-400">
                                                    {{ $expense->category?->name ?: '--' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Customer -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm font-medium text-bgray-800 dark:text-bgray-100">
                                                {{ $expense->customer?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Payment Mode & Badges -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-1">
                                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                    {{ $expense->paymentMode?->name ?: '--' }}
                                                </span>
                                                <div class="flex flex-wrap items-center gap-1">
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $expense->vat_payment === \App\Models\Expense::VAT_PAYMENT_VAT ? 'bg-success-50 text-success-600 dark:bg-darkblack-500 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-400' }}">
                                                        {{ $expense->vat_payment }}
                                                    </span>
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $expense->local_intl_payment === \App\Models\Expense::LOCAL_INTL_INTERNATIONAL ? 'bg-purple-50 text-purple-600 dark:bg-darkblack-500 dark:text-purple-300' : 'bg-blue-50 text-blue-600 dark:bg-darkblack-500 dark:text-blue-300' }}">
                                                        {{ $expense->local_intl_payment }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Monetary Amount -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                    {{ $expense->payment_currency ?? 'AED' }} {{ number_format($expense->payment_amount, 2) }}
                                                </span>
                                                @if ($expense->vat_amount > 0 || $expense->bank_charges > 0)
                                                    <div class="flex items-center gap-2 text-xs text-bgray-500 dark:text-bgray-400">
                                                        @if ($expense->vat_amount > 0)
                                                            <span>VAT: {{ number_format($expense->vat_amount, 2) }}</span>
                                                        @endif
                                                        @if ($expense->bank_charges > 0)
                                                            <span>Fee: {{ number_format($expense->bank_charges, 2) }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Comment -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm text-bgray-600 dark:text-bgray-300 line-clamp-1" title="{{ $expense->comment }}">
                                                {{ $expense->comment ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-4">
                                            <div class="flex items-center space-x-2">
                                                @can('expense.edit')
                                                    <x-edit-button :action="route('expenses.edit', $expense->id)" />
                                                @endcan
                                                @can('expense.delete')
                                                    <x-delete-form :action="route('expenses.destroy', $expense->id)" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="9" message="No expenses found." />
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
        <x-filters.select name="customer_id" label="Customer" :options="$customers->pluck('name', 'id')->toArray()" />
        <x-filters.select name="vat_payment" label="VAT Payment" :options="$vat_payment_options" />
        <x-filters.select name="local_intl_payment" label="Local / Intl" :options="$local_intl_options" />
    </x-filters.drawer>
@endsection
