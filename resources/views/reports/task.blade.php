@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')

    <!-- TOP ACTIONS -->
    <div class="mb-6 flex flex-wrap items-center gap-3">

        <x-filters.button />

        <x-export-button
            :action="route('reports.task.export')"
            :params="request()->query()"
            label="Export Excel"
        />

        <x-column-manager
            :columns="$columns"
            report="task_report"
        />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search
                target=".task-table"
                placeholder="Search tasks..."
            />
        </div>

    </div>

    <!-- REPORT STATS -->
    <div class="mb-6 grid grid-cols-4 gap-4">

        <!-- TOTAL -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="text-sm text-gray-500 dark:text-gray-300">
                Total Tasks
            </div>

            <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">
                {{ $taskStats['total'] ?? 0 }}
            </div>

        </div>

        <!-- COMPLETED -->
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm dark:border-green-700 dark:bg-green-900/20">

            <div class="text-sm text-gray-500 dark:text-gray-300">
                Completed
            </div>

            <div class="mt-2 text-3xl font-bold text-green-700 dark:text-green-300">
                {{ $taskStats['completed'] ?? 0 }}
            </div>

        </div>

        <!-- IN PROGRESS -->
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm dark:border-yellow-700 dark:bg-yellow-900/20">

            <div class="text-sm text-gray-500 dark:text-gray-300">
                In Progress
            </div>

            <div class="mt-2 text-3xl font-bold text-yellow-700 dark:text-yellow-300">
                {{ $taskStats['in_progress'] ?? 0 }}
            </div>

        </div>

        <!-- OPEN -->
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-700 dark:bg-blue-900/20">

            <div class="text-sm text-gray-500 dark:text-gray-300">
                Open
            </div>

            <div class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-300">
                {{ $taskStats['open'] ?? 0 }}
            </div>

        </div>

    </div>

    <!-- TABLE -->
    <div class="2xl:space-x-[48px]">

        <section class="mb-6 2xl:mb-0 2xl:flex-1">

            <div class="w-full rounded-lg bg-white dark:bg-darkblack-600">

                <div class="flex-col space-y-5">

                    <!-- TABLE -->
                    <div class="table-content w-full overflow-x-auto">

                        <table class="task-table w-full min-w-[1300px] text-sm">

                            <!-- TABLE HEAD -->
                            <thead>

                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                                    <!-- SL -->
                                    <td class="px-6 py-5 xl:w-[80px]">
                                        #
                                    </td>

                                    <!-- TASK -->
                                    <td class="px-6 py-5 xl:w-[220px] col-task">

                                        <x-sorting.sortable-column
                                            column="name"
                                            label="Task"
                                        />

                                    </td>

                                    <!-- PROJECT -->
                                    <td class="px-6 py-5 xl:w-[220px] col-project">

                                        <x-sorting.sortable-column
                                            column="project_id"
                                            label="Project"
                                        />

                                    </td>

                                    <!-- MILESTONE -->
                                    <td class="px-6 py-5 xl:w-[220px] col-milestone">
                                        Milestone
                                    </td>

                                    <!-- SPRINT -->
                                    <td class="px-6 py-5 xl:w-[220px] col-sprint">
                                        Sprint
                                    </td>

                                    <!-- ASSIGNEE -->
                                    <td class="px-6 py-5 xl:w-[220px] col-assignee">
                                        Assignee
                                    </td>

                                    <!-- ESTIMATED -->
                                    <td class="px-6 py-5 xl:w-[180px] col-estimated_hours">
                                        Estimated <br> Time
                                    </td>

                                    <!-- ACTUAL -->
                                    <td class="px-6 py-5 xl:w-[180px] col-actual_hours">
                                        Actual <br> Time
                                    </td>

                                    <!-- PROGRESS -->
                                    <td class="px-6 py-5 xl:w-[220px] col-progress">
                                        Progress
                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-6 py-5 xl:w-[180px] col-status">

                                        <x-sorting.sortable-column
                                            column="status_id"
                                            label="Status"
                                        />

                                    </td>

                                </tr>

                            </thead>

                            <!-- TABLE BODY -->
                            <tbody class="divide-y divide-gray-200">

                                @forelse($tasks as $task)

                                    <tr class="hover:bg-gray-50 transition">

                                        <!-- SL -->
                                        <td class="px-4 py-4">

                                            {{ $tasks->firstItem() + $loop->index }}

                                        </td>

                                        <!-- TASK -->
                                        <td class="px-4 py-4 font-medium text-gray-800 text-[12px] col-task">

                                            <a
                                                href="{{ route('tasks.edit', $task->id) }}"
                                                target="_blank"
                                                class="hover:text-blue-600"
                                            >
                                                {{ $task->name }}
                                            </a>

                                        </td>

                                        <!-- PROJECT -->
                                        <td class="px-4 py-4 text-[12px] col-project">

                                            {{ $task->project->name ?? '-' }}

                                        </td>

                                        <!-- MILESTONE -->
                                        <td class="px-4 py-4 text-[12px] col-milestone">

                                            {{ $task->projectMilestone->name ?? '-' }}

                                        </td>

                                        <!-- SPRINT -->
                                        <td class="px-4 py-4 text-[12px] col-sprint">

                                            {{ $task->projectSprint->name ?? '-' }}

                                        </td>

                                        <!-- ASSIGNEE -->
                                        <td class="px-4 py-4 text-[12px] col-assignee">

                                            {{ $task->currentAssignee->name ?? '-' }}

                                        </td>

                                        <!-- ESTIMATED -->
                                        <td class="px-4 py-4 whitespace-nowrap col-estimated_hours">

                                            {{ formatSecondsToHoursMinutes($task->estimated_time_seconds ?? 0) }}

                                        </td>

                                        <!-- ACTUAL -->
                                        <td class="px-4 py-4 whitespace-nowrap col-actual_hours">

                                            {{ formatSecondsToHoursMinutes($task->actual_time_seconds ?? 0) }}

                                        </td>

                                        <!-- PROGRESS -->
                                        <td class="px-4 py-4 min-w-[180px] col-progress">

                                            <div class="flex items-center gap-3">

                                                @php

                                                    $estimatedSeconds = $task->estimated_time_seconds ?? 0;

                                                    $actualSeconds = $task->actual_time_seconds ?? 0;

                                                    $progress_percentage = $estimatedSeconds > 0
                                                        ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
                                                        : 0;

                                                    $display_progress = ($progress_percentage > 0 && $progress_percentage < 2)
                                                        ? 2
                                                        : min($progress_percentage, 100);

                                                @endphp

                                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">

                                                    <div
                                                        class="bg-blue-500 h-2.5 rounded-full transition-all duration-300"
                                                        style="width: {{ $display_progress }}%">
                                                    </div>

                                                </div>

                                                <span class="text-xs font-medium text-gray-700 min-w-[36px]">

                                                    {{ $progress_percentage }}%

                                                </span>

                                            </div>

                                        </td>

                                        <!-- STATUS -->
                                        <td class="px-4 py-4 col-status">

                                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $task->status_badge_class }}">

                                                {{ $task->status->name ?? 'No Status' }}

                                            </span>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="10"
                                            class="text-center py-10 text-gray-500">

                                            No tasks found.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <!-- PAGINATION -->
                    <x-pagination
                        :paginator="$tasks"
                        :per-page="$perPage"
                    />

                </div>

            </div>

        </section>

    </div>

<!-- FILTERS -->
<x-filters.drawer>

    <x-filters.input-search
        name="search"
        label="Task"
    />

    <x-filters.multi-select
        name="project_id"
        label="Project"
        :options="$projects"
    />

    <x-filters.multi-select
        name="project_milestone_id"
        label="Milestone"
        :options="$projectMilestones"
    />

    <x-filters.multi-select
        name="project_sprint_id"
        label="Sprint"
        :options="$projectSprints"
    />

    <x-filters.multi-select
        name="current_assignee_id"
        label="Assignee"
        :options="$assignees"
    />

    <x-filters.multi-select
        name="status_id"
        label="Status"
        :options="$statuses"
    />

    <x-filters.date-range
        label="Task Date Range"
        startName="start_date"
        endName="end_date"
    />

</x-filters.drawer>

@endsection
