@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 rounded-md border border-bgray-300 px-4 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:text-bgray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Back</span>
            </a>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="rounded-md bg-success-300 px-4 py-1.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-customer-restore-bulk-button disabled>
                    Bulk Restore
                </button>

                <form method="POST" action="{{ route('customers.restore.bulk') }}" class="hidden" data-customer-restore-bulk-form>
                    @csrf
                    <div data-customer-restore-bulk-hidden-inputs></div>
                </form>
            </div>
        </div>

        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">
                        <div class="table-content w-full overflow-x-auto">
                            <table class="w-full">
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="px-6 py-5 xl:px-0">
                                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-customer-restore-select-all>
                                    </td>
                                    <td>
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Company Name</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Email</span>
                                    </td>
                                    <td class="px-6 py-5 xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($customers->currentPage() - 1) * $customers->perPage();
                                @endphp
                                @forelse ($customers as $customer)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <input type="checkbox" value="{{ $customer->id }}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-customer-restore-checkbox>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-5">
                                                <div class="flex-1">
                                                    <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                                                        {{ $customer->name }}
                                                    </h4>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm text-gray-500 dark:text-bgray-300">Customer Code: {{ $customer->customer_code }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base text-bgray-600 dark:text-bgray-50">{{ $customer->email ?? '--' }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <form action="{{ route('customers.restore', $customer->id) }}" method="POST" data-customer-restore-form>
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-md bg-success-300 px-4 py-1.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400">
                                                    Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="5" message="No deleted customers found." />
                                @endforelse
                            </table>
                        </div>

                        <x-pagination :paginator="$customers" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/modules/customer-restore.js')
@endpush
