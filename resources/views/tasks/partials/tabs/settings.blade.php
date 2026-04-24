@php
    $selectedTagIds = $task->tags->pluck('id')->map(fn($id) => (string) $id)->all();
    $taskPlacementOptions = [
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

<form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-8" data-task-settings-form data-can-edit="{{ $canEditTask ? 'true' : 'false' }}" data-parent-task-url="{{ route('tasks.parent-options', $task) }}" data-task-placement='@json($taskPlacementOptions)'>
    @csrf
    @method('PUT')

    <div class="rounded-2xl border border-bgray-200 bg-white p-6 dark:border-darkblack-400 dark:bg-darkblack-600">
        @unless ($canEditTask)
            <div class="mb-5 rounded-xl border border-bgray-200 bg-bgray-50 px-4 py-3 text-sm text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                You have view-only access to this task.
            </div>
        @endunless

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <div class="md:col-span-2 xl:col-span-2">
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Task Name <x-red-star />
                </label>
                <input type="text" name="name" value="{{ $task->name }}" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="name"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Status
                </label>
                <select name="status_id" class="tom-select-no-search w-full">
                    <option value="">Select status</option>
                    @foreach ($taskStatuses as $status)
                        <option value="{{ $status->id }}" {{ (int) $task->status_id === (int) $status->id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="status_id"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Assignee
                </label>
                <select name="current_assignee_id" class="tom-select w-full" data-sort="0">
                    <option value="">Select assignee</option>
                    @foreach ($assignableUsers as $assignableUser)
                        <option value="{{ $assignableUser->id }}" {{ (int) $task->current_assignee_id === (int) $assignableUser->id ? 'selected' : '' }}>
                            {{ $assignableUser->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="current_assignee_id"></p>
            </div>

            @unless ($isLinearFlow)
                <div>
                    <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                        Milestone
                    </label>
                    <select name="project_milestone_id" class="tom-select w-full" data-sort="0" data-task-settings-module-select>
                        <option value="">Select milestone or leave empty for backlog</option>
                        @foreach ($projectMilestones as $projectMilestone)
                            <option value="{{ $projectMilestone->id }}" {{ (int) $task->project_milestone_id === (int) $projectMilestone->id ? 'selected' : '' }}>
                                {{ $projectMilestone->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="project_milestone_id"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                        Sprint
                    </label>
                    <select name="project_sprint_id" class="tom-select w-full" data-sort="0" data-task-settings-sprint-select>
                        <option value="">Select sprint or leave empty for backlog</option>
                        @foreach ($projectSprints as $projectSprint)
                            <option value="{{ $projectSprint->id }}" data-module-id="{{ $projectSprint->project_milestone_id }}" data-module-name="{{ $projectSprint->projectMilestone?->name ?? '--' }}" {{ (int) $task->project_sprint_id === (int) $projectSprint->id ? 'selected' : '' }}>
                                {{ $projectSprint->name }}@if ($projectSprint->projectMilestone?->name)
                                    - {{ $projectSprint->projectMilestone->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300" data-task-settings-placement-hint></p>
                    <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="project_sprint_id"></p>
                </div>
            @endunless

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Parent Task
                </label>
                <select name="parent_task_id" class="tom-select w-full" data-sort="0" data-task-settings-parent-task-select>
                    <option value="">Select parent task</option>
                    @foreach ($parentTaskOptions as $parentTaskOption)
                        <option value="{{ $parentTaskOption->id }}" {{ (int) $task->parent_task_id === (int) $parentTaskOption->id ? 'selected' : '' }}>
                            {{ $parentTaskOption->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="parent_task_id"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Task Type
                </label>

                <div class="flex items-center gap-2">
                    <select name="task_type_id" class="tom-select-no-search w-full">
                        @foreach ($taskTypeOptions as $option)
                            <option value="{{ $option->id }}" {{ (string) $task->task_type_id === (string) $option->id ? 'selected' : '' }}>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>

                    @if ($canEditTask)
                        @can('task_settings.create')
                            <button type="button" data-target="#task-type-modal" data-select-target="task_type_id" data-module="Task Type" data-url="{{ route('settings.task-types.store') }}" data-method="POST" data-sort_order="{{ $nextTaskTypeSortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Task Type" aria-label="Add Task Type">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endcan
                    @endif
                </div>

                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="task_type_id"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Task Mode
                </label>

                <div class="flex items-center gap-2">
                    <select name="task_mode_id" class="tom-select-no-search w-full">
                        @foreach ($taskModeOptions as $option)
                            <option value="{{ $option->id }}" {{ (string) $task->task_mode_id === (string) $option->id ? 'selected' : '' }}>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>

                    @if ($canEditTask)
                        @can('task_settings.create')
                            <button type="button" data-target="#task-mode-modal" data-select-target="task_mode_id" data-module="Task Mode" data-url="{{ route('settings.task-modes.store') }}" data-method="POST" data-sort_order="{{ $nextTaskModeSortOrder ?? 1 }}" class="modal-open inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-lg border border-success-200 bg-success-50 text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-100" title="Add Task Mode" aria-label="Add Task Mode">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endcan
                    @endif
                </div>

                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="task_mode_id"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Priority
                </label>
                <select name="priority" class="tom-select-no-search w-full">
                    @foreach ($taskPriorityOptions as $option)
                        <option value="{{ $option['value'] }}" {{ $task->priority === $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="priority"></p>
            </div>

            <div>
                <x-forms.estimated-time-input name="estimated_time_minutes" :total-minutes="$task->estimated_time_seconds ? (int) round($task->estimated_time_seconds / 60) : 0" :show-label="false" />
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="estimated_time_minutes"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Due Date
                </label>
                <input type="text" name="due_date_time" value="{{ $task->due_date_time?->format('Y-m-d H:i') }}" class="datepicker w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-enable-time="true" data-time-24hr="true" data-format="Y-m-d H:i" placeholder="Select a date and time" autocomplete="off">
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="due_date_time"></p>
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Description
                </label>
                <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-300 bg-white p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ $task->description }}</textarea>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="description"></p>
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Tags
                </label>
                <select name="tag_ids[]" class="tom-select-tags w-full" multiple>
                    @foreach ($tagOptions as $tagOption)
                        <option value="{{ $tagOption->id }}" {{ in_array((string) $tagOption->id, $selectedTagIds, true) ? 'selected' : '' }}>
                            {{ $tagOption->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="tag_ids"></p>
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="inline-flex items-center gap-3 rounded-xl border border-bgray-200 bg-bgray-50 px-4 py-3 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                    <input type="hidden" name="is_billable" value="0">
                    <input type="checkbox" name="is_billable" value="1" class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-success-300" {{ $task->is_billable ? 'checked' : '' }}>
                    <span>Billable task</span>
                </label>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="is_billable"></p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-bgray-200 pt-5 dark:border-darkblack-400">
            @if ($canEditTask)
                <button type="submit" class="rounded-lg bg-success-300 px-6 py-3 text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60" data-task-settings-submit disabled>
                    Update Task
                </button>
            @else
                <button type="button" class="rounded-lg border border-bgray-300 bg-white px-6 py-3 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100" disabled>
                    View Only
                </button>
            @endif
        </div>
    </div>
</form>

@if ($canEditTask)
    @can('task_settings.create')
        <x-form-modal modalId="task-type-modal" module="Task Type" formId="taskTypeInlineForm" action="{{ route('settings.task-types.store') }}" button="Create Task Type">
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-source required>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Code <x-red-star /></label>
                <input type="text" name="code" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-target required>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
                <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
                <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
            </div>

            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="text-sm font-semibold text-gray-700 dark:text-bgray-50">Is Default</span>
            </label>
        </x-form-modal>

        <x-form-modal modalId="task-mode-modal" module="Task Mode" formId="taskModeInlineForm" action="{{ route('settings.task-modes.store') }}" button="Create Task Mode">
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Name <x-red-star /></label>
                <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-source required>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Code <x-red-star /></label>
                <input type="text" name="code" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-auto-code-target required>
            </div>

            <div>
                <div class="mb-2.5 flex items-center justify-between gap-3">
                    <label class="block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
                    <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-modal-description-count>0</span>/250</span>
                </div>
                <textarea name="description" rows="3" maxlength="250" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"></textarea>
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Color</label>
                <input type="color" name="color" class="h-12 w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500">
            </div>

            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Sort Order <x-red-star /></label>
                <input type="number" name="sort_order" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" required>
            </div>

            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="is_default" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="text-sm font-semibold text-gray-700 dark:text-bgray-50">Is Default</span>
            </label>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="is_rework" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="text-sm font-semibold text-gray-700 dark:text-bgray-50">Is Rework?</span>
                </label>

                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="is_productive" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="text-sm font-semibold text-gray-700 dark:text-bgray-50">Is Productive?</span>
                </label>

                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="track_performance" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="text-sm font-semibold text-gray-700 dark:text-bgray-50">Track Performance?</span>
                </label>

                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="customer_request" value="1" class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <span class="text-sm font-semibold text-gray-700 dark:text-bgray-50">Customer Request?</span>
                </label>
            </div>
        </x-form-modal>
    @endcan
@endif
