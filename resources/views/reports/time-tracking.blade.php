@extends('layouts.master')

@section('page-content')

<main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]">

    <!-- TOP ACTIONS -->
    <div class="mb-6 flex flex-wrap items-center gap-3">

        <x-filters.button />

        <x-export-button
            :href="route('reports.time.tracking.export', request()->query())"
            label="Export Excel"
        />

        <x-column-manager
            :columns="$columns"
            report="time_tracking_report"
        />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">

            <x-table-search
                target=".time-tracking-table"
                placeholder="Search logs..."
            />

        </div>

    </div>

    <!-- STATS -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-2">

        <!-- TOTAL LOGS -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="text-sm text-gray-500 dark:text-gray-300">
                Total Logs
            </div>

            <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">
                {{ $timeStats['total_logs'] }}
            </div>

        </div>

        <!-- TOTAL HOURS -->
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-700 dark:bg-blue-900/20">

            <div class="text-sm text-gray-500 dark:text-gray-300">
                Total Hours Logged
            </div>

            <div class="mt-2 text-3xl font-bold text-blue-700 dark:text-blue-300">
                {{ $timeStats['total_hours'] }}
            </div>

        </div>

    </div>

    <!-- TABLE -->
    <div class="w-full rounded-lg bg-white dark:bg-darkblack-600">

        <div class="table-content w-full overflow-x-auto">

            <table class="time-tracking-table w-full min-w-[1200px] text-sm">

                <!-- HEAD -->
                <thead>

                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                        <td class="px-6 py-5 xl:w-[80px]">
                            #
                        </td>

                        <td class="px-6 py-5 xl:w-[180px] col-user">

                            <x-sorting.sortable-column
                                column="user_id"
                                label="User"
                            />

                        </td>

                        <td class="px-6 py-5 xl:w-[220px] col-project">
                            Project
                        </td>

                        <td class="px-6 py-5 xl:w-[220px] col-task">
                            Task
                        </td>

                        <td class="px-6 py-5 xl:w-[160px] col-date">

                            <x-sorting.sortable-column
                                column="log_date"
                                label="Date"
                            />

                        </td>

                        <td class="px-6 py-5 xl:w-[180px] col-hours_logged">
                            Hours Logged
                        </td>

                        <td class="px-6 py-5 xl:w-[350px] col-description">
                            Description
                        </td>

                    </tr>

                </thead>

                <!-- BODY -->
                <tbody class="divide-y divide-gray-200">

                    @forelse($timeLogs as $log)

                        <tr class="hover:bg-gray-50 transition">

                            <!-- SL -->
                            <td class="px-4 py-4">
                                {{ $timeLogs->firstItem() + $loop->index }}
                            </td>

                            <!-- USER -->
                            <td class="px-4 py-4 text-[12px] col-user">
                                {{ $log->user->name ?? '-' }}
                            </td>

                            <!-- PROJECT -->
                            <td class="px-4 py-4 text-[12px] col-project">
                                {{ $log->task->project->name ?? '-' }}
                            </td>

                            <!-- TASK -->
                            <td class="px-4 py-4 text-[12px] col-task">
                                {{ $log->task->name ?? '-' }}
                            </td>

                            <!-- DATE -->
                            <td class="px-4 py-4 text-[12px] whitespace-nowrap col-date">
                                {{ optional($log->log_date)->format('d M Y') }}
                            </td>

                            <!-- HOURS -->
                            <td class="px-4 py-4 whitespace-nowrap col-hours_logged">
                                {{ $log->formatted_time }}
                            </td>

                            <!-- DESCRIPTION -->
                            <td class="px-4 py-4 text-[12px] col-description">
                                {{ $log->description ?? '-' }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center py-10 text-gray-500">

                                No time logs found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->
        <x-pagination
            :paginator="$timeLogs"
            :per-page="$perPage"
        />

    </div>

</main>

<!-- FILTERS -->
<x-filters.drawer>

    <x-filters.multi-select
        name="user_id"
        label="User"
        :options="$users"
    />

    <x-filters.multi-select
        name="project_id"
        label="Project"
        :options="$projects"
    />

    <x-filters.date-range
        label="Date Range"
        startName="start_date"
        endName="end_date"
    />

</x-filters.drawer>

@endsection