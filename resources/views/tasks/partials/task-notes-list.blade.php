<div id="task-notes-history">
    <div class="flex items-center justify-between gap-4">
        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Notes History</h3>
        <div class="flex flex-wrap items-center justify-end gap-3">
            <span id="task-notes-count" data-total="{{ $taskNotes->total() }}" class="text-sm text-bgray-700 dark:text-bgray-300">{{ $taskNotes->total() }} Notes</span>

            @if (!empty($canCreate))
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400" data-task-note-modal-open>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Note & Files</span>
                </button>
            @endif
        </div>
    </div>

    <div id="task-notes-list" class="mt-6 space-y-5">
        @forelse ($taskNotes as $note)
            @include('tasks.partials.task-note-card', ['note' => $note, 'canRemove' => $canRemove])
        @empty
            <div id="task-notes-empty-state" class="rounded-xl border border-dashed border-bgray-300 px-6 py-10 text-center text-sm text-gray-400 dark:border-darkblack-400">
                No task notes and files added yet.
            </div>
        @endforelse
    </div>

    @if ($taskNotes->hasPages())
        <div class="mt-6">
            {{ $taskNotes->appends(['tab' => 'notes'])->links() }}
        </div>
    @endif
</div>
