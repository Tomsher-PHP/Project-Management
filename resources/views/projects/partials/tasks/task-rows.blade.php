@php
    $showEmptyState = $showEmptyState ?? true;
    $showTaskActionColumn = $showTaskActionColumn ?? auth()->user()?->can('task.delete') || ($project->project_flow !== 'linear' && auth()->user()?->can('task.move'));
@endphp

@forelse ($tasks as $task)
    @include('projects.partials.tasks.task-row', [
        'project' => $project,
        'group' => $group,
        'task' => $task,
        'showTaskActionColumn' => $showTaskActionColumn,
    ])

    @include('projects.partials.tasks.subtask-rows', [
        'project' => $project,
        'group' => $group,
        'tasks' => $task->childTasks,
        'showTaskActionColumn' => $showTaskActionColumn,
        'parentTaskId' => $task->id,
        'depth' => 1,
    ])
@empty
    @if ($showEmptyState)
        <tr>
            <td colspan="{{ $showTaskActionColumn ? 8 : 7 }}" class="px-6 py-10 text-center">
                <div class="mx-auto max-w-md rounded-2xl border border-dashed border-bgray-300 bg-bgray-50 px-6 py-8 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <p class="text-base font-semibold text-bgray-900 dark:text-white">No tasks</p>
                    <p class="mt-2 text-sm text-bgray-600 dark:text-bgray-300">
                        This group is ready, but there are no tasks to display yet.
                    </p>
                </div>
            </td>
        </tr>
    @endif
@endforelse
