@foreach ($boardStatuses as $status)
    @php
        $column = $tasksByStatus[$status->id] ?? [
            'tasks' => collect(),
            'taskIds' => [],
            'total' => 0,
            'hasMore' => false,
            'nextPage' => null,
        ];
    @endphp

    <div class="flex w-[282px] flex-shrink-0 flex-col overflow-hidden rounded-[12px] border border-[#edf1f7] bg-white shadow-[0_8px_22px_rgba(18,25,95,0.05)] dark:border-darkblack-400 dark:bg-darkblack-500">

        <!-- Column Header -->
        <div class="border-b border-[#edf1f7] bg-white px-3.5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="h-3.5 w-3.5 flex-shrink-0 rounded-full shadow-[0_0_0_4px_rgba(8,102,255,0.08)]" style="background-color: {{ $status->color }};"></span>
                    <h6 class="mb-0 truncate text-[12px] font-extrabold uppercase tracking-normal text-[#111653] dark:text-white">
                        {{ $status->name }}
                    </h6>
                </div>

                <span class="inline-flex min-w-6 items-center justify-center rounded-md bg-[#f1f3f7] px-1.5 py-0.5 text-[12px] font-extrabold text-[#111653] dark:bg-darkblack-600 dark:text-blue-200" data-kanban-total-count>
                    {{ $column['total'] ?? 0 }}
                </span>
            </div>
        </div>

        <!-- Column Body -->
        <div class="kanban-board flex h-full flex-col gap-2.5 overflow-y-auto overflow-x-hidden bg-white px-3 pb-0 pt-3 dark:bg-darkblack-600" data-status-id="{{ $status->id }}" data-task-ids='@json($column['taskIds'] ?? [])' data-next-page="{{ $column['nextPage'] ?? '' }}" data-has-more="{{ !empty($column['hasMore']) ? 'true' : 'false' }}" data-loading="false">
            @include('tasks.kanban._cards', ['tasks' => $column['tasks'] ?? collect(), 'status' => $status])

            <div class="hidden py-2 text-center text-xs text-gray-400 dark:text-gray-500" data-kanban-load-indicator>
                Loading more...
            </div>

            <button type="button" class="mt-auto inline-flex h-11 items-center justify-center gap-2 border-t border-[#edf1f7] text-[13px] font-extrabold text-[#0866ff] transition hover:bg-[#f7faff]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" />
                </svg>
                <span>Add Task</span>
            </button>
        </div>
    </div>
@endforeach
