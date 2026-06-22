@php
    $schedule = $taskSchedule ?? null;
    $isEdit = $schedule !== null;
    $initialData = $isEdit
        ? [
            'project_id' => (string) $schedule->project_id,
            'project_milestone_id' => (string) ($schedule->project_milestone_id ?? ''),
            'project_sprint_id' => (string) ($schedule->project_sprint_id ?? ''),
            'current_assignee_id' => (string) ($schedule->current_assignee_id ?? ''),
            'estimated_time_minutes' => intdiv((int) $schedule->estimated_time_seconds, 60),
        ]
        : [];
@endphp

<div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-schedule-task-modal="{{ $isEdit ? 'edit' : 'create' }}" data-today="{{ now(config('constants.timezone'))->format('Y-m-d') }}">
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-schedule-task-close></div>
    <div class="relative flex min-h-full w-full items-start justify-center p-4 py-6 sm:p-6 sm:py-10">
        <div class="relative z-10 w-full max-w-5xl" data-schedule-task-panel>
            <div class="flex max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[24px] bg-white shadow-2xl dark:bg-darkblack-600 sm:max-h-[calc(100vh-5rem)]">
                <div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
                    <h3 class="text-lg font-semibold text-bgray-900 dark:text-white">{{ $isEdit ? 'Edit Scheduled Task' : 'Schedule Task' }}</h3>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300" data-schedule-task-close>✕</button>
                </div>

                <form class="space-y-5 overflow-y-auto px-5 py-5" data-schedule-task-form data-url="{{ $isEdit ? route('schedule-tasks.update', $schedule) : route('schedule-tasks.store') }}" data-edit="{{ $isEdit ? 'true' : 'false' }}">
                    @if ($isEdit)
                        <input type="hidden" name="_method" value="PUT">
                    @endif

                    <section>
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-bgray-900 dark:text-white">Basic</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Project <x-red-star /></label>
                                <select name="project_id" class="tom-select w-full" data-sort="0">
                                    <option value="">Select project</option>
                                    @foreach ($taskCreateProjects as $project)
                                        <option value="{{ $project->id }}" @selected((string) $schedule?->project_id === (string) $project->id)>{{ $project->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="project_id"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Milestone</label>
                                <select name="project_milestone_id" class="tom-select w-full" data-sort="0">
                                    <option value="">Select project first</option>
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="project_milestone_id"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Sprint</label>
                                <select name="project_sprint_id" class="tom-select w-full" data-sort="0">
                                    <option value="">Select project first</option>
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="project_sprint_id"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Assignee</label>
                                <select name="current_assignee_id" class="tom-select w-full" data-sort="0">
                                    <option value="">Select project first</option>
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="current_assignee_id"></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Task Name <x-red-star /></label>
                                <input type="text" name="name" value="{{ $schedule?->name }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="name"></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Estimated Time</label>
                                <x-forms.estimated-time-input name="estimated_time_minutes" :total-minutes="$isEdit ? intdiv((int) $schedule->estimated_time_seconds, 60) : 0" :show-label="false" />
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="estimated_time_minutes"></p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40" data-schedule-task-advanced {{ $isEdit ? '' : 'hidden' }}>
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-bgray-900 dark:text-white">Advanced</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Description</label>
                                <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">{{ $schedule?->description }}</textarea>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="description"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Task Type</label>
                                <select name="task_type_id" class="tom-select-no-search w-full">
                                    <option value="">Select task type</option>
                                    @foreach ($taskTypeOptions as $option)
                                        <option value="{{ $option->id }}" @selected($isEdit ? $schedule->task_type_id === $option->id : $loop->first)>{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="task_type_id"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Task Mode</label>
                                <select name="task_mode_id" class="tom-select-no-search w-full">
                                    <option value="">Select task mode</option>
                                    @foreach ($taskModeOptions as $option)
                                        <option value="{{ $option->id }}" @selected($isEdit ? $schedule->task_mode_id === $option->id : $loop->first)>{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="task_mode_id"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Priority</label>
                                <select name="priority" class="tom-select-no-search w-full">
                                    @foreach ($taskPriorityOptions as $option)
                                        <option value="{{ $option->value }}" @selected(($schedule?->priority ?? $defaultTaskPriority) === $option->value)>{{ $option->label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="priority"></p>
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-3 rounded-xl border border-bgray-200 bg-white px-4 py-3 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                                    <input type="hidden" name="is_billable" value="0">
                                    <input type="checkbox" name="is_billable" value="1" @checked($schedule?->is_billable) class="h-4 w-4 rounded border-gray-300 text-success-300 focus:ring-success-300">
                                    Billable
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-success-200 bg-success-50/40 p-4 dark:border-success-900/40 dark:bg-darkblack-500/40">
                        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-bgray-900 dark:text-white">Schedule Configuration</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Frequency <x-red-star /></label>
                                <select name="frequency_type" class="tom-select-no-search w-full" data-schedule-frequency>
                                    <option value="daily" @selected(($schedule?->frequency_type ?? 'daily') === 'daily')>Daily</option>
                                    <option value="weekdays" @selected($schedule?->frequency_type === 'weekdays')>Selected Days</option>
                                    <option value="weekly" @selected($schedule?->frequency_type === 'weekly')>Weekly</option>
                                    <option value="monthly" @selected($schedule?->frequency_type === 'monthly')>Monthly</option>
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="frequency_type"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Due After Hours</label>
                                <input type="number" name="due_after_hours" value="{{ $schedule?->due_after_hours }}" min="0" step="1" placeholder="Example: 24" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">Number of hours after task generation to set the due date. Leave empty if no due date should be assigned.</p>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="due_after_hours"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Start Date <x-red-star /></label>
                                <input type="text" name="start_date" value="{{ $schedule?->start_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-schedule-date="start" data-alt-input="true" data-alt-format="d-m-Y" autocomplete="off">
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="start_date"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">End Date <x-red-star /></label>
                                <input type="text" name="end_date" value="{{ $schedule?->end_date?->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-schedule-date="end" data-alt-input="true" data-alt-format="d-m-Y" placeholder="No End Date" autocomplete="off" data-schedule-end-date-generated="false">
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="end_date"></p>
                            </div>
                            <div class="md:col-span-2" data-schedule-frequency-section="weekdays">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Selected Days</label>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)
                                        <label class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                                            <input type="checkbox" name="week_days[]" value="{{ $day }}" @checked($isEdit ? in_array($day, $schedule->week_days ?? [], true) : $day <= 5) class="rounded border-gray-300 text-success-300 focus:ring-success-300"> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="week_days"></p>
                            </div>
                            <div data-schedule-frequency-section="weekly">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Day Of Week</label>
                                <select name="weekly_day" class="tom-select-no-search w-full">
                                    @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)
                                        <option value="{{ $day }}" @selected(($schedule?->weekly_day ?? 1) === $day)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="weekly_day"></p>
                            </div>
                            <div class="md:col-span-2" data-schedule-frequency-section="monthly">
                                <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">Days Of Month</label>
                                <div class="flex flex-wrap gap-2">
                                    @for ($day = 1; $day <= 31; $day++)
                                        <label class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-2 text-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                                            <input type="checkbox" name="month_days[]" value="{{ $day }}" @checked($isEdit ? in_array($day, $schedule->month_days ?? [], true) : $day === 1) class="rounded border-gray-300 text-success-300 focus:ring-success-300"> {{ $day }}
                                        </label>
                                    @endfor
                                </div>
                                <p class="mt-1 hidden text-xs text-red-500" data-schedule-task-error="month_days"></p>
                            </div>
                        </div>
                    </section>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" class="mr-auto rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-medium text-success-400" data-schedule-advanced-toggle>{{ $isEdit ? 'Hide Advanced' : 'Show Advanced' }}</button>
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-schedule-task-close>Cancel</button>
                        <button type="submit" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white hover:bg-success-400" data-schedule-task-submit>{{ $isEdit ? 'Update Schedule' : 'Save Schedule' }}</button>
                    </div>
                </form>
                <script type="application/json" data-schedule-task-initial>@json($initialData)</script>
            </div>
        </div>
    </div>
</div>
