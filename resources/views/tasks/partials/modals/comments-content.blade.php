<div class="flex h-full flex-col" data-task-comments-root data-task-id="{{ $task->id }}" data-total-comments="{{ $totalComments }}">

    {{-- Comments List --}}
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <div
            class="min-h-0 max-h-[50vh] flex-1 overflow-y-auto px-6 py-6"
            data-task-comments-scroll
        >
            @include('tasks.partials.modals.comment-items', [
                'comments' => $comments
            ])
        </div>

        {{-- Comment Form --}}
        <div class="shrink-0 border-t border-bgray-200 bg-white px-6 py-5 pb-7 dark:border-darkblack-400 dark:bg-darkblack-600 sm:pb-6">
            <form
                method="POST"
                action="{{ route('tasks.comments.store', $task) }}"
                data-task-comment-form
            >
                @csrf

                <div class="flex flex-col gap-3">
                    <textarea
                        id="task-comment-message"
                        name="comment"
                        rows="3"
                        class="w-full rounded-xl border border-bgray-300 bg-white px-4 py-3 text-sm text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                        placeholder="Write a comment..."
                        data-task-comment-input
                    ></textarea>

                    <p
                        class="hidden text-sm text-error-300"
                        data-task-comment-error
                    ></p>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60"
                            data-task-comment-submit
                        >
                            Send
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
