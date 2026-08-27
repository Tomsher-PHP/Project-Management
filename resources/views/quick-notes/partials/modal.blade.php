<div class="modal fixed inset-0 z-50 hidden overflow-y-auto modal-form" id="quick-note-modal">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-2xl">
            <div id="quick-note-modal-card" class="overflow-hidden rounded-2xl bg-white shadow-2xl transition-colors duration-200 dark:bg-darkblack-600">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-900/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                        <h3 class="modal-title text-xl font-semibold text-bgray-900 dark:text-white" id="quick-note-modal-title">
                            New Quick Note
                        </h3>
                    </div>

                    <button type="button" data-modal-close class="modal-close inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300">
                        ✕
                    </button>
                </div>

                <!-- Form -->
                <form id="quick-note-form" class="flex max-h-[82vh] flex-col" action="{{ route('quick-notes.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="quick_note_method" value="POST">
                    <input type="hidden" name="note_id" id="quick_note_id" value="">
                    <input type="hidden" name="content" id="quick_note_content_input" value="">
                    <input type="hidden" name="color" id="quick_note_color_input" value="">
                    <input type="hidden" name="is_pinned" id="quick_note_is_pinned_input" value="0">
                    <input type="hidden" name="is_archived" id="quick_note_is_archived_input" value="0">

                    <div class="overflow-y-auto px-6 py-5 sm:px-7 space-y-5">
                        <!-- Title & Pin bar -->
                        <div>
                            <div class="relative flex items-center gap-3">
                                <input type="text" name="title" id="quick_note_title_input" placeholder="Title..." class="w-full rounded-xl border border-bgray-200 bg-bgray-50/50 px-4 py-3 text-base font-semibold text-bgray-900 placeholder-bgray-400 focus:border-success-300 focus:bg-white focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500/50 dark:text-white dark:placeholder-bgray-500 dark:focus:border-success-300 dark:focus:bg-darkblack-500">
                                
                                <button type="button" id="quick_note_pin_toggle_btn" title="Toggle Pin" class="flex-shrink-0 inline-flex h-11 w-11 items-center justify-center rounded-xl border border-bgray-200 bg-bgray-50 text-bgray-500 hover:text-amber-500 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="quick_note_pin_icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 hidden text-xs text-red-500" data-error-field="title"></p>
                        </div>

                        <!-- Content Quill Editor -->
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Note Content</label>
                            <div class="custom-quill-wrapper overflow-hidden rounded-xl border border-bgray-200 dark:border-darkblack-400">
                                <div id="quick_note_quill_editor" class="h-44 bg-white dark:bg-darkblack-500 text-bgray-900 dark:text-white"></div>
                            </div>
                            <p class="mt-1 hidden text-xs text-red-500" data-error-field="content"></p>
                        </div>

                        <!-- Hidden Project & Task inputs -->
                        <input type="hidden" name="project_id" id="quick_note_project_id" value="">
                        <input type="hidden" name="task_id" id="quick_note_task_id" value="">

                        <!-- Color Swatches -->
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Note Color</label>
                            <div class="flex flex-wrap items-center gap-2" id="quick_note_color_swatches">
                                <button type="button" data-color="" title="Default White" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-bgray-300 bg-white ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 active-swatch"></button>
                                <button type="button" data-color="yellow" title="Soft Yellow" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-amber-300 bg-amber-100 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-amber-700 dark:bg-amber-900/60"></button>
                                <button type="button" data-color="green" title="Soft Green" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-emerald-300 bg-emerald-100 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-emerald-700 dark:bg-emerald-900/60"></button>
                                <button type="button" data-color="blue" title="Soft Blue" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-sky-300 bg-sky-100 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-sky-700 dark:bg-sky-900/60"></button>
                                <button type="button" data-color="purple" title="Soft Purple" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-purple-300 bg-purple-100 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-purple-700 dark:bg-purple-900/60"></button>
                                <button type="button" data-color="pink" title="Soft Pink" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-rose-300 bg-rose-100 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-rose-700 dark:bg-rose-900/60"></button>
                                <button type="button" data-color="orange" title="Soft Orange" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-orange-300 bg-orange-100 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-orange-700 dark:bg-orange-900/60"></button>
                                <button type="button" data-color="gray" title="Soft Gray" class="color-swatch-btn h-8 w-8 rounded-full border-2 border-slate-300 bg-slate-200 ring-2 ring-transparent transition hover:scale-110 focus:outline-none dark:border-slate-600 dark:bg-slate-700"></button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" id="quick_note_archive_toggle_btn" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm2-2h10V4a2 2 0 00-2-2H9a2 2 0 00-2 2v2z" />
                            </svg>
                            <span id="quick_note_archive_btn_label">Archive Note</span>
                        </button>

                        <div class="flex items-center gap-3">
                            <button type="button" data-modal-close class="modal-close rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:bg-darkblack-400">
                                Cancel
                            </button>

                            <button type="submit" id="quick_note_submit_btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:opacity-50">
                                <svg class="hidden h-4 w-4 animate-spin" id="quick_note_submit_spinner" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="quick_note_submit_label">Save Note</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
