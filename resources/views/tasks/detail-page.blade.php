@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <section class="space-y-6" data-task-tabs data-task-id="{{ $task->id }}" data-default-tab="overview" data-tabs-url-template="{{ $tabsUrlTemplate }}">
            <div id="task-detail-header">
                @include('tasks.partials.header')
            </div>

            <div class="rounded-[20px] bg-white px-4 py-4 shadow-sm dark:bg-darkblack-600 xl:px-5 xl:py-5">
                <div class="mb-4 flex flex-col gap-3 border-b border-bgray-200 pb-4 dark:border-darkblack-400 xl:flex-row xl:items-center xl:justify-between">
                    <div class="overflow-x-auto">
                        <div class="flex min-w-max items-center gap-5">
                            <button type="button" data-task-tab-trigger="overview" class="border-b-2 border-success-300 pb-2.5 text-[15px] font-semibold text-success-300 transition">
                                Overview
                            </button>

                            @can('activity_log.view')
                                <button type="button" data-task-tab-trigger="activity" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                    Activity
                                </button>
                            @endcan

                            <button type="button" data-task-tab-trigger="notes" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Notes & Files
                            </button>

                            <button type="button" data-task-tab-trigger="settings" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Settings
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($project && auth()->user()->can('view', $project))
                            <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300">
                                <span>Open Project</span>
                            </a>
                        @endif

                        @can('delete', $task)
                            <x-delete-form :action="route('tasks.destroy', $task)" />
                        @endcan
                    </div>
                </div>

                <div data-task-tab-panels>
                    <div data-task-tab-panel="overview" data-loaded="true">
                        @include('tasks.partials.tabs.overview')
                    </div>

                    @can('activity_log.view')
                        <div class="hidden" data-task-tab-panel="activity" data-loaded="false"></div>
                    @endcan

                    <div class="hidden" data-task-tab-panel="notes" data-loaded="false"></div>
                    <div class="hidden" data-task-tab-panel="settings" data-loaded="false"></div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/modules/task-detail.js')
@endpush
