<div class="card cursor-pointer bg-white dark:bg-darkblack-600 rounded-md shadow-sm hover:shadow-md transition" data-task-id="{{ $task->id }}">
    <div class="p-4 space-y-4">
        @php
            $isCompleted = $status->is_completed ?? false;
            $priority = $task->priority ?? 'medium';
            $priorityConfig = $priorities[$priority] ?? [];
            $priorityBgClass = $priorityConfig['bg_class'] ?? 'bg-gray-100';
            $priorityTextClass = $priorityConfig['bg_text'] ?? 'text-gray-900';
            $priorityLabel = $priorityConfig['label'] ?? ucfirst($priority);

            $estimatedTime = $task->estimatedTimeFormatted;
            $dueDate = $task->due_date;
            $isDueOrPast = $dueDate && !$isCompleted ? $dueDate->lessThanOrEqualTo(today()) : false;
            $dueDateTextClass = $isDueOrPast ? 'text-error-300 dark:text-error-200' : 'text-gray-500 dark:text-gray-400';

            $stringLimit = fn(?string $value, int $length = 25, string $end = '...'): string => \Illuminate\Support\Str::limit($value ?? '', $length, $end);
        @endphp

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug {{ $isCompleted ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-white' }}">
                    <a href="#" data-fc-type="modal" data-fc-target="task-detail-modal" class="block truncate">
                        {{ $stringLimit($task->name ?? $task->code ?? 'Untitled task', 25, '...') }}
                    </a>
                </h5>

                <div class="mt-2 space-y-1 text-[11px] leading-snug">
                    @if ($task->project)
                        <p class="truncate text-gray-500 dark:text-gray-400" title="{{ $task->project->name }}">
                            {{ $stringLimit($task->project->name, 20, '...') }}
                        </p>
                    @endif
                    @if ($task->projectModule)
                        <p class="truncate text-gray-500 dark:text-gray-400" title="{{ $task->projectModule->name }}">
                            {{ $stringLimit($task->projectModule->name, 20, '...') }}
                        </p>
                    @endif
                </div>
            </div>

            <span class="h-3.5 w-3.5 rounded-full {{ $priorityBgClass }}" title="Priority: {{ $priorityLabel }}"></span>
        </div>

        <div class="flex items-start justify-between gap-3">
            <small class="text-[11px] uppercase tracking-wide {{ $dueDateTextClass }} @if($isDueOrPast) border border-b-alertsErrorBase p-1 @endif">
                {{ $dueDate?->format($globalDateFormat) }}
            </small>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $estimatedTime }}</span>
            </div>

            <div class="flex items-center gap-2" title="{{ $task->currentAssignee ? 'Assignee: ' . $task->currentAssignee->name : 'Not Assigned' }}">
                @if ($task->currentAssignee)
                    @if (! empty($task->currentAssignee->profileImageUrl))
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
