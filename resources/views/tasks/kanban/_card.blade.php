@php
    $authUser = auth()->user();
    $canOpenTask = $task->project && $authUser && ($authUser->is_super_admin || $authUser->can('task.view_all_tasks') || $authUser->can('task.view') || (int) ($task->current_assignee_id ?? 0) === (int) $authUser->id);
@endphp

<div class="card {{ $canOpenTask ? 'cursor-pointer' : 'cursor-default' }} bg-white dark:bg-darkblack-600 rounded-md shadow-sm hover:shadow-md transition" data-task-id="{{ $task->id }}" @if ($canOpenTask) data-project-task-detail-open
        data-project-task-detail-url="{{ route('projects.tasks.modal', [$task->project, $task]) }}"
        data-project-task-group-key=""
        title="Open task" @endif>
    <div class="p-4 space-y-4">
        @php
            $isCompleted = $status->is_completed ?? false;
            $priority = $task->priority ?? 'medium';
            $priorityConfig = $priorities[$priority] ?? [];
            $priorityBgClass = $priorityConfig['bg_class'] ?? 'bg-gray-100';
            $priorityTextClass = $priorityConfig['bg_text'] ?? 'text-gray-900';
            $priorityLabel = $priorityConfig['label'] ?? ucfirst($priority);

            $estimatedTime = $task->estimatedTimeFormatted;
            $dueDate = $task->due_date_time;
            $dueDateDisplay = \App\Providers\AppServiceProvider::formatAppDateTime($dueDate);
            $dueDateStatus = $task->status ?? $status ?? null;

            $stringLimit = fn(?string $value, int $length = 25, string $end = '...'): string => \Illuminate\Support\Str::limit($value ?? '', $length, $end);
        @endphp

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h5 class="leading-snug">
                    <x-task-name-status :name="$task->name ?? ($task->code ?? 'Untitled task')" :request-type="$task->request_type" :request-status="$task->request_status" :limit="25" text-class="text-sm font-semibold {{ $isCompleted ? 'line-through text-gray-400 dark:text-gray-500' : 'text-bgray-900 dark:text-white' }}" name-class="block" class="max-w-full" />
                </h5>

                <div class="mt-2 space-y-1 text-[11px] leading-snug">
                    @if ($task->project)
                        <p class="truncate text-gray-500 dark:text-gray-400" title="Project: {{ $task->project->name }}">
                            {{ $stringLimit($task->project->name, 20, '...') }}
                        </p>
                    @endif
                    @if ($task->projectMilestone)
                        <p class="truncate text-gray-500 dark:text-gray-400" title="Milestone: {{ $task->projectMilestone->name }}">
                            {{ $stringLimit($task->projectMilestone->name, 20, '...') }}
                        </p>
                    @endif
                </div>
            </div>

            <span class="h-3.5 w-3.5 rounded-full {{ $priorityBgClass }}" title="Priority: {{ $priorityLabel }}"></span>
        </div>

        @if ($dueDate)
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex min-w-0 items-center gap-0.5 text-xs leading-5 {{ taskDueDateClass($dueDate, $task->estimated_time_seconds, $dueDateStatus) }}">
                    {!! taskDueDateIcon($dueDate, $task->estimated_time_seconds, $dueDateStatus) !!}
                    <small class="truncate text-xs uppercase leading-5 tracking-wide" title="Due date">
                        {{ $dueDateDisplay }}
                    </small>
                </span>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $estimatedTime }}</span>
            </div>

            <div class="flex items-center gap-2" title="{{ $task->currentAssignee ? 'Assignee: ' . $task->currentAssignee->name : 'Not Assigned' }}">
                @if ($task->currentAssignee)
                    @if (!empty($task->currentAssignee->profileImageUrl))
                        <img src="{{ $task->currentAssignee->profileImageUrl }}" alt="{{ $task->currentAssignee->name }}" class="h-8 w-8 rounded-full object-cover" />
                    @else
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-semibold uppercase text-white">
                            {{ strtoupper(substr($task->currentAssignee->name, 0, 1)) }}
                        </div>
                    @endif
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-[10px] font-semibold uppercase text-gray-600 dark:bg-darkblack-500 dark:text-gray-300">
                        NA
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>