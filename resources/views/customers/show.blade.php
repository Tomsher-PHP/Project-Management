@extends('layouts.master')

@section('page-content')
    <section class="space-y-6" data-customer-detail-tabs data-default-tab="contact">
        @include('customers.partials.header')

        <div class="rounded-[20px] bg-white px-4 py-4 shadow-sm dark:bg-darkblack-600 xl:px-5 xl:py-5">
            <div class="mb-4 overflow-x-auto border-b border-bgray-200 dark:border-darkblack-400">
                <div class="flex min-w-max items-center gap-5">
                    <button type="button" role="tab" aria-selected="true" data-customer-tab-trigger="contact" class="border-b-2 border-success-300 pb-2.5 text-[15px] font-semibold text-success-400 transition">
                        Contact
                    </button>
                    <button type="button" role="tab" aria-selected="false" data-customer-tab-trigger="profile-grade" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-700 transition dark:text-bgray-300">
                        Profile Grade
                    </button>
                    <button type="button" role="tab" aria-selected="false" data-customer-tab-trigger="address" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-700 transition dark:text-bgray-300">
                        Address
                    </button>
                </div>
            </div>

            <div data-customer-tab-panels>
                <div role="tabpanel" aria-label="Customer contacts" data-customer-tab-panel="contact">
                    @include('customers.partials.tabs.contacts')
                </div>
                <div class="hidden" role="tabpanel" aria-label="Customer profile grade" data-customer-tab-panel="profile-grade">
                    @include('customers.partials.tabs.profile-grade')
                </div>
                <div class="hidden" role="tabpanel" aria-label="Customer address" data-customer-tab-panel="address">
                    <section class="rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                        <h3 class="text-base font-bold text-bgray-900 dark:text-white">Company Address</h3>

                        <div class="mt-4 rounded-xl bg-bgray-50 p-4 text-sm leading-6 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                            @if (filled($customer->company_address))
                                <p class="whitespace-pre-line break-words">{{ $customer->company_address }}</p>
                            @else
                                <p>No company address available.</p>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>

        @include('customers.partials.modals.profile-grade')
    </section>
@endsection

@push('scripts')
    @vite('resources/js/modules/customer/profile-grade.js')
@endpush
