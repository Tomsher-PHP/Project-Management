@foreach ($tasks as $subtask)
    @include('tasks.partials.table-row', [
        'task' => $subtask,
        'isSubtask' => true,
        'parentTaskId' => $parentTaskId,
        'depth' => $depth,
    ])

    @if ($subtask->childTasks->isNotEmpty())
        @include('tasks.partials.subtask-rows', [
            'tasks' => $subtask->childTasks,
            'parentTaskId' => $subtask->id,
            'depth' => $depth + 1,
        ])
    @endif
@endforeach
