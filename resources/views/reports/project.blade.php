@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')
    @php
        $visibleColumnCount = collect($columns)->except('actions')->count() + 1;
    @endphp

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />

        <!-- EXPORT -->
        <form method="GET" action="{{ route('reports.project.export') }}" id="project-report-export-form" data-column-order='@json(array_keys($columns))' class="inline-flex">
            @foreach (request()->except('visible_columns') as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <input type="hidden" name="visible_columns" value="">

            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-bgray-500 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300" aria-label="Export report">
                <span class="inline-flex items-center justify-center text-current">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4" />

                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                    </svg>
                </span>

                <span class="text-sm font-semibold">
                    Export Excel
                </span>
            </button>
        </form>

        <!-- COLUMN MANAGER -->
        <x-column-manager :columns="$columns" report="project_report" />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search target=".project-table" placeholder="Search projects..." />
        </div>
    </div>

    <!-- REPORT STATS -->
    <div class="custom-scroll mb-6 flex items-center gap-3 overflow-x-auto py-0">
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Projects
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $projectStats['total'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Completed
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $projectStats['completed'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    In Progress
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $projectStats['in_progress'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Pending
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $projectStats['open'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Archived
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $projectStats['archieved'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            <table class="project-table w-full min-w-[2230px]">
                <!-- TABLE HEADER -->
                <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[50px]">
                            #
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-project_name">
                            <x-sorting.sortable-column column="name" label="Project Name" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-customer">
                            <x-sorting.sortable-column column="customer_id" label="Customer" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-sales_person">
                            Sales Person
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-timeline">
                            Timeline
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-start_date">
                            <x-sorting.sortable-column column="start_date" label="Start Date" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-end_date">
                            <x-sorting.sortable-column column="end_date" label="End Date" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-estimated_hours">
                            Estimated
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-actual_hours">
                            Actual
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px] col-progress">
                            Progress
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-priority">
                            <x-sorting.sortable-column column="priority" label="Priority" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-milestone_status">
                            Milestone <br> Status
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-status">
                            <x-sorting.sortable-column column="status_id" label="Status" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-stage">
                            <x-sorting.sortable-column column="stage_id" label="Status" />
                            Stage
                        </th>
                    </tr>
                </thead>

                <!-- TABLE BODY -->
                <tbody class="divide-y divide-gray-200 dark:divide-darkblack-400">
                    @forelse($projects as $project)
                        @php
                            $projectUrl = $project ? ($project->trashed() ? route('projects.restore.show', $project->id) : route('projects.edit', $project)) : null;
                            $primaryTimeline = $project->primaryReportTimeline;
                            $eligibleTimelines = $project->reportTimelines;
                            $hasMultipleTimelines = $eligibleTimelines->count() > 1;
                            $rowNumber = ($projects->firstItem() ?? 1) + $loop->index;
                            $projectStatusColor = $project->projectStatus->color ?? '#94A3B8';
                            $projectStageColor = $project->projectStage->color ?? '#94A3B8';
                            $priorityConfig = config('project_constants.project_priorities.' . $project->priority);
                            $estimatedSeconds = (int) ($primaryTimeline?->report_estimated_seconds ?? 0);
                            $actualSeconds = (int) ($primaryTimeline?->report_actual_seconds ?? 0);
                            $progressPercentage = (float) ($primaryTimeline?->report_progress_percentage ?? 0);
                            $progressBarWidth = min($progressPercentage, 100);
                            $actualTimeClasses = $actualSeconds <= $estimatedSeconds ? 'text-success-400 dark:text-success-300' : 'text-red-500 dark:text-red-400';
                            $progressColorClasses = match (true) {
                                $estimatedSeconds <= 0 => 'bg-gray-300 text-bgray-700 dark:text-bgray-300',
                                $progressPercentage <= 50 => 'bg-success-400 text-success-400 dark:text-success-300',
                                $progressPercentage <= 100 => 'bg-orange-400 text-orange-500 dark:text-orange-300',
                                default => 'bg-red-500 text-red-500 dark:text-red-400',
                            };
                            [$progressBarClass, $progressTextClass] = explode(' ', $progressColorClasses, 2);
                        @endphp

                        <tr data-project-row="{{ $project->id }}" class="text-bgray-700 transition-colors dark:text-bgray-50 {{ config('assets.classes.table_row_hover') }}">
                            <td class="px-2 py-2 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $rowNumber }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-project_name">
                                <div class="flex items-center gap-2">
                                    @if ($hasMultipleTimelines)
                                        <button type="button" class="project-timeline-toggle inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-bgray-600 transition hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:text-bgray-200 dark:hover:bg-darkblack-400" data-project-id="{{ $project->id }}" aria-expanded="false" aria-controls="project-timelines-{{ $project->id }}" aria-label="Show timelines for {{ $project->name }}">
                                            <svg class="h-4 w-4 transition-transform duration-200" data-timeline-chevron fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="inline-block h-7 w-1 shrink-0" aria-hidden="true"></span>
                                    @endif

                                    @if ($projectUrl)
                                        <a href="{{ $projectUrl }}" class="transition hover:text-success-300 dark:hover:text-success-300">
                                            {{ $project?->name ?? '-' }}
                                        </a>
                                    @else
                                        <span>{{ $project?->name ?? '-' }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-customer">
                                {{ $project->customer->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-sales_person">
                                {{ $project->salesPerson->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-timeline">
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <span class="inline-flex items-center text-xs font-semibold">
                                        {{ $primaryTimeline?->name ?? '-' }}
                                    </span>

                                    @if ($hasMultipleTimelines)
                                        <span class="inline-flex min-w-5 items-center justify-center rounded-md bg-blue-600 px-1 py-1 text-xs font-bold text-white shadow-sm">
                                            {{ $eligibleTimelines->count() }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-start_date">
                                {{ $primaryTimeline?->start_date?->format($globalDateFormat) ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-end_date">
                                {{ $primaryTimeline?->end_date?->format($globalDateFormat) ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-estimated_hours">
                                {{ $primaryTimeline ? formatSecondsToHoursMinutes($estimatedSeconds) : '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium whitespace-nowrap col-actual_hours {{ $actualTimeClasses }}">
                                {{ $primaryTimeline ? formatSecondsToHoursMinutes($actualSeconds) : '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 min-w-[220px] col-progress">
                                <div class="flex items-center gap-3">
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-400">
                                        <div class="h-2.5 rounded-full transition-all duration-300 {{ $progressBarClass }}" style="width: {{ $progressBarWidth }}%"></div>
                                    </div>

                                    <span class="min-w-[48px] text-xs font-semibold {{ $progressTextClass }}">
                                        {{ $primaryTimeline ? rtrim(rtrim(number_format($progressPercentage, 2, '.', ''), '0'), '.') . '%' : '-' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-priority">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium {{ $priorityConfig['bg_class'] ?? 'bg-bgray-100 dark:bg-darkblack-500' }} {{ $priorityConfig['bg_text'] ?? 'text-bgray-700 dark:text-bgray-300' }}">
                                    {{ $priorityConfig['label'] ?? ucfirst(str_replace('_', ' ', $project->priority ?? '-')) }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-milestone_status">
                                {{ $project->completed_milestones }} / {{ $project->total_milestones }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-status">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium text-white" style="border: 1px solid {{ $projectStatusColor }}; background-color: {{ $projectStatusColor }};">
                                    {{ $project->projectStatus->name ?? 'No Status' }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-stage">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium text-white" style="border: 1px solid {{ $projectStageColor }}; background-color: {{ $projectStageColor }};">
                                    {{ $project->projectStage->name ?? 'No Stage' }}
                                </span>
                            </td>
                        </tr>

                        @if ($hasMultipleTimelines)
                            <tr data-search-details-for="{{ $project->id }}" class="bg-blue-50/40 dark:bg-darkblack-500/60">
                                <td colspan="{{ $visibleColumnCount }}" class="p-0">
                                    <div id="project-timelines-{{ $project->id }}" data-timeline-panel="{{ $project->id }}" class="grid grid-rows-[0fr] opacity-0 transition-all duration-200 ease-in-out" aria-hidden="true">
                                        <div class="min-h-0 overflow-hidden">
                                            <div class="mx-4 mb-4 ml-16 mt-3 overflow-hidden rounded-lg border border-blue-200 bg-white shadow-sm dark:border-darkblack-300 dark:bg-darkblack-600">
                                                <div class="overflow-x-auto">
                                                    <table class="w-full min-w-[1000px]">
                                                        <thead class="bg-blue-50/80 dark:bg-darkblack-500">
                                                            <tr class="border-b border-blue-200 dark:border-darkblack-400">
                                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">Timeline Name</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">Status</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">Start Date</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">End Date</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">Estimated</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">Actual</th>
                                                                <th class="min-w-[260px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-100">Progress</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody class="divide-y divide-blue-100 dark:divide-darkblack-400">
                                                            @foreach ($eligibleTimelines as $timeline)
                                                                @php
                                                                    $timelineEstimatedSeconds = (int) $timeline->report_estimated_seconds;
                                                                    $timelineActualSeconds = (int) $timeline->report_actual_seconds;
                                                                    $timelineProgress = (float) $timeline->report_progress_percentage;
                                                                    $timelineProgressBarWidth = min($timelineProgress, 100);
                                                                    $timelineActualClasses = $timelineActualSeconds <= $timelineEstimatedSeconds ? 'text-success-400 dark:text-success-300' : 'text-red-500 dark:text-red-400';
                                                                    $timelineProgressClasses = match (true) {
                                                                        $timelineEstimatedSeconds <= 0 => 'bg-gray-300 text-bgray-700 dark:text-bgray-300',
                                                                        $timelineProgress <= 50 => 'bg-success-400 text-success-400 dark:text-success-300',
                                                                        $timelineProgress <= 100 => 'bg-orange-400 text-orange-500 dark:text-orange-300',
                                                                        default => 'bg-red-500 text-red-500 dark:text-red-400',
                                                                    };
                                                                    [$timelineProgressBarClass, $timelineProgressTextClass] = explode(' ', $timelineProgressClasses, 2);
                                                                    $isActiveTimeline = $timeline->status === \App\Models\ProjectTimeline::STATUS_ACTIVE;
                                                                @endphp

                                                                <tr class="{{ $isActiveTimeline ? 'bg-green-50/80 dark:bg-green-950/20' : 'bg-white dark:bg-darkblack-600' }}">
                                                                    <td class="px-4 py-3 text-sm font-semibold text-bgray-900 dark:text-bgray-100">{{ $timeline->name ?? '-' }}</td>
                                                                    <td class="px-4 py-3 text-sm">
                                                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $isActiveTimeline ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200' }}">
                                                                            {{ $timeline->report_status_label }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-200">{{ $timeline->start_date?->format($globalDateFormat) ?? '-' }}</td>
                                                                    <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-200">{{ $timeline->end_date?->format($globalDateFormat) ?? '-' }}</td>
                                                                    <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-200">{{ formatSecondsToHoursMinutes($timelineEstimatedSeconds) }}</td>
                                                                    <td class="px-4 py-3 text-sm font-medium whitespace-nowrap {{ $timelineActualClasses }}">{{ formatSecondsToHoursMinutes($timelineActualSeconds) }}</td>
                                                                    <td class="px-4 py-3 text-sm">
                                                                        <div class="flex items-center gap-3">
                                                                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-400">
                                                                                <div class="h-2.5 rounded-full transition-all duration-300 {{ $timelineProgressBarClass }}" style="width: {{ $timelineProgressBarWidth }}%"></div>
                                                                            </div>
                                                                            <span class="min-w-[56px] text-xs font-semibold {{ $timelineProgressTextClass }}">
                                                                                {{ rtrim(rtrim(number_format($timelineProgress, 2, '.', ''), '0'), '.') }}%
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <x-table-no-data col-span="{{ $visibleColumnCount }}" message="No projects found." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$projects" :per-page="$perPage" />
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
        $categoriesFilter = collect($projectCategories ?? [])
            ->map(
                fn($cat) => (object) [
                    'id' => (string) $cat->id,
                    'name' => $cat->name,
                ],
            )
            ->push(
                (object) [
                    'id' => 'others',
                    'name' => 'Others',
                ],
            );
    @endphp
    <x-filters.drawer>
        {{-- <x-filters.input-search name="name" label="Name" /> --}}
        <x-filters.multi-select name="customer_id" label="Customer" :options="$customers" />
        <x-filters.multi-select id="project-flow-filter" name="project_flow" label="Project Flow" :options="$typesFilter" />
        <x-filters.multi-select id="project-filter" name="id" label="Project" :options="$projectsFilter" />
        <x-filters.multi-select name="project_category_ids" label="Project Category" :options="$categoriesFilter" />
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.project-timeline-toggle').forEach((button) => {
                button.addEventListener('click', function() {
                    const projectId = this.dataset.projectId;
                    const panel = document.querySelector(`[data-timeline-panel="${projectId}"]`);
                    const projectRow = document.querySelector(`[data-project-row="${projectId}"]`);
                    const chevron = this.querySelector('[data-timeline-chevron]');

                    if (!panel) {
                        return;
                    }

                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    this.setAttribute('aria-expanded', String(!isExpanded));
                    this.setAttribute('aria-label', `${isExpanded ? 'Show' : 'Hide'} timelines`);
                    panel.setAttribute('aria-hidden', String(isExpanded));
                    panel.classList.toggle('grid-rows-[0fr]', isExpanded);
                    panel.classList.toggle('opacity-0', isExpanded);
                    panel.classList.toggle('grid-rows-[1fr]', !isExpanded);
                    panel.classList.toggle('opacity-100', !isExpanded);
                    chevron?.classList.toggle('rotate-90', !isExpanded);
                    projectRow?.classList.toggle('bg-blue-50/70', !isExpanded);
                    projectRow?.classList.toggle('dark:bg-blue-950/20', !isExpanded);
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('project-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="project_report"]');

            if (!exportForm) {
                return;
            }

            const visibleColumnsInput = exportForm.querySelector('input[name="visible_columns"]');
            const columnOrder = JSON.parse(exportForm.dataset.columnOrder || '[]');
            const storageKey = 'column_manager_project_report';
            const minimumVisibleColumns = 3;

            const syncVisibleColumns = () => {
                let saved = {};

                try {
                    saved = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
                } catch (error) {
                    saved = {};
                }

                const visibleColumns = columnOrder.filter((column) => saved[column] !== false);
                visibleColumnsInput.value = visibleColumns.join(',');
            };

            const getColumnCheckboxes = () => columnManager ?
                Array.from(columnManager.querySelectorAll('.cm-toggle')) : [];

            const getCheckedColumns = () => getColumnCheckboxes().filter((checkbox) => checkbox.checked);

            const toggleColumnVisibility = (column, show) => {
                document.querySelectorAll('.col-' + column).forEach((element) => {
                    element.style.display = show ? '' : 'none';
                });
            };

            const readSavedColumns = () => {
                try {
                    return JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
                } catch (error) {
                    return {};
                }
            };

            const writeSavedColumns = (saved) => {
                localStorage.setItem(storageKey, JSON.stringify(saved));
            };

            const enforceMinimumColumns = () => {
                if (!columnManager) {
                    return;
                }

                const checkboxes = getColumnCheckboxes();
                const checkedColumns = getCheckedColumns();

                if (checkedColumns.length < minimumVisibleColumns) {
                    const saved = readSavedColumns();

                    checkboxes
                        .filter((checkbox) => !checkbox.checked)
                        .slice(0, minimumVisibleColumns - checkedColumns.length)
                        .forEach((checkbox) => {
                            checkbox.checked = true;
                            saved[checkbox.dataset.column] = true;
                            toggleColumnVisibility(checkbox.dataset.column, true);
                        });

                    writeSavedColumns(saved);
                }

                const nextCheckedColumns = getCheckedColumns();
                const shouldLockChecked = nextCheckedColumns.length <= minimumVisibleColumns;

                checkboxes.forEach((checkbox) => {
                    checkbox.disabled = shouldLockChecked && checkbox.checked;
                });

                syncVisibleColumns();
            };

            if (columnManager) {
                const checkboxes = getColumnCheckboxes();

                checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            enforceMinimumColumns();
                            return;
                        }

                        if (getCheckedColumns().length < minimumVisibleColumns) {
                            const saved = readSavedColumns();

                            this.checked = true;
                            saved[this.dataset.column] = true;
                            toggleColumnVisibility(this.dataset.column, true);
                            writeSavedColumns(saved);
                        }

                        enforceMinimumColumns();
                    });
                });

                columnManager.querySelector('.cm-select-all')?.addEventListener('click', () => {
                    window.requestAnimationFrame(enforceMinimumColumns);
                });

                columnManager.querySelector('.cm-reset')?.addEventListener('click', () => {
                    window.requestAnimationFrame(enforceMinimumColumns);
                });
            }

            enforceMinimumColumns();
            syncVisibleColumns();
            exportForm.addEventListener('submit', syncVisibleColumns);
        });
    </script>

@endsection


@push('scripts')
    @vite('resources/js/project-flow.js')
@endpush
