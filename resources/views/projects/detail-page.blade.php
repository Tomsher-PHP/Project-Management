@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]" x-data="{ activeTab: localStorage.getItem('projectTab_{{ $project->id }}') || 'tasks' }">

        @php
            $priority = config('constants.project_priorities')[$project->priority] ?? null;
        @endphp

        <div class="2xl:flex 2xl:space-x-[30px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1" x-data="{ activeTab: localStorage.getItem('projectTab_{{ $project->id }}') || 'tasks' }">

                <!-- PROJECT HEADER -->
                <div class="mb-6 rounded-lg bg-white p-5 dark:bg-darkblack-600">

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <!-- LEFT: Project Info -->
                        <div>
                            <div class="flex items-center gap-3">
                                <!-- Priority Indicator -->
                                <div class="h-10 w-1 rounded {{ $priority['bg_class'] ?? 'bg-gray-300' }}"></div>

                                <div>
                                    <h2 class="text-xl font-bold text-bgray-900 dark:text-white">
                                        {{ $project->name }}
                                    </h2>
                                    <p class="text-sm text-bgray-500">
                                        Code: {{ $project->project_code ?? '--' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Meta Info -->
                            <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-bgray-600 dark:text-bgray-300">

                                <span>
                                    <strong>Customer:</strong> {{ $project->customer->name ?? '--' }}
                                </span>

                                <span>
                                    <strong>Project Type:</strong> {{ strtoupper($project->project_type ?? '--') }}
                                </span>

                                <span>
                                    <strong>Start:</strong> {{ optional($project->start_date)->format(config('constants.date_format')) ?? '--' }}
                                </span>

                                <span>
                                    <strong>Internal End:</strong> {{ optional($project->internal_end_date)->format(config('constants.date_format')) ?? '--' }}
                                </span>

                                <span>
                                    <strong>Customer End:</strong> {{ optional($project->client_end_date)->format(config('constants.date_format')) ?? '--' }}
                                </span>

                            </div>
                        </div>

                        <!-- RIGHT: Status + Priority -->
                        <div class="flex items-center gap-3">

                            <!-- Status -->
                            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $project->status ? 'bg-success-50 text-success-500' : 'bg-gray-100 text-gray-500' }}">
                                {{ $project->projectStatus->name ?? 'No Status' }}
                            </span>

                            <!-- Project Priority -->
                            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $priority['bg_class'] ?? 'bg-gray-100 text-gray-500' }} {{ $priority['bg_text'] ?? 'text-gray-500' }}">
                                {{ $priority['label'] ?? '--' }}
                            </span>

                        </div>

                    </div>
                </div>

                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">

                    <!-- Tabs Header -->
                    <div class="border-b border-bgray-300 dark:border-darkblack-400 mb-6">
                        <div class="flex space-x-6">
                            <button @click="activeTab = 'tasks'; localStorage.setItem('projectTab_{{ $project->id }}', 'tasks')" :class="activeTab === 'tasks' ? 'border-success-300 text-success-300' : 'text-bgray-500 border-transparent'" class="pb-3 border-b-2 font-semibold transition">
                                Tasks
                            </button>

                            <button @click="activeTab = 'overview'; localStorage.setItem('projectTab_{{ $project->id }}', 'overview')" :class="activeTab === 'overview' ? 'border-success-300 text-success-300' : 'text-bgray-500 border-transparent'" class="pb-3 border-b-2 font-semibold transition">
                                Overview
                            </button>

                            <button @click="activeTab = 'notes'; localStorage.setItem('projectTab_{{ $project->id }}', 'notes')" :class="activeTab === 'notes' ? 'border-success-300 text-success-300' : 'text-bgray-500 border-transparent'" class="pb-3 border-b-2 font-semibold transition">
                                Notes & Files
                            </button>

                            <button @click="activeTab = 'settings'; localStorage.setItem('projectTab_{{ $project->id }}', 'settings')" :class="activeTab === 'settings' ? 'border-success-300 text-success-300' : 'text-bgray-500 border-transparent'" class="pb-3 border-b-2 font-semibold transition">
                                Settings
                            </button>
                        </div>
                    </div>

                    <!-- TAB CONTENT -->
                    <div>

                        <!-- ================= TASKS ================= -->
                        <div x-show="activeTab === 'tasks'" x-transition>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Project Tasks</h3>
                                <button class="px-4 py-2 bg-success-300 text-white rounded-lg text-sm font-semibold hover:bg-success-400">
                                    + Add Task
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                            <th class="py-3 text-left text-sm font-semibold text-bgray-600">Task</th>
                                            <th class="py-3 text-left text-sm font-semibold text-bgray-600">Assigned To</th>
                                            <th class="py-3 text-left text-sm font-semibold text-bgray-600">Due Date</th>
                                            <th class="py-3 text-left text-sm font-semibold text-bgray-600">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Loop tasks -->
                                        <tr class="border-b border-bgray-200">
                                            <td class="py-4 font-medium text-bgray-900 dark:text-white">Sample static task</td>
                                            <td class="py-4 text-bgray-600">John</td>
                                            <td class="py-4 text-bgray-600">25 Mar 2026</td>
                                            <td class="py-4">
                                                <span class="px-3 py-1 text-xs rounded bg-warning-50 text-warning-500">Pending</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ================= OVERVIEW ================= -->
                        <div x-show="activeTab === 'overview'" x-transition>
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white mb-4">Project Overview</h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Stats Card -->
                                <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                                    <p class="text-sm text-bgray-500">New Tasks</p>
                                    <h2 class="text-2xl font-bold text-success-400">12</h2>
                                </div>

                                <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                                    <p class="text-sm text-bgray-500">In Progress</p>
                                    <h2 class="text-2xl font-bold text-warning-400">5</h2>
                                </div>

                                <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
                                    <p class="text-sm text-bgray-500">Completed</p>
                                    <h2 class="text-2xl font-bold text-blue-400">20</h2>
                                </div>
                            </div>

                            <!-- Chart placeholder -->
                            <div class="mt-6 h-[250px] flex items-center justify-center border border-dashed border-bgray-300 rounded-lg text-bgray-400">
                                Pie Chart (Tasks Stats)
                            </div>
                        </div>

                        <!-- ================= NOTES ================= -->
                        <div x-show="activeTab === 'notes'" x-transition>
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white mb-4">Notes</h3>

                            <textarea class="w-full rounded-lg border border-bgray-300 p-3 dark:bg-darkblack-500 dark:text-white" rows="4" placeholder="Write notes..."></textarea>

                            <button class="mt-3 px-5 py-2 bg-success-300 text-white rounded-lg font-semibold">
                                Save Notes
                            </button>

                            <!-- Attachments -->
                            <div class="mt-6">
                                <h4 class="font-semibold mb-3">Attachments</h4>

                                <div class="border border-dashed border-bgray-300 rounded-lg p-6 text-center text-bgray-400">
                                    Upload Files
                                </div>
                            </div>
                        </div>

                        <!-- ================= SETTINGS ================= -->
                        <div x-show="activeTab === 'settings'" x-transition>

                            @include('projects.settings-form')

                        </div>

                    </div>
                </div>
            </section>
            <!-- Project comment section -->
            <section class="flex w-full flex-col space-x-0 lg:flex-row lg:space-x-6 2xl:w-[400px] 2xl:flex-col 2xl:space-x-0">
                <div class="flex w-full flex-col justify-between rounded-lg bg-white dark:border dark:border-darkblack-400 dark:bg-darkblack-600 lg:w-1/2 2xl:w-full">
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
    {{-- <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            ClassicEditor.create(document.querySelector('#project-editor')).catch(console.error);

            // Task status pie chart
            const ctx = document.getElementById('task-status-chart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['New', 'In Progress', 'Completed'],
                    datasets: [{
                        data: [{{ $project->tasks_new }}, {{ $project->tasks_in_progress }}, {{ $project->tasks_completed }}],
                        backgroundColor: ['#3B82F6', '#FBBF24', '#10B981'],
                    }]
                }
            });
        });
    </script> --}}
@endpush
