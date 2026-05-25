@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />
        <x-export-button
            :href="route('reports.project.export', request()->query())"
            label="Export Excel"
        />
        <x-column-manager    :columns="$columns" report="project_report" />
        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search target=".project-table" placeholder="Search projects..." />
        </div>
    </div>

    <!-- REPORT STATS -->
    <div class="mb-6 grid grid-cols-5 gap-4">

        <!-- TOTAL -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="text-sm text-gray-500 dark:text-gray-300">
                Total Projects
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $projectStats['total'] }}
            </div>
        </div>

        <!-- COMPLETED -->
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm dark:border-green-700 dark:bg-green-900/20">
            <div class="text-sm text-gray-500 dark:text-gray-300">
                Completed
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $projectStats['completed'] }}
            </div>
        </div>

        <!-- IN PROGRESS -->
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm dark:border-yellow-700 dark:bg-yellow-900/20">
            <div class="text-sm text-gray-500 dark:text-gray-300">
                In Progress
            </div>

            <div class="mt-2 text-3xl font-bold">
                {{ $projectStats['in_progress'] }}
            </div>
        </div>

        <!-- NOT STARTED -->
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="text-sm">
                Pending
            </div>

            <div class="mt-2 text-3xl font-bold text-gray-700 dark:text-white">
                {{ $projectStats['open'] }}
            </div>
        </div>

        <!-- ARCHIEVED -->
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="text-sm">
                Archieved
            </div>

            <div class="mt-2 text-3xl font-bold text-gray-700 dark:text-white">
                {{ $projectStats['archieved'] }}
            </div>
        </div>

    </div>


    <!-- TABLE -->
    <div class="2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white dark:bg-darkblack-600">
                <div class="flex-col space-y-5">
                    <!-- TABLE SECTION -->
                    <div class="table-content w-full overflow-x-auto">
                        <table class="project-table w-full min-w-[1300px] text-sm">
                            <!-- TABLE HEADER -->
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <td class="px-6 py-5 xl:w-[165px]">#</td>

                                    <td class="px-6 py-5 xl:w-[165px] col-project_name">
                                        <!-- Project Name -->
                                        <x-sorting.sortable-column
                                            column="name"
                                            label="Project Name"
                                        />
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-customer">
                                        <!-- Customer -->
                                        <x-sorting.sortable-column
                                            column="customer_id"
                                            label="Customer"
                                        />
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-sales_person">
                                        Sales Person
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-start_date">
                                        <!-- Start Date -->
                                        <x-sorting.sortable-column
                                            column="start_date"
                                            label="Start Date"
                                        />
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-end_date">
                                        <!-- End Date -->
                                        <x-sorting.sortable-column
                                            column="end_date"
                                            label="End Date"
                                        />
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-estimated_hours">
                                        Estimated <br> Time (hrs)
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-actual_hours">
                                        Actual Time <br> (hrs)
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-progress">
                                        Progress
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-priority">
                                        <!-- Priority -->
                                         <x-sorting.sortable-column
                                                column="priority"
                                                label="Priority"
                                            />
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-milestone_status">
                                        Milestone <br> Status
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-status">
                                        <!-- Status -->
                                        <x-sorting.sortable-column
                                            column="status_id"
                                            label="Status"
                                        />
                                    </td>

                                    <td class="px-6 py-5 xl:w-[165px] col-stage">
                                        <x-sorting.sortable-column
                                            column="stage_id"
                                            label="Status"
                                        />
                                        Stage
                                    </td>
                                </tr>
                            </thead>

                            <!-- TABLE BODY -->
                            <tbody class="divide-y divide-gray-200">

                                @forelse($projects as $project)

                                    @php
                                        $progress = $project->progress ?? 0;
                                        $actualHours = round(($project->actual_time_seconds ?? 0) / 3600);
                                        $status = $project->status->name ?? 'Not Started';
                                        $statusClasses = match(strtolower($status)) {
                                            'completed' => 'bg-green-100 text-green-700',
                                            'in progress' => 'bg-yellow-100 text-yellow-700',
                                            'not started' => 'bg-gray-100 text-gray-700',
                                            default => 'bg-blue-100 text-blue-700',
                                        };
                                        $flowLabel = ucfirst($project->project_flow ?? 'linear');
                                    @endphp

                                    <tr class="hover:bg-gray-50 transition">

                                        <!-- SL NO -->
                                        <td class="px-4 py-4">
                                            {{ $loop->iteration }}
                                        </td>

                                        <!-- PROJECT -->
                                        <td class="px-4 py-4 font-medium text-gray-800 text-[12px] col-project_name">
                                            <div class="flex items-stretch">
                                                <div class="relative flex-1 pr-8">
                                                    <x-project-flow-icon :flow="$project->project_flow" class="absolute right-0 top-[10px]" :title="'Project Flow: ' . $flowLabel" />
                                                    <a href="{{ route('projects.edit', $project->id) }}" target="_blank">
                                                    {{ $project->name }}
                                                        <p class="text-sm text-bgray-700">
                                                        {{ $project->project_code }}
                                                        </p>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- CUSTOMER -->
                                        <td class="px-4 py-4 text-[12px] col-customer">
                                            {{ $project->customer->name ?? '-' }}
                                        </td>

                                        <!-- SALES PERSON -->
                                        <td class="px-4 py-4 text-[12px] col-sales_person">
                                            {{ $project->salesPerson->name ?? '-' }}
                                        </td>

                                        <!-- START DATE -->
                                        <td class="px-4 py-4 text-[12px] whitespace-nowrap col-start_date">
                                            {{ optional($project->start_date)->format('d M Y') }}
                                        </td>

                                        <!-- END DATE -->
                                        <td class="px-4 py-4 text-[12px] whitespace-nowrap col-end_date">
                                            {{ optional($project->end_date)->format('d M Y') }}
                                        </td>
                                                                                
                                        <!-- ESTIMATED HOURS -->
                                        <td class="px-4 py-4 whitespace-nowrap col-estimated_hours">
                                            {{ formatSecondsToHoursMinutes($project->projectMilestones->sum('estimated_time_seconds')) }}
                                        </td>

                                        <!-- ACTUAL HOURS -->
                                        <td class="px-4 py-4 whitespace-nowrap col-actual_hours">
                                            {{ formatSecondsToHoursMinutes($project->projectMilestones->sum('actual_time_seconds')) }}
                                        </td>

                                        <!-- PROGRESS -->
                                        <td class="px-4 py-4 min-w-[180px] col-progress">
                                            <div class="flex items-center gap-3">
                                                @php
                                                    $estimatedSeconds = $project->projectMilestones->sum('estimated_time_seconds') ?? 0;
                                                    $actualSeconds = $project->projectMilestones->sum('actual_time_seconds') ?? 0;

                                                    $progress_percentage = $estimatedSeconds > 0
                                                        ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
                                                        : 0;

                                                    // Minimum visible width for very small progress
                                                    $display_progress = ($progress_percentage > 0 && $progress_percentage < 2)
                                                        ? 2
                                                        : min($progress_percentage, 100);
                                                @endphp

                                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                                    <div
                                                        class="bg-blue-500 h-2.5 rounded-full transition-all duration-300"
                                                        style="width: {{ $progress_percentage }}%">
                                                    </div>
                                                </div>

                                                <span class="text-xs font-medium text-gray-700 min-w-[36px]">
                                                    {{ $progress_percentage }}%
                                                </span>
                                            </div>
                                        </td>

                                        <!-- PRIORITY -->
                                        <td class="px-4 py-4 min-w-[180px] col-priority">
                                            {{ $project->priority }}
                                        </td>

                                        <!-- MILESTONE STATUS -->
                                        <td class="px-4 py-4 text-[12px] col-milestone_status">
                                            {{ $project->completed_milestones }} / {{ $project->total_milestones }}
                                        </td>

                                        <!-- STATUS -->
                                        <td class="px-4 py-4 col-status">
                                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $statusClasses }}">
                                                {{ $project->projectStatus->name ?? 'No Status' }}
                                            </span>
                                        </td>

                                        <!-- STAGE -->
                                        <td class="px-4 py-4 col-stage">
                                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $statusClasses }}">
                                                {{ $project->projectStage->name ?? 'No Stage' }}
                                            </span>
                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="12" class="text-center py-10 text-gray-500">
                                            No projects found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$projects" :per-page="$perPage" />
                </div>
            </div>
        </section>
    </div>
