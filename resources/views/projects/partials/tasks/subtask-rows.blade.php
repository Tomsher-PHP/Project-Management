@foreach ($tasks as $subtask)
    @include('projects.partials.tasks.task-row', [
        'project' => $project,
        'group' => $group,
        'task' => $subtask,
        'showTaskActionColumn' => $showTaskActionColumn,
        'isSubtask' => true,
        'parentTaskId' => $parentTaskId,
        'depth' => $depth,
        'search' => $search ?? null,
    ])

    @if ($subtask->childTasks->isNotEmpty())
        @include('projects.partials.tasks.subtask-rows', [
            'project' => $project,
            'group' => $group,
            'tasks' => $subtask->childTasks,
            'showTaskActionColumn' => $showTaskActionColumn,
            'parentTaskId' => $subtask->id,
            'depth' => $depth + 1,
            'search' => $search ?? null,
        ])
    @endif
@endforeach
