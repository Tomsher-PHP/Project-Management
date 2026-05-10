@extends('layouts.master')

@push('styles')
    @vite(['resources/css/modules/user-timeline.css', 'resources/css/modules/kanban.css'])
@endpush

@section('page-content')
    <main class="w-full bg-[#fbfcff] px-3 pb-5 pt-[74px] dark:bg-darkblack-700 sm:px-5 xl:px-4" data-user-workspace>
        <div class="space-y-2.5">
            <div class="hidden items-center justify-end" data-workspace-auto-refresh-indicator aria-live="polite">
                <div class="inline-flex items-center gap-2 rounded-full border border-[#d9e4f5] bg-white/95 px-3 py-1.5 text-xs font-semibold text-[#52607a] shadow-[var(--workspace-soft-shadow)] dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">
                    <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#9bb3d5] border-t-transparent dark:border-bgray-400 dark:border-t-transparent"></span>
                    <span>Refreshing workspace...</span>
                </div>
            </div>

            @include('workspace.partials.daily-timeline')
            @php
                $boardTaskTotal = collect($tasksByStatus ?? [])->sum(fn($column) => (int) ($column['total'] ?? 0));
                $boardStatusTotal = $boardStatuses->count();
                $priorityOptions = collect($priorities ?? [])->map(
                    fn($config, $key) => (object) [
                        'id' => $key,
                        'name' => $config['label'],
                    ],
                );
                $workspaceFilterCount = collect([filled(request('project_id')) ? 'project_id' : null, filled(request('project_milestone_id')) ? 'project_milestone_id' : null, filled(request('project_sprint_id')) ? 'project_sprint_id' : null, filled(request('priority')) ? 'priority' : null])
                    ->filter()
                    ->count();
                $workspaceHasActiveFilters = $workspaceFilterCount > 0;
            @endphp

            <section class="space-y-6" data-project-tasks-root data-project-task-response-mode="reload">
                <div class="overflow-hidden rounded-[14px] border border-[var(--workspace-border)] bg-white shadow-[var(--workspace-panel-shadow)] dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="border-b border-[#edf1f7] bg-white px-4 py-3 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="inline-flex h-6 w-6 items-center justify-center text-[#111653] dark:text-bgray-50">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4Zm9 0h7v4h-7V4ZM4 13h7v7H4v-7Zm9-3h7v10h-7V10Z" />
                                    </svg>
                                </span>
                                <h3 class="text-[17px] font-extrabold tracking-normal text-bgray-800 dark:text-bgray-50">Work Board</h3>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <label class="relative min-w-[200px] flex-1 sm:min-w-[240px] lg:max-w-[300px]">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 inline-flex items-center text-bgray-500 dark:text-bgray-300">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.412 14.412 3.176 3.176" />
                                            <circle cx="8.75" cy="8.75" r="5.75" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <input type="search" value="{{ request('search', '') }}" placeholder="Search tasks by name" class="h-10 w-full rounded-lg border border-bgray-400 bg-white pl-9 pr-3 text-sm font-semibold text-[#111653] shadow-[var(--workspace-soft-shadow)] outline-none transition placeholder:text-bgray-400 focus:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:placeholder:text-bgray-400" data-workspace-kanban-search autocomplete="off" />
                                </label>

                                <button type="button"
                                    class="{{ $workspaceHasActiveFilters ? 'border-success-200 bg-success-50/80 text-success-400 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300' : 'border-bgray-400 bg-white text-[#111653] dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400' }} inline-flex h-10 w-10 items-center justify-center rounded-lg border shadow-[var(--workspace-soft-shadow)] transition hover:border-[#d7e3f6] hover:bg-[#fbfdff] hover:text-success-400 dark:hover:text-success-300"
                                    data-workspace-kanban-filter-button aria-label="Open filters" title="Filter tasks">
                                    <span class="relative inline-flex">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8v5.25a1.5 1.5 0 0 1-.879 1.365l-3 1.364A.75.75 0 0 1 9 19.796V12.5l-6-8Z" />
                                        </svg>

                                        <span class="@if (!$workspaceHasActiveFilters) hidden @endif absolute -right-2 -top-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-success-300 px-1.5 text-[11px] font-bold text-white" data-workspace-kanban-filter-count>
                                            {{ $workspaceFilterCount }}
                                        </span>
                                    </span>
                                </button>

                                @include('tasks.kanban._sort_dropdown')
                            </div>
                        </div>
                    </div>

                    <div class="custom-scroll overflow-x-auto bg-white dark:bg-darkblack-700">
                        <div id="kanban-container" class="flex h-[calc(100vh-620px)] min-h-[410px] min-w-max gap-3.5 p-3.5" data-kanban-url="{{ route('user.workspace') }}">
                            @include('tasks.kanban._board', ['boardStatuses' => $boardStatuses, 'priorities' => $priorities ?? []])
                        </div>
                    </div>
                </div>

                <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-project-task-detail-modal>
                    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-detail-close></div>

                    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
                        <div class="relative z-10 w-full max-w-7xl" data-project-task-detail-content></div>
                    </div>
                </div>
            </section>

            <x-filters.drawer>
                <div data-workspace-kanban-filters>
                    <x-filters.multi-select name="project_id" label="Project" :options="$projects" />
                    <x-filters.multi-select name="project_milestone_id" label="Milestone" :options="$projectMilestones" />
                    <x-filters.multi-select name="project_sprint_id" label="Sprint" :options="$projectSprints" />
                    <x-filters.multi-select name="priority" label="Priority" :options="$priorityOptions" />
                </div>
            </x-filters.drawer>

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
    @vite('resources/js/modules/workspace/user-timeline.js')
    @vite('resources/js/modules/projects/project-tasks.js')
    @vite('resources/js/modules/tasks/kanban-board.js')
    @vite('resources/js/modules/workspace/workspace-kanban-filters.js')
    @vite('resources/js/modules/workspace/workspace-auto-refresh.js')
@endpush
