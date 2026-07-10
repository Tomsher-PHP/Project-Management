@extends('layouts.master')

@section('page-content')
    @php
        $canAssignAppraisals = auth()->user()?->can('appraisal.create');
    @endphp

    <div class="space-y-6" data-appraisal-root data-assignment-url="{{ route('appraisal.assignment-data') }}" data-submit-url="{{ route('appraisal.assign') }}" data-publish-url="{{ route('appraisal.publish') }}" data-show-url-template="{{ route('appraisal.show', ['appraisal' => '__ID__']) }}" data-unpublish-url-template="{{ route('appraisal.unpublish', ['appraisal' => '__ID__']) }}" data-can-assign="{{ $canAssignAppraisals ? 'true' : 'false' }}">
        <script type="application/json" data-appraisal-initial-data>
            @json($assignmentData)
        </script>

        <section class="rounded-lg bg-white px-6 py-5 shadow-sm dark:bg-darkblack-600">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Appraisals</h2>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">Month</label>
                        <select class="tom-select-no-search min-w-[150px]" data-appraisal-month>
                            @foreach ($months as $monthValue => $monthLabel)
                                <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $month)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">Year</label>
                        <select class="tom-select-no-search min-w-[120px]" data-appraisal-year>
                            @foreach ($years as $yearValue => $yearLabel)
                                <option value="{{ $yearValue }}" @selected((int) $yearValue === (int) $year)>{{ $yearLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        @if ($canAssignAppraisals)
            <div class="flex flex-wrap gap-2">
                <button type="button" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200" data-appraisal-tab-button data-tab="my">
                    My Appraisals
                </button>
                <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-bgray-50 px-4 py-2 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-tab-button data-tab="assign">
                    Assign Appraisals
                </button>
            </div>
        @endif

        <section data-appraisal-tab-panel="my">
            <div class="rounded-lg bg-white px-6 py-5 shadow-sm dark:bg-darkblack-600">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">My Appraisals</h3>
                    </div>
                </div>

                <div class="table-content mt-5 w-full overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <th class="px-4 py-4 text-left xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">User</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[170px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Assignee Submitted At</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[170px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reporter Submitted At</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[170px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Manager Submitted At</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[160px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">KPI Agreed At</span>
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
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal.js')
@endpush
