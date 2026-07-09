@extends('layouts.master')

@section('page-content')
    @php
        $canAssignAppraisals = auth()->user()?->can('appraisal.create');
    @endphp

    <div class="space-y-6" data-appraisal-root data-assignment-url="{{ route('appraisal.assignment-data') }}" data-submit-url="{{ route('appraisal.assign') }}" data-can-assign="{{ $canAssignAppraisals ? 'true' : 'false' }}">
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
            <div class="rounded-lg bg-white px-6 py-10 text-center shadow-sm dark:bg-darkblack-600">
                <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">My Appraisals</h3>
                <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">Self-review will be available in a future update.</p>
            </div>
        </section>

        @if ($canAssignAppraisals)
            <section class="hidden" data-appraisal-tab-panel="assign">
                <div class="grid gap-6 xl:grid-cols-[minmax(320px,420px)_1fr]">
                    <div class="rounded-lg bg-white px-5 py-5 shadow-sm dark:bg-darkblack-600">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Users</h3>
                                <p class="text-sm text-bgray-600 dark:text-bgray-300"><span data-appraisal-selected-count>0</span> selected</p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-xs font-semibold text-success-400 transition hover:border-success-300 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-users-select-all>Select All</button>
                                <button type="button" class="rounded-lg border border-bgray-200 bg-bgray-50 px-3 py-2 text-xs font-semibold text-bgray-700 transition hover:border-bgray-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-users-clear>Clear All</button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <input type="search" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Search users" data-appraisal-user-search>
                        </div>

                        <div class="mt-4 max-h-[560px] space-y-2 overflow-y-auto pr-1" data-appraisal-users></div>
                    </div>

                    <div class="rounded-lg bg-white px-5 py-5 shadow-sm dark:bg-darkblack-600">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Categories & Questions</h3>
                                <p class="text-sm text-bgray-600 dark:text-bgray-300">Edit this assignment snapshot without changing settings.</p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-submit="draft">Submit</button>
                                <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-appraisal-submit="published">Submit & Publish</button>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4" data-appraisal-categories></div>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal.js')
@endpush
