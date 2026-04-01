@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        @php
            $priority = config('constants.project_priorities')[$project->priority] ?? null;
        @endphp

        <div class="2xl:flex 2xl:space-x-[30px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1" data-project-tabs data-project-id="{{ $project->id }}" data-default-tab="overview" data-tabs-url-template="{{ route('projects.tabs.show', ['project' => $project, 'tab' => '__TAB__']) }}">

                <!-- PROJECT HEADER -->
                <div id="project-header">
                    @include('projects.partials.header', [
                        'projectTimeline' => $projectTimeline,
                        'customerTimeline' => $customerTimeline,
                    ])
                </div>

                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

                    <!-- Tabs Header -->
                    <div class="border-b border-bgray-300 dark:border-darkblack-400 mb-6">
                        <div class="flex space-x-6">
                            <button type="button" data-project-tab-trigger="overview" class="border-b-2 border-success-300 pb-3 font-semibold text-success-300 transition">
                                Overview
                            </button>

                            @if ($project->is_agile)
                                <button type="button" data-project-tab-trigger="modules" class="border-b-2 border-transparent pb-3 font-semibold text-bgray-500 transition">
                                    Modules
                                </button>
                            @endif

                            <button type="button" data-project-tab-trigger="tasks" class="border-b-2 border-transparent pb-3 font-semibold text-bgray-500 transition">
                                Tasks
                            </button>

                            <button type="button" data-project-tab-trigger="team" class="border-b-2 border-transparent pb-3 font-semibold text-bgray-500 transition">
                                Team
                            </button>

                            <button type="button" data-project-tab-trigger="scope" class="border-b-2 border-transparent pb-3 font-semibold text-bgray-500 transition">
                                Scope
                            </button>

                            <button type="button" data-project-tab-trigger="notes" class="border-b-2 border-transparent pb-3 font-semibold text-bgray-500 transition">
                                Notes & Files
                            </button>

                            <button type="button" data-project-tab-trigger="settings" class="border-b-2 border-transparent pb-3 font-semibold text-bgray-500 transition">
                                Settings
                            </button>
                        </div>
                    </div>

                    <!-- TAB CONTENT -->
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
            <!-- Activity log section -->

            <!-- Project comment section -->
            <section class="flex w-full flex-col gap-6 2xl:w-[400px]">
                @can('activity_log.view')
                    <x-activity-log.section title="Activity Log" :activities="$projectActivities" empty-message="No activity logged for this project yet." :view-all-url="route('activity.log', ['subject_type' => 'project', 'subject_id' => $project->id])" />
                @endcan


                <div class="flex w-full flex-col justify-between rounded-lg bg-white dark:border dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="flex justify-between border-b border-bgray-300 px-[26px] py-6 dark:border-darkblack-400">
                        <h1 class="text-2xl font-semibold text-bgray-900 dark:text-white">
                            Comments
                        </h1>
                    </div>
                    <div class="w-full px-5 py-6 lg:px-[35px] lg:py-[38px]">
                        <div class="mb-5 flex flex-col space-y-[32px]">
                            <div class="flex justify-start">
                                <div class="flex items-end space-x-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-[35px] w-[36px] overflow-hidden rounded-full">
                                            <img src="{{ asset('assets/images/avatar/user-1.png') }}" alt="avater" class="h-full w-full object-cover" />
                                        </div>
                                        <div class="rounded-lg bg-bgray-100 p-3 dark:bg-darkblack-500">
                                            <p class="text-sm font-medium text-bgray-900 dark:text-white">
                                                First comment of the project.
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-medium text-bgray-500">10:00 PM</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex h-[58px] w-full items-center space-x-4">
                            <div class="flex h-full w-full items-center justify-between rounded-lg border border-transparent bg-bgray-100 px-5 focus-within:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 lg:w-[318px]">
                                <label class="w-full">
                                    <input type="text" placeholder="Type your comment..." class="w-full border-none bg-bgray-100 p-0 pl-[15px] font-medium placeholder:text-sm placeholder:font-medium placeholder:text-bgray-400 focus:outline-none focus:ring-0 dark:bg-darkblack-500 dark:text-white" />
                                </label>
                            </div>
                            <button type="button">
                                <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.3894 0H2.61094C0.339326 0 -0.844596 2.63548 0.696196 4.26234L3.78568 7.52441C4.23 7.99355 4.47673 8.60858 4.47673 9.24704V15.4553C4.47673 17.8735 7.61615 18.9233 9.13941 17.0145L19.4463 4.09894C20.7775 2.43071 19.5578 0 17.3894 0Z" fill="#22C55E" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

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
