@extends('layouts.master')

@push('styles')
    <style>
        .kanban-ghost {
            opacity: 0.4;
        }

        .kanban-chosen {
            transform: scale(1.02);
        }

        .kanban-drag {
            transform: rotate(2deg);
        }
    </style>
@endpush

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-task-create-root data-project-tasks-root data-project-task-response-mode="reload">
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can('task.create')
                <button type="button" class="inline-flex items-center rounded-md bg-success-300 px-4 py-1.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400" data-task-create-open>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Task</span>
                </button>
            @endcan

            <x-filters.button />
            <x-project-flow-indicator class="sm:ml-auto" />
        </div>

        @php
            $typeOptions = collect($taskTypeOptions)->map(
                fn($type) => (object) [
                    'id' => $type->id,
                    'name' => $type->name,
                ],
            );

            $modeOptions = collect($taskModeOptions)->map(
                fn($mode) => (object) [
                    'id' => $mode->id,
                    'name' => $mode->name,
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
            <div class="rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

                <!-- Horizontal Scroll Wrapper -->
                <div class="overflow-x-auto custom-scroll">

                    <!-- Board Container -->
                    <div class="flex gap-6 p-6 min-w-max h-[calc(100vh-220px)]">

                        <!-- LOOP YOUR STATUSES HERE -->
                        @foreach ($boardStatuses as $status)
                            <div class="flex flex-col flex-shrink-0 w-80 border rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-darkblack-500 overflow-hidden">

                                <!-- Column Header -->
                                <h5 class="uppercase mb-0 w-full rounded-t-md px-4 py-3 text-white" style="background-color: {{ $status->color }};">
                                    {{ $status->name }}
                                    ({{ $tasksByStatus[$status->id]->count() ?? 0 }})
                                </h5>

                                <!-- Column Body -->
                                <div class="flex flex-col gap-4 kanban-board overflow-y-auto overflow-x-hidden h-full px-4 pb-4 pt-4" data-status-id="{{ $status->id }}">

                                    @foreach ($tasksByStatus[$status->id] ?? [] as $task)
                                        @include('tasks.kanban._card', ['task' => $task])
                                    @endforeach

                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </section>

        <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-project-task-detail-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-detail-close></div>

            <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-7xl" data-project-task-detail-content></div>
            </div>
        </div>

        @can('task.create')
            @include('tasks.partials.create-modal')
        @endcan
    </main>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Task" />
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
        <x-filters.multi-select name="project_module_id" label="Module" :options="$projectModules" />
        <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
        <x-filters.multi-select name="current_assignee_id" label="Assignee" :options="$assignees" />
        <x-filters.multi-select name="status_id" label="Status" :options="$statuses" />
        <x-filters.multi-select name="priority" label="Priority" :options="$priorityOptions" />
        <x-filters.multi-select name="task_type_id" label="Type" :options="$typeOptions" />
        <x-filters.multi-select name="task_mode_id" label="Task Mode" :options="$modeOptions" />
    </x-filters.drawer>

    <script id="task-filter-dependencies" type="application/json">
        @json([
            'modules' => $projectModules->values(),
            'sprints' => $projectSprints->values(),
        ])
    </script>

    @can('task.create')
        <script id="task-create-dependencies" type="application/json">
            @json($taskCreateDependencies)
        </script>
    @endcan
@endsection

@push('scripts')
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/task-list-subtasks.js')
    @can('task.create')
        @vite('resources/js/modules/task-list-create.js')
    @endcan
    @vite('resources/js/modules/tasks/kanban-board.js')
@endpush
