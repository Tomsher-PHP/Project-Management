@extends('layouts.master')

@section('page-content')
        <section class="space-y-6" data-task-tabs data-task-id="{{ $task->id }}" data-default-tab="overview" data-tabs-url-template="{{ $tabsUrlTemplate }}" data-project-tasks-root data-project-task-response-mode="reload">
            <div id="task-detail-header">
                @include('tasks.partials.header')
            </div>

            <div class="rounded-[20px] bg-white px-4 py-4 shadow-sm dark:bg-darkblack-600 xl:px-5 xl:py-5">
                <div class="mb-4 flex flex-col gap-3 border-b border-bgray-200 pb-4 dark:border-darkblack-400 xl:flex-row xl:items-center xl:justify-between">
                    <div class="overflow-x-auto">
                        <div class="flex min-w-max items-center gap-5">
                            <button type="button" data-task-tab-trigger="overview" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-700 dark:text-bgray-300 transition">
                                Overview
                            </button>

                            <button type="button" data-task-tab-trigger="scope" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-700 dark:text-bgray-300 transition">
                                Project Scope
                            </button>

                            <button type="button" data-task-tab-trigger="notes" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-700 dark:text-bgray-300 transition">
                                Notes & Files
                            </button>

                            <button type="button" data-task-tab-trigger="history" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-700 dark:text-bgray-300 transition">
                                History
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @can('activity_log.view')
                            <button type="button" data-task-insights-trigger data-task-insights-url="{{ route('tasks.activity.modal', $task) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                                <span class="inline-flex h-4 w-4 items-center justify-center text-bgray-600 dark:text-bgray-300">
                                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 4.5V9L12 10.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15.75 9C15.75 12.7279 12.7279 15.75 9 15.75C5.27208 15.75 2.25 12.7279 2.25 9C2.25 5.27208 5.27208 2.25 9 2.25C12.7279 2.25 15.75 5.27208 15.75 9Z" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>
                                <span>Activity</span>
                                <span class="inline-flex h-5 min-w-[1.15rem] items-center justify-center rounded-full bg-bgray-100 px-1.5 text-[10px] font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                    {{ $taskActivitiesCount }}
                                </span>
                            </button>
                        @endcan

                        <button type="button" data-task-insights-trigger data-task-insights-url="{{ route('tasks.comments.modal', $task) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                            <span class="inline-flex h-4 w-4 items-center justify-center text-bgray-600 dark:text-bgray-300">
                                <svg width="14" height="14" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.25 6.75H12.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M5.25 9.75H10.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M6.75 14.25L4.13388 15.9931C3.80201 16.2143 3.375 15.9764 3.375 15.5776V4.5C3.375 3.67157 4.04657 3 4.875 3H13.125C13.9534 3 14.625 3.67157 14.625 4.5V12C14.625 12.8284 13.9534 13.5 13.125 13.5H7.58211C7.28548 13.5 6.99551 13.5879 6.75 13.7525V14.25Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>Comments</span>
                            <span class="inline-flex h-5 min-w-[1.15rem] items-center justify-center rounded-full bg-bgray-100 px-1.5 text-[10px] font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50" data-task-comments-count>
                                {{ $taskCommentsCount }}
                            </span>
                        </button>

                        @if ($project && auth()->user()->can('view', $project))
                            <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                                <span>Open Project</span>
                            </a>
                        @endif

                        @can('delete', $task)
                            <x-delete-form :action="route('tasks.destroy', $task)" />
                        @endcan
                    </div>
                </div>

                <div data-task-tab-panels>
                    <div data-task-tab-panel="overview" data-loaded="false"></div>

                    <div class="hidden" data-task-tab-panel="scope" data-loaded="false"></div>
                    <div class="hidden" data-task-tab-panel="notes" data-loaded="false"></div>
                    <div class="hidden" data-task-tab-panel="history" data-loaded="false"></div>
                </div>
            </div>

            <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-project-task-detail-modal>
                <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-detail-close></div>

                <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                    <div class="relative z-10 w-full max-w-7xl" data-project-task-detail-content></div>
                </div>
            </div>
        </section>

        @include('tasks.partials.modals.insights-modal')
@endsection

@push('scripts')
    @vite('resources/js/modules/task-detail.js')
@endpush
