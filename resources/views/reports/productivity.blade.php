@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />

        @if ($canExport)
            <form method="GET" action="{{ route('reports.productivity.export') }}" id="productivity-report-export-form" data-column-order='@json(array_keys($columns))' class="inline-flex">
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
        @endif

        <x-column-manager :columns="$columns" report="productivity_report" />
    </div>

    <div class="custom-scroll mb-6 flex items-center gap-3 overflow-x-auto py-0">
        <div class="flex min-w-[170px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Result
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['total_result'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[170px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Tasks
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['tasks'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[170px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Completed
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['completed'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[170px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Estimated Hours
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['estimated_hours'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[170px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Spend Hours
                </div>
                <div class="mt-2 text-2xl font-black leading-none {{ $summaryStats['spend_hours_color_class'] }}">
                    {{ $summaryStats['spend_hours'] }}
                </div>
            </div>
        </div>
    </div>

    <div class="relative z-20 overflow-visible rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            @php
                $tableColumnCount = count($columns) + 1;
                $reportNumber = ($reports->currentPage() - 1) * $reports->perPage();
            @endphp

            <table class="productivity-report-table w-full min-w-[1200px]">
                <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[50px]">
                            #
                        </th>
                        <th scope="col" class="col-user px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[240px]">
                            <x-sorting.sortable-column column="user" label="User" />
                        </th>
                        <th scope="col" class="col-tasks_count px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[170px]">
                            <x-sorting.sortable-column column="tasks_count" label="Tasks Count" />
                        </th>
                        <th scope="col" class="col-completed_tasks_count px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px]">
                            <x-sorting.sortable-column column="completed_tasks_count" label="Completed Tasks Count" />
                        </th>
                        <th scope="col" class="col-estimated_hours px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px]">
                            <x-sorting.sortable-column column="estimated_hours" label="Estimated Hours" />
                        </th>
                        <th scope="col" class="col-spend_hours px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[160px]">
                            <x-sorting.sortable-column column="spend_hours" label="Spend Hours" />
                        </th>
                        <th scope="col" class="col-efficiency px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[160px]">
                            @php
                                $currentSort = request('sort_by');
                                $currentDir = request('sort_dir', 'asc');
                                $isEfficiencySortActive = $currentSort === 'efficiency';
                                $nextEfficiencyDir = $isEfficiencySortActive && $currentDir === 'asc' ? 'desc' : 'asc';
                                $efficiencyIconColor = $isEfficiencySortActive ? '#2563EB' : '#718096';
                            @endphp

                            <a href="{{ request()->fullUrlWithQuery([
                                'sort_by' => 'efficiency',
                                'sort_dir' => $nextEfficiencyDir,
                            ]) }}" class="flex w-full cursor-pointer items-center space-x-2.5">
                                <span class="inline-flex items-center gap-1.5 text-base font-medium text-bgray-600 dark:text-bgray-50">
                                    <span>Efficiency (%)</span>
                                    <span class="group relative inline-flex h-4 w-4 shrink-0 items-center justify-center text-bgray-400 transition hover:text-success-300 dark:text-bgray-300 dark:hover:text-success-300" aria-label="Efficiency formula">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25h.75v5.25h.75" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h.01" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <span class="pointer-events-none absolute right-0 top-full z-50 mt-2 hidden w-72 rounded-lg bg-bgray-600 px-3 py-2.5 text-left text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                            Efficiency = (Spend Hours ÷ Estimated Hours) × 100<br>
                                            100% = exactly on estimate<br>
                                            Below 100% = under estimate<br>
                                            Above 100% = exceeded estimate
                                        </span>
                                    </span>
                                </span>

                                <span>
                                    <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.332 1.31567V13.3157" stroke="{{ $efficiencyIconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M5.66602 11.3157L3.66602 13.3157L1.66602 11.3157" stroke="{{ $efficiencyIconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M3.66602 13.3157V1.31567" stroke="{{ $efficiencyIconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12.332 3.31567L10.332 1.31567L8.33203 3.31567" stroke="{{ $efficiencyIconColor }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </a>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-darkblack-400">
                    @forelse($reports as $row)
                        @php
                            $reportNumber++;
                        @endphp

                        <tr class="text-bgray-700 transition hover:bg-bgray-50 dark:text-bgray-50 dark:hover:bg-darkblack-500/80">
                            <td class="px-2 py-2 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $reportNumber }}
                            </td>

                            <td class="col-user px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300">
                                {{ $row['user_name'] }}
                            </td>

                            <td class="col-tasks_count px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $row['tasks_count'] }}
                            </td>

                            <td class="col-completed_tasks_count px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $row['completed_tasks_count'] }}
                            </td>

                            <td class="col-estimated_hours px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $row['estimated_hours'] }}
                            </td>

                            <td class="col-spend_hours px-2 py-2 text-sm">
                                <span class="{{ $row['efficiency_color_class'] }}">
                                    {{ $row['spend_hours'] }}
                                </span>
                            </td>

                            <td class="col-efficiency px-2 py-2 text-sm">
                                <span class="{{ $row['efficiency_color_class'] }}">
                                    {{ $row['efficiency_label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <x-table-no-data col-span="{{ $tableColumnCount }}" message="No records found." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$reports" :per-page="$perPage" />
    </div>

    <x-filters.drawer>
        <x-filters.date-range label="Date Range" startName="from_date" endName="to_date" />
        <x-filters.multi-select name="user_id" label="Users" :options="$users" />
    </x-filters.drawer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('productivity-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="productivity_report"]');

            if (!columnManager) {
                return;
            }

            const storageKey = 'column_manager_productivity_report';
            const minimumVisibleColumns = 3;
            const visibleColumnsInput = exportForm?.querySelector('input[name="visible_columns"]');
            const columnOrder = JSON.parse(exportForm?.dataset.columnOrder || '@json(array_keys($columns))');

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

            const syncVisibleColumns = () => {
                if (!visibleColumnsInput) {
                    return;
                }

                const saved = readSavedColumns();
                const visibleColumns = columnOrder.filter((column) => saved[column] !== false);
                visibleColumnsInput.value = visibleColumns.join(',');
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
                syncVisibleColumns();
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
            exportForm?.addEventListener('submit', syncVisibleColumns);
        });
    </script>
@endsection
