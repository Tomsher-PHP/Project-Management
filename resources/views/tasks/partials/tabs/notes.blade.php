@php
    $canCreate = $canCreate ?? auth()->user()->can('task.add_notes_files');
    $canRemove = $canRemove ?? auth()->user()->can('task.remove_notes_files');
    $canEditAny = auth()->check();
@endphp

<div class="w-full">
    @if (!$canCreate)
        <div class="mb-6">
            <p class="rounded-lg border border-dashed border-bgray-300 px-4 py-3 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                You have view-only access to task notes.
            </p>
        </div>
    @endif

    @include('tasks.partials.modals.task-note-modal')
</div>

<div class="w-full">
    @include('tasks.partials.task-notes-list', [
        'task' => $task,
        'taskNotes' => $taskNotes,
        'canRemove' => $canRemove,
        'canCreate' => $canCreate,
    ])
</div>
