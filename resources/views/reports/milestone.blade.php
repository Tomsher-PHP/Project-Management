@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')

    <!-- TOP ACTIONS -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />

        <x-export-button :action="route('reports.milestone.export')" :params="request()->query()" label="Export Excel" />

        <x-column-manager :columns="$columns" report="milestone_report" />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search target=".milestone-table" placeholder="Search milestones..." />
        </div>
    </div>

    <!-- TABLE -->
    <div class="2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white dark:bg-darkblack-600">
                <div class="flex-col space-y-5">
                    <div class="table-content w-full overflow-x-auto">
                        <table class="milestone-table w-full min-w-[1200px] text-sm">

                            <!-- HEADER -->
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="px-6 py-5 w-[80px]">
                                        #
                                    </td>
                                    <td class="px-6 py-5 col-project">
                                        <x-sorting.sortable-column column="project_id" label="Project" />
                                    </td>
                                    <td class="px-6 py-5 col-milestone">
                                        <x-sorting.sortable-column column="name" label="Milestone Name" />
                                    </td>
                                    <td class="px-6 py-5 col-due_date">
                                        <x-sorting.sortable-column column="due_date" label="Due Date" />
                                    </td>
                                    <td class="px-6 py-5 col-total_tasks">
                                        Total Milestones
                                    </td>
                                    <td class="px-6 py-5 col-completed_tasks">
                                        Deliverables Completed
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
                                @forelse($milestones as $milestone)
                                    @php
                                        $statusName = $milestone->status->name ?? 'Pending';

                                        $statusClasses = match (strtolower($statusName)) {
                                            'completed' => 'bg-green-100 text-green-700',
                                            'in progress' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp

                                    <tr class="hover:bg-gray-50 transition">
                                        <!-- SL -->
                                        <td class="px-4 py-4">
                                            {{ $milestones->firstItem() + $loop->index }}
                                        </td>
                                        <!-- PROJECT -->
                                        <td class="px-4 py-4 col-project">
                                            {{ $milestone->project->name ?? '-' }}
                                        </td>

                                        <!-- MILESTONE -->
                                        <td class="px-4 py-4 font-medium text-gray-800 col-milestone">

                                            {{ $milestone->name }}

                                        </td>

                                        <!-- DUE DATE -->
                                        <td class="px-4 py-4 whitespace-nowrap col-due_date">

                                            {{ optional($milestone->due_date)->format('d M Y') }}

                                        </td>

                                        <!-- TOTAL -->
                                        <td class="px-4 py-4 col-total_tasks">

                                            {{ $milestone->total_tasks }}

                                        </td>

                                        <!-- COMPLETED -->
                                        <td class="px-4 py-4 col-completed_tasks">

                                            {{ $milestone->completed_tasks }}

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

                                                    <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-300" style="width: {{ $milestone->progress_percentage }}%">
                                                    </div>

                                                </div>

                                                <span class="text-xs font-medium text-gray-700 min-w-[36px]">

                                                    {{ $milestone->progress_percentage }}%

                                                </span>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="8" class="text-center py-10 text-gray-500">

                                            No milestones found.

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <!-- PAGINATION -->
                    <x-pagination :paginator="$milestones" :per-page="$perPage" />

                </div>

            </div>

        </section>

    </div>

    <!-- FILTERS -->
    <x-filters.drawer>
        <x-filters.date-range label="Due Date Range" startName="start_date" endName="end_date" />
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
    </x-filters.drawer>

@endsection
