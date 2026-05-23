@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                @can('customer.create')
                    <a href="{{ route('customers.create') }}" class="inline-flex items-center px-4 py-1.5
                       rounded-md bg-success-300
                       text-sm font-semibold text-white
                       hover:bg-success-400
                       transition duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>

                        <span>New Customer</span>
                    </a>
                @endcan

                <x-filters.button />
            </div>

            @can('customer.restore')
                <a href="{{ route('customers.restore.index') }}" class="inline-flex items-center gap-2 rounded-md border border-success-300 px-4 py-1.5 text-sm font-semibold text-success-400 transition duration-200 hover:bg-success-300 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h8" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 17h8" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 8l5 4-5 4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12h-8" />
                    </svg>
                    <span>Restore Customers</span>
                </a>
            @endcan
        </div>

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <x-sorting.sortable-column column="name" label="Company Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Industry</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Country</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Sales Person</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($customers->currentPage() - 1) * $customers->perPage();
                                @endphp
                                @forelse ($customers as $key => $customer)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-5">
                                                <div class="flex-1">
                                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                                        {{ $customer->name }}
                                                    </h4>
                                                    <div class="flex flex-col">
                                                        <span class="text-base font-medium text-bgray-700 dark:text-bgray-50">Customer Code: {{ $customer->customer_code }}</span>
                                                        <span class="text-gray-500 dark:text-bgray-50">Email: {{ $customer->email }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">{{ $customer->industry->name ?? '--' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex flex-col w-full">

                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">
                                                    {{ $customer->country->name ?? '--' }}
                                                </span>

                                                @if (!empty($customer->emirate))
                                                    <span class="text-sm text-gray-500 dark:text-bgray-300 px-4">
                                                        {{ ucfirst($customer->emirate) }}
                                                    </span>
                                                @endif

                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:text-bgray-50">{{ $customer->salesPerson?->name ?? '--' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$customer" route="customers.toggleStatus" entity="customer" permission="customer.edit" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('customer.edit')
                                                    <x-edit-button :action="route('customers.edit', $customer->id)" />
                                                @endcan
                                                @can('customer.delete')
                                                    <x-delete-form :action="route('customers.destroy', $customer->id)" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="8" message="No customers found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$customers" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="name" label="Name" />
        <x-filters.input name="email" label="Company Email" />
        <x-filters.multi-select name="industry_id" label="Industry" :options="$industries" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->
@endsection
