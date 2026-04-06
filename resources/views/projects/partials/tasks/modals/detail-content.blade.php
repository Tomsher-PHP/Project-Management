@php
    $selectedTagIds = $task->tags->pluck('id')->map(fn ($id) => (string) $id)->all();
    $textInputClasses = $canEditTask
        ? 'w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white'
        : 'w-full rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 text-sm text-bgray-600 focus:border-bgray-200 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200';
    $textareaClasses = $canEditTask
        ? 'w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white'
        : 'w-full rounded-lg border border-bgray-200 bg-bgray-50 p-3 text-sm text-bgray-600 focus:border-bgray-200 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200';
@endphp

<div class="overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-darkblack-600">
    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
        <div>
            <h3 class="text-xl font-semibold text-bgray-900 dark:text-white">Manage Task</h3>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                Review and update the working details for this task.
            </p>
        </div>

        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-task-detail-close>
            ✕
        </button>
    </div>

    <form class="flex max-h-[82vh] flex-col xl:grid xl:h-[82vh] xl:max-h-[82vh] xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]" data-project-task-detail-form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="min-h-0 overflow-y-auto border-b border-bgray-200 px-6 py-6 dark:border-darkblack-400 xl:border-b-0 xl:border-r sm:px-7">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Task Name <x-red-star /></label>
                    <input type="text" name="title" value="{{ $task->title }}" class="{{ $textInputClasses }}" @disabled(! $canEditTask)>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="title"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Task Code</label>
                    <input type="text" value="{{ $task->code }}" class="w-full rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 text-sm text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200" readonly>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Description</label>
                    <textarea name="description" rows="4" class="{{ $textareaClasses }}" @disabled(! $canEditTask)>{{ $task->description }}</textarea>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="description"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Status</label>
                    <select name="status_id" class="tom-select-no-search w-full" @disabled(! $canEditTask)>
                        <option value="">Select status</option>
                        @foreach ($taskStatuses as $status)
                            <option value="{{ $status->id }}" {{ (int) $task->status_id === (int) $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="status_id"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Assignee</label>
                    <select name="current_assignee_id" class="tom-select w-full" data-sort="0" @disabled(! $canEditTask)>
                        <option value="">Select assignee</option>
                        @foreach ($assignableUsers as $assignableUser)
                            <option value="{{ $assignableUser->id }}" {{ (int) $task->current_assignee_id === (int) $assignableUser->id ? 'selected' : '' }}>
                                {{ $assignableUser->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="current_assignee_id"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Task Type</label>
                    <select name="task_type" class="tom-select-no-search w-full" @disabled(! $canEditTask)>
                        @foreach ($taskTypeOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $task->task_type === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="task_type"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Task Mode</label>
                    <select name="task_mode" class="tom-select-no-search w-full" @disabled(! $canEditTask)>
                        @foreach ($taskModeOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $task->task_mode === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="task_mode"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Priority</label>
                    <select name="priority" class="tom-select-no-search w-full" @disabled(! $canEditTask)>
                        @foreach ($taskPriorityOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $task->priority === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="priority"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Parent Task</label>
                    <select name="parent_task_id" class="tom-select w-full" data-sort="0" @disabled(! $canEditTask)>
                        <option value="">Select parent task</option>
                        @foreach ($parentTaskOptions as $parentTaskOption)
                            <option value="{{ $parentTaskOption->id }}" {{ (int) $task->parent_task_id === (int) $parentTaskOption->id ? 'selected' : '' }}>
                                {{ $parentTaskOption->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="parent_task_id"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Estimate Time</label>
                    <input type="number" name="estimated_time_minutes" min="0" step="1" value="{{ $task->estimated_time_seconds ? (int) round($task->estimated_time_seconds / 60) : 0 }}" class="{{ $textInputClasses }}" @disabled(! $canEditTask)>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="estimated_time_minutes"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Start Date</label>
                    <input type="date" name="start_date" value="{{ $task->start_date?->format('Y-m-d') }}" class="datepicker {{ $textInputClasses }}" placeholder="Select a date" @disabled(! $canEditTask)>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="start_date"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Due Date</label>
                    <input type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}" class="datepicker {{ $textInputClasses }}" placeholder="Select a date" @disabled(! $canEditTask)>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="due_date"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2.5 block text-left text-sm text-bgray-500 dark:text-bgray-50">Tags</label>
                    <select name="tag_ids[]" class="tom-select-tags w-full" multiple @disabled(! $canEditTask)>
                        @foreach ($tagOptions as $tagOption)
                            <option value="{{ $tagOption->id }}" {{ in_array((string) $tagOption->id, $selectedTagIds, true) ? 'selected' : '' }}>
                                {{ $tagOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="tag_ids"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-3 rounded-xl border border-bgray-200 bg-bgray-50 px-4 py-3 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                        <input type="hidden" name="is_billable" value="0">
                        <input type="checkbox" name="is_billable" value="1" class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-success-300" {{ $task->is_billable ? 'checked' : '' }} @disabled(! $canEditTask)>
                        <span>Billable task</span>
                    </label>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="is_billable"></p>
                </div>
            </div>
        </div>

        <aside class="flex min-h-0 flex-col overflow-hidden bg-bgray-50/60 p-6 dark:bg-darkblack-500/40">
            <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                <div class="space-y-4">
                    <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Summary</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                                <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Estimated</p>
                                <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</p>
                            </div>
                            <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                                <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Derived</p>
                                <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->derived_time_formatted }}</p>
                            </div>
                            <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                                <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Actual</p>
                                <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->actual_time_formatted }}</p>
                            </div>
                            <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                                <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Completed</p>
                                <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->completed_at ? \App\Providers\AppServiceProvider::formatAppDateTime($task->completed_at) : '--' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Context</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-500 dark:text-bgray-300">Module</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->projectModule?->name ?? '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-500 dark:text-bgray-300">Sprint</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->projectSprint?->name ?? '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-500 dark:text-bgray-300">Created By</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->addedBy?->name ?? '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-500 dark:text-bgray-300">Created At</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ \App\Providers\AppServiceProvider::formatAppDateTime($task->created_at) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-500 dark:text-bgray-300">Updated At</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ \App\Providers\AppServiceProvider::formatAppDateTime($task->updated_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-bgray-200 pt-4 dark:border-darkblack-400">
                <button type="button" class="rounded-lg border border-bgray-300 bg-white px-6 py-3 text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-task-detail-close>
                    {{ $canEditTask ? 'Cancel' : 'Close' }}
                </button>

                @if ($canEditTask)
                    <button type="submit" class="rounded-lg bg-success-300 px-6 py-3 text-white transition duration-200 hover:bg-success-400" data-project-task-detail-submit>
                        Update Task
                    </button>
                @endif
            </div>
        </aside>
    </form>
</div>
