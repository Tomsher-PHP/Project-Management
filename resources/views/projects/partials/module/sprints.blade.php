<div class="space-y-3 border-l-2 border-dashed border-bgray-200 pl-4 dark:border-darkblack-400 md:pl-6">
    @foreach ($modulePreviewSprints as $previewSprint)
        <div x-data="{ sprintOpen: true }" class="overflow-hidden rounded-2xl border border-bgray-200 bg-bgray-50 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="flex flex-col gap-3 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" class="inline-flex h-8 w-8 shrink-0 cursor-move items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $previewSprint['color'] }}"></span>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">{{ $previewSprint['name'] }}</p>
                            <span class="inline-flex rounded-full bg-bgray-100 px-2.5 py-1 text-xs font-medium text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">
                                {{ count($previewSprint['tasks']) }} tasks
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    @can('task.create')
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-1.5 text-sm font-medium text-success-400 transition duration-200 hover:border-success-300 hover:bg-success-300 hover:text-white dark:border-success-900/30 dark:bg-darkblack-600 dark:text-success-300 dark:hover:border-success-300 dark:hover:bg-success-300 dark:hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Task</span>
                        </button>
                    @endcan

                    @can('project_sprint.edit')
                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                            </svg>
                        </button>
                    @endcan

                    @can('project_sprint.delete')
                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 transition duration-200 hover:border-red-500 hover:bg-red-500 hover:text-white dark:border-red-900/40 dark:bg-darkblack-600 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-500 dark:hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    @endcan

                    <button type="button" @click="sprintOpen = !sprintOpen" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300">
                        <svg class="h-4 w-4 transition duration-200" :class="sprintOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <div x-show="sprintOpen" x-transition class="border-t border-bgray-200 px-4 py-4 dark:border-darkblack-400">
                <div class="space-y-3 border-l-2 border-dashed border-bgray-200 pl-4 dark:border-darkblack-400 md:ml-4 md:pl-5">
                    @include('projects.partials.module.tasks', ['previewTasks' => $previewSprint['tasks']])
                </div>
            </div>
        </div>
    @endforeach
</div>
