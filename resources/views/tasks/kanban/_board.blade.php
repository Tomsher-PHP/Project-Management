@foreach ($boardStatuses as $status)
    @php
        $column = $tasksByStatus[$status->id] ?? [
            'tasks' => collect(),
            'taskIds' => [],
            'total' => 0,
            'hasMore' => false,
            'nextPage' => null,
        ];
    @endphp

    <div class="flex flex-col flex-shrink-0 w-80 rounded-md bg-gray-50 dark:bg-darkblack-500 overflow-hidden">

        <!-- Column Header -->
        <h6 class="uppercase mb-0 w-full rounded-t-md px-4 py-1 text-sm font-semibold text-white" style="background-color: {{ $status->color }};">
            {{ $status->name }}
            (<span data-kanban-total-count>{{ $column['total'] ?? 0 }}</span>)
        </h6>

        <!-- Column Body -->
        <div class="flex flex-col gap-4 kanban-board overflow-y-auto overflow-x-hidden h-full px-4 pb-4 pt-4" data-status-id="{{ $status->id }}" data-task-ids='@json($column['taskIds'] ?? [])' data-next-page="{{ $column['nextPage'] ?? '' }}" data-has-more="{{ !empty($column['hasMore']) ? 'true' : 'false' }}" data-loading="false">
            @include('tasks.kanban._cards', ['tasks' => $column['tasks'] ?? collect(), 'status' => $status])

            <div class="hidden py-2 text-center text-xs text-gray-400 dark:text-gray-500" data-kanban-load-indicator>
                Loading more...
            </div>
        </div>
    </div>
@endforeach