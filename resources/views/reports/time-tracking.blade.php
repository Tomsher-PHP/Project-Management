@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')

    <!-- TOP ACTION BAR -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <!-- FILTER BUTTON -->
        <x-filters.button />

        <!-- EXPORT -->
        <form method="GET" action="{{ route('reports.time_tracking.export') }}" id="time-tracking-report-export-form" data-column-order='@json(array_keys($columns))' class="inline-flex">
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
        <x-column-manager :columns="$columns" report="time_tracking_report" />
    </div>

    <!-- REPORT STATS -->
    <div class="custom-scroll mb-6 flex items-center gap-3 overflow-x-auto py-0">

        <!-- TOTAL TIME -->
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Time
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $dailyStats['total_hours'] }}
                </div>
            </div>
        </div>

        <!-- TOTAL USERS -->
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Users
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $dailyStats['active_users'] }}
                </div>
            </div>
        </div>

        <!-- TOTAL PROJECTS -->
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Projects
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $dailyStats['project_count'] }}
                </div>
            </div>
        </div>

        <!-- TOTAL TASKS -->
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Tasks
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $dailyStats['task_count'] }}
                </div>
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

        <div class="overflow-x-auto">
            @php
                $tableColumnCount = count($columns) + 1;
            @endphp

            <table class="daily-report-table w-full min-w-[1650px]">

                <!-- HEADER -->
                <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[50px]">
                            #
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-project">
                            Project
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-milestone">
                            Milestone
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-sprint">
                            Sprint
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-task">
                            Task
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-user">
                            User
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-date">
                            Date
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-start_time">
                            Start Time
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-end_time">
                            End Time
                        </th>

                        <th scope="col" class="px-6 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-duration">
                            Duration
                        </th>

                    </tr>
                </thead>

                <!-- BODY -->
                <tbody class="divide-y divide-gray-200 dark:divide-darkblack-400">
                    @php
                        $reportNumber = ($reports->currentPage() - 1) * $reports->perPage();
                    @endphp

                    @forelse($displayRows as $row)
                        <!-- BREAK ROW -->
                        @if (($row['type'] ?? null) === 'break')
                            <tr class="border-y border-yellow-200 bg-yellow-50 dark:border-yellow-900/30 dark:bg-yellow-900/20">
                                <td colspan="{{ $tableColumnCount }}" class="px-5 py-4 text-center text-sm font-medium text-black dark:text-bgray-50">
                                    BREAK:
                                    {{ $row['break']['duration_label'] ?? formatSecondsToHMS($row['break']['duration_seconds'] ?? 0) }}
                                </td>
                            </tr>
                            @continue
                        @endif
                        @php
                            $report = $row['report'];
                            $milestone = $report->task?->projectMilestone ?? $report->task?->projectSprint?->projectMilestone;
                            $sprint = $report->task?->projectSprint;
                            $reportNumber++;
                        @endphp

                        <!-- DATA ROW -->
                        <tr class="text-bgray-700 transition hover:bg-bgray-50 dark:text-bgray-50 dark:hover:bg-darkblack-500/80">
                            <td class="px-5 py-2 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $reportNumber }}
                            </td>

                            <td class="px-5 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-project">
                                <x-project-flow-icon :flow="$report->task?->project?->project_flow" size="sm" />
                                {{ $report->task?->project?->name ?? '-' }}
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-milestone">
                                {{ $milestone?->name ?? '-' }}
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-sprint">
                                {{ $sprint?->name ?? '-' }}
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-task">
                                {{ $report->task?->name ?? '-' }}
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-user">
                                {{ $report->user?->name ?? '-' }}
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-date">
                                @appDate($report->started_at)
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-start_time">
                                @appTime($report->started_at)
                            </td>

                            <td class="px-5 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-end_time">
                                @appTime($report->ended_at)
                            </td>

                            <td class="px-5 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-duration">
                                {{ formatSecondsToHMS($report->duration_seconds) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColumnCount }}" class="px-5 py-10 text-center text-sm text-bgray-500 dark:text-bgray-300">
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
        <x-pagination :paginator="$reports" :per-page="$perPage" />
    </div>

    <!-- FILTER DRAWER -->
    <x-filters.drawer>
        <x-filters.date-range label="Date Range" startName="start_date" endName="end_date" />
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
        <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$projectMilestones" />
        <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
        <x-filters.multi-select name="user_id" label="Users" :options="$users" />
    </x-filters.drawer>

    <script id="task-filter-dependencies" type="application/json">
        @json([
            'milestones' => $projectMilestones->values(),
            'sprints' => $projectSprints->values(),
        ])
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('time-tracking-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="time_tracking_report"]');

            if (!exportForm) {
                return;
            }

            const visibleColumnsInput = exportForm.querySelector('input[name="visible_columns"]');
            const columnOrder = JSON.parse(exportForm.dataset.columnOrder || '[]');
            const storageKey = 'column_manager_time_tracking_report';
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

            const getColumnCheckboxes = () => columnManager
                ? Array.from(columnManager.querySelectorAll('.cm-toggle'))
                : [];

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
