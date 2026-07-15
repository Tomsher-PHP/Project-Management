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

    <x-form-modal modalId="multi-step-modal" module="Appraisal Category" formId="appraisalCategoryForm" action="{{ route('settings.appraisal.store') }}" button="Save" maxWidth="max-w-7xl">
        <div class="md:col-span-2">
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Category Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. Communication">
        </div>

        <div class="md:col-span-2">
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Default Category</label>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_default" value="0" data-appraisal-category-default-input>
                <button type="button" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-appraisal-category-default-toggle role="switch" aria-checked="false" aria-label="Toggle default status">
                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
                <span class="text-xs font-medium text-bgray-500 dark:text-bgray-300" data-appraisal-category-default-label>Disabled</span>
            </div>
        </div>

        <div class="md:col-span-2" data-appraisal-question-builder data-appraisal-target-question-type="{{ $targetQuestionType }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="block text-left text-sm text-bgray-700 dark:text-bgray-50">Questions <x-red-star /></label>

                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-semibold text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-question-add>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Question</span>
                </button>
            </div>

            <div class="mt-4 space-y-3" data-appraisal-question-list></div>

            <template id="appraisal-question-template">
                <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-question-item>
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start">
                        <div class="flex items-center gap-2 xl:pt-6">
                            <button type="button" class="inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 hover:border-success-200 hover:text-success-400 active:cursor-grabbing dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" data-appraisal-question-handle aria-label="Drag question">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </button>
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-appraisal-question-number></span>
                        </div>

                        <div class="grid min-w-0 flex-1 gap-3 lg:grid-cols-12">
                            <input type="hidden" name="question_ids[]" data-appraisal-question-id>
                            <input type="hidden" name="question_is_active[]" value="1" data-appraisal-question-active-input>

                            <label class="block lg:col-span-9">
                                <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Question</span>
                                <input type="text" name="questions[]" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter an appraisal question">
                            </label>

                            <label class="block lg:col-span-3">
                                <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Question Type</span>
                                <select name="question_types[]" class="tom-select-no-search w-full" data-appraisal-question-type>
                                    @foreach ($questionTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="hidden flex-col gap-3 md:flex-row lg:col-span-12" data-appraisal-target-fields>
                                <label class="block min-w-0 flex-1">
                                    <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Measurement Type <x-red-star /></span>
                                    <select name="measurement_types[]" class="tom-select-no-search w-full" data-appraisal-measurement-type>
                                        <option value="">Select measurement type</option>
                                        @foreach ($measurementTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block min-w-0 flex-1">
                                    <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Target Value <x-red-star /></span>
                                    <input type="number" name="target_values[]" step="any" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="e.g. 92.5" data-appraisal-target-value>
                                </label>

                                <label class="block min-w-0 flex-1">
                                    <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Unit <x-red-star /></span>
                                    <select name="units[]" class="tom-select w-full" data-appraisal-unit>
                                        <option value="">Select unit</option>
                                        @foreach ($questionUnits as $unit)
                                            <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 xl:pt-6">
                            <div class="flex min-w-[100px] items-center gap-2 xl:flex-col xl:items-start xl:gap-1">
                                <button type="button" class="switch-btn active relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-appraisal-question-active-toggle role="switch" aria-checked="true" aria-label="Toggle question status">
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                </button>
                                <span class="text-xs font-medium text-success-400 dark:text-success-300" data-appraisal-question-active-label>Enabled</span>
                            </div>
                            <button type="button" class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-appraisal-question-remove aria-label="Remove question">
                                ✕
                            </button>
                        </div>
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
