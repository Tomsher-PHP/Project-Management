@php
    $isDeletedProjectView = $project->trashed();
    $taskGroupsPageUrl = $isDeletedProjectView ? route('projects.restore.tasks.groups.index', ['id' => $project->id]) : route('projects.tasks.groups.index', $project);
    $taskCreateProjectModules = $projectMilestones->reject(fn($projectMilestone) => (bool) ($projectMilestone->is_backlog || $projectMilestone->is_system))->values();
    $taskCreateProjectSprints = $projectSprints->reject(fn($projectSprint) => (bool) ($projectSprint->is_backlog || $projectSprint->is_system))->values();
    $taskCreateDefaultSprintId = $taskCreateProjectSprints->first()?->id;
    $taskPlacementOptions = [
        'milestones' => $taskCreateProjectModules
            ->map(
                fn($projectMilestone) => [
                    'value' => (string) $projectMilestone->id,
                    'text' => $projectMilestone->name,
                ],
            )
            ->values(),
        'sprints' => $taskCreateProjectSprints
            ->map(
                fn($projectSprint) => [
                    'value' => (string) $projectSprint->id,
                    'text' => $projectSprint->name . ($projectSprint->projectMilestone?->name ? ' - ' . $projectSprint->projectMilestone->name : ''),
                    'project_milestone_id' => (string) ($projectSprint->project_milestone_id ?? ''),
                ],
            )
            ->values(),
    ];
    $taskMovePlacementOptions = [
        'milestones' => $projectMilestones
            ->map(
                fn($projectMilestone) => [
                    'value' => (string) $projectMilestone->id,
                    'text' => $projectMilestone->name,
                ],
            )
            ->values(),
        'sprints' => $projectSprints
            ->map(
                fn($projectSprint) => [
                    'value' => (string) $projectSprint->id,
                    'text' => $projectSprint->name . ($projectSprint->projectMilestone?->name ? ' - ' . $projectSprint->projectMilestone->name : ''),
                    'project_milestone_id' => (string) ($projectSprint->project_milestone_id ?? ''),
                ],
            )
            ->values(),
    ];
@endphp