<!-- Filter drawer -->
 @php
    $typesFilter = collect($types)->map(
        fn($label, $key) => (object) [
            'id' => $key,
            'name' => $label,
        ],
    );
    $prioritiesFilter = collect($priorities)->map(
        fn($value, $key) => (object) [
            'id' => $key,
            'name' => $value['label'],
        ],
    ); 
    
@endphp
<x-filters.drawer>
    <x-filters.input-search name="name" label="Name" />
    <x-filters.multi-select id="project-flow-filter" name="project_flow" label="Project Flow" :options="$typesFilter" />
    <x-filters.multi-select id="project-filter" name="id" label="Project" :options="$projectsFilter" />
    <x-filters.multi-select name="customer_id" label="Customer" :options="$customers" />
    <x-filters.multi-select name="priority" label="Priority" :options="$prioritiesFilter" />
    <x-filters.multi-select name="status_id" label="Project Status" :options="$statuses" />
    <x-filters.date-range label="Project Date Range" startName="start_date" endName="end_date" />
</x-filters.drawer>
<!-- Filter drawer end -->

<script>
    window.reportConfig = {
        projectsByFlowUrl: "{{ route('reports.projects.by-flow') }}"
    };
</script>

@endsection


@push('scripts')
    @vite('resources/js/project-flow.js')
@endpush
