<div class="modal fixed inset-0 z-[60] hidden overflow-y-auto" data-project-checklist-modal>
    <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-checklist-close></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-7xl">
            <div class="overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex flex-col gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:flex-row sm:items-start sm:justify-between sm:px-7">
                    <div class="min-w-0">
                        <h3 class="mt-2 text-xl font-semibold text-bgray-900 dark:text-white">
                            Manage Project Checklists
                        </h3>
                        <div class="mt-2 flex items-center gap-3 text-sm text-bgray-700 dark:text-bgray-300">
                            <x-user-avatar name="Choose a team member" class="h-9 w-9 flex-shrink-0 text-xs" data-project-checklist-member-avatar />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-bgray-900 dark:text-white" data-project-checklist-member-name>Choose a team member</p>
                                <p class="truncate" data-project-checklist-member-meta>Drag templates into the workspace and tailor the questions before saving.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-full border border-bgray-200 bg-bgray-50 px-3 py-1.5 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            Assigned: <span data-project-checklist-count>0</span>
                        </div>

                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-checklist-close>
                            ✕
                        </button>
                    </div>
                </div>

                <div class="grid h-[82vh] max-h-[82vh] gap-0 overflow-hidden xl:grid-cols-[minmax(0,1.9fr)_minmax(320px,0.95fr)]">
                    <div class="flex min-h-0 flex-col border-b border-bgray-200 p-6 dark:border-darkblack-400 xl:border-b-0 xl:border-r">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">Assigned Checklists</h4>
                            </div>

                            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-medium text-success-500 transition duration-200 hover:border-success-300 hover:bg-success-100 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300" data-project-checklist-add-blank>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Add Blank Checklist</span>
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto rounded-2xl border border-dashed border-success-200 bg-success-50/30 p-4 pr-3 dark:border-success-900/30 dark:bg-darkblack-500/20">
                            <div class="space-y-4" data-project-checklist-workspace></div>
                        </div>
                    </div>

                    <aside class="flex min-h-0 flex-col overflow-hidden bg-bgray-50/60 p-6 dark:bg-darkblack-500/40">
                        <div class="mb-5">
                            <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">Checklist Library</h4>
                        </div>

                        <label class="relative mb-4 block">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-bgray-400">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 013.93 9.35l3.61 3.61a1 1 0 01-1.414 1.414l-3.61-3.61A5.5 5.5 0 118.5 3zm0 2a3.5 3.5 0 100 7 3.5 3.5 0 000-7z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <input type="text" class="w-full rounded-xl border border-bgray-200 bg-white py-3 pl-11 pr-4 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" placeholder="Search checklist library..." data-project-checklist-library-search>
                        </label>

                        <div class="min-h-0 flex-1 overflow-y-auto pr-1 [scrollbar-gutter:stable]" data-project-checklist-library></div>
                    </aside>
                </div>

                <div class="flex flex-col gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:flex-row sm:items-center sm:justify-end sm:px-7">
                    <div class="flex w-full items-center justify-end gap-3 sm:w-auto">
                        <button type="button" class="inline-flex items-center justify-center rounded-lg border border-bgray-200 px-4 py-2 text-sm font-medium text-bgray-700 transition duration-200 hover:border-bgray-300 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500" data-project-checklist-close>
                            Cancel
                        </button>
                        <button type="button" class="inline-flex items-center justify-center rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-70" data-project-checklist-save>
                            Save Checklists
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
