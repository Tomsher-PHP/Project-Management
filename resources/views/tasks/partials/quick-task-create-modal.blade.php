<div class="modal fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-quick-task-create-modal id="quick_task_create_modal">
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-quick-task-create-close></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-lg rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600" data-quick-task-create-panel>
            <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-3 dark:border-darkblack-400">
                <h3 class="text-xl font-semibold text-bgray-900 dark:text-white">
                    Create Quick Task
                </h3>

                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-quick-task-create-close aria-label="Close modal">
                    ✕
                </button>
            </div>

            <form
                class="space-y-5 px-6 py-5"
                data-quick-task-create-form
                data-store-url="{{ route('tasks.quick-task.store') }}"
                data-default-project-id="{{ $quickTaskDefaultProject?->id ?? '' }}"
                data-default-project-name="{{ $quickTaskDefaultProject?->name ?? '' }}"
                data-default-project-code="{{ $quickTaskDefaultProject?->project_code ?: '--' }}"
                autocomplete="off"
            >
                <div>
                    <label for="quick_task_project_id" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                        Project <x-red-star />
                    </label>
                    <select
                        id="quick_task_project_id"
                        name="project_id"
                        class="tom-select-lazy w-full"
                        data-route="{{ route('tasks.quick-task.projects') }}"
                        data-sort="0"
                        data-placeholder="Search your project here.."
                        data-allow-empty-option="true"
                    >
                        @if (isset($quickTaskDefaultProject) && $quickTaskDefaultProject)
                            <option value="{{ $quickTaskDefaultProject->id }}" selected data-data='@json(['subtype' => $quickTaskDefaultProject->project_code ?: '--'])'>
                                {{ $quickTaskDefaultProject->name }}
                            </option>
                        @endif
                    </select>
                    <p class="mt-1 hidden text-xs text-red-500" data-quick-task-create-error="project_id"></p>
                </div>

                <div>
                    <label for="quick_task_name" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-300">
                        Task Name <x-red-star />
                    </label>
                    <input
                        id="quick_task_name"
                        type="text"
                        name="name"
                        maxlength="255"
                        autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                        placeholder="Enter task name"
                    >
                    <p class="mt-1 hidden text-xs text-red-500" data-quick-task-create-error="name"></p>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 pt-4 dark:border-darkblack-400">
                    <button type="button" class="rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-medium text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-darkblack-300" data-quick-task-create-close>
                        Cancel
                    </button>

                    <button type="submit" class="rounded-lg border border-success-300 bg-white px-4 py-2.5 text-sm font-semibold text-success-400 transition duration-200 hover:bg-success-50 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-darkblack-500 dark:text-success-300 dark:hover:bg-darkblack-400" data-quick-task-submit="save" data-default-label="Save" data-loading-label="Saving...">
                        Save
                    </button>

                    <button type="button" class="rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60" data-quick-task-submit="save_and_start" data-default-label="Save &amp; Start" data-loading-label="Starting...">
                        Save &amp; Start
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
