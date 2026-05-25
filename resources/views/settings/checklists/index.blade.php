@extends('layouts.master')

@section('page-content')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">

            <div class="flex flex-wrap items-center gap-3">
                @can('checklist_template.create')
                    <button type="button" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-module="Checklist Template" data-url="{{ route('settings.checklists.store') }}" data-method="POST">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Checklist Template</span>
                    </button>
                @endcan

                <x-filters.button />
            </div>

            <div class="grid gap-3 md:grid-cols-3">

                <div class="flex items-center justify-between rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-darkblack-600 gap-2">
                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">Total</span>
                    <span class="text-lg font-bold text-bgray-900 dark:text-white">
                        {{ $stats['total'] ?? 0 }}
                    </span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-darkblack-600 gap-2">
                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">Active</span>
                    <span class="text-lg font-bold text-success-400">
                        {{ $stats['active'] ?? 0 }}
                    </span>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-darkblack-600 gap-2">
                    <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">System</span>
                    <span class="text-lg font-bold text-warning-300">
                        {{ $stats['system'] ?? 0 }}
                    </span>
                </div>

            </div>

        </div>

        <div id="checklist-template-index-content">
            @include('settings.checklists.partials.index-content')
        </div>

    <x-form-modal modalId="multi-step-modal" module="Checklist Template" formId="checklistForm" action="{{ route('settings.checklists.store') }}" button="Create Checklist Template" method="POST">

        <div class="md:col-span-2">
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="e.g. Website Launch Checklist">
        </div>

        <div class="md:col-span-2" data-checklist-question-builder>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <label class="block text-left text-sm text-bgray-700 dark:text-bgray-50">Questions <x-red-star /></label>
                </div>

                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-semibold text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-checklist-question-add>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Question</span>
                </button>
            </div>

            <div class="mt-4 space-y-3" data-checklist-question-list>
                <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-checklist-question-item>
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-checklist-question-number>1</span>
                        <div class="flex-1">
                            <input type="text" name="questions[]" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter a checklist question">
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-checklist-question-remove aria-label="Remove question">
                            ✕
                        </button>
                    </div>
                </div>
            </div>

            <template id="checklist-question-template">
                <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-checklist-question-item>
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-checklist-question-number></span>
                        <div class="flex-1">
                            <input type="text" name="questions[]" class="w-full rounded-lg border border-gray-300 p-2.5 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Enter a checklist question">
                        </div>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-checklist-question-remove aria-label="Remove question">
                            ✕
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-form-modal>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Checklist Template Name" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
@endsection

@push('scripts')
    @vite('resources/js/modules/checklist-template-form.js')
@endpush
