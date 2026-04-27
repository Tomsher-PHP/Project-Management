@extends('layouts.master')

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
            <div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                            <tr>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="name" label="Task" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="project.name" label="Project" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="currentAssignee.name" label="Assignee" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Type</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Mode</span>
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="estimated_time_seconds" label="Estimate Time" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <x-sorting.sortable-column column="due_date_time" label="Due Date" />
                                </th>
                                <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-b-darkblack-400">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-darkblack-600" data-task-subtasks-root>
                            @forelse ($tasks as $task)
                                @include('tasks.partials.table-row', ['task' => $task])

                                @include('tasks.partials.subtask-rows', [
                                    'tasks' => $task->childTasks,
                                    'parentTaskId' => $task->id,
                                    'depth' => 1,
                                ])
                            @empty
                                <x-table-no-data col-span="9" message="No tasks found." sub-message="There are no tasks to display for the current filters." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-pagination :paginator="$tasks" :per-page="$perPage" />
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
@endpush
