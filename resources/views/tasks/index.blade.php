@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can('task.create')
                <a href="javascript:void(0)" class="inline-flex items-center rounded-md bg-success-300 px-4 py-1.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Task</span>
                </a>
            @endcan

            <x-filters.button />
        </div>

        @php
            $typePalette = [
                'gray' => ['bg' => '#E5E7EB', 'text' => '#374151'],
                'green' => ['bg' => '#DCFCE7', 'text' => '#166534'],
                'red' => ['bg' => '#FEE2E2', 'text' => '#B91C1C'],
                'pink' => ['bg' => '#FCE7F3', 'text' => '#BE185D'],
                'blue' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
                'violet' => ['bg' => '#EDE9FE', 'text' => '#6D28D9'],
                'orange' => ['bg' => '#FFEDD5', 'text' => '#C2410C'],
                'cyan' => ['bg' => '#CFFAFE', 'text' => '#0E7490'],
            ];

            $typeOptions = collect($types)->map(
                fn($config, $key) => (object) [
                    'id' => $key,
                    'name' => $config['label'],
                ],
            );

            $modeOptions = collect($modes)->map(
                fn($config, $key) => (object) [
                    'id' => $key,
                    'name' => $config['label'],
                ],
            );

            $priorityOptions = collect($priorities)->map(
                fn($config, $key) => (object) [
                    'id' => $key,
                    'name' => $config['label'],
                ],
            );
        @endphp

        <section>
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="title" label="Task" />
                                </th>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="project.name" label="Project" />
                                </th>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="currentAssignee.name" label="Assignee" />
                                </th>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="status.name" label="Status" />
                                </th>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="task_type" label="Type" />
                                </th>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="task_mode" label="Task Mode" />
                                </th>
                                <th class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                    <x-sorting.sortable-column column="estimated_time_seconds" label="Estimate Time" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="due_date" label="Due Date" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600">
                            @forelse ($tasks as $task)
                                @php
                                    $statusColor = $task->status?->color ?: '#CBD5E1';
                                    $priorityConfig = config('project_constants.task_priorities.' . ($task->priority ?: 'medium')) ?? config('project_constants.task_priorities.medium');
                                    $typeConfig = config('project_constants.task_type.' . ($task->task_type ?: 'normal')) ?? config('project_constants.task_type.normal');
                                    $modeConfig = config('project_constants.task_mode.' . ($task->task_mode ?: 'standard')) ?? config('project_constants.task_mode.standard');
                                    $typeColor = $typePalette[$typeConfig['color'] ?? 'gray'] ?? $typePalette['gray'];
                                    $modeColor = $typePalette[$modeConfig['color'] ?? 'blue'] ?? $typePalette['blue'];
                                    $typeLabel = $typeConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->task_type ?: 'normal'));
                                    $modeLabel = $modeConfig['label'] ?? ucfirst(str_replace('_', ' ', $task->task_mode ?: 'standard'));
                                @endphp

                                <tr class="transition hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/60">
                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 h-12 w-1.5 flex-shrink-0 rounded-full {{ $priorityConfig['bg_class'] ?? 'bg-primary' }}"></span>

                                            <div class="min-w-0">
                                                <p class="truncate text-lg font-semibold text-bgray-900 dark:text-white">
                                                    {{ $task->title }}
                                                </p>
                                                <p class="mt-1 text-sm text-[#7C97C1] dark:text-bgray-300">
                                                    {{ $task->code ?: 'TSK-' . str_pad($task->id, 5, '0', STR_PAD_LEFT) }}
                                                </p>

                                                @if ($task->project?->project_flow === 'agile')
                                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-bgray-500 dark:text-bgray-300">
                                                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 dark:bg-darkblack-500">
                                                            Module: {{ $task->projectModule?->name ?? '--' }}
                                                        </span>
                                                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 dark:bg-darkblack-500">
                                                            Sprint: {{ $task->projectSprint?->name ?? '--' }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-bgray-900 dark:text-white">
                                                {{ $task->project?->name ?? '--' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        @if ($task->currentAssignee)
                                            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-100">
                                                {{ $task->currentAssignee->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                                                Unassigned
                                            </span>
                                        @endif
                                    </td>

                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        @if ($task->status)
                                            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-100">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColor }}"></span>
                                                {{ $task->status->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                                                No status
                                            </span>
                                        @endif
                                    </td>

                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        <span class="inline-flex min-w-[96px] items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold" style="background-color: {{ $typeColor['bg'] }}; color: {{ $typeColor['text'] }};">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>

                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        <span class="inline-flex min-w-[120px] items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold" style="background-color: {{ $modeColor['bg'] }}; color: {{ $modeColor['text'] }};">
                                            {{ $modeLabel }}
                                        </span>
                                    </td>

                                    <td class="border-b border-r border-bgray-200 border-r-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400 dark:border-r-darkblack-400">
                                        <div class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</div>
                                        <div class="text-xs text-bgray-500 dark:text-bgray-300">Actual {{ $task->actual_time_formatted }}</div>
                                    </td>

                                    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
                                        @if ($task->due_date)
                                            <div class="text-sm font-medium text-bgray-900 dark:text-white">@appDate($task->due_date)</div>
                                        @else
                                            <span class="text-sm text-bgray-500 dark:text-bgray-300">No due date</span>
                                        @endif
                                    </td>

                                    <td class="border-b border-bgray-200 px-4 py-4 align-top dark:border-b-darkblack-400">
                                        <div class="flex items-center">
                                            @can('delete', $task)
                                                <x-delete-form :action="route('tasks.destroy', $task->id)" />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center">
                                        <div class="mx-auto max-w-md rounded-2xl border border-dashed border-bgray-300 bg-bgray-50 px-6 py-8 dark:border-darkblack-400 dark:bg-darkblack-500">
                                            <p class="text-base font-semibold text-bgray-900 dark:text-white">No tasks found</p>
                                            <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">
                                                There are no tasks to display for the current filters.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$tasks" :per-page="$perPage" />
        </section>
    </main>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Task" />
        <x-filters.multi-select name="current_assignee_id" label="Assignee" :options="$assignees" />
        <x-filters.multi-select name="status_id" label="Status" :options="$statuses" />
        <x-filters.multi-select name="priority" label="Priority" :options="$priorityOptions" />
        <x-filters.multi-select name="task_type" label="Type" :options="$typeOptions" />
        <x-filters.multi-select name="task_mode" label="Task Mode" :options="$modeOptions" />
    </x-filters.drawer>
@endsection
