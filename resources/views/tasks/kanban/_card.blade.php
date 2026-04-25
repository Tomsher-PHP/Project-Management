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
            $isDueOrPast = $dueDate && ! $isCompleted
                ? $dueDate->copy()->timezone(config('constants.timezone'))->lessThanOrEqualTo(now(config('constants.timezone')))
                : false;
            $dueDateTextClass = $isDueOrPast ? 'text-error-300 dark:text-error-200' : 'text-gray-500 dark:text-gray-400';

            $stringLimit = fn(?string $value, int $length = 25, string $end = '...'): string => \Illuminate\Support\Str::limit($value ?? '', $length, $end);
        @endphp

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h5 class="leading-snug">
                    <x-task-name-status
                        :name="$task->name ?? ($task->code ?? 'Untitled task')"
                        :request-type="$task->request_type"
                        :request-status="$task->request_status"
                        :limit="25"
                        text-class="text-sm font-semibold {{ $isCompleted ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-white' }}"
                        name-class="block"
                        class="max-w-full"
                    />
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
                <span class="inline-flex min-w-0 items-center gap-1.5 {{ $dueDateTextClass }} @if ($isDueOrPast) rounded border border-b-alertsErrorBase px-1.5 py-0.5 @endif">
                    <svg class="h-5 w-5 flex-shrink-0 stroke-current" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.6758 5.8186H6.67578C5.57121 5.8186 4.67578 6.71403 4.67578 7.8186V19.8186C4.67578 20.9232 5.57121 21.8186 6.67578 21.8186H18.6758C19.7804 21.8186 20.6758 20.9232 20.6758 19.8186V7.8186C20.6758 6.71403 19.7804 5.8186 18.6758 5.8186Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M16.6758 3.8186V7.8186" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M8.67578 3.8186V7.8186" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M4.67578 11.8186H20.6758" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M11.6758 15.8186H12.6758" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M12.6758 15.8186V18.8186" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
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
