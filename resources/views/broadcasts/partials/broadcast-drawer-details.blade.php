@php
    $dateFormat = $globalDateFormat ?? config('constants.date_format', 'd M Y');
    $timeFormat = $globalTimeFormat ?? config('constants.time_format', 'h:i A');
    $categoryColor = $broadcast->category?->color ?? '#10B981';

    $priorityConfig = match ($broadcast->priority) {
        \App\Models\Broadcast::PRIORITY_URGENT => [
            'label' => 'Urgent',
            'class' => 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
        ],
        \App\Models\Broadcast::PRIORITY_IMPORTANT => [
            'label' => 'Important',
            'class' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
        ],
        default => [
            'label' => 'Normal',
            'class' => 'bg-bgray-100 text-bgray-700 border border-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300 dark:border-darkblack-400',
        ],
    };
@endphp

<div id="broadcast-drawer-details-view" class="space-y-5">
    <!-- Back Navigation -->
    <div>
        <button type="button" data-broadcast-back-to-list class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-sm transition hover:bg-bgray-50 hover:text-bgray-900 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Back to Broadcasts</span>
        </button>
    </div>

    <!-- Header / Meta -->
    <div class="space-y-3">
        <div class="flex items-center gap-2 flex-wrap">
            @if ($broadcast->category)
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $categoryColor }}1A; color: {{ $categoryColor }}; border: 1px solid {{ $categoryColor }}40;">
                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $categoryColor }};"></span>
                    {{ $broadcast->category->name }}
                </span>
            @endif

            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $priorityConfig['class'] }}">
                {{ $priorityConfig['label'] }}
            </span>
        </div>

        <h2 class="text-lg font-bold text-bgray-900 dark:text-white leading-snug">
            {{ $broadcast->title }}
        </h2>

        <div class="grid grid-cols-2 gap-3 rounded-xl bg-bgray-50 p-3 text-xs dark:bg-darkblack-500">
            <div>
                <span class="text-bgray-500 dark:text-bgray-400">Published:</span>
                <p class="font-semibold text-bgray-900 dark:text-white mt-0.5">
                    @if ($broadcast->published_at)
                        {{ $broadcast->published_at->format($dateFormat) }} {{ $broadcast->published_at->format($timeFormat) }}
                    @else
                        {{ $broadcast->created_at->format($dateFormat) }}
                    @endif
                </p>
            </div>
            <div>
                <span class="text-bgray-500 dark:text-bgray-400">Date Range:</span>
                <p class="font-semibold text-bgray-900 dark:text-white mt-0.5">
                    @if ($broadcast->start_date && $broadcast->end_date)
                        {{ $broadcast->start_date->format($dateFormat) }} – {{ $broadcast->end_date->format($dateFormat) }}
                    @elseif ($broadcast->start_date)
                        From {{ $broadcast->start_date->format($dateFormat) }}
                    @elseif ($broadcast->end_date)
                        Until {{ $broadcast->end_date->format($dateFormat) }}
                    @else
                        No date restriction
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="space-y-2">
        <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Announcement</h4>
        <div class="prose max-w-none text-sm text-bgray-800 dark:text-bgray-200 dark:prose-invert rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
            {!! $broadcast->content !!}
        </div>
    </div>

    <!-- Attachments -->
    @if ($broadcast->attachments->isNotEmpty())
        <div class="space-y-2 pt-2 border-t border-bgray-200 dark:border-darkblack-400">
            <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                Attachments ({{ $broadcast->attachments->count() }})
            </h4>
            <div class="space-y-2">
                @foreach ($broadcast->attachments as $attachment)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-bgray-200 bg-white p-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-400/10 dark:text-success-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-bgray-900 dark:text-white truncate" title="{{ $attachment->original_name ?: $attachment->file_name }}">
                                    {{ $attachment->original_name ?: $attachment->file_name }}
                                </p>
                                <p class="text-[11px] text-bgray-500 dark:text-bgray-400">
                                    {{ number_format($attachment->file_size / 1024, 1) }} KB
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('broadcasts.drawer.attachments.download', [$broadcast, $attachment]) }}"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-bgray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-success-500 shadow-sm transition hover:bg-success-50 hover:text-success-600 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-success-400 dark:hover:bg-darkblack-500"
                            download>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Download</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

