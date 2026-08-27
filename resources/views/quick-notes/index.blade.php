@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]" id="quick-notes-container">
        <!-- Page Header & Action Bar -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-bgray-900 dark:text-white sm:text-3xl">Quick Notes</h1>
                    <span id="notes-count-badge" class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-700 dark:bg-success-900/40 dark:text-success-300">
                        {{ $notes->where('is_archived', false)->count() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">Capture thoughts, link projects & tasks, and stay organized</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" id="open-create-note-btn" class="inline-flex items-center gap-2 rounded-xl bg-success-300 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-success-400 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Note</span>
                </button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:flex-row sm:items-center sm:justify-between">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-bgray-400 dark:text-bgray-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" id="notes-search-input" placeholder="Search notes..." class="w-full rounded-xl border border-bgray-200 bg-bgray-50/70 pl-10 pr-9 py-2 text-sm text-bgray-900 placeholder-bgray-400 focus:border-success-300 focus:bg-white focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:placeholder-bgray-500 dark:focus:border-success-300">
                <button type="button" id="clear-search-btn" class="hidden absolute inset-y-0 right-0 items-center pr-3 text-bgray-400 hover:text-bgray-600 dark:text-bgray-500 dark:hover:text-bgray-300">✕</button>
            </div>

            <!-- Project & Task Select Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <select id="filter-project-select" class="rounded-xl border border-bgray-200 bg-bgray-50 px-3 py-2 text-sm text-bgray-700 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                    <option value="">All Projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>

                <select id="filter-task-select" class="rounded-xl border border-bgray-200 bg-bgray-50 px-3 py-2 text-sm text-bgray-700 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                    <option value="">All Tasks</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task->id }}" data-project-id="{{ $task->project_id }}">{{ $task->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Active / Archived Tab Toggle -->
            <div class="inline-flex rounded-xl border border-bgray-200 bg-bgray-50/50 p-1 dark:border-darkblack-400 dark:bg-darkblack-500/50">
                <button type="button" id="tab-active-btn" class="rounded-lg px-3.5 py-1.5 text-xs font-semibold text-white bg-success-300 transition shadow-sm">
                    Active Notes
                </button>
                <button type="button" id="tab-archived-btn" class="rounded-lg px-3.5 py-1.5 text-xs font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white transition">
                    Archived (<span id="archived-count-label">{{ $notes->where('is_archived', true)->count() }}</span>)
                </button>
            </div>
        </div>

        @php
            $pinnedNotes = $notes->filter(fn($n) => $n->is_pinned && !$n->is_archived);
            $otherActiveNotes = $notes->filter(fn($n) => !$n->is_pinned && !$n->is_archived);
            $archivedNotes = $notes->filter(fn($n) => $n->is_archived);
        @endphp

        <!-- Notes View Sections -->
        <div id="notes-view-content">
            <!-- Active Notes View -->
            <div id="active-notes-section" class="space-y-8">
                <!-- Pinned Notes Grid -->
                <div id="pinned-section" class="{{ $pinnedNotes->isEmpty() ? 'hidden' : '' }}">
                    <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                        <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <span>PINNED</span>
                    </div>

                    <div id="pinned-notes-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 min-h-[50px]">
                        @foreach ($pinnedNotes as $note)
                            @include('quick-notes.partials.note-card', ['note' => $note])
                        @endforeach
                    </div>
                </div>

                <!-- Others / Regular Active Notes Grid -->
                <div id="others-section" class="{{ $otherActiveNotes->isEmpty() && !$pinnedNotes->isEmpty() ? 'hidden' : '' }}">
                    <div id="others-header-label" class="mb-3 text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400 {{ $pinnedNotes->isEmpty() ? 'hidden' : '' }}">
                        OTHERS
                    </div>

                    <div id="others-notes-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 min-h-[50px]">
                        @foreach ($otherActiveNotes as $note)
                            @include('quick-notes.partials.note-card', ['note' => $note])
                        @endforeach
                    </div>
                </div>

                <!-- Empty State for Active Notes -->
                <div id="active-empty-state" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-bgray-300 bg-white py-16 text-center dark:border-darkblack-400 dark:bg-darkblack-600 {{ $notes->where('is_archived', false)->isNotEmpty() ? 'hidden' : '' }}">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-bgray-100 text-bgray-400 dark:bg-darkblack-500 dark:text-bgray-500">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">No active notes</h3>
                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">Notes you add will appear here. Capture ideas, reminders, and tasks.</p>
                    <button type="button" data-trigger-create class="mt-5 inline-flex items-center gap-2 rounded-xl bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                        + Create a Note
                    </button>
                </div>
            </div>

            <!-- Archived Notes View -->
            <div id="archived-notes-section" class="hidden">
                <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                    ARCHIVED NOTES
                </div>

                <div id="archived-notes-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 min-h-[50px]">
                    @foreach ($archivedNotes as $note)
                        @include('quick-notes.partials.note-card', ['note' => $note])
                    @endforeach
                </div>

                <!-- Empty State for Archived Notes -->
                <div id="archived-empty-state" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-bgray-300 bg-white py-16 text-center dark:border-darkblack-400 dark:bg-darkblack-600 {{ $archivedNotes->isNotEmpty() ? 'hidden' : '' }}">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-bgray-100 text-bgray-400 dark:bg-darkblack-500 dark:text-bgray-500">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm2-2h10V4a2 2 0 00-2-2H9a2 2 0 00-2 2v2z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">No archived notes</h3>
                    <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">Notes you archive will be stored here for future reference.</p>
                </div>
            </div>
        </div>

        <!-- Note Create / Edit Modal -->
        @include('quick-notes.partials.modal')
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const modal = document.getElementById('quick-note-modal');
            const form = document.getElementById('quick-note-form');
            const titleInput = document.getElementById('quick_note_title_input');
            const contentInput = document.getElementById('quick_note_content_input');
            const noteIdInput = document.getElementById('quick_note_id');
            const methodInput = document.getElementById('quick_note_method');
            const projectSelect = document.getElementById('quick_note_project_id');
            const taskSelect = document.getElementById('quick_note_task_id');
            const colorInput = document.getElementById('quick_note_color_input');
            const isPinnedInput = document.getElementById('quick_note_is_pinned_input');
            const isArchivedInput = document.getElementById('quick_note_is_archived_input');
            const pinToggleBtn = document.getElementById('quick_note_pin_toggle_btn');
            const archiveToggleBtn = document.getElementById('quick_note_archive_toggle_btn');
            const archiveBtnLabel = document.getElementById('quick_note_archive_btn_label');
            const submitBtn = document.getElementById('quick_note_submit_btn');
            const submitSpinner = document.getElementById('quick_note_submit_spinner');
            const submitLabel = document.getElementById('quick_note_submit_label');
            const modalTitle = document.getElementById('quick-note-modal-title');
            const searchInput = document.getElementById('notes-search-input');
            const clearSearchBtn = document.getElementById('clear-search-btn');
            const filterProjectSelect = document.getElementById('filter-project-select');
            const filterTaskSelect = document.getElementById('filter-task-select');
            const tabActiveBtn = document.getElementById('tab-active-btn');
            const tabArchivedBtn = document.getElementById('tab-archived-btn');
            const activeSection = document.getElementById('active-notes-section');
            const archivedSection = document.getElementById('archived-notes-section');
            const pinnedGrid = document.getElementById('pinned-notes-grid');
            const othersGrid = document.getElementById('others-notes-grid');
            const archivedGrid = document.getElementById('archived-notes-grid');
            const pinnedSection = document.getElementById('pinned-section');
            const othersSection = document.getElementById('others-section');
            const othersHeaderLabel = document.getElementById('others-header-label');
            const activeEmptyState = document.getElementById('active-empty-state');
            const archivedEmptyState = document.getElementById('archived-empty-state');
            const notesCountBadge = document.getElementById('notes-count-badge');
            const archivedCountLabel = document.getElementById('archived-count-label');

            let activeTab = 'active';

            // Initialize Quill Editor
            let quill = null;
            const editorEl = document.getElementById('quick_note_quill_editor');
            if (editorEl && window.Quill) {
                quill = new window.Quill(editorEl, {
                    theme: 'snow',
                    placeholder: 'Take a note...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link', 'clean']
                        ]
                    }
                });

                quill.on('text-change', () => {
                    const html = quill.root.innerHTML;
                    const plain = quill.getText().trim();
                    contentInput.value = plain === '' ? '' : html;
                });
            }

            // Toast notification helper using window.Alert
            function showToast(message, icon = 'success') {
                if (window.Alert && typeof window.Alert.success === 'function') {
                    if (icon === 'error') {
                        window.Alert.error(message);
                    } else {
                        window.Alert.success(message);
                    }
                } else if (window.Swal) {
                    window.Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: icon,
                        title: message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    alert(message);
                }
            }

            // Helper to parse HTML element from string
            function createElementFromHTML(htmlString) {
                const div = document.createElement('div');
                div.innerHTML = htmlString.trim();
                return div.firstElementChild;
            }

            // Recalculate section visibilities, counts & empty states dynamically
            function updateUIState() {
                const pinnedCards = Array.from(pinnedGrid.children).filter(c => c.classList.contains('note-card'));
                const othersCards = Array.from(othersGrid.children).filter(c => c.classList.contains('note-card'));
                const archivedCards = Array.from(archivedGrid.children).filter(c => c.classList.contains('note-card'));

                const activeCount = pinnedCards.length + othersCards.length;
                const archivedCount = archivedCards.length;

                notesCountBadge.textContent = activeCount;
                archivedCountLabel.textContent = archivedCount;

                // Pinned section
                if (pinnedCards.length > 0) {
                    pinnedSection.classList.remove('hidden');
                    othersHeaderLabel.classList.remove('hidden');
                } else {
                    pinnedSection.classList.add('hidden');
                    othersHeaderLabel.classList.add('hidden');
                }

                // Others section
                if (othersCards.length > 0 || pinnedCards.length === 0) {
                    othersSection.classList.remove('hidden');
                } else {
                    othersSection.classList.add('hidden');
                }

                // Empty states
                if (activeCount === 0) {
                    activeEmptyState.classList.remove('hidden');
                } else {
                    activeEmptyState.classList.add('hidden');
                }

                if (archivedCount === 0) {
                    archivedEmptyState.classList.remove('hidden');
                } else {
                    archivedEmptyState.classList.add('hidden');
                }

                applyFilters();
            }

            // Bind Drag & Drop Events to a Card
            let draggedCard = null;

            function bindCardDragEvents(card) {
                card.addEventListener('dragstart', (e) => {
                    draggedCard = card;
                    card.classList.add('opacity-40', 'scale-[0.98]');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', card.getAttribute('data-note-id'));
                });

                card.addEventListener('dragend', () => {
                    card.classList.remove('opacity-40', 'scale-[0.98]');
                    if (draggedCard) {
                        const targetGrid = card.parentElement;
                        if (targetGrid) {
                            saveReorderedGrid(targetGrid);
                        }
                        draggedCard = null;
                    }
                });
            }

            function setupGridDropEvents(grid) {
                grid.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';

                    if (!draggedCard || draggedCard.parentElement !== grid) return;

                    const afterElement = getDragAfterElement(grid, e.clientY, e.clientX);
                    if (afterElement == null) {
                        grid.appendChild(draggedCard);
                    } else {
                        grid.insertBefore(draggedCard, afterElement);
                    }
                });
            }

            function getDragAfterElement(container, y, x) {
                const draggableElements = [...container.querySelectorAll('.note-card:not(.opacity-40)')];
                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offsetY = y - box.top - box.height / 2;
                    const offsetX = x - box.left - box.width / 2;
                    const distance = Math.hypot(offsetX, offsetY);

                    if (distance < closest.distance) {
                        return { distance: distance, element: child };
                    } else {
                        return closest;
                    }
                }, { distance: Number.POSITIVE_INFINITY }).element;
            }

            // Save reordered grid items to backend
            function saveReorderedGrid(grid) {
                const cards = Array.from(grid.querySelectorAll('.note-card'));
                if (cards.length === 0) return;

                const notesPayload = cards.map((c, index) => ({
                    id: parseInt(c.getAttribute('data-note-id')),
                    sort_order: index
                }));

                fetch("{{ route('quick-notes.reorder') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ notes: notesPayload })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status) {
                        showToast('Notes reordered', 'success');
                    }
                })
                .catch(() => {});
            }

            // Bind drag events to all initial cards
            document.querySelectorAll('.note-card').forEach(bindCardDragEvents);
            [pinnedGrid, othersGrid, archivedGrid].forEach(setupGridDropEvents);

            // Open Modal for Create
            function openCreateModal() {
                form.action = "{{ route('quick-notes.store') }}";
                methodInput.value = 'POST';
                noteIdInput.value = '';
                titleInput.value = '';
                contentInput.value = '';
                if (quill) quill.setContents([]);
                projectSelect.value = '';
                taskSelect.value = '';
                filterTaskSelectOptions();
                colorInput.value = '';
                isPinnedInput.value = '0';
                isArchivedInput.value = '0';
                updateModalColor('');
                updatePinButtonUI(false);
                updateArchiveButtonUI(false);
                modalTitle.textContent = 'New Quick Note';
                submitLabel.textContent = 'Save Note';
                clearErrors();
                modal.classList.remove('hidden');
            }

            // Open Modal for Edit
            function openEditModal(noteId) {
                clearErrors();
                fetch(`/quick-notes/${noteId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (!res.status) throw new Error(res.message || 'Note not found');
                    const note = res.data;

                    form.action = `/quick-notes/${note.id}`;
                    methodInput.value = 'PUT';
                    noteIdInput.value = note.id;
                    titleInput.value = note.title || '';
                    contentInput.value = note.content || '';
                    
                    if (quill) {
                        quill.clipboard.dangerouslyPasteHTML(note.content || '');
                    }

                    projectSelect.value = note.project_id || '';
                    filterTaskSelectOptions();
                    taskSelect.value = note.task_id || '';

                    colorInput.value = note.color || '';
                    isPinnedInput.value = note.is_pinned ? '1' : '0';
                    isArchivedInput.value = note.is_archived ? '1' : '0';

                    updateModalColor(note.color || '');
                    updatePinButtonUI(note.is_pinned);
                    updateArchiveButtonUI(note.is_archived);

                    modalTitle.textContent = 'Edit Quick Note';
                    submitLabel.textContent = 'Update Note';

                    modal.classList.remove('hidden');
                })
                .catch(err => {
                    showToast(err.message || 'Failed to load note details', 'error');
                });
            }

            // Close Modal
            function closeModal() {
                modal.classList.add('hidden');
                clearErrors();
            }

            function clearErrors() {
                document.querySelectorAll('[data-error-field]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            }

            function displayErrors(errors) {
                clearErrors();
                Object.keys(errors).forEach(field => {
                    const el = document.querySelector(`[data-error-field="${field}"]`);
                    if (el) {
                        el.textContent = errors[field][0];
                        el.classList.remove('hidden');
                    }
                });
            }

            function filterTaskSelectOptions() {
                const selectedProjectId = projectSelect.value;
                Array.from(taskSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const taskProjId = opt.getAttribute('data-project-id');
                    if (!selectedProjectId || taskProjId === selectedProjectId) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                        if (opt.selected) taskSelect.value = '';
                    }
                });
            }

            projectSelect?.addEventListener('change', filterTaskSelectOptions);

            // Swatch color selection inside modal
            document.querySelectorAll('#quick_note_color_swatches .color-swatch-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const color = btn.getAttribute('data-color');
                    colorInput.value = color;
                    updateModalColor(color);
                });
            });

            function updateModalColor(color) {
                const swatchBtns = document.querySelectorAll('#quick_note_color_swatches .color-swatch-btn');
                swatchBtns.forEach(b => {
                    const btnColor = b.getAttribute('data-color');
                    const checkMark = b.querySelector('.swatch-check');
                    if (btnColor === (color || '')) {
                        b.classList.add('ring-2', 'ring-success-400', 'border-success-400', 'scale-110');
                        if (checkMark) checkMark.classList.remove('opacity-0');
                    } else {
                        b.classList.remove('ring-2', 'ring-success-400', 'border-success-400', 'scale-110');
                        if (checkMark) checkMark.classList.add('opacity-0');
                    }
                });
            }

            pinToggleBtn?.addEventListener('click', () => {
                const current = isPinnedInput.value === '1';
                isPinnedInput.value = current ? '0' : '1';
                updatePinButtonUI(!current);
            });

            function updatePinButtonUI(isPinned) {
                const icon = document.getElementById('quick_note_pin_icon');
                if (isPinned) {
                    pinToggleBtn.classList.add('bg-amber-50', 'text-amber-500', 'border-amber-300');
                    if (icon) icon.setAttribute('fill', 'currentColor');
                } else {
                    pinToggleBtn.classList.remove('bg-amber-50', 'text-amber-500', 'border-amber-300');
                    if (icon) icon.setAttribute('fill', 'none');
                }
            }

            archiveToggleBtn?.addEventListener('click', () => {
                const current = isArchivedInput.value === '1';
                isArchivedInput.value = current ? '0' : '1';
                updateArchiveButtonUI(!current);
            });

            function updateArchiveButtonUI(isArchived) {
                if (isArchived) {
                    archiveBtnLabel.textContent = 'Unarchive Note';
                    archiveToggleBtn.classList.add('bg-bgray-200', 'dark:bg-darkblack-400');
                } else {
                    archiveBtnLabel.textContent = 'Archive Note';
                    archiveToggleBtn.classList.remove('bg-bgray-200', 'dark:bg-darkblack-400');
                }
            }

            document.getElementById('open-create-note-btn')?.addEventListener('click', openCreateModal);
            document.querySelectorAll('[data-trigger-create]').forEach(b => b.addEventListener('click', openCreateModal));
            document.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', closeModal));

            // Form Submit via AJAX (Flicker-Free DOM update)
            form?.addEventListener('submit', (e) => {
                e.preventDefault();

                if (quill) {
                    const html = quill.root.innerHTML;
                    const plain = quill.getText().trim();
                    contentInput.value = plain === '' ? '' : html;
                }

                clearErrors();
                submitBtn.disabled = true;
                submitSpinner.classList.remove('hidden');

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                })
                .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
                .then(res => {
                    submitBtn.disabled = false;
                    submitSpinner.classList.add('hidden');

                    if (!res.ok) {
                        if (res.status === 422 && res.body.errors) {
                            displayErrors(res.body.errors);
                        } else {
                            showToast(res.body.message || 'Error saving quick note', 'error');
                        }
                        return;
                    }

                    const note = res.body.data;
                    const html = res.body.html;

                    if (html) {
                        const newCard = createElementFromHTML(html);
                        bindCardDragEvents(newCard);

                        const existingCard = document.querySelector(`.note-card[data-note-id="${note.id}"]`);
                        if (existingCard) {
                            existingCard.remove();
                        }

                        if (note.is_archived) {
                            archivedGrid.prepend(newCard);
                        } else if (note.is_pinned) {
                            pinnedGrid.prepend(newCard);
                        } else {
                            othersGrid.prepend(newCard);
                        }

                        updateUIState();
                    }

                    closeModal();
                    showToast(res.body.message || 'Saved successfully');
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitSpinner.classList.add('hidden');
                    showToast('An unexpected error occurred.', 'error');
                });
            });

            // Delegate card action clicks (Edit, Pin, Archive, Delete)
            document.getElementById('notes-view-content')?.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) {
                    const card = e.target.closest('.note-card');
                    if (card && !e.target.closest('button')) {
                        openEditModal(card.getAttribute('data-note-id'));
                    }
                    return;
                }

                const card = btn.closest('.note-card');
                const noteId = card?.getAttribute('data-note-id');
                const action = btn.getAttribute('data-action');

                if (!noteId) return;

                if (action === 'edit') {
                    openEditModal(noteId);
                } else if (action === 'pin') {
                    togglePinNote(noteId, card);
                } else if (action === 'archive') {
                    toggleArchiveNote(noteId, card);
                } else if (action === 'delete') {
                    deleteNote(noteId, card);
                }
            });

            // Toggle Pin Action via AJAX
            function togglePinNote(noteId, card) {
                const currentPinnedCards = pinnedGrid ? pinnedGrid.querySelectorAll('.note-card').length : 0;
                const isCurrentlyPinned = card.getAttribute('data-note-pinned') === '1';

                if (!isCurrentlyPinned && currentPinnedCards >= 4) {
                    showToast('You can pin a maximum of 4 notes.', 'error');
                    return;
                }

                fetch(`/quick-notes/${noteId}/pin`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status && res.html) {
                        const newCard = createElementFromHTML(res.html);
                        bindCardDragEvents(newCard);
                        card.remove();

                        const note = res.data;
                        if (note.is_pinned) {
                            pinnedGrid.prepend(newCard);
                        } else {
                            othersGrid.prepend(newCard);
                        }

                        updateUIState();
                        showToast(res.message);
                    } else {
                        showToast(res.message || 'Failed to update pin status', 'error');
                    }
                })
                .catch(() => showToast('Failed to update pin status', 'error'));
            }

            // Toggle Archive Action via AJAX
            function toggleArchiveNote(noteId, card) {
                fetch(`/quick-notes/${noteId}/archive`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status && res.html) {
                        const newCard = createElementFromHTML(res.html);
                        bindCardDragEvents(newCard);
                        card.remove();

                        const note = res.data;
                        if (note.is_archived) {
                            archivedGrid.prepend(newCard);
                        } else if (note.is_pinned) {
                            pinnedGrid.prepend(newCard);
                        } else {
                            othersGrid.prepend(newCard);
                        }

                        updateUIState();
                        showToast(res.message);
                    } else {
                        showToast(res.message || 'Failed to update archive status', 'error');
                    }
                })
                .catch(() => showToast('Failed to update archive status', 'error'));
            }

            // Delete Note Action via AJAX
            function deleteNote(noteId, card) {
                const doDelete = () => {
                    fetch(`/quick-notes/${noteId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.status) {
                            card.classList.add('scale-90', 'opacity-0', 'transition-all', 'duration-200');
                            setTimeout(() => {
                                card.remove();
                                updateUIState();
                            }, 200);
                            showToast(res.message);
                        } else {
                            showToast(res.message || 'Failed to delete note', 'error');
                        }
                    })
                    .catch(() => showToast('Failed to delete note', 'error'));
                };

                if (window.Alert && typeof window.Alert.confirm === 'function') {
                    window.Alert.confirm({
                        title: 'Delete Quick Note?',
                        text: 'Are you sure you want to delete this note? This action cannot be undone.',
                        confirmText: 'Yes, delete it',
                        confirmColor: '#EF4444',
                        cancelColor: '#6B7280'
                    }).then(result => {
                        if (result.isConfirmed) doDelete();
                    });
                } else if (window.Swal) {
                    window.Swal.fire({
                        title: 'Delete Quick Note?',
                        text: 'Are you sure you want to delete this note? This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EF4444',
                        cancelButtonColor: '#6B7280',
                        confirmButtonText: 'Yes, delete it'
                    }).then(result => {
                        if (result.isConfirmed) doDelete();
                    });
                } else if (confirm('Are you sure you want to delete this note?')) {
                    doDelete();
                }
            }

            // Tab Switching (Active vs Archived)
            tabActiveBtn?.addEventListener('click', () => {
                activeTab = 'active';
                tabActiveBtn.className = 'rounded-lg px-3.5 py-1.5 text-xs font-semibold text-white bg-success-300 transition shadow-sm';
                tabArchivedBtn.className = 'rounded-lg px-3.5 py-1.5 text-xs font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white transition';
                activeSection.classList.remove('hidden');
                archivedSection.classList.add('hidden');
            });

            tabArchivedBtn?.addEventListener('click', () => {
                activeTab = 'archived';
                tabArchivedBtn.className = 'rounded-lg px-3.5 py-1.5 text-xs font-semibold text-white bg-success-300 transition shadow-sm';
                tabActiveBtn.className = 'rounded-lg px-3.5 py-1.5 text-xs font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white transition';
                archivedSection.classList.remove('hidden');
                activeSection.classList.add('hidden');
            });

            // Instant Client-Side Search & Filter
            let debounceTimer = null;

            function applyFilters() {
                const searchVal = searchInput?.value.toLowerCase().trim() || '';
                const selectedProject = filterProjectSelect?.value || '';
                const selectedTask = filterTaskSelect?.value || '';

                if (searchVal) {
                    clearSearchBtn.classList.remove('hidden');
                    clearSearchBtn.classList.add('flex');
                } else {
                    clearSearchBtn.classList.add('hidden');
                    clearSearchBtn.classList.remove('flex');
                }

                document.querySelectorAll('.note-card').forEach(card => {
                    const title = (card.querySelector('.note-card-title')?.textContent || '').toLowerCase();
                    const content = (card.querySelector('.note-card-content')?.textContent || '').toLowerCase();
                    const projId = card.getAttribute('data-project-id') || '';
                    const taskId = card.getAttribute('data-task-id') || '';

                    const matchesSearch = !searchVal || title.includes(searchVal) || content.includes(searchVal);
                    const matchesProject = !selectedProject || projId === selectedProject;
                    const matchesTask = !selectedTask || taskId === selectedTask;

                    if (matchesSearch && matchesProject && matchesTask) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            searchInput?.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(applyFilters, 150);
            });

            filterProjectSelect?.addEventListener('change', applyFilters);
            filterTaskSelect?.addEventListener('change', applyFilters);

            clearSearchBtn?.addEventListener('click', () => {
                searchInput.value = '';
                applyFilters();
            });
        });
    </script>
@endpush
