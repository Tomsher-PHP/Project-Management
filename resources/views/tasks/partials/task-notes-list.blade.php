<div id="task-notes-history">
    <div class="flex items-center justify-between gap-4">
        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Notes History</h3>
        <span id="task-notes-count" data-total="{{ $taskNotes->total() }}" class="text-sm text-bgray-500 dark:text-bgray-300">{{ $taskNotes->total() }} Notes</span>
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
            {{ $taskNotes->links() }}
        </div>
    @endif
</div>
