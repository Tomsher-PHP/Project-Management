@extends('layouts.master')
@section('without-main', true)

@push('styles')
    @vite(['resources/css/modules/user-timeline.css', 'resources/css/modules/kanban.css'])
    <style>
        #kanban-container .kanban-board {
            height: auto;
        }
    </style>
@endpush

@section('page-content')
    <main class="w-full bg-[#fbfcff] px-3 pb-5 pt-[74px] dark:bg-darkblack-700 sm:px-5 xl:px-4" data-user-workspace data-task-create-root>
        <div class="space-y-2.5">
            <div class="hidden items-center justify-end" data-workspace-auto-refresh-indicator aria-live="polite">
                <div class="inline-flex items-center gap-2 rounded-full border border-[#d9e4f5] bg-white/95 px-3 py-1.5 text-xs font-semibold text-[#52607a] shadow-[var(--workspace-soft-shadow)] dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">
                    <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#9bb3d5] border-t-transparent dark:border-bgray-400 dark:border-t-transparent"></span>
                    <span>Refreshing workspace...</span>
                </div>
            </div>

            <!-- Daily Timeline Section -->
            <div id="workspace-daily-timeline-container"
                data-refresh-url="{{ route('workspace.daily-timeline.refresh') }}"
                data-selected-date="{{ $selectedDateValue }}"
                data-user-id="{{ $workspaceTimelineUserId }}">
                @include('workspace.partials.daily-timeline')
            </div>

            <!-- Task Board Section -->
            @include('workspace.partials.kanban-board')

            <x-filters.drawer>
                <div data-workspace-kanban-filters>
                    <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
                    <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$projectMilestones" />
                    <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
                    <x-filters.multi-select name="priority" label="Priority" :options="$priorityOptions" />
                </div>
            </x-filters.drawer>

            <!-- Task Create Modal -->
            @include('tasks.partials.create-modal')

            <script id="task-create-dependencies" type="application/json">
                @json($taskCreateDependencies)
            </script>

            <!-- Handoff Request Modal -->
            @include('tasks.partials.handoff-create-modal')

            <!-- Break Work Request Modal -->
            @include('workspace.partials._break-request-modal')

            <script id="task-filter-dependencies" type="application/json">
                @json([
                    'milestones' => $projectMilestones->values(),
                    'sprints' => $projectSprints->values(),
                ])
            </script>

        </div>
    </main>
@endsection

@push('scripts')
    <script>
        const initialFlowType = @json($selectedFlowType);
    </script>
    @vite('resources/js/modules/workspace/workspace-kanban-heights.js')
    @vite('resources/js/modules/workspace/user-timeline.js')
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/task-list-create.js')
    @vite('resources/js/modules/tasks/kanban-board.js')
    @vite('resources/js/modules/workspace/workspace-kanban-filters.js')
    @vite('resources/js/modules/workspace/workspace-user-selector.js')
    @vite('resources/js/modules/workspace/workspace-auto-refresh.js')
    @vite('resources/js/modules/workspace/break-work-request.js')
    @vite('resources/js/modules/tasks/handoff.js')
@endpush
