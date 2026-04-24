@extends('layouts.master')

@push('styles')
    <style>
        .kanban-ghost {
            background: transparent !important;
            border: 1px dashed rgba(166, 167, 168, 0.8);
            /* border-radius: 12px; */
            box-sizing: border-box;

            /* IMPORTANT: remove inner content */
            color: transparent !important;
        }
        .kanban-ghost * {
            visibility: hidden !important;
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

            <button type="button" class="inline-flex items-center rounded-md border border-bgray-200 bg-white px-4 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-100 dark:hover:border-success-300 dark:hover:text-success-300" data-task-create-open data-task-create-request-type="self">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Request Task</span>
            </button>

            <x-filters.button />

            <div id="flow-switcher" class="inline-flex rounded-lg border overflow-hidden sm:ml-auto">
                <button data-flow="agile" class="flow-btn px-4 py-2 text-sm font-semibold transition bg-white text-gray-700 dark:bg-darkblack-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-darkblack-500">
                    Agile
                </button>
                <button data-flow="linear" class="flow-btn px-4 py-2 text-sm font-semibold transition bg-white text-gray-700 dark:bg-darkblack-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-darkblack-500">
                    Linear
                </button>
            </div>
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
            <div class="rounded-[14px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

                <!-- Horizontal Scroll Wrapper -->
                <div class="overflow-x-auto custom-scroll">

                    <!-- Board Container -->
                    <div id="kanban-container" class="flex gap-6 p-6 min-w-max h-[calc(100vh-220px)]">

                        <!-- LOOP YOUR STATUSES HERE -->
                        @include('tasks.kanban._board', ['boardStatuses' => $boardStatuses])

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

        @include('tasks.partials.create-modal')
    </main>

    <x-filters.drawer>
        <x-filters.input-search name="search" label="Task" />
        <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
        <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$projectMilestones" />
        <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
        <x-filters.multi-select name="current_assignee_id" label="Assignee" :options="$assignees" />
        <x-filters.multi-select name="status_id" label="Status" :options="$statuses" />
        <x-filters.multi-select name="priority" label="Priority" :options="$priorityOptions" />
        <x-filters.multi-select name="task_type_id" label="Type" :options="$typeOptions" />
        <x-filters.multi-select name="task_mode_id" label="Task Mode" :options="$modeOptions" />
    </x-filters.drawer>

    <script id="task-filter-dependencies" type="application/json">
        @json([
            'milestones' => $projectMilestones->values(),
            'sprints' => $projectSprints->values(),
        ])
    </script>

    <script id="task-create-dependencies" type="application/json">
        @json($taskCreateDependencies)
    </script>
@endsection

@push('scripts')
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/task-list-create.js')
    @vite('resources/js/modules/tasks/kanban-board.js')
@endpush
