@extends('layouts.master')

@section('page-content')
<main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]">
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />
        <x-export-button
            :href="route('reports.project.export', request()->query())"
            label="Export Excel"
        />
    </div>

    <div class="2xl:space-x-[48px]">
        <section class="mb-6 2xl:mb-0 2xl:flex-1">
            <div class="w-full rounded-lg bg-white dark:bg-darkblack-600">
                <div class="flex-col space-y-5">
                    <!-- TABLE SECTION -->
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full min-w-[1300px] text-sm">
                            <!-- TABLE HEADER -->
                            <thead class="bg-[#0F172A] text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">#</th>

                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap text-[12px]">
                                        Project Name
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap text-[12px]">
                                        Customer
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap text-[12px]">
                                        Sales Person
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap text-[12px]">
                                        Start Date
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap text-[12px]">
                                        End Date
                                    </th>

                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap text-[12px]">
                                        Estimated <br> Time (hrs)
                                    </th>

                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap text-[12px]">
                                        Actual Time <br> (hrs)
                                    </th>

                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap text-[12px]">
                                        Progress
                                    </th>

                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap text-[12px]">
                                        Milestone <br> Status
                                    </th>

                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap text-[12px]">
                                        Status
                                    </th>

                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap text-[12px]">
                                        Stage
                                    </th>

                                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap text-[12px]">
                                        Actions
                                    </th>
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
                                    @endphp

                                    <tr class="hover:bg-gray-50 transition">

                                        <!-- SL NO -->
                                        <td class="px-4 py-4">
                                            {{ $loop->iteration }}
                                        </td>

                                        <!-- PROJECT -->
                                        <td class="px-4 py-4 font-medium text-gray-800 text-[12px]">
                                            {{ $project->name }}
                                        </td>

                                        <!-- CUSTOMER -->
                                        <td class="px-4 py-4 text-[12px]">
                                            {{ $project->customer->name ?? '-' }}
                                        </td>

                                        <!-- SALES PERSON -->
                                        <td class="px-4 py-4 text-[12px]">
                                            {{ $project->salesPerson->name ?? '-' }}
                                        </td>

                                        <!-- START DATE -->
                                        <td class="px-4 py-4 text-[12px]">
                                            {{ optional($project->start_date)->format('d M Y') }}
                                        </td>

                                        <!-- END DATE -->
                                        <td class="px-4 py-4 text-[12px]">
                                            {{ optional($project->end_date)->format('d M Y') }}
                                        </td>

                                        <!-- ESTIMATED HOURS -->
                                        <td class="px-4 py-4 text-center">
                                            {{ $project->estimated_hours }}
                                        </td>

                                        <!-- ACTUAL HOURS -->
                                        <td class="px-4 py-4 text-center">
                                            {{ $project->actual_hours }}
                                        </td>

                                        <!-- PROGRESS -->
                                        <td class="px-4 py-4 min-w-[180px]">

                                            <div class="flex items-center gap-3">
                                                @php 
                                                $progress_percentage = ($project->estimated_time_seconds ?? 0) > 0
                                                    ? round(
                                                        (($project->actual_time_seconds ?? 0) /
                                                        $project->estimated_time_seconds) * 100
                                                    )
                                                    : 0;
                                                @endphp
                                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                                    <div class="bg-blue-500 h-2.5 rounded-full"
                                                        style="width: {{ $progress_percentage }}%">
                                                    </div>
                                                </div>

                                                <span class="text-xs font-medium text-gray-700 min-w-[36px]">
                                                    {{ $progress_percentage }}%
                                                </span>

                                            </div>

                                        </td>

                                        <!-- MILESTONE STATUS -->
                                        <td class="px-4 py-4 text-center text-[12px]">
                                            {{ $project->completed_milestones }} / {{ $project->total_milestones }}
                                        </td>

                                        <!-- STATUS -->
                                        <td class="px-4 py-4 text-center">

                                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $statusClasses }}">
                                                {{ $project->projectStatus->name ?? 'No Status' }}
                                            </span>

                                        </td>

                                        <!-- STAGE -->
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium {{ $statusClasses }}">
                                                {{ $project->projectStage->name ?? 'No Stage' }}
                                            </span>
                                        </td>

                                        

                                        <!-- ACTION -->
                                        <td class="px-4 py-4 text-center">

                                            <a href="#"
                                            class="text-blue-600 hover:text-blue-800">

                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5 inline-block"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>

                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                            c4.478 0 8.268 2.943 9.542 7
                                                            -1.274 4.057-5.064 7-9.542 7
                                                            -4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>

                                            </a>

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
                </div>
            </div>
        </section>
    </div>
</main>

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
    <x-filters.multi-select name="id" label="Project" :options="$projectsFilter" />
    <x-filters.multi-select name="customer_id" label="Customer" :options="$customers" />
    <x-filters.multi-select name="project_flow" label="Project Flow" :options="$typesFilter" />
    <x-filters.multi-select name="priority" label="Priority" :options="$prioritiesFilter" />
    <x-filters.multi-select name="status_id" label="Project Status" :options="$statuses" />
</x-filters.drawer>
<!-- Filter drawer end -->

@endsection