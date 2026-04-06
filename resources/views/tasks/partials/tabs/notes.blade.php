<div class="rounded-2xl border border-bgray-200 bg-white p-6 dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-bgray-400 dark:text-bgray-300">Notes & Files</p>
        <h3 class="mt-3 text-2xl font-bold text-bgray-900 dark:text-white">Task notes and files will live here.</h3>
        <p class="mt-3 text-sm leading-7 text-bgray-600 dark:text-bgray-300">
            This task detail page now includes a dedicated Notes & Files tab, but there is not yet a task-specific notes/files data model wired in the current codebase.
            Until that is added, use the linked project workspace for shared notes and attachments.
        </p>

        @if ($project && auth()->user()->can('view', $project))
            <div class="mt-5">
                <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-semibold text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-300 hover:text-white dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-success-300 dark:hover:text-white">
                    <span>Open Project Notes & Files</span>
                </a>
            </div>
        @endif
    </div>
</div>
