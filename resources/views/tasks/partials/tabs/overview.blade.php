<div class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,1fr)]">
    <div class="space-y-5">
        <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Description</p>
            <div class="mt-4 rounded-xl bg-bgray-50 px-4 py-4 text-sm leading-7 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200">
                {{ $task->description ?: 'No task description added yet.' }}
            </div>
        </div>

        <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Task Details</p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Assignee</p>
                    <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->currentAssignee?->name ?? 'Unassigned' }}</p>
                </div>

                <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Parent Task</p>
                    <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->parentTask?->title ?? '--' }}</p>
                </div>

                <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Start Date</p>
                    <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->start_date ? $task->start_date->format($globalDateFormat) : '--' }}</p>
                </div>

                <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Due Date</p>
                    <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->due_date ? $task->due_date->format($globalDateFormat) : '--' }}</p>
                </div>

                <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Billable</p>
                    <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->is_billable ? 'Yes' : 'No' }}</p>
                </div>

                <div class="rounded-xl bg-bgray-50 px-4 py-3 dark:bg-darkblack-500">
                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">Current Assignment</p>
                    <p class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $task->currentAssignmentLog?->user?->name ?? '--' }}</p>
                </div>
            </div>

            <div class="mt-5">
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-bgray-400 dark:text-bgray-300">Tags</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($task->tags as $tag)
                        <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $tag->color ?: '#94A3B8' }}"></span>
                            {{ $tag->name }}
                        </span>
                    @empty
                        <span class="text-sm text-bgray-500 dark:text-bgray-300">No tags assigned.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Recent Time Logs</p>
                <span class="text-xs font-medium text-bgray-500 dark:text-bgray-300">{{ $taskTimeLogs->count() }} shown</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($taskTimeLogs as $timeLog)
                    @php
                        $durationSeconds = max(0, (int) ($timeLog->duration_seconds ?? 0));
                        $durationHours = floor($durationSeconds / 3600);
                        $durationMinutes = floor(($durationSeconds % 3600) / 60);
                    @endphp
                    <div class="rounded-xl border border-bgray-200 px-4 py-3 dark:border-darkblack-400">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $timeLog->user?->name ?? 'Unknown User' }}</p>
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                                    {{ $timeLog->started_at ? \App\Providers\AppServiceProvider::formatAppDateTime($timeLog->started_at) : '--' }}
                                    @if ($timeLog->ended_at)
                                        to {{ \App\Providers\AppServiceProvider::formatAppDateTime($timeLog->ended_at) }}
                                    @elseif ($timeLog->is_running)
                                        to Running
                                    @endif
                                </p>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ sprintf('%02d:%02d', $durationHours, $durationMinutes) }}</p>
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">{{ $timeLog->note ?: 'No note' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No time logs recorded for this task yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <aside class="space-y-5">
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
                    <dt class="text-bgray-500 dark:text-bgray-300">Project</dt>
                    <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $project?->name ?? '--' }}</dd>
                </div>
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
                    <dt class="text-bgray-500 dark:text-bgray-300">Updated By</dt>
                    <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ $task->updatedBy?->name ?? '--' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-bgray-500 dark:text-bgray-300">Updated At</dt>
                    <dd class="text-right font-medium text-bgray-900 dark:text-white">{{ \App\Providers\AppServiceProvider::formatAppDateTime($task->updated_at) }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Status History</p>
                <span class="text-xs font-medium text-bgray-500 dark:text-bgray-300">{{ $taskStatusHistories->count() }} shown</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($taskStatusHistories as $history)
                    <div class="rounded-xl border border-bgray-200 px-4 py-3 dark:border-darkblack-400">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                                    {{ $history->status?->name ?? 'Status Updated' }}
                                </p>
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                                    {{ $history->addedBy?->name ?? 'System' }}
                                </p>
                            </div>

                            <div class="text-right">
                                <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $history->status?->color ?: '#94A3B8' }}"></span>
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">
                                    {{ $history->added_at ? \App\Providers\AppServiceProvider::formatAppDateTime($history->added_at) : '--' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No status changes recorded for this task yet.
                    </div>
                @endforelse
            </div>
        </div>
    </aside>
</div>
