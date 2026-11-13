
@extends('layouts.master')

@section('page-content')
    <div class="2xl:flex 2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                    <div class="col-span-12 w-full">

                        {{-- Page Header --}}
                        <div class="flex flex-row items-center gap-3 border-b border-bgray-200 pb-5">
                            <x-back-button />
                            <h3 class="text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Create New Leave Request
                            </h3>
                        </div>

                        {{-- Validation Errors --}}
                        @if ($errors->any())
                            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Leave Request Form --}}
                        <div class="mt-8">
                            <form
                                id="create-leave-request-form"
                                action="{{ route('leave-requests.store') }}"
                                data-unpaid-leave-confirm
                                method="POST"
                                enctype="multipart/form-data">

                                @csrf

                                <input type="hidden" name="user_id" value="{{ auth()->id() }}">

                                @include('leave_requests._form', [
                                    'isCreate' => true,
                                    'approvalMode' => false,
                                    'fullEdit' => true,
                                    'restrictedEdit' => false,
                                    'leaveRequest' => null,
                                ])

                                {{-- Form Buttons --}}
                                <div class="mt-8 flex justify-end gap-3 border-t border-bgray-200 pt-5">
                                    <a
                                        href="{{ route('leave-requests.index') }}"
                                        class="rounded-lg border border-bgray-200 px-5 py-2.5 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:text-white">
                                        Cancel
                                    </a>

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-success-400">
                                        Submit Leave Request
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
