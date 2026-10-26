<div
    id="developer-notes-modal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="developer-notes-title"
>
    <div class="relative flex min-h-[420px] max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-[8px] bg-white shadow-2xl dark:border dark:border-darkblack-400 dark:bg-darkblack-600">

        {{-- Modal Header --}}
        <div class="flex items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400" id="editable-dev-notes">
            <div>
                <h2
                    id="developer-notes-title"
                    class="text-lg font-semibold text-gray-900 dark:text-white"
                >
                    Developer Notes
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Add and manage notes, updates, and other relevant details for this task.
                </p>
            </div>

            <button
                type="button"
                id="developer-notes-close"
                class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-700 dark:hover:text-white"
                aria-label="Close developer notes"
            >
                &times;
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 space-y-4 overflow-y-auto p-5">

            {{-- Loading State --}}
            <div
                id="developer-notes-loading"
                class="hidden rounded-md bg-gray-50 p-4 text-sm text-gray-500 dark:bg-darkblack-500 dark:text-gray-400"
                role="status"
                aria-live="polite"
            >
                Loading developer notes...
            </div>

            {{-- Error Message --}}
            <div
                id="developer-notes-error"
                class="hidden rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300"
                role="alert"
                aria-live="assertive"
            ></div>

            {{-- Note Preview (kept for compatibility with the JavaScript) --}}
            <div
                id="developer-notes-list"
                class="hidden space-y-3"
            ></div>

            {{-- Empty State (kept for compatibility with the JavaScript) --}}
            <div
                id="developer-notes-empty"
                class="hidden rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400"
            >
                No developer notes have been added yet.
            </div>

            {{-- Completed Task: Read-Only Message --}}
            <div
                id="developer-notes-readonly"
                class="hidden rounded-md border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-gray-300 flex items-start justify-between gap-4 border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400"
                role="status"
            >
                <div class="flex items-start gap-2">
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    </svg>

                    <div>
                        <p class="font-medium">
                            Read-only developer notes
                        </p>
                        <p class="mt-1">
                            This task is completed. You can view the saved developer note, but it cannot be edited.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    id="readonly-developer-notes-close"
                    class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-700 dark:hover:text-white"
                    aria-label="Close developer notes"
                >
                    &times;
                </button>

            </div>

            {{-- Add / Edit / View Developer Note --}}
            <form
                id="developer-notes-form"
                class="hidden  dark:border-gray-700"
            >
                <div>

                    <textarea
                        id="developer-note-input"
                        name="note"
                        rows="8"
                        maxlength="10000"
                        placeholder="Enter implementation details, technical findings, debugging notes, or other relevant information..."
                        class="w-full resize-y rounded-xl border border-bgray-300 bg-white px-4 py-3 text-sm leading-6 text-bgray-900 placeholder:text-gray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder:text-gray-500"
                        aria-describedby="developer-note-help"
                    ></textarea>

                    <p
                        id="developer-note-help"
                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        Maximum 10,000 characters.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        id="developer-notes-submit"
                        class="inline-flex items-center justify-center rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Save Note
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
