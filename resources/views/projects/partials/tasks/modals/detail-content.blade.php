@php
    $isRejectedTask = $task->isRejectedRequest();
    $rejectionReason = $task->rejection_reason ?? 'No reason provided';
    $authUser = auth()->user();
    $canOpenTaskDetailPage = $authUser && $authUser->can('task.view') && $authUser->can('view', $task);
    $selectedTagIds = $task->tags->pluck('id')->map(fn($id) => (string) $id)->all();
    $textInputClasses = $canEditTask ? 'w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white' : 'w-full rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 text-sm text-bgray-600 focus:border-bgray-200 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300';
    $textareaClasses = $canEditTask ? 'w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white' : 'w-full rounded-lg border border-bgray-200 bg-bgray-50 p-3 text-sm text-bgray-600 focus:border-bgray-200 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300';
    $isPlacementLockedForSubtask = $canEditTask && filled($task->parent_task_id);
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

<div class="overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-darkblack-600">
    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
        <div>
            <h3 class="text-xl font-semibold text-bgray-900 dark:text-white">Manage Task</h3>
            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                {{ $isRejectedTask ? 'Rejected tasks are read-only and cannot be updated.' : 'Review and update the working details for this task.' }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if ($canOpenTaskDetailPage)
                <a href="{{ route('tasks.edit', $task) }}" class="inline-flex items-center gap-2 rounded-lg border bg-bgray-100 px-4 py-2 text-sm font-medium text-bgray-700 transition duration-200 hover:text-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-900/40 dark:hover:bg-darkblack-400 dark:hover:text-success-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                    </svg>
                    <span>Task Details</span>
                </a>
            @endif

            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-task-detail-close>
                ✕
            </button>
        </div>
    </div>

    <form class="flex max-h-[82vh] flex-col xl:grid xl:h-[82vh] xl:max-h-[82vh] xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]" data-project-task-detail-form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST" data-parent-task-url="{{ route('projects.tasks.parent-options', $project) }}" data-current-task-id="{{ $task->id }}" data-task-placement='@json($taskPlacementOptions)'>
        @csrf
        @method('PUT')

        <div class="min-h-0 overflow-y-auto border-b border-bgray-200 px-6 py-6 dark:border-darkblack-400 xl:border-b-0 xl:border-r sm:px-7">
            @if ($isRejectedTask)
                <div class="mb-6 rounded-xl border border-error-300/30 bg-red-50 px-4 py-3 text-sm font-medium text-error-300 dark:border-error-300/20 dark:bg-darkblack-500">
                    Rejection Reason: {{ $rejectionReason }}
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                @unless ($isLinearFlow)
                    <div>
                        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Milestone</label>
                        <select name="project_milestone_id" class="tom-select w-full" data-sort="0" data-project-task-detail-module-select @disabled(!$canEditTask || $isPlacementLockedForSubtask)>
                            <option value="">Select milestone or leave empty for backlog</option>
                            @foreach ($projectMilestones as $projectMilestone)
                                <option value="{{ $projectMilestone->id }}" {{ (int) $task->project_milestone_id === (int) $projectMilestone->id ? 'selected' : '' }}>
                                    {{ $projectMilestone->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($isPlacementLockedForSubtask)
                            <input type="hidden" name="project_milestone_id" value="{{ $task->project_milestone_id ?? '' }}">
                        @endif
                        <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="project_milestone_id"></p>
                    </div>

                    <div>
                        <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Sprint</label>
                        <select name="project_sprint_id" class="tom-select w-full" data-sort="0" data-project-task-detail-sprint-select @disabled(!$canEditTask || $isPlacementLockedForSubtask)>
                            <option value="">Select sprint or leave empty for backlog</option>
                            @foreach ($projectSprints as $projectSprint)
                                <option value="{{ $projectSprint->id }}" data-module-id="{{ $projectSprint->project_milestone_id }}" {{ (int) $task->project_sprint_id === (int) $projectSprint->id ? 'selected' : '' }}>
                                    {{ $projectSprint->name }}@if ($projectSprint->projectMilestone?->name)
                                        - {{ $projectSprint->projectMilestone->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @if ($isPlacementLockedForSubtask)
                            <input type="hidden" name="project_sprint_id" value="{{ $task->project_sprint_id ?? '' }}">
                        @endif
                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300" data-project-task-detail-placement-hint></p>
                        @if ($isPlacementLockedForSubtask)
                            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">Subtasks inherit milestone and sprint from the parent task.</p>
                        @endif
                        <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="project_sprint_id"></p>
                    </div>
                @endunless

                <div class="{{ $isLinearFlow ? 'md:col-span-2' : '' }}">
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Parent Task</label>
                    <select name="parent_task_id" class="tom-select w-full" data-sort="0" data-parent-task-select @disabled(!$canEditTask)>
                        <option value="">Select parent task</option>
                        @foreach ($parentTaskOptions as $parentTaskOption)
                            <option value="{{ $parentTaskOption->id }}" {{ (int) $task->parent_task_id === (int) $parentTaskOption->id ? 'selected' : '' }}>
                                {{ $parentTaskOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="parent_task_id"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Task Name <x-red-star /></label>
                    <input type="text" name="name" value="{{ $task->name }}" class="{{ $textInputClasses }}" @disabled(!$canEditTask)>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="name"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Description</label>
                    <textarea name="description" rows="4" class="{{ $textareaClasses }}" @disabled(!$canEditTask)>{{ $task->description }}</textarea>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="description"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Status</label>
                    <select name="status_id" class="tom-select-no-search w-full" @disabled(!$canEditTask)>
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
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Assignee</label>
                    <select name="current_assignee_id" class="tom-select w-full" data-sort="0" @disabled(!$canEditTask)>
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
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Task Type</label>
                    <select name="task_type_id" class="tom-select-no-search w-full" @disabled(!$canEditTask)>
                        @foreach ($taskTypeOptions as $option)
                            <option value="{{ $option->id }}" {{ $task->task_type_id === $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="task_type_id"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Task Mode</label>
                    <select name="task_mode_id" class="tom-select-no-search w-full" @disabled(!$canEditTask)>
                        @foreach ($taskModeOptions as $option)
                            <option value="{{ $option->id }}" {{ $task->task_mode_id === $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="task_mode_id"></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Priority</label>
                    <select name="priority" class="tom-select-no-search w-full" @disabled(!$canEditTask)>
                        @foreach ($taskPriorityOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $task->priority === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="priority"></p>
                </div>

                <div>
                    <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="$task->estimated_time_seconds ? (int) round($task->estimated_time_seconds / 60) : 0" :show-label="false" :disabled="!$canEditTask" />
                    <p class="mt-1 hidden text-sm" data-project-task-detail-error="estimated_time_minutes"></p>
                    @if ($authUser && (int) $authUser->id === (int) $task->current_assignee_id)
                        @php
                            $pendingExceedRequest = \App\Models\TaskExtendTimeRequest::where('task_id', $task->id)->where('user_id', $authUser->id)->where('status', 'pending')->first();
                        @endphp
                        <div class="mt-2">
                            <button type="button" class="inline-flex items-center gap-1.5 text-xs font-semibold text-success-300 hover:text-success-100 transition duration-200" data-request-estimate-change-trigger data-task-id="{{ $task->id }}" data-task-name="{{ $task->name }}" data-current-estimate="{{ $task->estimated_time_formatted }}" data-store-url="{{ route('tasks.extend-time-requests.store', $task) }}" data-pending-url="{{ route('tasks.extend-time-requests.pending', $task) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $pendingExceedRequest ? 'Edit Estimate Change Request' : 'Request Estimate Change' }}</span>
                            </button>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Due Date</label>
                    <input type="text" name="due_date_time" value="{{ $task->due_date_time?->copy()->timezone(config('constants.timezone'))->format('Y-m-d H:i') }}" class="datepicker {{ $textInputClasses }}" data-enable-time="true" data-time-24hr="true" data-format="Y-m-d H:i" placeholder="Select a date and time" autocomplete="off" @disabled(!$canEditTask)>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="due_date_time"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Tags</label>
                    <select name="tag_ids[]" class="tom-select-tags w-full" multiple @disabled(!$canEditTask)>
                        @foreach ($tagOptions as $tagOption)
                            <option value="{{ $tagOption->id }}" {{ in_array((string) $tagOption->id, $selectedTagIds, true) ? 'selected' : '' }}>
                                {{ $tagOption->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 hidden text-sm text-red-500" data-project-task-detail-error="tag_ids"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-3 rounded-xl border border-bgray-200 bg-bgray-50 px-4 py-3 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                        <input type="hidden" name="is_billable" value="0">
                        <input type="checkbox" name="is_billable" value="1" class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-success-300" {{ $task->is_billable ? 'checked' : '' }} @disabled(!$canEditTask)>
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
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-700 dark:text-bgray-300">Summary</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                                <p class="text-xs font-medium text-bgray-700 dark:text-bgray-300">Estimated</p>
                                <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->estimated_time_formatted }}</p>
                            </div>
                            <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                                <p class="text-xs font-medium text-bgray-700 dark:text-bgray-300">Actual</p>
                                <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->actual_time_formatted }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-700 dark:text-bgray-300">Context</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-700 dark:text-bgray-300">Milestone</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->projectMilestone?->name ?? '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-700 dark:text-bgray-300">Sprint</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->projectSprint?->name ?? '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-700 dark:text-bgray-300">Created By</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->addedBy?->name ?? '--' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-700 dark:text-bgray-300">Created At</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">@appDateTime($task->created_at)</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-bgray-700 dark:text-bgray-300">Updated At</dt>
                                <dd class="text-right font-medium text-bgray-900 dark:text-white">@appDateTime($task->updated_at)</dd>
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
