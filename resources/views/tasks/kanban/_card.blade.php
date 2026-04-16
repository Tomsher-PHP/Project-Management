<div class="card cursor-pointer bg-white dark:bg-darkblack-600 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition" data-task-id="{{ $task->id }}">
    <div class="p-4 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <small class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ $task->created_at?->format($globalDateFormat) }}
            </small>

            @php
                $priority = $task->priority ?? 'medium';
                $priorityConfig = $priorities[$priority] ?? [];
                $priorityBgClass = $priorityConfig['bg_class'] ?? 'bg-gray-100';
                $priorityTextClass = $priorityConfig['bg_text'] ?? 'text-gray-900';
                $priorityLabel = $priorityConfig['label'] ?? ucfirst($priority);

                $estimatedTime = $task->estimatedTimeFormatted;
            @endphp

            <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $priorityBgClass }} {{ $priorityTextClass }}">
                {{ $priorityLabel }}
            </span>
        </div>

        <h5 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug">
            <a href="#" data-fc-type="modal" data-fc-target="task-detail-modal" class="block truncate">
                {{ \Illuminate\Support\Str::limit($task->name ?? $task->code ?? 'Untitled task', 20, '...') }}
            </a>
        </h5>

        <div class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-3">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $estimatedTime }}</span>
            </div>

            @if ($task->currentAssignee)
                <div class="flex items-center gap-2">
                    @if (! empty($task->currentAssignee->profileImageUrl))
                        <img src="{{ $task->currentAssignee->profileImageUrl }}" alt="{{ $task->currentAssignee->name }}" class="h-8 w-8 rounded-full object-cover" />
                    @else
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-semibold uppercase text-white">
                            {{ strtoupper(substr($task->currentAssignee->name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">
                        {{ $task->currentAssignee->name }}
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
