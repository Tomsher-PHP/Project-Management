@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-back-button :url="route('settings.index')" label="Back" />

        @can('appraisal_settings.create')
            <x-button.create-button type="button" class="modal-open" data-target="#multi-step-modal" data-module="Appraisal Category" data-url="{{ route('settings.appraisal.store') }}" data-method="POST" label="Category" />
        @endcan
    </div>

    <div id="appraisal-category-index-content">
        @include('settings.appraisal-categories.partials.index-content')
    </div>

    <x-form-modal modalId="multi-step-modal" module="Appraisal Category" formId="appraisalCategoryForm" action="{{ route('settings.appraisal.store') }}" button="Save" maxWidth="max-w-5xl">
        <div class="md:col-span-2">
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Category Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. Communication">
        </div>

        <div class="md:col-span-2" data-appraisal-question-builder>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="block text-left text-sm text-bgray-700 dark:text-bgray-50">Questions <x-red-star /></label>

                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-semibold text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-question-add>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Question</span>
                </button>
            </div>

            <div class="mt-4 space-y-3" data-appraisal-question-list>
                <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-question-item>
                    <div class="flex items-start gap-3">
                        <button type="button" class="mt-0.5 inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 hover:border-success-200 hover:text-success-400 active:cursor-grabbing dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" data-appraisal-question-handle aria-label="Drag question">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            </svg>
                        </button>
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-appraisal-question-number>1</span>
                        <div class="flex-1">
                            <input type="hidden" name="question_ids[]" data-appraisal-question-id>
                            <input type="hidden" name="question_is_active[]" value="1" data-appraisal-question-active-input>
                            <input type="text" name="questions[]" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter an appraisal question">
                        </div>
                        <div class="flex min-w-[100px] flex-col items-start gap-1 pt-1">
                            <button type="button" class="switch-btn active relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-appraisal-question-active-toggle role="switch" aria-checked="true" aria-label="Toggle question status">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                            <span class="text-xs font-medium text-success-400 dark:text-success-300" data-appraisal-question-active-label>Enabled</span>
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-appraisal-question-remove aria-label="Remove question">
                            ✕
                        </button>
                    </div>
                </div>
            </div>

            <template id="appraisal-question-template">
                <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-question-item>
                    <div class="flex items-start gap-3">
                        <button type="button" class="mt-0.5 inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 hover:border-success-200 hover:text-success-400 active:cursor-grabbing dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" data-appraisal-question-handle aria-label="Drag question">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            </svg>
                        </button>
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-appraisal-question-number></span>
                        <div class="flex-1">
                            <input type="hidden" name="question_ids[]" data-appraisal-question-id>
                            <input type="hidden" name="question_is_active[]" value="1" data-appraisal-question-active-input>
                            <input type="text" name="questions[]" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter an appraisal question">
                        </div>
                        <div class="flex min-w-[100px] flex-col items-start gap-1 pt-1">
                            <button type="button" class="switch-btn active relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-appraisal-question-active-toggle role="switch" aria-checked="true" aria-label="Toggle question status">
                                <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                            <span class="text-xs font-medium text-success-400 dark:text-success-300" data-appraisal-question-active-label>Enabled</span>
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-appraisal-question-remove aria-label="Remove question">
                            ✕
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-form-modal>
    <!-- Page ends -->
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appaisal-settings.js')
@endpush
