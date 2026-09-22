@extends('layouts.master')

@section('page-content')
    <!-- Top Action & Filter Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            @can('reimbursement.create')
                <x-button.create-button type="button" id="open_create_reimbursement_modal_btn" label="Reimbursement" />
            @endcan

            <x-filters.button />

            <x-filters.list-search placeholder="Search reimbursements..." />
        </div>

        <!-- Tab Navigation (Right Aligned) -->
        <div class="inline-flex overflow-hidden rounded-lg border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            @can('expense.view')
                <a href="{{ route('expenses.index') }}" class="px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('expenses.*') ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                    Expenses
                </a>
            @endcan
            @can('check_expense.view')
                <a href="{{ route('cheques.index') }}" class="px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('cheques.*') ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                    Cheque Expenses
                </a>
            @endcan
            @can('reimbursement.view')
                <a href="{{ route('reimbursements.index') }}" class="px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('reimbursements.*') ? 'bg-success-300 text-white' : 'text-bgray-600 hover:bg-bgray-50 dark:text-bgray-300 dark:hover:bg-darkblack-500' }}">
                    Reimbursements
                </a>
            @endcan
        </div>
    </div>

    <!-- Reimbursements Table Card -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1 min-w-0">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600 shadow-sm">
                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">#</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Employee</th>
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
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="approval_status" label="Approval Status" />
                                    </th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="amount_reimbursed" label="Amount Reimbursed" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Reimbursement Mode</th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="employee_confirmation" label="Employee Confirmation" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Service / Product</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Comment</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Created</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startNumber = ($reimbursements->currentPage() - 1) * $reimbursements->perPage();
                                @endphp
                                @forelse ($reimbursements as $reimbursement)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                        <!-- Row Index -->
                                        <td class="px-4 py-4 text-sm font-medium text-bgray-700 dark:text-bgray-50 whitespace-nowrap">
                                            {{ $startNumber + $loop->iteration }}
                                        </td>

                                        <!-- Employee -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                {{ $reimbursement->user?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Payment Mode -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                {{ $reimbursement->paymentMode?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Paid Date -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                @appDate($reimbursement->paid_date)
                                            </span>
                                        </td>

                                        <!-- Invoice Date -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                @appDate($reimbursement->invoice_date)
                                            </span>
                                        </td>

                                        <!-- VAT Payment -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $reimbursement->vat_payment === \App\Models\Reimbursement::VAT_PAYMENT_VAT ? 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-600' : 'bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                                                {{ strtoupper($reimbursement->vat_payment) }}
                                            </span>
                                        </td>

                                        <!-- Local/Intl Payment -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $reimbursement->local_intl_payment === \App\Models\Reimbursement::LOCAL_INTL_INTERNATIONAL ? 'bg-purple-500 text-white dark:bg-darkblack-500 dark:text-purple-500' : 'bg-blue-500 text-white dark:bg-darkblack-500 dark:text-blue-500' }}">
                                                {{ strtoupper($reimbursement->local_intl_payment) }}
                                            </span>
                                        </td>

                                        <!-- Payment Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                {{ $globalCompanyCurrency . ' ' . number_format($reimbursement->payment_amount, 2) }}
                                            </span>
                                        </td>

                                        <!-- Other Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($reimbursement->other_amount > 0 || filled($reimbursement->other_currency))
                                                <span class="text-sm font-medium text-bgray-900 dark:text-white">
                                                    {{ $reimbursement->other_currency ? $reimbursement->other_currency . ' ' : '' }}{{ number_format($reimbursement->other_amount ?? 0, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                            @endif
                                        </td>

                                        <!-- VAT Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($reimbursement->vat_amount > 0)
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ number_format($reimbursement->vat_amount, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                            @endif
                                        </td>

                                        <!-- Bank Charges / Fees -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($reimbursement->bank_charges > 0)
                                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                    {{ number_format($reimbursement->bank_charges, 2) }}
                                                </span>
                                            @else
                                                <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                            @endif
                                        </td>

                                        <!-- Invoice # -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                {{ $reimbursement->invoice_number ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Service Provider -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-800 dark:text-bgray-300">
                                                {{ $reimbursement->serviceProvider?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Category -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                {{ $reimbursement->category?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Vendor -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                {{ $reimbursement->vendor?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Customer -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                {{ $reimbursement->customer?->name ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Approval Status -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @php
                                                $statusClass = match ($reimbursement->approval_status) {
                                                    \App\Models\Reimbursement::APPROVAL_STATUS_APPROVED => 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-600',
                                                    \App\Models\Reimbursement::APPROVAL_STATUS_VERIFY => 'bg-blue-50 text-blue-500 dark:bg-darkblack-500 dark:text-blue-400',
                                                    \App\Models\Reimbursement::APPROVAL_STATUS_REJECTED => 'bg-red-50 text-red-500 dark:bg-darkblack-500 dark:text-red-400',
                                                    default => 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400',
                                                };
                                                $statusLabel = \App\Models\Reimbursement::APPROVAL_STATUS_OPTIONS[$reimbursement->approval_status] ?? ucfirst($reimbursement->approval_status);
                                            @endphp
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>

                                        <!-- Amount Reimbursed -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @php
                                                $amountStatusClass = match ($reimbursement->amount_reimbursed) {
                                                    \App\Models\Reimbursement::AMOUNT_REIMBURSED_GIVEN => 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-600',
                                                    default => 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400',
                                                };
                                                $amountStatusLabel = \App\Models\Reimbursement::AMOUNT_REIMBURSED_OPTIONS[$reimbursement->amount_reimbursed] ?? ucfirst($reimbursement->amount_reimbursed);
                                            @endphp
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $amountStatusClass }}">
                                                {{ $amountStatusLabel }}
                                            </span>
                                        </td>

                                        <!-- Reimbursement Mode -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-800 dark:text-bgray-300">
                                                {{ \App\Models\Reimbursement::REIMBURSEMENT_MODE_OPTIONS[$reimbursement->reimbursement_mode] ?? ($reimbursement->reimbursement_mode ? ucfirst($reimbursement->reimbursement_mode) : '--') }}
                                            </span>
                                        </td>

                                        <!-- Employee Confirmation -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @php
                                                $confirmClass = match ($reimbursement->employee_confirmation) {
                                                    \App\Models\Reimbursement::EMPLOYEE_CONFIRMATION_RECEIVED => 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-600',
                                                    default => 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400',
                                                };
                                                $confirmLabel = \App\Models\Reimbursement::EMPLOYEE_CONFIRMATION_OPTIONS[$reimbursement->employee_confirmation] ?? ucfirst($reimbursement->employee_confirmation);
                                            @endphp
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $confirmClass }}">
                                                {{ $confirmLabel }}
                                            </span>
                                        </td>

                                        <!-- Service / Product -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300 line-clamp-2 max-w-xs" title="{{ $reimbursement->service_product }}">
                                                {{ $reimbursement->service_product ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Comment -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300 line-clamp-2 max-w-xs" title="{{ $reimbursement->comment }}">
                                                {{ $reimbursement->comment ?: '--' }}
                                            </span>
                                        </td>

                                        <!-- Created -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-bgray-800 dark:text-bgray-300">
                                                    {{ $reimbursement->addedBy?->name ?: '--' }}
                                                </span>
                                                <span class="text-xs text-bgray-700 dark:text-bgray-300">
                                                    @appDate($reimbursement->created_at)
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-1">
                                                @can('reimbursement.view')
                                                    <x-view-button action="javascript:void(0)" class="open-show-reimbursement-modal-btn" data-reimbursement-id="{{ $reimbursement->id }}" data-show-url="{{ route('reimbursements.show', $reimbursement->id) }}" title="View Reimbursement Details" />
                                                @endcan
                                                @can('reimbursement.edit')
                                                    <x-edit-button action="javascript:void(0)" class="open-edit-reimbursement-modal-btn" data-reimbursement-id="{{ $reimbursement->id }}" data-update-url="{{ route('reimbursements.update', $reimbursement->id) }}" data-fetch-url="{{ route('reimbursements.edit', $reimbursement->id) }}" data-reimbursement='@json($reimbursement)' title="Edit Reimbursement" />
                                                @endcan
                                                @can('reimbursement.delete')
                                                    <x-delete-form :action="route('reimbursements.destroy', $reimbursement->id)" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="24" message="No reimbursements found." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <x-pagination :paginator="$reimbursements" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>

    <!-- Filter Drawer -->
    <x-filters.drawer>
        <x-filters.multi-select name="user_id" label="Employee" :options="$users" />
        <x-filters.multi-select name="payment_mode_id" label="Payment Mode" :options="$payment_modes" />
        <x-filters.multi-select name="service_provider_id" label="Service Provider" :options="$service_providers" />
        <x-filters.multi-select name="category_id" label="Category" :options="$categories" />
        <x-filters.multi-select name="vendor_id" label="Vendor" :options="$vendors" />
        <x-filters.multi-select name="customer_id" label="Customer" :options="$customers" />
        <x-filters.select name="approval_status" label="Approval Status" class="tom-select-no-search w-full" :options="$approval_status_options" />
        <x-filters.select name="amount_reimbursed" label="Amount Reimbursed" class="tom-select-no-search w-full" :options="$amount_reimbursed_options" />
        <x-filters.select name="reimbursement_mode" label="Reimbursement Mode" class="tom-select-no-search w-full" :options="$reimbursement_mode_options" />
        <x-filters.select name="employee_confirmation" label="Employee Confirmation" class="tom-select-no-search w-full" :options="$employee_confirmation_options" />
        <x-filters.select name="vat_payment" label="VAT Payment" class="tom-select-no-search w-full" :options="$vat_payment_options" />
        <x-filters.select name="local_intl_payment" label="Local / Intl" class="tom-select-no-search w-full" :options="$local_intl_options" />
    </x-filters.drawer>

    <!-- Show Details Modal -->
    @include('reimbursements.show-modal')

    <!-- Create/Edit Form Modal -->
    @include('reimbursements.form-modal')
@endsection
