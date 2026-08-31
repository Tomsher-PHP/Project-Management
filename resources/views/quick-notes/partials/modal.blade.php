<div class="modal fixed inset-0 z-50 hidden overflow-y-auto modal-form" id="quick-note-modal">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-2xl">
            <div id="quick-note-modal-card" class="overflow-hidden rounded-[8px] bg-white shadow-2xl transition-colors duration-200 dark:bg-darkblack-600">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center text-black dark:text-white">
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
                        <!-- Title -->
                        <div>
                            <input type="text" name="title" id="quick_note_title_input" placeholder="Title..." class="w-full rounded-xl border border-bgray-200 bg-white px-4 py-3 text-base font-semibold text-bgray-900 placeholder-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder-bgray-400 dark:focus:border-success-300">
                            <p class="mt-1 hidden text-xs text-red-500" data-error-field="title"></p>
                        </div>

                        <!-- Content Quill Editor -->
                        <div>
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
                            @php
                                $softColors = config('constants.soft_colors', ['#f3f4f6', '#fee2e2', '#fde68a', '#d1fae5', '#dbeafe', '#e9d5ff', '#fbcfe8', '#cffafe']);
                            @endphp
                            <div class="flex flex-wrap items-center gap-3" id="quick_note_color_swatches">
                                <button type="button" data-color="" title="Default" class="color-swatch-btn relative block w-9 h-9 rounded-md border border-gray-300 transition transform hover:scale-110 focus:outline-none bg-white dark:bg-darkblack-500">
                                    <span class="swatch-check absolute inset-0 flex items-center justify-center text-gray-800 dark:text-white text-sm pointer-events-none opacity-0 font-bold">✓</span>
                                </button>
                                @foreach ($softColors as $color)
                                    <button type="button" data-color="{{ $color }}" title="{{ $color }}" class="color-swatch-btn relative block w-9 h-9 rounded-md border border-gray-300 transition transform hover:scale-110 focus:outline-none" style="background-color: {{ $color }}">
                                        <span class="swatch-check absolute inset-0 flex items-center justify-center text-black text-sm pointer-events-none opacity-0 font-bold">✓</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" data-modal-close class="rounded-xl border border-bgray-300 bg-white px-5 py-2.5 text-sm font-semibold text-bgray-700 hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                            Cancel
                        </button>

                        <button type="submit" id="quick_note_submit_btn" class="inline-flex items-center gap-2 rounded-xl bg-success-300 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-success-400 focus:outline-none">
                            <svg class="hidden h-4 w-4 animate-spin text-white" id="quick_note_submit_spinner" viewBox="0 0 24 24" fill="none">
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
