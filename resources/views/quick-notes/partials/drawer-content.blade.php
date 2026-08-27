@php
    $pinnedNotes = $notes->filter(fn($n) => $n->is_pinned && !$n->is_archived);
    $otherActiveNotes = $notes->filter(fn($n) => !$n->is_pinned && !$n->is_archived);
    $archivedNotes = $notes->filter(fn($n) => $n->is_archived);
@endphp

<!-- Drawer Header (Single Row) -->
<div class="flex items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400">
    <!-- Left: Title OR Search Bar -->
    <div class="flex items-center flex-1 mr-3 min-w-0">
        <!-- Title Block (Default Visible) -->
        <div id="drawer-title-wrapper" class="flex items-center">
            <span class="inline-flex h-9 w-9 items-center justify-center text-black dark:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </span>
            <div class="flex items-center">
                <h2 class="text-md font-bold text-bgray-900 dark:text-white whitespace-nowrap">Quick Notes</h2>
            </div>
        </div>

        <!-- Search Bar (Default Hidden) -->
        <div id="drawer-search-wrapper" class="hidden relative flex-1 max-w-sm">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-bgray-400 dark:text-bgray-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input type="text" id="notes-search-input" placeholder="Search notes..." class="w-full rounded-xl border border-bgray-200 bg-bgray-50/50 pl-9 pr-8 py-1.5 text-xs text-bgray-900 placeholder-bgray-400 focus:border-success-300 focus:bg-white focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder-bgray-500">
        </div>
    </div>

    <!-- Right: Search Icon -> '+' Button -> Active/Archived Toggle -->
    <div class="flex items-center gap-2 flex-shrink-0">
        <!-- Search Toggle Icon Button -->
        <button type="button" id="toggle-search-btn" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition hover:bg-bgray-100 hover:text-bgray-900 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" title="Search Notes" aria-label="Search Notes">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>

        <!-- '+' New Note Button -->
        <button type="button" id="open-create-note-btn" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-bgray-500 bg-white px-2 py-1.5 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300" aria-label="New Note" title="New Note">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>

        <!-- Active / Archived Tab Toggle -->
        <div class="inline-flex rounded-lg border border-bgray-200 bg-white p-0.5 dark:border-darkblack-400 dark:bg-darkblack-500">
            <button type="button" id="tab-active-btn" class="rounded-md px-2.5 py-1 text-xs font-semibold text-white bg-success-300 transition shadow-sm">
                Active (<span id="notes-count-badge">{{ $notes->where('is_archived', false)->count() }}</span>)
            </button>
            <button type="button" id="tab-archived-btn" class="rounded-md px-2.5 py-1 text-xs font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white transition">
                Archived (<span id="archived-count-label">{{ $archivedNotes->count() }}</span>)
            </button>
        </div>
    </div>
</div>

<!-- Drawer Body Content -->
<div id="notes-view-content" class="flex-1 overflow-y-auto p-5 space-y-6">
    <!-- Active Notes Section -->
    <div id="active-notes-section" class="space-y-6">
        <!-- Pinned Section -->
        <div id="pinned-section" class="{{ $pinnedNotes->isEmpty() ? 'hidden' : '' }}">
            <div class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">
                <svg class="h-3.5 w-3.5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <span>PINNED</span>
            </div>

            <div id="pinned-notes-grid" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                @foreach ($pinnedNotes as $note)
                    @include('quick-notes.partials.note-card', ['note' => $note])
                @endforeach
            </div>
        </div>

        <!-- Others Section -->
        <div id="others-section" class="{{ $otherActiveNotes->isEmpty() && !$pinnedNotes->isEmpty() ? 'hidden' : '' }}">
            <div id="others-header-label" class="mb-3 text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-300 {{ $pinnedNotes->isEmpty() ? 'hidden' : '' }}">
                OTHERS
            </div>

            <div id="others-notes-grid" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                @foreach ($otherActiveNotes as $note)
                    @include('quick-notes.partials.note-card', ['note' => $note])
                @endforeach
            </div>
        </div>

        <!-- Active Empty State -->
        <div id="active-empty-state" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-bgray-300 bg-white py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600 {{ $notes->where('is_archived', false)->isNotEmpty() ? 'hidden' : '' }}">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-bgray-100 text-bgray-400 dark:bg-darkblack-500 dark:text-bgray-500">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h4 class="mt-3 text-base font-semibold text-bgray-900 dark:text-white">No active quick notes</h4>
            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">Capture ideas, tasks, and quick thoughts here.</p>
            <button type="button" data-trigger-create class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-success-300 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                + Create Note
            </button>
        </div>
    </div>

    <!-- Archived Notes Section -->
    <div id="archived-notes-section" class="hidden space-y-4">
        <div class="text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-400">
            ARCHIVED NOTES
        </div>

        <div id="archived-notes-grid" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            @foreach ($archivedNotes as $note)
                @include('quick-notes.partials.note-card', ['note' => $note])
            @endforeach
        </div>

        <!-- Archived Empty State -->
        <div id="archived-empty-state" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-bgray-300 bg-white py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600 {{ $archivedNotes->isNotEmpty() ? 'hidden' : '' }}">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-bgray-100 text-bgray-400 dark:bg-darkblack-500 dark:text-bgray-500">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm2-2h10V4a2 2 0 00-2-2H9a2 2 0 00-2 2v2z" />
                </svg>
            </div>
            <h4 class="mt-3 text-base font-semibold text-bgray-900 dark:text-white">No archived notes</h4>
            <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">Notes you archive will appear here.</p>
        </div>
    </div>
</div>
