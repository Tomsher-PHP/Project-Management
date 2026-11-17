@extends('layouts.master')

@section('page-content')
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                    <div class="col-span-12 w-full">
                        <div class="flex flex-row items-center gap-3 border-b border-bgray-200 pb-5">
                            <x-back-button />
                            <h3 class="text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Edit Customer
                            </h3>
                        </div>

                        <div class="mt-8">
                            @include('customers._form')
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <x-add-form-modal modalId="multi-step-modal" module="Contact" formId="customerContactForm" action="#" button="Add">
        @include('customers._contact-modal-fields')
    </x-add-form-modal>
@endsection

@push('scripts')
    @vite('resources/js/modules/customer-contact.js')
@endpush
