<div id="task-insights-modal" class="fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto px-4 py-6">
    <div data-task-insights-overlay class="fixed inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-60"></div>

    <div class="relative flex min-h-[420px] max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-[8px] bg-white shadow-2xl dark:border dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex shrink-0 items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
            <div>
                <h3 class="text-lg font-bold text-bgray-900 dark:text-white" data-task-insights-title>
                    Task Activity
                </h3>
            </div>

            <button type="button" data-task-insights-close class="flex h-8 w-8 items-center justify-center rounded-md text-xl text-bgray-500 hover:bg-bgray-100 dark:hover:bg-darkblack-500" aria-label="Close modal">
                &times;
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div class="shrink-0 border-b border-bgray-200 px-6 dark:border-darkblack-400">
                <div class="flex items-center gap-6">

                    <button type="button" data-task-insights-tab="developer-notes" class="border-b-2 border-success-300 px-1 py-4 text-sm font-semibold text-success-400">
                        Completion Notes
                    </button>

                    <button type="button" data-task-insights-tab="comments" class="border-b-2 border-transparent px-1 py-4 text-sm font-semibold text-bgray-500 transition hover:text-success-400 dark:text-bgray-300">
                        Comments
                        <span class="ml-1 text-xs" data-task-insights-comments-count></span>
                    </button>

                </div>
            </div>

            <!-- Completion Notes: Default Active Tab -->
            <div class="flex min-h-0 flex-1 flex-col overflow-y-auto p-6" data-task-insights-panel="developer-notes">
                <div id="task-insights-developer-notes-content">
                    @include('tasks.partials.modals.dev-notes-modal')
                </div>
            </div>

            <!-- Comments: Loaded on Tab Click -->
            <div class="hidden min-h-0 flex-1 flex-col overflow-hidden" data-task-insights-panel="comments">
                <div id="task-insights-modal-content" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <div class="flex flex-1 items-center justify-center p-8 text-sm text-bgray-500">
                        Select Comments to load task comments.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
