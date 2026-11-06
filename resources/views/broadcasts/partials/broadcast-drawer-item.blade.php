@php
    $today = $today ?? now()->toDateString();
    $isUpcoming = $broadcast->start_date && $broadcast->start_date->format('Y-m-d') > $today;
    $dateFormat = $globalDateFormat ?? config('constants.date_format', 'd M Y');
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

    if ($broadcast->start_date && $broadcast->end_date) {
        $dateText = $broadcast->start_date->format($dateFormat) === $broadcast->end_date->format($dateFormat) ? $broadcast->start_date->format($dateFormat) : $broadcast->start_date->format($dateFormat) . ' – ' . $broadcast->end_date->format($dateFormat);
    } elseif ($broadcast->start_date) {
        $dateText = 'From ' . $broadcast->start_date->format($dateFormat);
    } elseif ($broadcast->end_date) {
        $dateText = 'Until ' . $broadcast->end_date->format($dateFormat);
    } else {
        $dateText = 'Published ' . ($broadcast->published_at ? $broadcast->published_at->format($dateFormat) : $broadcast->created_at->format($dateFormat));
    }
@endphp

<div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm transition hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-500 dark:hover:border-success-300/40" data-broadcast-card="{{ $broadcast->id }}">
    <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
        <div class="flex items-center gap-2 flex-wrap">
            @if ($broadcast->category)
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $categoryColor }}1A; color: {{ $categoryColor }}; border: 1px solid {{ $categoryColor }}40;">
                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $categoryColor }};"></span>
                    {{ $broadcast->category->name }}
                </span>
            @endif

            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $priorityConfig['class'] }}">
                {{ $priorityConfig['label'] }}
            </span>
        </div>

        @if ($isUpcoming)
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 border border-amber-200 dark:bg-amber-400/10 dark:text-amber-400 dark:border-amber-400/20">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                Upcoming
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-[11px] font-semibold text-success-600 border border-success-200 dark:bg-success-400/10 dark:text-success-400 dark:border-success-400/20">
                <span class="h-1.5 w-1.5 rounded-full bg-success-300"></span>
                Current
            </span>
        @endif
    </div>

    <h3 class="text-sm font-bold text-bgray-900 dark:text-white line-clamp-2 mb-1.5">
        {{ $broadcast->title }}
    </h3>

    <div class="flex items-center gap-1.5 text-xs text-bgray-700 dark:text-bgray-300 mb-3">
        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>{{ $dateText }}</span>
    </div>

    <div class="flex items-center justify-between border-t border-bgray-100 pt-3 dark:border-darkblack-400">
        <div class="text-[11px] text-bgray-700 dark:text-bgray-300">
            @if (($broadcast->attachments_count ?? ($broadcast->relationLoaded('attachments') ? $broadcast->attachments->count() : 0)) > 0)
                <span class="inline-flex items-center gap-1 font-medium text-bgray-700 dark:text-bgray-300" title="{{ $broadcast->attachments_count ?? $broadcast->attachments->count() }} Attachment(s)">
                    <svg class="h-3.5 w-3.5 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span>{{ $broadcast->attachments_count ?? $broadcast->attachments->count() }}</span>
                </span>
            @endif
        </div>

        <button type="button" data-broadcast-view-details="{{ $broadcast->id }}" class="group/btn inline-flex items-center gap-1 text-xs font-semibold text-success-400 hover:text-success-500 dark:text-success-300 dark:hover:text-success-200 transition">
            <span>View Details</span>
            <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover/btn:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>
