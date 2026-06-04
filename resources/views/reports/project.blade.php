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
        {{-- <x-export-button :action="route('reports.project.export')" :params="request()->query()" label="Export Excel" /> --}}

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
            <table class="project-table w-full min-w-[2050px]">
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

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-start_date">
                            <x-sorting.sortable-column column="start_date" label="Start Date" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-end_date">
                            <x-sorting.sortable-column column="end_date" label="End Date" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-estimated_hours">
                            Estimated <br> Time (hrs)
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-actual_hours">
                            Actual Time <br> (hrs)
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
                            $status = $project->status->name ?? 'Not Started';
                            $statusClasses = match (strtolower($status)) {
                                'completed' => 'bg-green-100 text-green-700',
                                'in progress' => 'bg-yellow-100 text-yellow-700',
                                'not started' => 'bg-gray-100 text-gray-700',
                                default => 'bg-blue-100 text-blue-700',
                            };
                            $projectUrl = $project ? ($project->trashed() ? route('projects.restore.show', $project->id) : route('projects.edit', $project)) : null;
                        @endphp

                        <tr class="text-bgray-700 transition hover:bg-bgray-50 dark:text-bgray-50 dark:hover:bg-darkblack-500/80">
                            <td class="px-2 py-2 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-project_name">
                                @if ($projectUrl)
                                    <a href="{{ $projectUrl }}" class="transition hover:text-success-300 dark:hover:text-success-300">
                                        {{ $project?->name ?? '-' }}
                                    </a>
                                @else
                                    {{ $project?->name ?? '-' }}
                                @endif
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-customer">
                                {{ $project->customer->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-sales_person">
                                {{ $project->salesPerson->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-start_date">
                                {{ optional($project->start_date)->format('d M Y') }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-end_date">
                                {{ optional($project->end_date)->format('d M Y') }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-estimated_hours">
                                {{ formatSecondsToHoursMinutes($project->projectMilestones->sum('estimated_time_seconds')) }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-actual_hours">
                                {{ formatSecondsToHoursMinutes($project->projectMilestones->sum('actual_time_seconds')) }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 min-w-[220px] col-progress">
                                <div class="flex items-center gap-3">
                                    @php
                                        $estimatedSeconds = $project->projectMilestones->sum('estimated_time_seconds') ?? 0;
                                        $actualSeconds = $project->projectMilestones->sum('actual_time_seconds') ?? 0;
                                        $progressPercentage = $estimatedSeconds > 0 ? round(($actualSeconds / $estimatedSeconds) * 100, 2) : 0;
                                    @endphp

                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-400">
                                        <div class="h-2.5 rounded-full bg-blue-500 transition-all duration-300" style="width: {{ $progressPercentage }}%"></div>
                                    </div>

                                    <span class="min-w-[48px] text-xs font-medium text-bgray-700 dark:text-bgray-300">
                                        {{ $progressPercentage }}%
                                    </span>
                                </div>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-priority">
                                {{ $project->priority }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-milestone_status">
                                {{ $project->completed_milestones }} / {{ $project->total_milestones }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-status">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                    {{ $project->projectStatus->name ?? 'No Status' }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-stage">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                    {{ $project->projectStage->name ?? 'No Stage' }}
                                </span>
                            </td>
                        </tr>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('project-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="project_report"]');

            if (!exportForm || !columnManager) {
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

            const getColumnCheckboxes = () => Array.from(columnManager.querySelectorAll('.cm-toggle'));
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
            };

            const applySavedColumns = () => {
                const saved = readSavedColumns();

                getColumnCheckboxes().forEach((checkbox) => {
                    const isVisible = saved[checkbox.dataset.column] !== false;
                    checkbox.checked = isVisible;
                    toggleColumnVisibility(checkbox.dataset.column, isVisible);
                });

                enforceMinimumColumns();
            };

            getColumnCheckboxes().forEach((checkbox) => {
                checkbox.addEventListener('change', function() {
                    const saved = readSavedColumns();
                    saved[this.dataset.column] = this.checked;
                    writeSavedColumns(saved);
                    toggleColumnVisibility(this.dataset.column, this.checked);
                    enforceMinimumColumns();
                    syncVisibleColumns();
                });
            });

            columnManager.querySelector('.cm-select-all')?.addEventListener('click', () => {
                window.requestAnimationFrame(() => {
                    const saved = {};

                    getColumnCheckboxes().forEach((checkbox) => {
                        checkbox.checked = true;
                        saved[checkbox.dataset.column] = true;
                        toggleColumnVisibility(checkbox.dataset.column, true);
                    });

                    writeSavedColumns(saved);
                    enforceMinimumColumns();
                    syncVisibleColumns();
                });
            });

            columnManager.querySelector('.cm-reset')?.addEventListener('click', () => {
                window.requestAnimationFrame(() => {
                    localStorage.removeItem(storageKey);
                    applySavedColumns();
                    syncVisibleColumns();
                });
            });

            applySavedColumns();
            syncVisibleColumns();
            exportForm.addEventListener('submit', syncVisibleColumns);
        });
    </script>

@endsection


@push('scripts')
    @vite('resources/js/project-flow.js')
@endpush
