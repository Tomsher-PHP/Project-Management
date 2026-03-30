<div id="project-notes-history">
    <div class="flex items-center justify-between gap-4">
        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Notes History</h3>
        <span id="project-notes-count" data-total="{{ $projectNotes->total() }}" class="text-sm text-bgray-500 dark:text-bgray-300">{{ $projectNotes->total() }} Notes</span>
    </div>

    <div id="project-notes-list" class="mt-6 space-y-5">
        @forelse ($projectNotes as $note)
            @include('projects.partials.project-note-card', ['note' => $note, 'canRemove' => $canRemove])
        @empty
            <div id="project-notes-empty-state" class="rounded-xl border border-dashed border-bgray-300 px-6 py-10 text-center text-sm text-gray-400 dark:border-darkblack-400">
                No project notes and files added yet.
            </div>
        @endforelse
    </div>

    @if ($projectNotes->hasPages())
        <div class="mt-6">
            {{ $projectNotes->links() }}
        </div>
    @endif
</div>
