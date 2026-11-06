<div class="flex min-h-0 flex-col gap-5">

    <div id="developer-notes-loading" class="hidden flex items-center justify-center py-10">
        <div class="flex items-center gap-2 text-sm text-bgray-500 dark:text-bgray-300">
            <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            Loading developer notes...
        </div>
    </div>

    <!-- Error -->
    <div id="developer-notes-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"></div>

    <!-- Read-only message -->
    <div id="developer-notes-readonly" class="hidden items-start gap-1 px-4 py-1 text-sm text-blue-500">
        <svg class="mt-0.5 h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <div>
            <p class="font-semibold">Read-only developer note</p>
            <p class="mt-1">
                This task is completed or you do not have permission to edit this note.
            </p>
        </div>
    </div>

    <!-- Empty state -->
    <div id="developer-notes-empty" class="hidden py-6 text-center text-sm text-bgray-500 dark:text-bgray-400">
        No developer notes added yet.
    </div>

    <!-- Notes list -->
    <div id="developer-notes-list" class="hidden"></div>

    <form id="developer-notes-form" class="hidden space-y-4">
        <div>
            {{-- <label class="mb-2 block text-sm font-semibold text-bgray-700 dark:text-white">
                Developer Note
            </label> --}}

            <input
                type="hidden"
                id="developer-note-input"
                name="developer_note"
            >

            <div class="custom-quill-wrapper overflow-hidden rounded-lg border border-bgray-300 dark:border-darkblack-400">
                <div
                    id="developer-note-editor"
                    class="h-44 bg-white dark:bg-darkblack-500 dark:text-white"
                ></div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" id="developer-notes-submit" class="rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-60">
                Save Note
            </button>
        </div>
    </form>

</div>
