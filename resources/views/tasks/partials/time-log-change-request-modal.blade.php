<div id="timeLogChangeRequestModal" class="modal modal-form fixed inset-0 z-[70] hidden items-center justify-center overflow-y-auto" data-time-log-change-request-modal>
    <div class="modal-close fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-time-log-change-request-overlay></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="relative z-10 w-full max-w-3xl">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-darkblack-600">
                <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h4 id="timeLogChangeRequestModalTitle" class="text-xl font-semibold text-bgray-900 dark:text-white">
                            Request Time Log Change
                        </h4>
                    </div>

                    <button type="button" class="modal-close inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300" aria-label="Close time log change request modal" data-time-log-change-request-close>
                        ✕
                    </button>
                </div>

                <form id="timeLogChangeRequestForm" action="{{ route('tasks.time-log-change-requests.store') }}" method="POST" class="flex max-h-[80vh] flex-col" data-time-log-change-request-form data-store-url="{{ route('tasks.time-log-change-requests.store') }}">
                    @csrf
                    <input type="hidden" id="timeLogChangeRequestTaskId" name="task_id" value="{{ $taskId ?? (isset($task) ? $task->id : '') }}" data-time-log-change-request-task-id>
                    <input type="hidden" id="timeLogChangeRequestTaskTimeLogId" name="task_time_log_id" value="" data-time-log-change-request-time-log-id>
                    <input type="hidden" id="timeLogChangeRequestOriginalStartedAt" name="original_started_at" value="" data-time-log-change-request-original-started-at>
                    <input type="hidden" id="timeLogChangeRequestOriginalEndedAt" name="original_ended_at" value="" data-time-log-change-request-original-ended-at>

                    <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7">
                        <div class="space-y-6">
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="timeLogChangeRequestNewStartedAt" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        New Started At <x-red-star />
                                    </label>
                                    <input type="text" id="timeLogChangeRequestNewStartedAt" name="new_started_at" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-enable-time="true" data-enable-seconds="true" data-time-24hr="true" data-format="Y-m-d H:i:S" data-time-log-change-request-started-at placeholder="Select start date and time" autocomplete="off">
                                    <p class="mt-1 hidden text-sm text-error-300" data-time-log-change-request-error-for="new_started_at"></p>
                                </div>

                                <div>
                                    <label for="timeLogChangeRequestNewEndedAt" class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        New Ended At <x-red-star />
                                    </label>
                                    <input type="text" id="timeLogChangeRequestNewEndedAt" name="new_ended_at" class="datepicker w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-enable-time="true" data-enable-seconds="true" data-time-24hr="true" data-format="Y-m-d H:i:S" data-time-log-change-request-ended-at placeholder="Select end date and time" autocomplete="off">
                                    <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300" data-time-log-change-request-duration>Duration: --</p>
                                    <p class="mt-1 hidden text-sm text-error-300" data-time-log-change-request-error-for="new_ended_at"></p>
                                </div>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label for="timeLogChangeRequestReason" class="block text-sm font-medium text-bgray-700 dark:text-bgray-50">
                                        Reason <x-red-star />
                                    </label>
                                </div>
                                <textarea id="timeLogChangeRequestReason" name="reason" rows="4" class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" placeholder="Explain why this time log should be changed" data-time-log-change-request-reason></textarea>
                                <p class="mt-1 hidden text-sm text-error-300" data-time-log-change-request-error-for="reason"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" id="timeLogChangeRequestCancelButton" class="modal-close rounded-lg border border-bgray-300 bg-white px-5 py-2 font-semibold text-bgray-700 transition duration-200 hover:border-bgray-400 hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-darkblack-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-time-log-change-request-close>
                            Cancel
                        </button>
                        <button type="button" id="timeLogChangeRequestSubmitButton" class="rounded-lg bg-success-300 px-5 py-2 font-semibold text-white transition duration-200 hover:bg-success-400" data-time-log-change-request-submit>
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
