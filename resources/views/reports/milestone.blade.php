@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')
    @php
        $visibleColumnCount = collect($columns)->count() + 1;
    @endphp

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />

        <form method="GET" action="{{ route('reports.milestone.export') }}" id="milestone-report-export-form" data-column-order='@json(array_keys($columns))' class="inline-flex">
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

        <x-column-manager :columns="$columns" report="milestone_report" />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search target=".milestone-table" placeholder="Search milestones..." />
        </div>
    </div>

    <div class="custom-scroll mb-6 flex items-center gap-3 overflow-x-auto py-0">
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Milestones
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $milestoneStats['total'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Open
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $milestoneStats['open'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    In Progress
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $milestoneStats['in_progress'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Completed
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $milestoneStats['completed'] }}
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            <table class="milestone-table w-full min-w-[2100px]">
                <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[50px]">
                            #
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-milestone">
                            <x-sorting.sortable-column column="name" label="Milestone" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-project">
                            <x-sorting.sortable-column column="project_id" label="Project" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-owner">
                            <x-sorting.sortable-column column="owner_id" label="Owner" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[140px] col-start">
                            <x-sorting.sortable-column column="start_date" label="Start" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[140px] col-end">
                            <x-sorting.sortable-column column="end_date" label="End" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[130px] col-total_sprints">
                            Total Sprints
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[130px] col-total_tasks">
                            Total Tasks
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[150px] col-estimated">
                            Estimated
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[150px] col-derived">
                            Derived
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[150px] col-actual">
                            Actual
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px] col-progress">
                            Progress
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-status">
                            <x-sorting.sortable-column column="status_id" label="Status" />
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-darkblack-400">
                    @forelse($milestones as $milestone)
                        @php
                            $project = $milestone->project;
                            $projectUrl = $project ? ($project->trashed() ? route('projects.restore.show', $project->id) : route('projects.edit', $project)) : null;
                            $statusColor = $milestone->status?->color ?? '#94A3B8';
                            $estimatedSeconds = (int) ($milestone->estimated_time_seconds ?? 0);
                            $derivedSeconds = (int) ($milestone->derived_time_seconds ?? 0);
                            $actualSeconds = (int) ($milestone->actual_time_seconds ?? 0);
                            $progressPercentage = $estimatedSeconds > 0 ? round(($actualSeconds / $estimatedSeconds) * 100, 2) : 0;
                            $progressLabel = rtrim(rtrim(number_format((float) $progressPercentage, 2, '.', ''), '0'), '.');
                            $progressBarWidth = min($progressPercentage, 100);
                            $derivedTimeClasses = $derivedSeconds <= $estimatedSeconds
                                ? 'text-success-400 dark:text-success-300'
                                : 'text-red-500 dark:text-red-400';
                            $actualTimeClasses = $actualSeconds <= $estimatedSeconds
                                ? 'text-success-400 dark:text-success-300'
                                : 'text-red-500 dark:text-red-400';
                            $progressColorClasses = match (true) {
                                $estimatedSeconds <= 0 => 'bg-gray-300 text-bgray-700 dark:text-bgray-300',
                                $progressPercentage <= 50 => 'bg-success-400 text-success-400 dark:text-success-300',
                                $progressPercentage <= 100 => 'bg-orange-400 text-orange-500 dark:text-orange-300',
                                default => 'bg-red-500 text-red-500 dark:text-red-400',
                            };
                            [$progressBarClass, $progressTextClass] = explode(' ', $progressColorClasses, 2);
                        @endphp

                        <tr class="text-bgray-700 transition dark:text-bgray-50 {{ config('assets.classes.table_row_hover') }}">
                            <td class="px-2 py-2 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-milestone">
                                {{ $milestone->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-project">
                                @if ($projectUrl)
                                    <a href="{{ $projectUrl }}" class="transition hover:text-success-300 dark:hover:text-success-300">
                                        {{ $project?->name ?? '-' }}
                                    </a>
                                @else
                                    {{ $project?->name ?? '-' }}
                                @endif
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-owner">
                                {{ $milestone->owner?->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-start">
                                {{ optional($milestone->start_date)->format('d M Y') }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-end">
                                {{ optional($milestone->end_date)->format('d M Y') }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-total_sprints">
                                {{ $milestone->total_sprints }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-total_tasks">
                                {{ $milestone->total_tasks }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-estimated">
                                {{ formatSecondsToHoursMinutes($milestone->estimated_time_seconds) }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium whitespace-nowrap col-derived {{ $derivedTimeClasses }}">
                                {{ formatSecondsToHoursMinutes($milestone->derived_time_seconds) }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium whitespace-nowrap col-actual {{ $actualTimeClasses }}">
                                {{ formatSecondsToHoursMinutes($milestone->actual_time_seconds) }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 min-w-[220px] col-progress">
                                <div class="flex items-center gap-3">
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-400">
                                        <div class="h-2.5 rounded-full transition-all duration-300 {{ $progressBarClass }}" style="width: {{ $progressBarWidth }}%"></div>
                                    </div>

                                    <span class="min-w-[48px] text-xs font-semibold {{ $progressTextClass }}">
                                        {{ $progressLabel }}%
                                    </span>
                                </div>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-status">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium text-white" style="border: 1px solid {{ $statusColor }}; background-color: {{ $statusColor }};">
                                    {{ $milestone->status?->name ?? 'No Status' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <x-table-no-data col-span="{{ $visibleColumnCount }}" message="No milestones found." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$milestones" :per-page="$perPage" />
    </div>

    <x-filters.drawer>
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
        <x-filters.multi-select name="owner_id" label="Owner" :options="$owners" />
        <x-filters.multi-select name="status_id" label="Status" :options="$statuses" />
        <x-filters.date-range label="Milestone Date Range" startName="start_date" endName="end_date" />
    </x-filters.drawer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('milestone-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="milestone_report"]');

            if (!exportForm) {
                return;
            }

            const visibleColumnsInput = exportForm.querySelector('input[name="visible_columns"]');
            const columnOrder = JSON.parse(exportForm.dataset.columnOrder || '[]');
            const storageKey = 'column_manager_milestone_report';
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
