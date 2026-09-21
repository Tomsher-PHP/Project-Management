@extends('layouts.master')

@section('page-content')
    <!-- Top Action & Filter Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            @can('check_expense.create')
                <x-button.create-button type="button" id="open_create_cheque_modal_btn" label="Cheque Expense" />
            @endcan

            <x-filters.button />

            <x-filters.list-search placeholder="Search cheque expenses..." />
        </div>

        @can('check_expense.view')
            <!-- Tab Navigation (Right Aligned) -->
            <div class="flex items-center rounded-lg border border-bgray-300 bg-white p-1 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 sm:ml-auto">
                <a href="{{ route('expenses.index') }}" class="rounded-md px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('expenses.*') ? 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-600' : 'text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white' }}">
                    Expenses
                </a>
                <a href="{{ route('cheques.index') }}" class="rounded-md px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('cheques.*') ? 'bg-success-50 text-success-400 dark:bg-darkblack-600 dark:text-success-600' : 'text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white' }}">
                    Cheque Expenses
                </a>
            </div>
        @endcan
    </div>

    <!-- Cheques Table Card -->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1 min-w-0">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600 shadow-sm">
                <div class="flex flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">#</th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="cheque_number" label="Cheque #" />
                                    </th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="amount" label="Amount" />
                                    </th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="cheque_date" label="Cheque Date" />
                                    </th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="cheque_given" label="Cheque Given" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Cheque To</th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Purpose</th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="cheque_status" label="Cheque Status" />
                                    </th>
                                    <th class="px-4 py-4 text-left whitespace-nowrap">
                                        <x-sorting.sortable-column column="debited_date" label="Debited Date" />
                                    </th>
                                    <th class="px-4 py-4 text-left font-medium text-bgray-700 dark:text-bgray-50 text-sm whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $startNumber = ($cheques->currentPage() - 1) * $cheques->perPage();
                                @endphp
                                @forelse ($cheques as $cheque)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                                        <!-- Row Index -->
                                        <td class="px-4 py-4 text-sm font-medium text-bgray-700 dark:text-bgray-50 whitespace-nowrap">
                                            {{ $startNumber + $loop->iteration }}
                                        </td>

                                        <!-- 1. Cheque # -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                {{ $cheque->cheque_number }}
                                            </span>
                                        </td>

                                        <!-- 2. Amount -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold text-bgray-900 dark:text-white">
                                                {{ $globalCompanyCurrency . ' ' . number_format($cheque->amount, 2) }}
                                            </span>
                                        </td>

                                        <!-- 3. Cheque Date -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                                @appDate($cheque->cheque_date)
                                            </span>
                                        </td>

                                        <!-- 4. Cheque Given -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                @appDate($cheque->cheque_given)
                                            </span>
                                        </td>

                                        <!-- 5. Cheque To -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                {{ $cheque->cheque_to }}
                                            </span>
                                        </td>

                                        <!-- 6. Purpose -->
                                        <td class="px-4 py-4">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300 line-clamp-2 max-w-xs" title="{{ $cheque->purpose }}">
                                                {{ $cheque->purpose }}
                                            </span>
                                        </td>

                                        <!-- 7. Cheque Status -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $cheque->cheque_status === \App\Models\Cheque::STATUS_DEBITED ? 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-600' : 'bg-warning-50 text-warning-400 dark:bg-darkblack-500 dark:text-warning-600' }}">
                                                {{ strtoupper($cheque->cheque_status) }}
                                            </span>
                                        </td>

                                        <!-- 8. Debited Date -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                                @if ($cheque->debited_date)
                                                    @appDate($cheque->debited_date)
                                                @else
                                                    --
                                                @endif
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-1">
                                                @can('check_expense.view')
                                                    <x-view-button action="javascript:void(0)" class="open-show-cheque-modal-btn" data-cheque-id="{{ $cheque->id }}" data-show-url="{{ route('cheques.show', $cheque->id) }}" title="View Cheque Details" />
                                                @endcan
                                                @can('check_expense.edit')
                                                    <x-edit-button action="javascript:void(0)" class="open-edit-cheque-modal-btn" data-cheque-id="{{ $cheque->id }}" data-update-url="{{ route('cheques.update', $cheque->id) }}" data-fetch-url="{{ route('cheques.edit', $cheque->id) }}" data-cheque='@json($cheque)' title="Edit Cheque" />
                                                @endcan
                                                @can('check_expense.delete')
                                                    <x-delete-form :action="route('cheques.destroy', $cheque->id)" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="10" message="No cheque expenses found." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <x-pagination :paginator="$cheques" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>

    <!-- Filter Drawer -->
    <x-filters.drawer>
        <x-filters.date-range label="Cheque Date" startName="cheque_date_from" endName="cheque_date_to" />
        <x-filters.date-range label="Cheque Given" startName="cheque_given_from" endName="cheque_given_to" />
        <x-filters.date-range label="Debit Date" startName="debited_date_from" endName="debited_date_to" />
        <x-filters.select name="cheque_status" label="Cheque Status" class="tom-select-no-search w-full" :options="$status_options" />
    </x-filters.drawer>

    <!-- Show Details Modal -->
    @include('cheques.show-modal')

    <!-- Create/Edit Form Modal -->
    @include('cheques.form-modal')
@endsection
