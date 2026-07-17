@extends('layouts.master')

@section('page-content')
    <div class="space-y-6" data-appraisal-answer-page data-save-draft-url="{{ route('appraisal.save-draft', ['appraisal' => $answerData['id']]) }}" data-submit-answers-url="{{ route('appraisal.submit-answers', ['appraisal' => $answerData['id']]) }}" data-index-url="{{ route('appraisal.index') }}">
        <script type="application/json" data-appraisal-answer-page-data>
            @json($answerData)
        </script>

        <header class="rounded-xl border border-bgray-200 bg-white px-5 py-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-6">
            <h1 class="text-xl font-bold text-bgray-900 dark:text-white sm:text-2xl">
                Answer Appraisal <span class="text-bgray-400">&bull;</span> {{ $answerData['period'] ?? '--' }}
            </h1>

            <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Assignee</dt>
                    <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ data_get($answerData, 'assignee.name', '--') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">KPI</dt>
                    <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $answerData['kpi_name'] ?? '--' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Status</dt>
                    <dd class="mt-1 text-sm font-semibold capitalize text-bgray-900 dark:text-white">{{ $answerData['status'] ?? '--' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Current Stage</dt>
                    <dd class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $answerData['role_label'] ?? '--' }}</dd>
                </div>
            </dl>
        </header>

        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-4">
            <main class="min-h-[480px] min-w-0 rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-6 xl:col-span-3 xl:max-h-[calc(100vh-240px)] xl:overflow-y-auto">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Questions</p>
                    <h2 class="mt-1 text-lg font-bold text-bgray-900 dark:text-white" data-appraisal-answer-category-title></h2>
                </div>

                <div class="mt-5 space-y-2" data-appraisal-answer-questions></div>

                <section class="mt-8 hidden border-t border-bgray-200 pt-6 dark:border-darkblack-400" data-appraisal-overall-comments-section>
                    <h2 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">Overall Comments</h2>
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <span class="text-sm font-bold text-bgray-900 dark:text-white">Reporter Comment</span>
                                <span class="text-xs text-bgray-500 dark:text-bgray-300" data-appraisal-reporter-comment-meta></span>
                            </div>
                            <textarea class="w-full rounded-lg border border-bgray-200 p-3 text-sm focus:border-success-300 focus:ring-success-300 disabled:bg-bgray-50 disabled:opacity-60 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-600" rows="4" placeholder="No comment provided yet." data-appraisal-reporter-comment-textarea disabled></textarea>
                        </div>

                        <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <span class="text-sm font-bold text-bgray-900 dark:text-white">Manager Comment</span>
                                <span class="text-xs text-bgray-500 dark:text-bgray-300" data-appraisal-manager-comment-meta></span>
                            </div>
                            <textarea class="w-full rounded-lg border border-bgray-200 p-3 text-sm focus:border-success-300 focus:ring-success-300 disabled:bg-bgray-50 disabled:opacity-60 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-600" rows="4" placeholder="No comment provided yet." data-appraisal-manager-comment-textarea disabled></textarea>
                        </div>
                    </div>
                </section>
            </main>

            <aside class="w-full space-y-5 xl:sticky xl:top-24 xl:col-span-1 xl:max-h-[calc(100vh-120px)] xl:overflow-y-auto">
                <section class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Overall Progress</h2>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-bgray-900 dark:text-white" data-appraisal-answer-overall-count>0 / 0 Questions</span>
                        <span class="text-sm font-bold text-success-500 dark:text-success-300" data-appraisal-answer-overall-percentage>0%</span>
                    </div>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-bgray-100 dark:bg-darkblack-500">
                        <div class="h-1.5 rounded-full bg-success-300 transition-all duration-300" data-appraisal-answer-overall-bar style="width: 0%"></div>
                    </div>
                </section>

                <section class="rounded-xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Categories</h2>
                    <div class="mt-4 space-y-2" data-appraisal-answer-categories></div>
                </section>
            </aside>
        </div>

        <footer class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-bgray-200 bg-white px-5 py-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:px-6">
            <p class="text-sm font-medium text-red-500" data-appraisal-answer-helper-message>
                All questions must be answered before submitting. You can save your progress as a draft anytime.
            </p>
            <div class="ml-auto flex items-center gap-3">
                <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-answer-save-draft>
                    Save Draft
                </button>
                <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-answer-submit disabled>
                    Submit
                </button>
            </div>
        </footer>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal-answer.js')
@endpush
