<div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-task-create-modal id="task_create_modal">
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-task-create-close></div>

    <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
        <div class="relative z-10 w-full max-w-lg transition-all duration-200" data-task-create-modal-panel>
            <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <div>
                        <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">Add Task</h3>
                    </div>

                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-task-create-close>
                        ✕
                    </button>
                </div>

                <form class="space-y-4 overflow-y-auto px-5 py-5" data-task-create-form data-store-url="{{ route('tasks.store') }}" data-advanced="false">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Project <x-red-star /></label>
                            <select name="project_id" class="tom-select w-full" data-sort="0">
                                <option value="">Select project</option>
                                @foreach ($taskCreateProjects as $projectOption)
                                    <option value="{{ $projectOption->id }}" data-data='@json(['subtype' => $projectOption->project_code ?: '--'])' {{ (string) ($taskCreateDependencies['defaults']['project_id'] ?? '') === (string) $projectOption->id ? 'selected' : '' }}>
                                        {{ $projectOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="project_id"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Module</label>
                            <select name="project_module_id" class="tom-select w-full" data-sort="0">
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="project_module_id"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">
                                Sprint
                                <span class="hidden" data-task-create-required-star="project_sprint_id">
                                    <x-red-star />
                                </span>
                            </label>
                            <select name="project_sprint_id" class="tom-select w-full" data-sort="0">
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300" data-task-create-placement-hint></p>
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="project_sprint_id"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Parent Task</label>
                            <select name="parent_task_id" class="tom-select w-full" data-sort="0" data-task-create-parent-select>
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="parent_task_id"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Name <x-red-star /></label>
                            <input type="text" name="name" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Enter task name">
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="name"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Assignee</label>
                            <select name="current_assignee_id" class="tom-select w-full" data-sort="0">
                                <option value="">Select project first</option>
                            </select>
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="current_assignee_id"></p>
                        </div>

                        <div class="md:col-span-2">
                            <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="0" help-text="Enter time naturally. We’ll convert it automatically for calculation." :show-label="false" />
                            <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="estimated_time_minutes"></p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40" data-task-create-advanced-section hidden>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Status</label>
                                <select name="status_id" class="tom-select-no-search w-full">
                                    <option value="">Select project first</option>
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="status_id"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Description</label>
                                <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add task details"></textarea>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="description"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Task Type</label>
                                <select name="task_type_id" class="tom-select-no-search w-full">
                                    @foreach ($taskTypeOptions as $option)
                                        <option value="{{ $option->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="task_type_id"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Task Mode</label>
                                <select name="task_mode_id" class="tom-select-no-search w-full">
                                    @foreach ($taskModeOptions as $option)
                                        <option value="{{ $option->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="task_mode_id"></p>
                            </div>
                            
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Priority</label>
                                <select name="priority" class="tom-select-no-search w-full">
                                    @foreach ($taskPriorityOptions as $option)
                                        <option value="{{ $option->value }}" {{ $option->value === $defaultTaskPriority ? 'selected' : '' }}>{{ $option->label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="priority"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Due Date</label>
                                <input type="text" name="due_date" value="" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-open-to-date="" placeholder="Choose a due date" autocomplete="off">
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="due_date"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-200">Tags</label>
                                <select name="tag_ids[]" class="tom-select-tags w-full" multiple>
                                    @foreach ($tagOptions as $tagOption)
                                        <option value="{{ $tagOption->id }}">{{ $tagOption->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="tag_ids"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 rounded-xl border border-bgray-200 bg-white px-4 py-3 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                                    <input type="hidden" name="is_billable" value="0">
                                    <input type="checkbox" name="is_billable" value="1" class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-success-300">
                                    <span>Billable task</span>
                                </label>
                                <p class="mt-1 hidden text-xs text-red-500" data-task-create-error="is_billable"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" class="inline-flex items-center rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-medium text-success-400 transition hover:border-success-300 hover:bg-success-100 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-300" data-task-create-advanced-toggle>
                            Show Advanced
                        </button>

                        <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:border-darkblack-300" data-task-create-close>
                            Cancel
                        </button>

                        <button type="submit" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-task-create-submit>
                            Save Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
