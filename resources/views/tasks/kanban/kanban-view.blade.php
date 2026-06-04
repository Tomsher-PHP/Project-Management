@extends('layouts.master')
@section('without-main', true)

@push('styles')
    @vite('resources/css/modules/kanban.css')
@endpush

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" data-task-create-root data-project-tasks-root data-project-task-response-mode="reload">
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @can('task.create')
                <x-button.create-button type="button" data-task-create-open title="Create new task" label="Task" />
            @endcan

            <x-button.create-button type="button" data-task-create-open data-task-create-request-type="self" title="Create new request task for your self" label="Request" />

            <x-filters.button />

            @include('tasks.kanban._sort_dropdown')

            @include('tasks.kanban._project_flow_btn')
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
                    <div id="kanban-container" class="flex gap-6 p-6 min-w-max h-[calc(100vh-220px)]" data-kanban-url="{{ route('tasks.kanbanMode') }}">

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
    <script>
        const initialFlowType = @json($selectedFlowType);
    </script>
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/task-list-create.js')
    @vite('resources/js/modules/tasks/kanban-board.js')
@endpush
