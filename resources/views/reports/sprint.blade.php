@extends('layouts.master')
@section('without-main', true)

@section('page-content')

<main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]">

    <!-- TOP ACTIONS -->
    <div class="mb-6 flex flex-wrap items-center gap-3">

        <x-filters.button />

        <x-export-button
            :href="route('reports.sprint.export', request()->query())"
            label="Export Excel"
        />

        <x-column-manager
            :columns="$columns"
            report="sprint_report"
        />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">

            <x-table-search
                target=".sprint-table"
                placeholder="Search sprints..."
            />

        </div>

    </div>

    <!-- TABLE -->
    <div class="2xl:space-x-[48px]">

        <section class="mb-6 2xl:mb-0 2xl:flex-1">

            <div class="w-full rounded-lg bg-white dark:bg-darkblack-600">

                <div class="flex-col space-y-5">

                    <div class="table-content w-full overflow-x-auto">

                        <table class="sprint-table w-full min-w-[1300px] text-sm">

                            <!-- HEADER -->
                            <thead>

                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">

                                    <td class="px-6 py-5 w-[80px]">
                                        #
                                    </td>

                                    <td class="px-6 py-5 col-project">
                                        <x-sorting.sortable-column
                                            column="project_id"
                                            label="Project"
                                        />
                                    </td>

                                    <td class="px-6 py-5 col-sprint">
                                        <x-sorting.sortable-column
                                            column="name"
                                            label="Sprint Name"
                                        />
                                    </td>

                                    <td class="px-6 py-5 col-start_date">
                                        <x-sorting.sortable-column
                                            column="start_date"
                                            label="Start Date"
                                        />
                                    </td>

                                    <td class="px-6 py-5 col-end_date">
                                        <x-sorting.sortable-column
                                            column="end_date"
                                            label="End Date"
                                        />
                                    </td>

                                    <td class="px-6 py-5 col-total_tasks">
                                        Total Tasks
                                    </td>

                                    <td class="px-6 py-5 col-completed_tasks">
                                        Completed
                                    </td>

                                    <td class="px-6 py-5 col-pending_tasks">
                                        Pending
                                    </td>

                                    <td class="px-6 py-5 col-status">
                                        Status
                                    </td>

                                    <td class="px-6 py-5 col-progress">
                                        Progress
                                    </td>

                                </tr>

                            </thead>

                            <!-- BODY -->
                            <tbody class="divide-y divide-gray-200">

                                @forelse($sprints as $sprint)

                                    @php

                                        $statusName =
                                            $sprint->status->name ?? 'Pending';

                                        $statusClasses = match(strtolower($statusName)) {
                                            'completed' => 'bg-green-100 text-green-700',
                                            'in progress' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };

                                    @endphp

                                    <tr class="hover:bg-gray-50 transition">

                                        <!-- SL -->
                                        <td class="px-4 py-4">

                                            {{ $sprints->firstItem() + $loop->index }}

                                        </td>

                                        <!-- PROJECT -->
                                        <td class="px-4 py-4 col-project">

                                            {{ $sprint->project->name ?? '-' }}

                                        </td>

                                        <!-- SPRINT -->
                                        <td class="px-4 py-4 font-medium text-gray-800 col-sprint">

                                            {{ $sprint->name }}

                                        </td>

                                        <!-- START -->
                                        <td class="px-4 py-4 whitespace-nowrap col-start_date">

                                            {{ optional($sprint->start_date)->format('d M Y') }}

                                        </td>

                                        <!-- END -->
                                        <td class="px-4 py-4 whitespace-nowrap col-end_date">

                                            {{ optional($sprint->end_date)->format('d M Y') }}

                                        </td>

                                        <!-- TOTAL -->
                                        <td class="px-4 py-4 col-total_tasks">

                                            {{ $sprint->total_tasks }}

                                        </td>

                                        <!-- COMPLETED -->
                                        <td class="px-4 py-4 col-completed_tasks">

                                            {{ $sprint->completed_tasks }}

                                        </td>

                                        <!-- PENDING -->
                                        <td class="px-4 py-4 col-pending_tasks">

                                            {{ $sprint->pending_tasks }}

                                        </td>

                                        <!-- STATUS -->
                                        <td class="px-4 py-4 col-status">

                                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $statusClasses }}">

                                                {{ $statusName }}

                                            </span>

                                        </td>

                                        <!-- PROGRESS -->
                                        <td class="px-4 py-4 min-w-[180px] col-progress">

                                            <div class="flex items-center gap-3">

                                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">

                                                    <div
                                                        class="bg-blue-500 h-2.5 rounded-full transition-all duration-300"
                                                        style="width: {{ $sprint->progress_percentage }}%">
                                                    </div>

                                                </div>

                                                <span class="text-xs font-medium text-gray-700 min-w-[36px]">

                                                    {{ $sprint->progress_percentage }}%

                                                </span>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="10"
                                            class="text-center py-10 text-gray-500">

                                            No sprints found.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <!-- PAGINATION -->
                    <x-pagination
                        :paginator="$sprints"
                        :per-page="$perPage"
                    />

                </div>

            </div>

        </section>

    </div>

</main>

<!-- FILTERS -->
<x-filters.drawer>

    <x-filters.multi-select
        name="project_id"
        label="Project"
        :options="$projects"
    />

    <x-filters.date-range
        label="Sprint Date Range"
        startName="start_date"
        endName="end_date"
    />

</x-filters.drawer>

@endsection
