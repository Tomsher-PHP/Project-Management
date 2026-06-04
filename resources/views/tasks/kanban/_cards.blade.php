@foreach ($tasks as $task)
    @include('tasks.kanban._card', ['task' => $task])
@endforeach
