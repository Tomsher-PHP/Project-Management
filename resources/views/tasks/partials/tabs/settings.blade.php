@php
    $selectedTagIds = $task->tags->pluck('id')->map(fn($id) => (string) $id)->all();
@endphp

<form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-8" data-task-settings-form data-can-edit="{{ $canEditTask ? 'true' : 'false' }}" data-parent-task-url="{{ route('tasks.parent-options', $task) }}">
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
                        Sprint
                    </label>
                    <select name="project_sprint_id" class="tom-select w-full" data-sort="0" data-task-settings-sprint-select>
                        <option value="">Select sprint</option>
                        @foreach ($projectSprints as $projectSprint)
                            <option value="{{ $projectSprint->id }}" data-module-name="{{ $projectSprint->projectModule?->name ?? '--' }}" {{ (int) $task->project_sprint_id === (int) $projectSprint->id ? 'selected' : '' }}>
                                {{ $projectSprint->name }}@if ($projectSprint->projectModule?->name)
                                    - {{ $projectSprint->projectModule->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
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
                <select name="task_type_id" class="tom-select-no-search w-full">
                    @foreach ($taskTypeOptions as $option)
                        <option value="{{ $option['value'] }}" {{ (string) $task->task_type_id === (string) $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="task_type_id"></p>
            </div>

            <div>
                <label class="mb-2.5 block text-sm font-medium text-bgray-600 dark:text-bgray-50">
                    Task Mode
                </label>
                <select name="task_mode_id" class="tom-select-no-search w-full">
                    @foreach ($taskModeOptions as $option)
                        <option value="{{ $option['value'] }}" {{ (string) $task->task_mode_id === (string) $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
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
                <input type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}" class="datepicker w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <p class="mt-1 hidden text-sm text-error-300" data-task-settings-error="due_date"></p>
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