<div class="space-y-3" data-project-tasks-root data-default-sprint-id="{{ $taskCreateDefaultSprintId ?? '' }}">
    <div class="flex flex-col gap-2 rounded-[20px] border border-bgray-200 bg-[linear-gradient(135deg,#f8fffb_0%,#ffffff_55%,#f4f8ff_100%)] px-4 py-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-success-400">Tasks</p>
            <h3 class="mt-0.5 text-[15px] font-bold text-bgray-900 dark:text-white">
                {{ $isLinearFlow ? 'Project task board' : 'Sprint task board' }}
            </h3>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span title="Task count" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                Total Tasks <span class="ml-1">{{ $totalTaskCount }}</span>
            </span>

            @unless ($isLinearFlow)
                <span title="Sprint count" class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">
                    Sprints <span class="ml-1">{{ $sprintCount }}</span>
                </span>
            @endunless

            @if (!$isDeletedProjectView)
                @can('task.create')
                    <button type="button" class="inline-flex items-center rounded-full bg-success-300 px-3 py-1 text-xs font-semibold text-white transition hover:bg-success-400" data-project-task-modal-open data-project-task-sprint-id="{{ $taskCreateDefaultSprintId ?? '' }}">
                        + Task
                    </button>
                @endcan
            @endif
        </div>
    </div>

    @if ($taskGroups->isEmpty())
        <div class="rounded-[24px] border border-dashed border-bgray-300 bg-bgray-50 px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">No tasks added yet</h4>
            <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">
                Tasks will appear here once they are linked to this project.
            </p>
        </div>
    @else
        <div class="space-y-4" data-project-task-group-list @unless ($isLinearFlow) data-load-url="{{ $taskGroupsPageUrl }}" data-current-page="{{ $taskGroupsPagination['page'] ?? 1 }}" data-next-page="{{ $taskGroupsPagination['next_page'] ?? '' }}" data-has-more-pages="{{ !empty($taskGroupsPagination['has_more_pages']) ? 'true' : 'false' }}" @endunless>
            @include('projects.partials.tasks.group-cards', [
                'project' => $project,
                'taskGroups' => $taskGroups,
                'initialGroupKey' => $initialGroupKey,
                'initialTasks' => $initialTasks,
                'initialTasksPagination' => $initialTasksPagination ?? ['page' => 1, 'next_page' => null, 'has_more_pages' => false],
            ])
        </div>

        @unless ($isLinearFlow)
            @if (!empty($taskGroupsPagination['has_more_pages']))
                <div class="flex justify-center" data-project-task-group-pagination-loading hidden>
                    <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">Loading more sprints...</span>
                </div>
                <div class="h-1 w-full" data-project-task-group-pagination-sentinel aria-hidden="true"></div>
            @endif
        @endunless
    @endif

    @if (!$isDeletedProjectView)
        @can('task.create')
            <div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-project-task-modal id="project_add_task_modal">
                <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-modal-close></div>

                <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
                    <div class="relative z-10 w-full max-w-lg transition-all duration-200" data-project-task-modal-panel>
                        <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                            <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                                <div>
                                    <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Add Task</h3>
                                </div>

                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-task-modal-close>
                                    ✕
                                </button>
                            </div>
                            <form class="space-y-4 overflow-y-auto px-5 py-5" data-project-task-form data-store-url="{{ route('projects.tasks.store', $project) }}" data-advanced="false" data-task-placement='@json($taskPlacementOptions)'>
                                <div class="grid gap-4 md:grid-cols-2">
                                    @unless ($isLinearFlow)
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Milestone</label>
                                            <select name="project_milestone_id" class="tom-select w-full" data-sort="0" data-project-task-module-select>
                                                <option value="">Select milestone or leave empty for backlog</option>
                                                @foreach ($taskCreateProjectModules as $projectMilestone)
                                                    <option value="{{ $projectMilestone->id }}">{{ $projectMilestone->name }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="project_milestone_id"></p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                                Sprint
                                                <span class="hidden" data-project-task-required-star="project_sprint_id"></span>
                                            </label>
                                            <select name="project_sprint_id" class="tom-select w-full" data-sort="0">
                                                <option value="">Select sprint or leave empty for backlog</option>
                                                @foreach ($taskCreateProjectSprints as $projectSprint)
                                                    <option value="{{ $projectSprint->id }}" data-module-id="{{ $projectSprint->project_milestone_id }}">
                                                        {{ $projectSprint->name }}@if ($projectSprint->projectMilestone?->name)
                                                            - {{ $projectSprint->projectMilestone->name }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300" data-project-task-placement-hint></p>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="project_sprint_id"></p>
                                        </div>
                                    @endunless

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Parent Task</label>
                                        <select name="parent_task_id" class="tom-select w-full" data-sort="0" data-parent-task-select data-parent-task-url="{{ route('projects.tasks.parent-options', $project) }}">
                                            <option value="">Select parent task</option>
                                        </select>
                                        <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="parent_task_id"></p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Name <x-red-star /></label>
                                        <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Enter task name">
                                        <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="name"></p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Assignee</label>
                                        <select name="current_assignee_id" class="tom-select w-full" data-sort="0">
                                            <option value="">Select assignee</option>
                                            @foreach ($assignableUsers as $assignableUser)
                                                <option value="{{ $assignableUser->id }}">{{ $assignableUser->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="current_assignee_id"></p>
                                    </div>

                                    <div class="{{ $isLinearFlow ? 'md:col-span-2' : '' }}">
                                        <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="$defaultTaskEstimateMinutes ?? 0" :show-label="false" />
                                        <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="estimated_time_minutes"></p>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40" data-project-task-advanced-section hidden>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Description</label>
                                            <input type="hidden" name="description" id="project_task_description_input">
                                            <div class="custom-quill-wrapper rounded-lg border border-gray-300 dark:border-darkblack-400 overflow-hidden">
                                                <div id="project_task_description_editor" class="h-48 bg-white dark:bg-darkblack-500 dark:text-white"></div>
                                            </div>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="description"></p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Task Type</label>
                                            <div class="flex items-center gap-2">
                                                <select name="task_type_id" class="tom-select-no-search w-full">
                                                    @foreach ($taskTypeOptions as $option)
                                                        <option value="{{ $option->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $option->name }}</option>
                                                    @endforeach
                                                </select>
                                                @can('task_settings.create')
                                                    <button type="button" data-target="#task-create-type-modal" data-select-target="task_type_id" data-module="Task Type" data-url="{{ route('settings.task-types.store') }}" data-method="POST" data-sort_order="{{ $nextTaskTypeSortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Task Type" aria-label="Add Task Type">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                    </button>
                                                @endcan
                                            </div>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="task_type_id"></p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Task Mode</label>
                                            <div class="flex items-center gap-2">
                                                <select name="task_mode_id" class="tom-select-no-search w-full">
                                                    @foreach ($taskModeOptions as $option)
                                                        <option value="{{ $option->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $option->name }}</option>
                                                    @endforeach
                                                </select>
                                                @can('task_settings.create')
                                                    <button type="button" data-target="#task-create-mode-modal" data-select-target="task_mode_id" data-module="Task Mode" data-url="{{ route('settings.task-modes.store') }}" data-method="POST" data-sort_order="{{ $nextTaskModeSortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Task Mode" aria-label="Add Task Mode">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                    </button>
                                                @endcan
                                            </div>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="task_mode_id"></p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Priority</label>
                                            <select name="priority" class="tom-select-no-search w-full">
                                                @foreach ($taskPriorityOptions as $option)
                                                    <option value="{{ $option['value'] }}" {{ $option['value'] === $defaultTaskPriority ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="priority"></p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Due Date</label>
                                            <input type="text" name="due_date_time" value="" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-enable-time="true" data-time-24hr="true" data-format="Y-m-d H:i" placeholder="Choose a due date and time" autocomplete="off">
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="due_date_time"></p>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Tags</label>
                                            <select name="tag_ids[]" class="tom-select-tags w-full" multiple>
                                                @foreach ($tagOptions as $tagOption)
                                                    <option value="{{ $tagOption->id }}">{{ $tagOption->name }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="tag_ids"></p>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="inline-flex items-center gap-3 rounded-xl border border-bgray-200 bg-white px-4 py-3 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                                                <input type="hidden" name="is_billable" value="0">
                                                <input type="checkbox" name="is_billable" value="1" class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-success-300" {{ $project->default_billable ? 'checked' : '' }}>
                                                <span>Billable task</span>
                                            </label>
                                            <p class="mt-1 hidden text-xs text-red-500" data-project-task-error="is_billable"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-1">
                                    <button type="button" class="inline-flex items-center rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-medium text-success-400 transition hover:border-success-300 hover:bg-success-100 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-300" data-project-task-advanced-toggle>
                                        Show Advanced
                                    </button>

                                    <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300" data-project-task-modal-close>
                                        Cancel
                                    </button>

                                    <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-project-task-submit>
                                        Save Task
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    @endif

    @if (!$isDeletedProjectView && !$isLinearFlow && auth()->user()?->can('task.move'))
        <div class="modal fixed inset-0 z-[75] hidden items-center justify-center overflow-y-auto" data-project-task-move-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-move-close></div>

            <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
                <div class="relative z-10 w-full max-w-md transition-all duration-200">
                    <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                        <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                            <div>
                                <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Move Task</h3>
                                <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                                    Move <span class="font-medium text-bgray-700 dark:text-bgray-300" data-project-task-move-task-name>this task</span>
                                    to another sprint.
                                </p>
                            </div>

                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-task-move-close>
                                ✕
                            </button>
                        </div>

                        <form class="space-y-4 overflow-y-auto px-5 py-5" data-project-task-move-form data-task-move-placement='@json($taskMovePlacementOptions)'>
                            <input type="hidden" name="_method" value="PATCH">

                            <div class="rounded-2xl border border-bgray-200 bg-bgray-50/70 px-4 py-3 text-sm text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500/40 dark:text-bgray-300">
                                <p>
                                    Current sprint:
                                    <span class="font-medium text-bgray-900 dark:text-white" data-project-task-move-current-sprint>--</span>
                                </p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Milestone</label>
                                <select name="project_milestone_id" class="tom-select w-full" data-sort="0" data-project-task-move-module-select>
                                    <option value="">All milestones</option>
                                    @foreach ($projectMilestones as $projectMilestone)
                                        <option value="{{ $projectMilestone->id }}">{{ $projectMilestone->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">
                                    Optional. Choose a milestone to narrow the sprint list.
                                </p>
                                <p class="mt-1 hidden text-xs text-red-500" data-project-task-move-error="project_milestone_id"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                    Sprint
                                    <x-red-star />
                                </label>
                                <select name="project_sprint_id" class="tom-select w-full" data-sort="0">
                                    <option value="">Select sprint</option>
                                    @foreach ($projectSprints as $projectSprint)
                                        <option value="{{ $projectSprint->id }}" data-module-id="{{ $projectSprint->project_milestone_id }}">
                                            {{ $projectSprint->name }}@if ($projectSprint->projectMilestone?->name)
                                                - {{ $projectSprint->projectMilestone->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-project-task-move-error="project_sprint_id"></p>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-1">
                                <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300" data-project-task-move-close>
                                    Cancel
                                </button>

                                <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-project-task-move-submit>
                                    Move
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fixed inset-0 z-[80] hidden overflow-y-auto" data-project-task-detail-modal>
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-task-detail-close></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-7xl" data-project-task-detail-content></div>
        </div>
    </div>

    @can('task_settings.create')
        <style>
            #task-create-type-modal .modal-overlay,
            #task-create-mode-modal .modal-overlay {
                z-index: 0;
            }

            #task-create-type-modal .modal-content,
            #task-create-mode-modal .modal-content {
                z-index: 1;
            }
        </style>

        <x-form-modal modal-id="task-create-type-modal" module="Task Type" form-id="taskCreateTypeInlineForm" action="{{ route('settings.task-types.store') }}" button="Create Task Type" modal-z-index="10050">
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-source required>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Code <x-red-star /></label>
                <input type="text" name="code" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-target required>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Color</label>
                <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
                <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
            </div>

            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                    <span>Is Default</span>
                    <span class="group relative inline-flex cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                            The default option is preselected when creating a new task.
                        </span>
                    </span>
                </span>
            </label>
        </x-form-modal>

        <x-form-modal modal-id="task-create-mode-modal" module="Task Mode" form-id="taskCreateModeInlineForm" action="{{ route('settings.task-modes.store') }}" button="Create Task Mode" modal-z-index="10050">
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-source required>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Code <x-red-star /></label>
                <input type="text" name="code" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-target required>
            </div>

            <div>
                <div class="mb-2.5 flex items-center justify-between gap-3">
                    <label class="block text-left text-sm text-bgray-700 dark:text-bgray-50">Description</label>
                    <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-modal-description-count>0</span>/250</span>
                </div>
                <textarea name="description" rows="3" maxlength="250" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Color</label>
                <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sort Order <x-red-star /></label>
                <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
            </div>

            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                    <span>Is Default</span>
                    <span class="group relative inline-flex cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                            The default option is preselected when creating a new task.
                        </span>
                    </span>
                </span>
            </label>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="is_rework" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                        <span>Is Rework?</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Mark this mode when the work represents revisions or corrections to previous work.
                            </span>
                        </span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="is_productive" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                        <span>Is Productive?</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-60 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Enable this when time logged under this mode should count as productive work.
                            </span>
                        </span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="customer_request" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-bgray-50">
                        <span>Customer Request?</span>
                        <span class="group relative inline-flex cursor-help">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-bgray-400 transition group-hover:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-64 rounded-lg bg-bgray-600 px-3 py-2.5 text-sm font-medium leading-6 text-white shadow-lg group-hover:block">
                                Enable this when the mode is used for work introduced through a customer or change request.
                            </span>
                        </span>
                    </span>
                </label>
            </div>
        </x-form-modal>
    @endcan
</div>
