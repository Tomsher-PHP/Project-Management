<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-400">
            Date
        </p>

        <p class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
            {{ $projectTracking->date?->format('d M Y') ?? '--' }}
        </p>
    </div>

    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-400">
            Title
        </p>

        <p class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
            {{ $projectTracking->title }}
        </p>
    </div>

    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-400">
            Description
        </p>

        <div class="mt-1 whitespace-pre-line text-sm text-bgray-700 dark:text-bgray-300">
            {{ $projectTracking->description ?: '--' }}
        </div>
    </div>

    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-400">
            Attachments
        </p>

        @if ($projectTracking->attachments->isNotEmpty())
            <div class="mt-3 space-y-2">
                @foreach ($projectTracking->attachments as $attachment)
                    <a href="{{ $attachment->url }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between rounded-lg border border-bgray-200 px-4 py-3 text-sm transition hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500">
                        <span class="truncate text-bgray-800 dark:text-bgray-200">
                            {{ $attachment->original_name }}
                        </span>

                        <span class="ml-3 shrink-0 text-success-400">
                            View
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                No attachments
            </p>
        @endif
    </div>
</div>
