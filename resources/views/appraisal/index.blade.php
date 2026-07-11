@extends('layouts.master')

@section('page-content')
    @php
        $canAssignAppraisals = auth()->user()?->can('appraisal.create');
    @endphp

    <div class="space-y-1" data-appraisal-root data-auth-user-id="{{ auth()->id() }}" data-assignment-url="{{ route('appraisal.assignment-data') }}" data-submit-url="{{ route('appraisal.assign') }}" data-publish-url="{{ route('appraisal.publish') }}" data-show-url-template="{{ route('appraisal.show', ['appraisal' => '__ID__']) }}" data-unpublish-url-template="{{ route('appraisal.unpublish', ['appraisal' => '__ID__']) }}" data-agree-kpi-url-template="{{ route('appraisal.agree-kpi', ['appraisal' => '__ID__']) }}" data-answer-form-url-template="{{ route('appraisal.answer-form', ['appraisal' => '__ID__']) }}"
        data-submit-answers-url-template="{{ route('appraisal.submit-answers', ['appraisal' => '__ID__']) }}" data-save-draft-url-template="{{ route('appraisal.save-draft', ['appraisal' => '__ID__']) }}" data-can-assign="{{ $canAssignAppraisals ? 'true' : 'false' }}">
        <script type="application/json" data-appraisal-initial-data>
            @json($assignmentData)
        </script>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200" data-appraisal-tab-button data-tab="my">
                    My Appraisals
                </button>
                @if ($canAssignAppraisals)
                    <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-bgray-50 px-4 py-2 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-tab-button data-tab="assign">
                        Assign Appraisals
                    </button>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select class="tom-select-no-search min-w-[150px]" data-appraisal-month>
                    @foreach ($months as $monthValue => $monthLabel)
                        <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $month)>{{ $monthLabel }}</option>
                    @endforeach
                </select>

                <select class="tom-select-no-search min-w-[120px]" data-appraisal-year>
                    @foreach ($years as $yearValue => $yearLabel)
                        <option value="{{ $yearValue }}" @selected((int) $yearValue === (int) $year)>{{ $yearLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <section data-appraisal-tab-panel="my">
            <div class="rounded-lg bg-white px-6 py-5 shadow-sm dark:bg-darkblack-600">
                <div class="table-content w-full overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <th class="px-4 py-4 text-left xl:w-[300px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">User</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[170px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Self Submitted</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[170px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reporter Submitted</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[170px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Manager Submitted</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[160px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">KPI Agreed</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[130px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">KPI Agreement</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[100px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody data-appraisal-my-list></tbody>
                    </table>
                </div>
            </div>
        </section>

        @if ($canAssignAppraisals)
            <section class="hidden" data-appraisal-tab-panel="assign">
                <div class="rounded-lg bg-white px-5 py-5 shadow-sm dark:bg-darkblack-600">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Assign Appraisals</h3>
                            <p class="text-sm text-bgray-600 dark:text-bgray-300"><span data-appraisal-selected-count>0</span> selected</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-open-assign disabled>
                                Assign
                            </button>
                            <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-publish-selected disabled>
                                Publish Selected
                            </button>
                            <div class="min-w-[240px]">
                                <input type="search" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Search users" data-appraisal-user-search>
                            </div>
                        </div>
                    </div>

                    <div class="table-content mt-5 w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <th class="px-4 py-4 text-left xl:w-[60px] xl:px-0">
                                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-users-select-all>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">User</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[220px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">KPI</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[260px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Categories</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[150px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[120px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody data-appraisal-users></tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @if ($canAssignAppraisals)
            <div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6" data-appraisal-assign-modal>
                <div class="max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white" data-appraisal-modal-title>Assign Appraisal</h3>
                        <button type="button" class="text-2xl leading-none text-bgray-500 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-appraisal-modal-close aria-label="Close">×</button>
                    </div>

                    <div class="max-h-[calc(92vh-145px)] overflow-y-auto px-6 py-5">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white" data-appraisal-modal-selected-count>0 Users Selected</p>
                            <div class="mt-3 flex flex-wrap gap-2" data-appraisal-modal-selected-users></div>
                        </div>

                        <div class="mt-5">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">KPI <x-red-star /></label>
                            <select class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-appraisal-kpi-select>
                                <option value="">Select KPI</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">KPI Description</label>
                            <div class="custom-quill">
                                <div class="h-48 min-h-[110px] rounded-b-lg bg-white dark:bg-darkblack-500" data-appraisal-kpi-description></div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <h4 class="text-base font-bold text-bgray-900 dark:text-white">Categories & Questions</h4>
                            </div>

                            <div class="space-y-4" data-appraisal-modal-categories></div>
                            <button type="button" class="mt-4 rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-assignment-category-add>
                                + Add Category
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-modal-close>Cancel</button>
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-submit="draft">Assign</button>
                        <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-appraisal-submit="published">Assign & Publish</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="modal fixed inset-0 z-[90] hidden items-center justify-center overflow-y-auto" data-appraisal-kpi-agreement-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-appraisal-kpi-agreement-close></div>

            <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">KPI Agreement</h3>
                        <button type="button" class="text-2xl leading-none text-bgray-500 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-appraisal-kpi-agreement-close aria-label="Close">×</button>
                    </div>

                    <div class="max-h-[calc(85vh-145px)] overflow-y-auto px-6 py-5">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">KPI Title</p>
                            <h4 class="mt-2 text-lg font-bold text-bgray-900 dark:text-white" data-appraisal-kpi-agreement-title></h4>
                        </div>

                        <div class="mt-4 rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">KPI Description</p>
                            <div class="prose prose-sm mt-3 max-w-none text-bgray-700 dark:prose-invert dark:text-bgray-100" data-appraisal-kpi-agreement-description></div>
                        </div>

                        <label class="mt-5 flex items-start gap-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <input type="checkbox" class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-kpi-agreement-checkbox>
                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-100">I have read, understood and agree to the KPI and expectations for this appraisal.</span>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-kpi-agreement-close>Cancel</button>
                        <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-kpi-agreement-submit disabled>Agree & Continue</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fixed inset-0 z-[90] hidden items-center justify-center overflow-y-auto" data-appraisal-answer-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-appraisal-answer-close></div>

            <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-7xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <div>
                            <h3 class="text-xl font-bold text-bgray-900 dark:text-white" data-appraisal-answer-modal-title>Answer Appraisal</h3>
                            <p class="mt-1 text-sm font-medium text-bgray-500 dark:text-bgray-300" data-appraisal-answer-meta></p>
                        </div>
                        <button type="button" class="text-2xl leading-none text-bgray-500 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-appraisal-answer-close aria-label="Close">×</button>
                    </div>

                    <div class="grid h-[calc(85vh-150px)] grid-cols-1 overflow-hidden lg:grid-cols-4">
                        <div class="h-full overflow-y-auto px-6 py-2 lg:col-span-3">
                            <div class="mt-2">
                                <h4 class="text-lg font-bold text-bgray-900 dark:text-white" data-appraisal-answer-category-title></h4>
                                <div class="mt-2 space-y-2" data-appraisal-answer-questions></div>
                            </div>
                        </div>

                        <aside class="border-t border-bgray-200 bg-bgray-50 px-4 py-5 dark:border-darkblack-400 dark:bg-darkblack-500 lg:h-full lg:overflow-y-auto lg:border-l lg:border-t-0">
                            <!-- Overall Progress Card -->
                            <div class="mb-6 rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                                <h5 class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">Overall Progress</h5>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm font-bold text-bgray-900 dark:text-white" data-appraisal-answer-overall-count>0 / 0 Questions</span>
                                    <span class="text-sm font-bold text-success-500 dark:text-success-300" data-appraisal-answer-overall-percentage>0%</span>
                                </div>
                                <div class="mt-2 h-1.5 w-full rounded-full bg-bgray-100 dark:bg-darkblack-500">
                                    <div class="h-1.5 rounded-full bg-success-300 transition-all duration-300" data-appraisal-answer-overall-bar style="width: 0%"></div>
                                </div>
                            </div>

                            <h4 class="text-sm font-bold uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">Categories</h4>
                            <div class="mt-4 space-y-2" data-appraisal-answer-categories></div>
                        </aside>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 bg-white dark:bg-darkblack-600">
                        <div class="text-sm text-red-500 font-medium" data-appraisal-answer-helper-message>
                            All questions must be answered before submitting. You can save your progress as a draft anytime.
                        </div>
                        <div class="flex items-center gap-3 ml-auto">
                            <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-answer-close>Cancel</button>
                            <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-answer-save-draft>Save Draft</button>
                            <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-answer-submit disabled>Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal.js')
@endpush
