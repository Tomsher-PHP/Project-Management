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
    <!-- Header / Meta -->
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
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

            <div data-broadcast-back-to-list class="[&>button]:!py-0.5 [&>button]:!px-2 [&>button]:!text-xs [&_svg]:!h-3.5 [&_svg]:!w-3.5">
                <x-back-button url="javascript:void(0);" label="Back" />
            </div>
        </div>

        <h2 class="text-lg font-bold text-bgray-900 dark:text-white leading-snug">
            {{ $broadcast->title }}
        </h2>

        <div class="grid grid-cols-2 gap-3 rounded-xl bg-bgray-50 p-3 text-xs dark:bg-darkblack-500">
            <div>
                <span class="text-bgray-700 dark:text-bgray-300">Published at:</span>
                <p class="font-semibold text-bgray-900 dark:text-white mt-0.5">
                    @if ($broadcast->published_at)
                        @appDateTime($broadcast->published_at)
                    @else
                        {{ $broadcast->created_at->format($dateFormat) }}
                    @endif
                </p>
            </div>
            <div>
                <span class="text-bgray-700 dark:text-bgray-300">Date Range:</span>
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
        <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">Announcement</h4>
        <div class="prose max-w-none text-sm text-bgray-800 dark:text-bgray-300 dark:prose-invert rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
            {!! $broadcast->content !!}
        </div>
    </div>

    <!-- Attachments -->
    @if ($broadcast->attachments->isNotEmpty())
        <div class="space-y-2 pt-2 border-t border-bgray-200 dark:border-darkblack-400">
            <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">
                Attachments ({{ $broadcast->attachments->count() }})
            </h4>
            <div class="space-y-2">
                @foreach ($broadcast->attachments as $attachment)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-bgray-200 bg-white p-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
                        <a href="{{ route('broadcasts.drawer.attachments.download', [$broadcast, $attachment]) }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 min-w-0 group hover:opacity-80 transition" title="View {{ $attachment->original_name ?: $attachment->file_name }}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-400/10 dark:text-success-400 group-hover:bg-success-100 dark:group-hover:bg-success-400/20">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-bgray-900 dark:text-white truncate group-hover:text-success-500 dark:group-hover:text-success-400">
                                    {{ $attachment->original_name ?: $attachment->file_name }}
                                </p>
                                <p class="text-[11px] text-bgray-700 dark:text-bgray-300">
                                    {{ number_format($attachment->file_size / 1024, 1) }} KB
                                </p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
