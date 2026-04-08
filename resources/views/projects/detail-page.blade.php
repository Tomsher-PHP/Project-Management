@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">

        @php
            $priority = config('project_constants.project_priorities')[$project->priority] ?? null;
        @endphp

        <section class="space-y-6" data-project-tabs data-project-id="{{ $project->id }}" data-default-tab="overview" data-tabs-url-template="{{ route('projects.tabs.show', ['project' => $project, 'tab' => '__TAB__']) }}">
            <div id="project-header">
                @include('projects.partials.header', [
                    'projectTimeline' => $projectTimeline,
                    'customerTimeline' => $customerTimeline,
                ])
            </div>

            <div class="rounded-[20px] bg-white px-4 py-4 shadow-sm dark:bg-darkblack-600 xl:px-5 xl:py-5">
                <div class="mb-4 flex flex-col gap-3 border-b border-bgray-200 pb-4 dark:border-darkblack-400 xl:flex-row xl:items-center xl:justify-between">
                    <div class="overflow-x-auto">
                        <div class="flex min-w-max items-center gap-5">
                            <button type="button" data-project-tab-trigger="overview" class="border-b-2 border-success-300 pb-2.5 text-[15px] font-semibold text-success-300 transition">
                                Overview
                            </button>

                            @if ($project->is_agile)
                                <button type="button" data-project-tab-trigger="modules" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                    Modules
                                </button>
                            @endif

                            <button type="button" data-project-tab-trigger="tasks" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Tasks
                            </button>

                            <button type="button" data-project-tab-trigger="team" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Team
                            </button>

                            <button type="button" data-project-tab-trigger="scope" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Scope
                            </button>

                            <button type="button" data-project-tab-trigger="notes" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Notes & Files
                            </button>

                            <button type="button" data-project-tab-trigger="settings" class="border-b-2 border-transparent pb-2.5 text-[15px] font-semibold text-bgray-500 transition">
                                Settings
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @can('activity_log.view')
                            <button type="button" data-project-insights-trigger data-project-insights-url="{{ route('projects.activity.modal', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300">
                                <span class="inline-flex h-4 w-4 items-center justify-center text-bgray-600 dark:text-bgray-200">
                                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 4.5V9L12 10.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15.75 9C15.75 12.7279 12.7279 15.75 9 15.75C5.27208 15.75 2.25 12.7279 2.25 9C2.25 5.27208 5.27208 2.25 9 2.25C12.7279 2.25 15.75 5.27208 15.75 9Z" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>
                                <span>Activity</span>
                                <span class="inline-flex h-5 min-w-[1.15rem] items-center justify-center rounded-full bg-bgray-100 px-1.5 text-[10px] font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100">
                                    {{ $projectActivitiesCount }}
                                </span>
                            </button>
                        @endcan

                        <button type="button" data-project-insights-trigger data-project-insights-url="{{ route('projects.comments.modal', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-success-300 dark:hover:text-success-300">
                            <span class="inline-flex h-4 w-4 items-center justify-center text-bgray-600 dark:text-bgray-200">
                                <svg width="14" height="14" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.25 6.75H12.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M5.25 9.75H10.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M6.75 14.25L4.13388 15.9931C3.80201 16.2143 3.375 15.9764 3.375 15.5776V4.5C3.375 3.67157 4.04657 3 4.875 3H13.125C13.9534 3 14.625 3.67157 14.625 4.5V12C14.625 12.8284 13.9534 13.5 13.125 13.5H7.58211C7.28548 13.5 6.99551 13.5879 6.75 13.7525V14.25Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>Comments</span>
                            <span class="inline-flex h-5 min-w-[1.15rem] items-center justify-center rounded-full bg-bgray-100 px-1.5 text-[10px] font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-100" data-project-comments-count>
                                {{ $projectCommentsCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <div data-project-tab-panels>
                    <div data-project-tab-panel="overview" data-loaded="true">
                        @include('projects.partials.tabs.overview')
                    </div>

                    @if ($project->is_agile)
                        <div class="hidden" data-project-tab-panel="modules" data-loaded="false"></div>
                    @endif

                    <div class="hidden" data-project-tab-panel="tasks" data-loaded="false"></div>
                    <div class="hidden" data-project-tab-panel="team" data-loaded="false"></div>
                    <div class="hidden" data-project-tab-panel="scope" data-loaded="false"></div>
                    <div class="hidden" data-project-tab-panel="notes" data-loaded="false"></div>
                    <div class="hidden" data-project-tab-panel="settings" data-loaded="false"></div>
                </div>
            </div>
        </section>

        @include('projects.partials.modals.insights-modal')
        @include('projects.partials.modals.change-project-attribute-modal')
    </main>
@endsection

@push('scripts')
    <script>
        window.ProjectApp = {
            id: {{ $project->id }},
            canCreateNotesFiles: @json(auth()->user()->can('project.add_notes_files')),
            canRemoveNotesFiles: @json(auth()->user()->can('project.remove_notes_files')),
            tabsUrlTemplate: @json(route('projects.tabs.show', ['project' => $project, 'tab' => '__TAB__'])),
        };
    </script>
    @vite('resources/js/modules/projects/project-detail.js')
@endpush
