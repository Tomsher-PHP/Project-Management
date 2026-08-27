@php
    $colorClass = match ($note->color) {
        'yellow' => 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/50',
        'green' => 'bg-emerald-50/90 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/50',
        'blue' => 'bg-sky-50/90 dark:bg-sky-950/40 border-sky-200 dark:border-sky-800/50',
        'purple' => 'bg-purple-50/90 dark:bg-purple-950/40 border-purple-200 dark:border-purple-800/50',
        'pink' => 'bg-rose-50/90 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/50',
        'orange' => 'bg-orange-50/90 dark:bg-orange-950/40 border-orange-200 dark:border-orange-800/50',
        'gray' => 'bg-slate-100/90 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700/50',
        default => 'bg-white dark:bg-darkblack-600 border-bgray-200 dark:border-darkblack-400',
    };
@endphp

<div class="note-card group relative flex flex-col justify-between rounded-2xl border p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg cursor-grab active:cursor-grabbing {{ $colorClass }}" draggable="true" data-note-id="{{ $note->id }}" data-note-pinned="{{ $note->is_pinned ? '1' : '0' }}" data-note-archived="{{ $note->is_archived ? '1' : '0' }}" data-note-color="{{ $note->color ?? '' }}" data-project-id="{{ $note->project_id ?? '' }}" data-task-id="{{ $note->task_id ?? '' }}">
    <div>
        <!-- Top row: Title and Pin button -->
        <div class="mb-3 flex items-start justify-between gap-3">
            <h4 class="note-card-title text-base font-semibold text-bgray-900 dark:text-white line-clamp-2 break-words">
                {{ $note->title ?: 'Untitled Note' }}
            </h4>

            <button type="button" data-action="pin" title="{{ $note->is_pinned ? 'Unpin note' : 'Pin note' }}" class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-bgray-400 transition hover:bg-black/5 hover:text-amber-500 dark:hover:bg-white/10 {{ $note->is_pinned ? 'text-amber-500' : 'opacity-0 group-hover:opacity-100' }}">
                <svg class="h-4 w-4" fill="{{ $note->is_pinned ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button>
        </div>

        <!-- Note Content HTML Preview -->
        @if ($note->content)
            <div class="note-card-content prose dark:prose-invert max-w-none text-sm text-bgray-700 dark:text-bgray-200 line-clamp-6 break-words">
                {!! $note->content !!}
            </div>
        @else
            <div class="note-card-content text-sm italic text-bgray-400 dark:text-bgray-500">
                No content
            </div>
        @endif


    </div>

    <!-- Card Footer / Actions -->
    <div class="mt-5 flex items-center justify-between border-t border-black/5 pt-3 dark:border-white/5">
        <span class="text-xs text-bgray-500 dark:text-bgray-400" title="{{ $note->updated_at?->format('F j, Y g:i A') }}">
            {{ $note->updated_at?->diffForHumans() }}
        </span>

        <div class="flex items-center gap-1 opacity-90 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
            <!-- Edit button -->
            <button type="button" data-action="edit" title="Edit note" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-bgray-500 hover:bg-black/5 hover:text-bgray-900 dark:text-bgray-400 dark:hover:bg-white/10 dark:hover:text-white">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </button>

            <!-- Archive button -->
            <button type="button" data-action="archive" title="{{ $note->is_archived ? 'Unarchive note' : 'Archive note' }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-bgray-500 hover:bg-black/5 hover:text-bgray-900 dark:text-bgray-400 dark:hover:bg-white/10 dark:hover:text-white">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm2-2h10V4a2 2 0 00-2-2H9a2 2 0 00-2 2v2z" />
                </svg>
            </button>

            <!-- Delete button -->
            <button type="button" data-action="delete" title="Delete note" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-bgray-500 hover:bg-red-50 hover:text-red-600 dark:text-bgray-400 dark:hover:bg-red-950/30 dark:hover:text-red-400">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</div>
