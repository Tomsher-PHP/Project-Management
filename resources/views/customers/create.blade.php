@extends('layouts.master')

@section('page-content')
    <!-- write your code here-->
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                    <div class="col-span-12 w-full">
                        <div class="border-b border-bgray-200 pb-5 flex flex-row items-center gap-3">
                            <x-back-button />
                            <h3 class="text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Create New Customer
                            </h3>
                        </div>

                        <div class="mt-8">
                            @include('customers._form', ['customer' => null, 'customerCode' => $customerCode])
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- write your code here-->

    <!-- Modal content start -->
    <x-add-form-modal modalId="multi-step-modal" module="Contact" formId="customerContactForm" action="#" button="Add">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label for="extra_contact_name" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Name <x-red-star />
                </label>
                <input type="text" id="extra_contact_name" name="name" placeholder="Enter name" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Email -->
            <x-forms.email-input label="Email" name="email" id="extra_contact_email" placeholder="Enter email address" domain-suffix="@gmail.com" />

            <!-- Designation -->
            <div class="flex flex-col gap-2">
                <label for="extra_contact_designation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Designation
                </label>
                <input type="text" id="extra_contact_designation" name="designation" placeholder="Enter designation" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Mobile -->
            <div class="flex flex-col gap-2">
                <label for="extra_contact_mobile" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Mobile Number
                </label>
                <input type="text" id="extra_contact_mobile" name="mobile" placeholder="Enter mobile number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Landline -->
            <div class="flex flex-col gap-2">
                <label for="extra_contact_landline" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    Landline
                </label>
                <input type="text" id="extra_contact_landline" name="landline" placeholder="Enter landline" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- WhatsApp -->
            <div class="flex flex-col gap-2">
                <label for="extra_contact_whatsapp" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                    WhatsApp Number
                </label>
                <input type="text" id="extra_contact_whatsapp" name="whatsapp" placeholder="Enter WhatsApp number" class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>

            <!-- Contact Person Login Access -->
            <div class="md:col-span-2 mt-2">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    <input type="checkbox"
                        name="needs_login"
                        value="1"
                        data-login-toggle
                        data-login-target="extra-contact-login-fields"
                        class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-0"
                    >
                    <span>This contact person needs login access</span>
                </label>
            </div>

            <!-- Login Fields -->
            <div id="extra-contact-login-fields" class="contact-login-fields md:col-span-2 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Username -->
                    <div class="flex flex-col gap-2">
                        <label for="extra_contact_username" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Username <x-red-star />
                        </label>
                        <input
                            type="text"
                            name="username"
                            id="extra_contact_username"
                            placeholder="Enter username"
                            autocomplete="username"
                            disabled
                            class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"
                        >
                    </div>

                    <!-- Password -->
                    <div class="flex flex-col gap-2">
                        <label for="extra_contact_password" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Password <x-red-star />
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="extra_contact_password"
                                placeholder="Enter password"
                                autocomplete="new-password"
                                disabled
                                class="w-full rounded-lg border border-gray-300 p-2 pr-10 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"
                            >
                            <button
                                type="button"
                                data-password-toggle
                                data-target="extra_contact_password"
                                class="absolute inset-y-0 right-3 flex items-center text-bgray-500 hover:text-success-300 dark:text-bgray-50"
                                aria-label="Show password"
                                title="Show password"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="flex flex-col gap-2">
                        <label for="extra_contact_password_confirmation" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Confirm Password <x-red-star />
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password_confirmation"
                                id="extra_contact_password_confirmation"
                                placeholder="Confirm password"
                                autocomplete="new-password"
                                disabled
                                class="w-full rounded-lg border border-gray-300 p-2 pr-10 focus:border-success-300 focus:ring-0 bg-white text-gray-900 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"
                            >
                            <button
                                type="button"
                                data-password-toggle
                                data-target="extra_contact_password_confirmation"
                                class="absolute inset-y-0 right-3 flex items-center text-bgray-500 hover:text-success-300 dark:text-bgray-50"
                                aria-label="Show password"
                                title="Show password"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </x-add-form-modal>
@endsection

@push('scripts')
    @vite('resources/js/modules/customer-contact.js')
@endpush
