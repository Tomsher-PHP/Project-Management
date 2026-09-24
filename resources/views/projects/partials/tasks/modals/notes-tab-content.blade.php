<div class="space-y-5">
    @can('task.add_notes_files')
        <div data-project-task-manage-note-container data-store-url="{{ route('tasks.notes.store', $task) }}">
            @include('tasks.partials.note-files-fields', [
                'noteInputId' => 'project_task_manage_note_input',
                'noteEditorId' => 'project_task_manage_note_editor',
                'attachmentsInputId' => 'project_task_manage_attachments_input',
                'selectedFilesContainerId' => 'project_task_manage_selected_files',
                'errorPrefix' => 'project-task-manage',
                'sectionTitle' => 'Add Note & Files',
            ])

            <div class="mt-3 flex justify-end">
                <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-xs font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60" data-project-task-manage-note-submit>
                    Add Note & Files
                </button>
            </div>
        </div>
    @endcan

    <div class="space-y-4">
        <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-700 dark:text-bgray-300">
            Existing Notes & Files ({{ $taskNotes->count() }})
        </h4>

        <div class="space-y-4" data-project-task-manage-notes-list>
            @forelse ($taskNotes as $note)
                @include('tasks.partials.task-note-card', [
                    'note' => $note,
                    'canRemove' => auth()->user()?->can('task.remove_notes_files') ?? false,
                ])
            @empty
                <div class="rounded-xl border border-dashed border-bgray-300 px-6 py-8 text-center text-xs text-gray-400 dark:border-darkblack-400">
                    No notes or files added to this task yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
