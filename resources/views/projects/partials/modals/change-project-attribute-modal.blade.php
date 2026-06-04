<div id="project-change-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" data-default-date="{{ now(config('constants.timezone'))->toDateString() }}">
    <div class="absolute inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-change-modal-close></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-lg">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 class="text-2xl font-semibold text-bgray-900 dark:text-white" data-project-change-title>Change Project Value</h3>
                        <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300" data-project-change-description>
                            Select an option to continue.
                        </p>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" data-project-change-modal-close>
                        ✕
                    </button>
                </div>

                <form class="flex flex-col" data-project-change-form>
                    <input type="hidden" data-project-change-value>

                    <div class="space-y-5 px-6 py-6 sm:px-7">
                        <div class="rounded-xl border border-bgray-200 bg-bgray-50 px-4 py-3 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-3 w-3 rounded-full" data-project-change-selected-color></span>
                                <span class="text-sm font-semibold text-bgray-900 dark:text-white" data-project-change-selected-name>No Option Selected</span>
                            </div>
                        </div>

                        <div>
                            <label for="project_change_date" class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Date <x-red-star /></label>
                            <input type="text" id="project_change_date" name="change_date" class="datepicker w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-format="{{ $globalDateFormat }}" placeholder="Select a date" autocomplete="off" required>
                            <p class="mt-1 hidden text-xs text-error-300" data-project-change-min-date-hint></p>
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-change-error-for="change_date"></p>
                        </div>

                        <div>
                            <div class="mb-2.5 flex items-center justify-between gap-3">
                                <label for="project_change_remarks" class="block text-left text-sm text-bgray-700 dark:text-bgray-50">Remark</label>
                                <span class="text-xs font-medium text-bgray-400 dark:text-bgray-300"><span data-project-change-remarks-count>0</span>/150</span>
                            </div>
                            <textarea id="project_change_remarks" name="remarks" rows="4" maxlength="150" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Add a note for this change"></textarea>
                            <p class="mt-1 text-sm text-error-300 hidden" data-project-change-error-for="remarks"></p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="rounded-lg border border-bgray-300 bg-white px-6 py-3 text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-change-modal-close>
                            Cancel
                        </button>

                        <button type="submit" class="rounded-lg bg-success-300 px-6 py-3 text-white transition duration-200 hover:bg-success-400" data-project-change-submit>
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
