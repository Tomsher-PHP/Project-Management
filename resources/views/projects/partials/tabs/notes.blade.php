@php
    $isDeletedProjectView = $project->trashed();
    $canCreate = !$isDeletedProjectView && auth()->user()->can('project.add_notes_files');
    $canRemove = !$isDeletedProjectView && auth()->user()->can('project.remove_notes_files');
    $canEditAny = !$isDeletedProjectView && auth()->check();
@endphp

<div class="w-full">
    @if (!$canCreate)
        <div class="mb-6">
            <p class="rounded-lg border border-dashed border-bgray-300 px-4 py-3 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                You have view-only access to project notes.
            </p>
        </div>
    @endif

    @include('projects.partials.modals.project-note-modal')
</div>

<div class="w-full">
    @include('projects.partials.project-notes-list', [
        'projectNotes' => $projectNotes,
        'canRemove' => $canRemove,
        'canCreate' => $canCreate,
        'isDeletedProjectView' => $isDeletedProjectView,
    ])
</div>
