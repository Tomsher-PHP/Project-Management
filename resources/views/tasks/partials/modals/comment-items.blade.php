<div class="space-y-4">
    @forelse ($comments as $comment)
        <article class="p-5">
            <div class="min-w-0">
                <div class="rounded-xl bg-bgray-50 px-4 py-3 text-sm leading-6 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200">
                    {!! nl2br(e($comment->comment)) !!}
                </div>

                <div class="mt-3 flex justify-end">
                    <div class="text-right">
                        <p class="text-xs font-semibold text-bgray-700 dark:text-bgray-100">
                            {{ $comment->user?->name ?? 'Unknown User' }}
                        </p>
                        <p class="text-[11px] font-medium text-bgray-500 dark:text-bgray-300">
                            {{ $comment->created_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}
                        </p>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-bgray-300 px-6 py-12 text-center text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
            No comments have been added to this task yet.
        </div>
    @endforelse
</div>
