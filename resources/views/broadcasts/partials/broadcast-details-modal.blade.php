@php
    use App\Models\Broadcast;
@endphp

<div class="overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-2xl dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="flex items-start justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
        <div class="pr-5">
            <h2 class="text-xl font-bold text-bgray-900 dark:text-white">{{ $broadcast->title }}</h2>
            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">Created by {{ $broadcast->creator?->name ?? 'Unknown' }} on {{ $broadcast->created_at->format('d M Y') }}</p>
        </div>
        <button type="button" data-broadcast-modal-close class="rounded-md p-2 text-bgray-600 hover:bg-bgray-100 dark:text-bgray-300 dark:hover:bg-darkblack-400" aria-label="Close modal">✕</button>
    </div>

    <div class="max-h-[72vh] overflow-y-auto px-6 py-5">
        <div class="grid grid-cols-2 gap-4 rounded-xl bg-bgray-50 p-4 dark:bg-darkblack-500 sm:grid-cols-4">
            <div><p class="text-xs text-bgray-500 dark:text-bgray-400">Category</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->category?->name ?? '—' }}</p></div>
            <div><p class="text-xs text-bgray-500 dark:text-bgray-400">Priority</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ Broadcast::priorityOptions()[$broadcast->priority] ?? 'Unknown' }}</p></div>
            <div><p class="text-xs text-bgray-500 dark:text-bgray-400">Status</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ Broadcast::statusOptions()[$broadcast->status] ?? 'Unknown' }}</p></div>
            <div><p class="text-xs text-bgray-500 dark:text-bgray-400">Audience</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ Broadcast::audienceOptions()[$broadcast->audience_type] ?? 'Unknown' }}</p></div>
            <div><p class="text-xs text-bgray-500 dark:text-bgray-400">Starts</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->start_date?->format('d M Y') ?? 'Immediately' }}</p></div>
            <div><p class="text-xs text-bgray-500 dark:text-bgray-400">Ends</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->end_date?->format('d M Y') ?? 'No expiry' }}</p></div>
            <div class="col-span-2"><p class="text-xs text-bgray-500 dark:text-bgray-400">Published</p><p class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">{{ $broadcast->published_at?->format('d M Y, h:i A') ?? 'Not published' }}</p></div>
        </div>

        <div class="mt-6">
            <h3 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Content</h3>
            <div class="prose max-w-none text-bgray-700 dark:prose-invert dark:text-bgray-300">{!! $broadcast->content !!}</div>
        </div>

        @if ($broadcast->audience_type === Broadcast::AUDIENCE_TEAMS && $teamNames->isNotEmpty())
            <div class="mt-6"><h3 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Selected Teams</h3><p class="text-sm text-bgray-700 dark:text-bgray-300">{{ $teamNames->join(', ') }}</p></div>
        @elseif ($broadcast->audience_type === Broadcast::AUDIENCE_USERS && $broadcast->recipients->isNotEmpty())
            <div class="mt-6"><h3 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Selected Users</h3><p class="text-sm text-bgray-700 dark:text-bgray-300">{{ $broadcast->recipients->pluck('name')->join(', ') }}</p></div>
        @endif

        @if ($broadcast->attachments->isNotEmpty())
            <div class="mt-6">
                <h3 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">Attachments</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($broadcast->attachments as $attachment)
                        <a href="{{ route('broadcasts.attachments.download', [$broadcast, $attachment]) }}" class="inline-flex items-center rounded-lg border border-bgray-200 px-3 py-2 text-sm font-medium text-success-500 hover:bg-success-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500">{{ $attachment->original_name ?: $attachment->file_name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="flex justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
        <button type="button" data-broadcast-modal-close class="rounded-lg border border-bgray-300 px-4 py-2 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-400">Close</button>
    </div>
</div>

