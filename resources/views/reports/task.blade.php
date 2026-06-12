@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px]')

@section('page-content')
    @php
        $visibleColumnCount = collect($columns)->count() + 1;
    @endphp

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-filters.button />

        <form method="GET" action="{{ route('reports.task.export') }}" id="task-report-export-form" data-column-order='@json(array_keys($columns))' class="inline-flex">
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

        <x-column-manager :columns="$columns" report="task_report" />

        <div class="inline-flex flex-wrap items-center gap-2 rounded-xl bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:ml-auto">
            <x-table-search target=".task-table" placeholder="Search tasks..." />
        </div>
    </div>

    <div class="custom-scroll mb-6 flex items-center gap-3 overflow-x-auto py-0">
        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Tasks
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $taskStats['total'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Pending Tasks
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $taskStats['pending'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Active Tasks
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $taskStats['active'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Archived Tasks
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $taskStats['archived'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Completed Tasks
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ $taskStats['completed'] }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Estimated
                </div>

                <div class="mt-2 text-2xl font-black leading-none text-bgray-900 dark:text-bgray-100">
                    {{ formatSecondsToHoursMinutes($taskStats['total_estimated'] ?? 0) }}
                </div>
            </div>
        </div>

        <div class="flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="min-w-0 flex-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-100">
                    Total Actual
                </div>

                @php
                    $totalActual = (int) ($taskStats['total_actual'] ?? 0);
                    $totalEstimated = (int) ($taskStats['total_estimated'] ?? 0);
                    $actualColorClass = 'text-bgray-900 dark:text-bgray-100';
                    if ($totalActual < $totalEstimated) {
                        $actualColorClass = 'text-success-400 dark:text-success-300';
                    } elseif ($totalActual > $totalEstimated) {
                        $actualColorClass = 'text-red-500 dark:text-red-400';
                    }
                @endphp

                <div class="mt-2 text-2xl font-black leading-none {{ $actualColorClass }}">
                    {{ formatSecondsToHoursMinutes($totalActual) }}
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            <table class="task-table w-full min-w-[2800px]">
                <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[50px]">
                            #
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px] col-task">
                            <x-sorting.sortable-column column="name" label="Task" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px] col-parent_task">
                            Parent Task
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-project">
                            <x-sorting.sortable-column column="project_id" label="Project" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-milestone">
                            <x-sorting.sortable-column column="project_milestone_id" label="Milestone" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-sprint">
                            <x-sorting.sortable-column column="project_sprint_id" label="Sprint" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-status">
                            <x-sorting.sortable-column column="status_id" label="Status" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-type">
                            <x-sorting.sortable-column column="task_type_id" label="Type" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-mode">
                            <x-sorting.sortable-column column="task_mode_id" label="Mode" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-priority">
                            <x-sorting.sortable-column column="priority" label="Priority" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[165px] col-assignee">
                            <x-sorting.sortable-column column="current_assignee_id" label="Assignee" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-due_date">
                            <x-sorting.sortable-column column="due_date_time" label="Due Date" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-completed_at">
                            <x-sorting.sortable-column column="completed_at" label="Completed At" />
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[150px] col-estimated">
                            Estimated
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[150px] col-actual">
                            Actual
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[220px] col-progress">
                            Progress
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[120px] col-billable">
                            Billable
                        </th>

                        <th scope="col" class="px-2 py-5 text-left text-sm font-semibold text-bgray-600 dark:text-bgray-50 xl:w-[180px] col-created_at">
                            <x-sorting.sortable-column column="created_at" label="Created At" />
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-darkblack-400">
                    @forelse($tasks as $task)
                        @php
                            $project = $task->project;
                            $projectUrl = $project ? ($project->trashed() ? route('projects.restore.show', $project->id) : route('projects.edit', $project)) : null;
                            $statusColor = $task->status?->color ?? '#94A3B8';
                            $typeColor = $task->taskType?->color ?? '#CBD5E1';
                            $modeColor = $task->taskMode?->color ?? '#CBD5E1';
                            $priorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');
                            $estimatedSeconds = (int) ($task->estimated_time_seconds ?? 0);
                            $actualSeconds = (int) ($task->actual_time_seconds ?? 0);
                            $progressPercentage = $estimatedSeconds > 0 ? round(($actualSeconds / $estimatedSeconds) * 100, 2) : 0;
                            $progressLabel = rtrim(rtrim(number_format((float) $progressPercentage, 2, '.', ''), '0'), '.');
                            $progressBarWidth = min($progressPercentage, 100);
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

                        <tr class="text-bgray-700 transition hover:bg-bgray-50 dark:text-bgray-50 dark:hover:bg-darkblack-500/80">
                            <td class="px-2 py-2 text-sm text-bgray-600 dark:text-bgray-300">
                                {{ $tasks->firstItem() + $loop->index }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium text-bgray-900 dark:text-bgray-300 col-task">
                                <a href="{{ route('tasks.edit', $task) }}" class="transition hover:text-success-300 dark:hover:text-success-300">
                                    {{ $task->name ?? '-' }}
                                </a>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-parent_task">
                                {{ $task->parentTask?->name ?? '-' }}
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

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-milestone">
                                {{ $task->projectMilestone?->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-sprint">
                                {{ $task->projectSprint?->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-status">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium text-white" style="border: 1px solid {{ $statusColor }}; background-color: {{ $statusColor }};">
                                    {{ $task->status?->name ?? 'No Status' }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-type">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium text-white" style="border: 1px solid {{ $typeColor }}; background-color: {{ $typeColor }};">
                                    {{ $task->taskType?->name ?? '-' }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-mode">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium text-white" style="border: 1px solid {{ $modeColor }}; background-color: {{ $modeColor }};">
                                    {{ $task->taskMode?->name ?? '-' }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-priority">
                                <span class="inline-flex items-center rounded-md px-3 py-1 text-xs font-medium {{ $priorityConfig['bg_class'] ?? 'bg-bgray-100 dark:bg-darkblack-500' }} {{ $priorityConfig['bg_text'] ?? 'text-bgray-700 dark:text-bgray-300' }}">
                                    {{ $priorityConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->priority ?? '-')) }}
                                </span>
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 col-assignee">
                                {{ $task->currentAssignee?->name ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-due_date">
                                {{ $task->due_date_time?->format('d M Y h:i A') ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-completed_at">
                                {{ $task->completed_at?->format('d M Y h:i A') ?? '-' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-estimated">
                                {{ formatSecondsToHoursMinutes($task->estimated_time_seconds) }}
                            </td>

                            <td class="px-2 py-2 text-sm font-medium whitespace-nowrap col-actual {{ $actualTimeClasses }}">
                                {{ formatSecondsToHoursMinutes($task->actual_time_seconds) }}
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

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-billable">
                                {{ $task->is_billable ? 'Yes' : 'No' }}
                            </td>

                            <td class="px-2 py-2 text-sm text-bgray-700 dark:text-bgray-300 whitespace-nowrap col-created_at">
                                {{ $task->created_at?->format('d M Y h:i A') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <x-table-no-data col-span="{{ $visibleColumnCount }}" message="No tasks found." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$tasks" :per-page="$perPage" />
    </div>

    <x-filters.drawer>
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
        <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$projectMilestones" />
        <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
        <x-filters.multi-select name="current_assignee_id" label="Assignee" :options="$assignees" />
        <x-filters.multi-select name="status_id" label="Status" :options="$statuses" />
        <x-filters.multi-select name="priority" label="Priority" :options="$priorities" />
        <x-filters.multi-select name="task_type_id" label="Task Type" :options="$taskTypeOptions" />
        <x-filters.multi-select name="task_mode_id" label="Task Mode" :options="$taskModeOptions" />
        <x-filters.date-range label="Created Date Range" startName="start_date" endName="end_date" />
    </x-filters.drawer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('task-report-export-form');
            const columnManager = document.querySelector('.column-manager[data-report="task_report"]');

            if (!exportForm) {
                return;
            }

            const visibleColumnsInput = exportForm.querySelector('input[name="visible_columns"]');
            const columnOrder = JSON.parse(exportForm.dataset.columnOrder || '[]');
            const storageKey = 'column_manager_task_report';
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
