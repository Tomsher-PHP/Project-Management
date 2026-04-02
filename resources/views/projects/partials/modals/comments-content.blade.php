@php
    $totalComments = $comments->count();
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-success-400">Project Comments</p>
            <h3 class="mt-2 text-2xl font-bold text-bgray-900 dark:text-white">{{ $project->name }}</h3>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">
                {{ $totalComments }} {{ \Illuminate\Support\Str::plural('comment', $totalComments) }} available for this project.
            </p>
        </div>

        <button type="button" data-project-insights-close class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-bgray-100 text-bgray-600 transition hover:bg-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.5 4.5L4.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                <path d="M4.5 4.5L13.5 13.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-6">
        <div class="space-y-4">
            @forelse ($comments as $comment)
                <article class="rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="flex items-start gap-4">
                        <div class="h-11 w-11 shrink-0 overflow-hidden rounded-xl bg-bgray-100 dark:bg-darkblack-500">
                            <img src="{{ $comment->user?->profileImageUrl ?? asset(config('assets.images.default_avatar')) }}" alt="{{ $comment->user?->name ?? 'User' }}" class="h-full w-full object-cover" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $comment->user?->name ?? 'Unknown User' }}
                                    </p>
                                    <p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">
                                        {{ $comment->created_at?->timezone($globalTimezone)->format($globalDateFormat . ' ' . $globalTimeFormat) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 rounded-xl bg-bgray-50 px-4 py-3 text-sm leading-6 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200">
                                {!! nl2br(e($comment->comment)) !!}
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-bgray-300 px-6 py-12 text-center text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                    No comments have been added to this project yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
