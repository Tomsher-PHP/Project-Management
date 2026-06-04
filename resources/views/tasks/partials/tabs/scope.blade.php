<div class="w-full pt-6">
    @if ($project)
        @include('projects.partials.scope-files', [
            'project' => $project,
            'heading' => 'Project Scope',
            'showUpload' => false,
            'showDelete' => false,
        ])
    @else
        <div class="rounded-xl border border-dashed border-bgray-300 px-6 py-10 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
            No project is linked to this task.
        </div>
    @endif
</div>
