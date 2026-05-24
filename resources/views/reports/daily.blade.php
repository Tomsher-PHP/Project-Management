@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')

    <!-- TOP ACTION BAR -->
    <div class="mb-6 flex flex-wrap items-center gap-3">

        <!-- FILTER BUTTON -->
        <x-filters.button />

        <!-- EXPORT -->
        <x-export-button
            :href="route('reports.daily.export', request()->query())"
            label="Export Excel" />

        <!-- COLUMN MANAGER -->
        <x-column-manager :columns="$columns" report="daily_report" />

        <!-- SEARCH -->
        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search target=".daily-report-table" placeholder="Search daily reports..." />
        </div>

    </div>

    <!-- REPORT STATS -->
    <div class="mb-6 grid grid-cols-5 gap-4">

        <!-- TOTAL ENTRIES -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="text-sm text-gray-500 dark:text-gray-300">
                Total Entries
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $dailyStats['total_entries'] }}
            </div>
        </div>

        <!-- TOTAL TIME -->
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <div class="text-sm text-gray-500">
                Total Time
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $dailyStats['total_hours'] }}
            </div>
        </div>

        <!-- TOTAL STAFF -->
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm">
            <div class="text-sm text-gray-500">
                Staff
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $dailyStats['active_staffs'] }}
            </div>
        </div>

        <!-- TOTAL PROJECTS -->
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm">
            <div class="text-sm text-gray-500">
                Projects
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $dailyStats['project_count'] }}
            </div>
        </div>

        <!-- TOTAL TASKS -->
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-5 shadow-sm">
            <div class="text-sm text-gray-500">
                Tasks
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $dailyStats['task_count'] }}
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1300px]">

                <!-- HEADER -->
                <thead>

                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                        <th class="px-6 py-5 xl:w-[165px]">
                            #
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-project">
                            Project
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-staff">
                            Staff
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-date">
                            Date
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-start_time">
                            Start Time
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-end_time">
                            End Time
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-total_time">
                            Total Time (Minutes)
                        </th>

                        <th class="px-6 py-5 xl:w-[165px] col-task">
                            Task
                        </th>

                    </tr>

                </thead>

                <!-- BODY -->
                <tbody class="divide-y divide-gray-200 dark:divide-darkblack-400">

                    @php
                    $previousEnd = null;
                    @endphp

                    @forelse($reports as $report)

                    {{-- BREAK ROW --}}
                    @if($previousEnd)

                    @php

                    $breakMinutes =
                    $previousEnd->diffInMinutes(
                    $report->started_at
                    );

                    @endphp

                    @if($breakMinutes > 0)

                    <tr class="bg-yellow-50 dark:bg-yellow-900/10">

                        <td
                            colspan="9"
                            class="px-5 py-4 text-center text-sm font-medium">
                            BREAK:
                            {{ $breakMinutes }} mins
                        </td>

                    </tr>

                    @endif

                    @endif

                    {{-- DATA ROW --}}
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-darkblack-500">

                        <td class="px-5 py-4 text-sm">
                            {{ $loop->iteration }}
                        </td>

                        <td class="px-5 py-4 text-sm font-medium  col-project">
                            {{ $report->task?->project?->name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-sm  col-staff">
                            {{ $report->user?->name ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-sm  col-date">
                            {{ $report->started_at?->format('Y-m-d') }}
                        </td>

                        <td class="px-5 py-4 text-sm col-start_time">
                            {{ $report->started_at?->format('H:i:s') }}
                        </td>

                        <td class="px-5 py-4 text-sm col-end_time">
                            {{ $report->ended_at?->format('H:i:s') }}
                        </td>

                        <td class="px-5 py-4 text-sm font-medium col-total_time">
                            {{ round($report->duration_seconds / 60) }}
                        </td>

                        <td class="px-5 py-4 text-sm col-task">
                            {{ $report->task?->name ?? '-' }}
                        </td>

                    </tr>

                    @php
                    $previousEnd = $report->ended_at;
                    @endphp

                    @empty

                    <tr>

                        <td
                            colspan="9"
                            class="px-5 py-10 text-center text-sm text-gray-500">
                            No records found.
                        </td>

                    </tr>

                    @endforelse



                </tbody>

            </table>

        </div>

    </div>

    <!-- PAGINATION -->
    <div class="mt-6">

        <x-pagination
            :paginator="$reports"
            :per-page="$perPage" />

    </div>

<!-- FILTER DRAWER -->
<x-filters.drawer>

    <x-filters.date-range
        label="Date Range"
        startName="start_date"
        endName="end_date" />

    <x-filters.multi-select
        name="project_id"
        label="Project"
        :options="$projects" />

    <x-filters.multi-select
        name="user_id"
        label="Staff"
        :options="$staffs" />

    <x-filters.multi-select
        name="task_id"
        label="Task"
        :options="$tasks" />

</x-filters.drawer>

@endsection
