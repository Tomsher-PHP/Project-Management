@php
    $selectedSortLabel = ! empty($selectedKanbanSort) && isset($kanbanSortOptions[$selectedKanbanSort])
        ? $kanbanSortOptions[$selectedKanbanSort]
        : ($kanbanSortOptions[\App\Services\TaskServices::KANBAN_SORT_RECOMMENDED] ?? 'Recommended');
@endphp

<div class="relative min-w-[220px] shrink-0" data-kanban-sort-dropdown data-selected-sort="{{ $selectedKanbanSort ?? '' }}">
    <button type="button" class="inline-flex h-10 w-full items-center justify-between gap-3 rounded-lg border border-bgray-400 bg-white px-4 text-sm text-[#111653] shadow-[var(--workspace-soft-shadow)] transition hover:border-[#d7e3f6] hover:bg-[#fbfdff] dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400" data-kanban-sort-trigger aria-haspopup="menu" aria-expanded="false">
        <span class="inline-flex min-w-0 items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-bgray-700 dark:text-bgray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M6 4a1 1 0 0 1 1 1v8.586l1.293-1.293a1 1 0 1 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 1.414-1.414L5 13.586V5a1 1 0 0 1 1-1Zm8 12a1 1 0 0 1-1-1V6.414l-1.293 1.293a1 1 0 1 1-1.414-1.414l3-3a1 1 0 0 1 1.414 0l3 3a1 1 0 1 1-1.414 1.414L15 6.414V15a1 1 0 0 1-1 1Z" />
            </svg>
            <span class="shrink-0 font-medium text-bgray-700 dark:text-bgray-300">Sort By:</span>
            <span class="truncate font-extrabold text-[#111653] dark:text-bgray-50" data-kanban-sort-label>{{ $selectedSortLabel }}</span>
        </span>

        <svg class="h-4 w-4 shrink-0 text-bgray-500 dark:text-bgray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div class="absolute right-0 top-12 z-20 hidden w-full overflow-hidden rounded-lg bg-white shadow-lg dark:bg-darkblack-500" data-kanban-sort-menu>
        <ul class="max-h-72 overflow-y-auto">
            @foreach ($kanbanSortOptions as $sortValue => $sortLabel)
                <li>
                    <button type="button" class="flex w-full items-center justify-between px-5 py-2 text-left text-sm font-semibold text-bgray-900 transition hover:bg-bgray-100 dark:text-white hover:dark:bg-darkblack-600" data-kanban-sort-option data-value="{{ $sortValue }}" data-label="{{ $sortLabel }}">
                        <span class="@if (($selectedKanbanSort ?? null) === $sortValue) text-success-400 dark:text-success-300 @endif">
                            {{ $sortLabel }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>
