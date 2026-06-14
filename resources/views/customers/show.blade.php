@extends('layouts.master')

@section('page-content')
    <section class="space-y-6">
        @include('customers.partials.header')

        <div class="rounded-[20px] bg-white px-4 py-4 shadow-sm dark:bg-darkblack-600 xl:px-5 xl:py-5">
            <div class="mb-4 overflow-x-auto border-b border-bgray-200 dark:border-darkblack-400">
                <div class="flex min-w-max items-center gap-5">
                    <button type="button" role="tab" aria-selected="true" class="border-b-2 border-success-300 pb-2.5 text-[15px] font-semibold text-success-400 transition">
                        Contact
                    </button>
                </div>
            </div>

            <div role="tabpanel" aria-label="Customer contacts">
                @include('customers.partials.tabs.contacts')
            </div>
        </div>
    </section>
@endsection
