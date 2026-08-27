document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const toggleBtn = document.getElementById('quick-notes-toggle-btn');
    const drawer = document.getElementById('quick-notes-drawer');
    const backdrop = document.getElementById('quick-notes-drawer-backdrop');
    const panel = document.getElementById('quick-notes-drawer-panel');
    const drawerBody = document.getElementById('quick-notes-drawer-body');
    const modal = document.getElementById('quick-note-modal');

    if (!toggleBtn || !drawer) return;

    let drawerLoaded = false;
    let quill = null;

    // Toast Helper using project Alert library
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

    // Open Drawer
    function openDrawer() {
        drawer.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Slide in animation
        requestAnimationFrame(() => {
            if (backdrop) {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
            }
            if (panel) {
                panel.classList.remove('translate-x-full');
                panel.classList.add('translate-x-0');
            }
        });

        if (!drawerLoaded) {
            loadDrawerContent();
        }
    }

    // Close Drawer
    function closeDrawer() {
        if (backdrop) {
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
        }
        if (panel) {
            panel.classList.remove('translate-x-0');
            panel.classList.add('translate-x-full');
        }

        setTimeout(() => {
            drawer.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }

    // Load Drawer Content via AJAX
    function loadDrawerContent() {
        drawerBody.innerHTML = `
            <div id="quick-notes-drawer-loading" class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="h-9 w-9 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="mt-4 text-sm font-medium text-bgray-600 dark:text-bgray-300">Loading Quick Notes...</span>
            </div>
        `;

        fetch('/quick-notes/drawer', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status && res.html) {
                drawerBody.innerHTML = res.html;
                drawerLoaded = true;
                initDrawerHandlers();
            } else {
                throw new Error(res.message || 'Failed to load notes');
            }
        })
        .catch(err => {
            drawerBody.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20 text-center px-6">
                    <div class="h-12 w-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center mb-3">✕</div>
                    <h3 class="text-base font-bold text-bgray-900 dark:text-white">Unable to load quick notes</h3>
                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">${err.message || 'An error occurred while fetching notes.'}</p>
                    <button type="button" id="quick-notes-retry-btn" class="mt-4 rounded-xl bg-success-300 px-4 py-2 text-xs font-semibold text-white transition hover:bg-success-400">
                        Retry Loading
                    </button>
                </div>
            `;
            document.getElementById('quick-notes-retry-btn')?.addEventListener('click', loadDrawerContent);
        });
    }

    // Toggle button & backdrop clicks
    toggleBtn.addEventListener('click', openDrawer);
    backdrop?.addEventListener('click', closeDrawer);

    // Dynamic Element Creator
    function createElementFromHTML(htmlString) {
        const div = document.createElement('div');
        div.innerHTML = htmlString.trim();
        return div.firstElementChild;
    }

    // Initialize Drawer Handlers after AJAX content loads
    function initDrawerHandlers() {
        const openCreateBtn = document.getElementById('open-create-note-btn');
        openCreateBtn?.addEventListener('click', openCreateModal);

        document.getElementById('close-quick-notes-drawer-btn')?.addEventListener('click', closeDrawer);

        document.querySelectorAll('[data-trigger-create]').forEach(b => b.addEventListener('click', openCreateModal));

        // Tab Switching
        const tabActiveBtn = document.getElementById('tab-active-btn');
        const tabArchivedBtn = document.getElementById('tab-archived-btn');
        const activeSection = document.getElementById('active-notes-section');
        const archivedSection = document.getElementById('archived-notes-section');

        tabActiveBtn?.addEventListener('click', () => {
            tabActiveBtn.className = 'rounded-md px-2.5 py-1 text-xs font-semibold text-white bg-success-300 transition shadow-sm';
            tabArchivedBtn.className = 'rounded-md px-2.5 py-1 text-xs font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white transition';
            activeSection?.classList.remove('hidden');
            archivedSection?.classList.add('hidden');
        });

        tabArchivedBtn?.addEventListener('click', () => {
            tabArchivedBtn.className = 'rounded-md px-2.5 py-1 text-xs font-semibold text-white bg-success-300 transition shadow-sm';
            tabActiveBtn.className = 'rounded-md px-2.5 py-1 text-xs font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white transition';
            archivedSection?.classList.remove('hidden');
            activeSection?.classList.add('hidden');
        });

        // Search Bar Toggle & Input Filtering
        const toggleSearchBtn = document.getElementById('toggle-search-btn');
        const titleWrapper = document.getElementById('drawer-title-wrapper');
        const searchWrapper = document.getElementById('drawer-search-wrapper');
        const searchInput = document.getElementById('notes-search-input');
        const clearSearchBtn = document.getElementById('clear-search-btn');
        let searchTimer = null;

        function showSearchBar() {
            titleWrapper?.classList.add('hidden');
            searchWrapper?.classList.remove('hidden');
            searchInput?.focus();
        }

        function hideSearchBar() {
            if (searchInput) searchInput.value = '';
            searchWrapper?.classList.add('hidden');
            titleWrapper?.classList.remove('hidden');
            applySearch();
        }

        toggleSearchBtn?.addEventListener('click', () => {
            if (searchWrapper?.classList.contains('hidden')) {
                showSearchBar();
            } else {
                hideSearchBar();
            }
        });

        clearSearchBtn?.addEventListener('click', hideSearchBar);

        function applySearch() {
            const query = searchInput?.value.toLowerCase().trim() || '';

            document.querySelectorAll('#quick-notes-drawer-body .note-card').forEach(card => {
                const title = (card.querySelector('.note-card-title')?.textContent || '').toLowerCase();
                const content = (card.querySelector('.note-card-content')?.textContent || '').toLowerCase();
                if (!query || title.includes(query) || content.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        searchInput?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applySearch, 150);
        });

        // Delegate Card Click Actions (Edit, Pin, Archive, Delete)
        const notesContent = document.getElementById('notes-view-content');
        notesContent?.addEventListener('click', (e) => {
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

        // Bind Drag & Drop for all cards
        document.querySelectorAll('#quick-notes-drawer-body .note-card').forEach(bindCardDragEvents);
        const pinnedGrid = document.getElementById('pinned-notes-grid');
        const othersGrid = document.getElementById('others-notes-grid');
        const archivedGrid = document.getElementById('archived-notes-grid');

        if (pinnedGrid) setupGridDropEvents(pinnedGrid);
        if (othersGrid) setupGridDropEvents(othersGrid);
        if (archivedGrid) setupGridDropEvents(archivedGrid);

        // Modal initialization
        initModalLogic();
    }

    // Recalculate section visibilities & counts
    function updateUIState() {
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

        if (!pinnedGrid || !othersGrid || !archivedGrid) return;

        const pinnedCards = Array.from(pinnedGrid.children).filter(c => c.classList.contains('note-card'));
        const othersCards = Array.from(othersGrid.children).filter(c => c.classList.contains('note-card'));
        const archivedCards = Array.from(archivedGrid.children).filter(c => c.classList.contains('note-card'));

        const activeCount = pinnedCards.length + othersCards.length;
        const archivedCount = archivedCards.length;

        if (notesCountBadge) notesCountBadge.textContent = activeCount;
        if (archivedCountLabel) archivedCountLabel.textContent = archivedCount;

        if (pinnedCards.length > 0) {
            pinnedSection?.classList.remove('hidden');
            othersHeaderLabel?.classList.remove('hidden');
        } else {
            pinnedSection?.classList.add('hidden');
            othersHeaderLabel?.classList.add('hidden');
        }

        if (othersCards.length > 0 || pinnedCards.length === 0) {
            othersSection?.classList.remove('hidden');
        } else {
            othersSection?.classList.add('hidden');
        }

        if (activeCount === 0) {
            activeEmptyState?.classList.remove('hidden');
        } else {
            activeEmptyState?.classList.add('hidden');
        }

        if (archivedCount === 0) {
            archivedEmptyState?.classList.remove('hidden');
        } else {
            archivedEmptyState?.classList.add('hidden');
        }
    }

    // Drag & Drop Handling
    let draggedCard = null;

    function bindCardDragEvents(card) {
        card.setAttribute('draggable', 'true');
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

    function saveReorderedGrid(grid) {
        const cards = Array.from(grid.querySelectorAll('.note-card'));
        if (cards.length === 0) return;

        const notesPayload = cards.map((c, index) => ({
            id: parseInt(c.getAttribute('data-note-id')),
            sort_order: index
        }));

        fetch('/quick-notes/reorder', {
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
            if (res.status) showToast('Notes reordered', 'success');
        })
        .catch(() => {});
    }

    // Action Handlers
    function togglePinNote(noteId, card) {
        const pinnedGrid = document.getElementById('pinned-notes-grid');
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
                const othersGrid = document.getElementById('others-notes-grid');

                if (note.is_pinned && pinnedGrid) {
                    pinnedGrid.prepend(newCard);
                } else if (othersGrid) {
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
                const archivedGrid = document.getElementById('archived-notes-grid');
                const pinnedGrid = document.getElementById('pinned-notes-grid');
                const othersGrid = document.getElementById('others-notes-grid');

                if (note.is_archived && archivedGrid) {
                    archivedGrid.prepend(newCard);
                } else if (note.is_pinned && pinnedGrid) {
                    pinnedGrid.prepend(newCard);
                } else if (othersGrid) {
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

    // Modal Logic & Form Handling
    let modalInitialized = false;

    function initModalLogic() {
        if (modalInitialized || !modal) return;
        modalInitialized = true;

        const form = document.getElementById('quick-note-form');
        const titleInput = document.getElementById('quick_note_title_input');
        const contentInput = document.getElementById('quick_note_content_input');
        const noteIdInput = document.getElementById('quick_note_id');
        const methodInput = document.getElementById('quick_note_method');
        const colorInput = document.getElementById('quick_note_color_input');
        const isPinnedInput = document.getElementById('quick_note_is_pinned_input');
        const isArchivedInput = document.getElementById('quick_note_is_archived_input');
        const pinToggleBtn = document.getElementById('quick_note_pin_toggle_btn');
        const submitBtn = document.getElementById('quick_note_submit_btn');
        const submitSpinner = document.getElementById('quick_note_submit_spinner');

        const editorEl = document.getElementById('quick_note_quill_editor');
        if (editorEl && window.Quill && !quill) {
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

        document.querySelectorAll('[data-modal-close]').forEach(b => b.addEventListener('click', closeModal));

        // Swatch selection
        document.querySelectorAll('#quick_note_color_swatches .color-swatch-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const color = btn.getAttribute('data-color');
                colorInput.value = color;
                updateModalColor(color);
            });
        });

        // Pin toggle
        pinToggleBtn?.addEventListener('click', () => {
            const current = isPinnedInput.value === '1';
            const pinnedGrid = document.getElementById('pinned-notes-grid');
            const currentPinnedCount = pinnedGrid ? pinnedGrid.querySelectorAll('.note-card').length : 0;

            if (!current && currentPinnedCount >= 4) {
                showToast('You can pin a maximum of 4 notes.', 'error');
                return;
            }

            isPinnedInput.value = current ? '0' : '1';
            updatePinButtonUI(!current);
        });

        // Form Submit
        form?.addEventListener('submit', (e) => {
            e.preventDefault();

            if (quill) {
                const html = quill.root.innerHTML;
                const plain = quill.getText().trim();
                contentInput.value = plain === '' ? '' : html;
            }

            clearErrors();
            submitBtn.disabled = true;
            submitSpinner?.classList.remove('hidden');

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
                submitSpinner?.classList.add('hidden');

                if (!res.ok) {
                    if (res.status === 422 && res.body.errors) {
                        displayErrors(res.body.errors);
                    } else {
                        showToast(res.body.message || 'Error saving note', 'error');
                    }
                    return;
                }

                const note = res.body.data;
                const html = res.body.html;

                if (html) {
                    const newCard = createElementFromHTML(html);
                    bindCardDragEvents(newCard);

                    const existingCard = document.querySelector(`#quick-notes-drawer-body .note-card[data-note-id="${note.id}"]`);
                    if (existingCard) {
                        existingCard.remove();
                    }

                    const archivedGrid = document.getElementById('archived-notes-grid');
                    const pinnedGrid = document.getElementById('pinned-notes-grid');
                    const othersGrid = document.getElementById('others-notes-grid');

                    if (note.is_archived && archivedGrid) {
                        archivedGrid.prepend(newCard);
                    } else if (note.is_pinned && pinnedGrid) {
                        pinnedGrid.prepend(newCard);
                    } else if (othersGrid) {
                        othersGrid.prepend(newCard);
                    }

                    updateUIState();
                }

                closeModal();
                showToast(res.body.message || 'Saved successfully');
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitSpinner?.classList.add('hidden');
                showToast('An unexpected error occurred.', 'error');
            });
        });
    }

    function openCreateModal() {
        initModalLogic();
        const form = document.getElementById('quick-note-form');
        const titleInput = document.getElementById('quick_note_title_input');
        const contentInput = document.getElementById('quick_note_content_input');
        const noteIdInput = document.getElementById('quick_note_id');
        const methodInput = document.getElementById('quick_note_method');
        const colorInput = document.getElementById('quick_note_color_input');
        const isPinnedInput = document.getElementById('quick_note_is_pinned_input');
        const isArchivedInput = document.getElementById('quick_note_is_archived_input');
        const modalTitle = document.getElementById('quick-note-modal-title');
        const submitLabel = document.getElementById('quick_note_submit_label');
        const archiveBtn = document.getElementById('quick_note_archive_toggle_btn');

        form.action = '/quick-notes';
        methodInput.value = 'POST';
        noteIdInput.value = '';
        titleInput.value = '';
        contentInput.value = '';
        if (quill) quill.setContents([]);
        colorInput.value = '';
        isPinnedInput.value = '0';
        isArchivedInput.value = '0';
        archiveBtn?.classList.add('hidden');
        updateModalColor('');
        updatePinButtonUI(false);
        modalTitle.textContent = 'New Quick Note';
        submitLabel.textContent = 'Save Note';
        clearErrors();
        modal.classList.remove('hidden');
    }

    function openEditModal(noteId) {
        initModalLogic();
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

            const form = document.getElementById('quick-note-form');
            const titleInput = document.getElementById('quick_note_title_input');
            const contentInput = document.getElementById('quick_note_content_input');
            const noteIdInput = document.getElementById('quick_note_id');
            const methodInput = document.getElementById('quick_note_method');
            const colorInput = document.getElementById('quick_note_color_input');
            const isPinnedInput = document.getElementById('quick_note_is_pinned_input');
            const isArchivedInput = document.getElementById('quick_note_is_archived_input');
            const modalTitle = document.getElementById('quick-note-modal-title');
            const submitLabel = document.getElementById('quick_note_submit_label');

            form.action = `/quick-notes/${note.id}`;
            methodInput.value = 'PUT';
            noteIdInput.value = note.id;
            titleInput.value = note.title || '';
            contentInput.value = note.content || '';
            
            if (quill) {
                quill.clipboard.dangerouslyPasteHTML(note.content || '');
            }

            colorInput.value = note.color || '';
            isPinnedInput.value = note.is_pinned ? '1' : '0';
            isArchivedInput.value = note.is_archived ? '1' : '0';

            const archiveBtn = document.getElementById('quick_note_archive_toggle_btn');
            archiveBtn?.classList.remove('hidden');

            updateModalColor(note.color || '');
            updatePinButtonUI(note.is_pinned);

            modalTitle.textContent = 'Edit Quick Note';
            submitLabel.textContent = 'Update Note';

            modal.classList.remove('hidden');
        })
        .catch(err => {
            showToast(err.message || 'Failed to load note details', 'error');
        });
    }

    function closeModal() {
        if (modal) modal.classList.add('hidden');
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

    function updatePinButtonUI(isPinned) {
        const pinToggleBtn = document.getElementById('quick_note_pin_toggle_btn');
        const icon = document.getElementById('quick_note_pin_icon');
        if (isPinned) {
            pinToggleBtn?.classList.add('bg-amber-50', 'text-amber-500', 'border-amber-300');
            if (icon) icon.setAttribute('fill', 'currentColor');
        } else {
            pinToggleBtn?.classList.remove('bg-amber-50', 'text-amber-500', 'border-amber-300');
            if (icon) icon.setAttribute('fill', 'none');
        }
    }
});
