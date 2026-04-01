@foreach ($previewTasks as $taskName)
    <div class="flex flex-col gap-3 rounded-xl border border-bgray-200 bg-white px-4 py-3 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 md:flex-row md:items-center md:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <button type="button" class="inline-flex h-8 w-8 shrink-0 cursor-move items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                </svg>
            </button>

            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-bgray-900 dark:text-white">{{ $taskName }}</p>
                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Task row preview with inline actions and drag handle</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @can('task.edit')
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                    </svg>
                </button>
            @endcan

            @can('task.delete')
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 transition duration-200 hover:border-red-500 hover:bg-red-500 hover:text-white dark:border-red-900/40 dark:bg-darkblack-600 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-500 dark:hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            @endcan
        </div>
    </div>
@endforeach
