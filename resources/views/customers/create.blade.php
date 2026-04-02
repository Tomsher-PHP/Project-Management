@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="col-span-12 w-full">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Create New Customer
                            </h3>

                            <div class="mt-8">
                                @include('customers._form', ['customer' => null, 'customerCode' => $customerCode])
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>

    <!-- Modal content start -->
    <x-add-form-modal modalId="multi-step-modal" module="Contact" formId="customerContactForm" action="#" button="Add">

        <div class="grid grid-cols-2 gap-4">

            <!-- Name -->
            <div class="flex flex-col">
                <input type="text" name="name" placeholder="Name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Email -->
            <div class="flex flex-col">
                <input type="email" name="email" placeholder="Email" oninput="this.value = this.value.toLowerCase()" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Designation -->
            <div class="flex flex-col">
                <input type="text" name="designation" placeholder="Designation" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Mobile -->
            <div class="flex flex-col">
                <input type="text" name="mobile" placeholder="Mobile Number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Landline -->
            <div class="flex flex-col">
                <input type="text" name="landline" placeholder="Landline" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- WhatsApp -->
            <div class="flex flex-col">
                <input type="text" name="whatsapp" placeholder="WhatsApp Number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

        </div>

    </x-add-form-modal>
@endsection

@push('scripts')
    @vite('resources/js/modules/customer-contact.js')
@endpush
