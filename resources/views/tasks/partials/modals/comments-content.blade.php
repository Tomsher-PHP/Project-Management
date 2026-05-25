<div class="flex h-full flex-col" data-task-comments-root>
    <div class="flex items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-success-400">Task Comments</p>
            <h3 class="mt-2 text-2xl font-bold text-bgray-900 dark:text-white">{{ $task->name }}</h3>
            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                Showing the latest {{ $comments->count() }} of {{ $totalComments }} {{ \Illuminate\Support\Str::plural('comment', $totalComments) }}.
            </p>
        </div>

        <button type="button" data-task-insights-close class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-bgray-100 text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.5 4.5L4.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                <path d="M4.5 4.5L13.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </button>
    </div>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div class="min-h-0 max-h-[50vh] flex-1 overflow-y-auto px-6 py-6" data-task-comments-scroll>
            @include('tasks.partials.modals.comment-items', ['comments' => $comments])
        </div>

        <div class="border-t border-bgray-200 bg-white px-6 py-5 pb-7 dark:border-darkblack-400 dark:bg-darkblack-600 sm:pb-6">
            <form method="POST" action="{{ route('tasks.comments.store', $task) }}" data-task-comment-form>
                @csrf
                <div class="flex flex-col gap-3">
                    <textarea id="task-comment-message" name="comment" rows="3" class="w-full rounded-xl border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Write a comment..." data-task-comment-input></textarea>
                    <p class="hidden text-sm text-error-300" data-task-comment-error></p>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60" data-task-comment-submit>
                            Send
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
