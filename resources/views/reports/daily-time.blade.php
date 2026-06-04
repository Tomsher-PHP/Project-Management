@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />

        @if ($canExport)
            <form method="GET" action="{{ route('reports.daily_time.export') }}" id="daily-time-report-export-form" data-column-order='@json(array_keys($columns))' class="inline-flex">
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
        {{-- <x-export-button :action="route('reports.daily_time.export')" :params="request()->except('visible_columns')" :hidden-fields="['visible_columns' => '']" :show="$canExport" id="daily-time-report-export-form" data-column-order='@json(array_keys($columns))' /> --}}

        <x-column-manager :columns="$columns" report="daily_time_report" />
    </div>

    <div class="custom-scroll mb-6 flex items-center gap-3 overflow-x-auto py-0">
        <div class="flex min-w-[180px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Worked Time
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['total_worked_time'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[180px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Users
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['total_users'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[180px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Records
                </div>
                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $summaryStats['total_records'] }}
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            @php
                $tableColumnCount = count($columns) + 1;
                $reportNumber = ($reports->currentPage() - 1) * $reports->perPage();
            @endphp

            <table class="daily-report-table w-full min-w-[1200px]">
                <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[50px]">
                            #
                        </th>
                        <th scope="col" class="col-user px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px]">
                            User
                        </th>
                        <th scope="col" class="col-date px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[150px]">
                            Date
                        </th>
                        <th scope="col" class="col-start_time px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[140px]">
                            Start Time
                        </th>
                        <th scope="col" class="col-end_time px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[140px]">
                            End Time
                        </th>
                        <th scope="col" class="col-worked_time px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[160px]">
                            Worked Hours
                        </th>
                        <th scope="col" class="col-shift_hour px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[160px]">
                            Shift Hours
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

                            <td class="col-date px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $row['date'] }}
                            </td>

                            <td class="col-start_time px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $row['start_time'] }}
                            </td>

                            <td class="col-end_time px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                @if ($row['end_time'] === 'Running')
                                    <span class="font-semibold text-success-300">Running</span>
                                @else
                                    {{ $row['end_time'] }}
                                @endif
                            </td>

                            <td class="col-worked_time px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300">
                                {{ $row['total_worked_time'] }}
                            </td>

                            <td class="col-shift_hour px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300">
                                @if ($row['shift_working_hour'] === 'Day Off')
                                    <span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>
                                @else
                                    {{ $row['shift_working_hour'] }}
                                @endif
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
            const exportForm = document.getElementById('daily-time-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="daily_time_report"]');

            if (!columnManager) {
                return;
            }

            const storageKey = 'column_manager_daily_time_report';
            const minimumVisibleColumns = 3;
            const visibleColumnsInput = exportForm?.querySelector('input[name="visible_columns"]');
            const columnOrder = JSON.parse(exportForm?.dataset.columnOrder || '[]');

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
