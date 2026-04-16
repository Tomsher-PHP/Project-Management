@foreach ($boardStatuses as $status)
    <div class="flex flex-col flex-shrink-0 w-80 rounded-md bg-gray-50 dark:bg-darkblack-500 overflow-hidden">

        <!-- Column Header -->
        <h6 class="uppercase mb-0 w-full rounded-t-md px-4 py-1 text-sm font-semibold text-white" style="background-color: {{ $status->color }};">
            {{ $status->name }}
            ({{ $tasksByStatus[$status->id]->count() ?? 0 }})
        </h6>

        <!-- Column Body -->
        <div class="flex flex-col gap-4 kanban-board overflow-y-auto overflow-x-hidden h-full px-4 pb-4 pt-4" data-status-id="{{ $status->id }}">

            @foreach ($tasksByStatus[$status->id] ?? [] as $task)
                @include('tasks.kanban._card', ['task' => $task])
            @endforeach

        </div>
    </div>
@endforeach
