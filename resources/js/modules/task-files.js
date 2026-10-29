import { createNoteFilesModalController } from './note-files-modal';

const taskFilesState = {
    editor: null,
    listenersBound: false,
    openEditNoteModal: null,
    loadNotesPagination: null,
    currentEditingNoteId: null,
    removedAttachmentIds: [],
    pendingFiles: [],
};

const taskNoteModal = createNoteFilesModalController({
    modalSelector: '[data-task-note-modal]',
    openSelector: '[data-task-note-modal-open]',
    closeSelector: '[data-task-note-modal-close]',
});

const initializeTaskFiles = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const tabsRoot = document.querySelector('[data-task-tabs]');
    const taskId = document.getElementById('task-notes-history')?.dataset.taskId || tabsRoot?.dataset.taskId || window.TaskApp?.id;
    const saveBtn = root.querySelector ? root.querySelector('#saveTaskNote') : document.getElementById('saveTaskNote');
    const attachmentsInput = root.querySelector ? root.querySelector('#task-note-attachments-input') : document.getElementById('task-note-attachments-input');
    const selectedFilesList = root.querySelector ? root.querySelector('#selected-task-note-files') : document.getElementById('selected-task-note-files');
    const existingAttachmentsSection = root.querySelector ? root.querySelector('#existing-task-note-attachments-section') : document.getElementById('existing-task-note-attachments-section');
    const existingAttachmentsList = root.querySelector ? root.querySelector('#existing-task-note-attachments-list') : document.getElementById('existing-task-note-attachments-list');
    const modalTitle = root.querySelector ? root.querySelector('#task-note-modal-title') : document.getElementById('task-note-modal-title');
    const modalSubtitle = root.querySelector ? root.querySelector('#task-note-modal-subtitle') : document.getElementById('task-note-modal-subtitle');
    const openCreateBtn = root.querySelector ? root.querySelector('[data-task-note-modal-open]') : document.querySelector('[data-task-note-modal-open]');

    if (!taskId) {
        return;
    }

    const editorElement = root.querySelector ? root.querySelector('#task-note') : document.getElementById('task-note');
    const taskNoteEditor = editorElement && editorElement.dataset.quillInitialized !== 'true'
        ? new Quill(editorElement, {
            theme: 'snow',
            readOnly: false,
            placeholder: 'Write a task note...',
            milestones: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ header: [1, 2, 3, false] }],
                    ['link'],
                ],
            },
        })
        : taskFilesState.editor;

    if (editorElement && editorElement.dataset.quillInitialized !== 'true' && taskNoteEditor) {
        editorElement.dataset.quillInitialized = 'true';
        taskFilesState.editor = taskNoteEditor;
    }

    const syncInputFiles = () => {
        const input = document.getElementById('task-note-attachments-input') || attachmentsInput;
        if (!input) return;
        try {
            const dt = new DataTransfer();
            taskFilesState.pendingFiles.forEach(file => dt.items.add(file));
            input.files = dt.files;
        } catch (e) {
            // DataTransfer fallback
        }
        if (!taskFilesState.pendingFiles.length) {
            input.value = '';
        }
    };

    const renderSelectedFiles = () => {
        const list = document.getElementById('selected-task-note-files') || selectedFilesList;
        if (!list) {
            return;
        }

        if (!taskFilesState.pendingFiles.length) {
            list.innerHTML = '';
            return;
        }

        list.innerHTML = taskFilesState.pendingFiles.map((file, index) => `
            <div class="inline-flex items-center gap-1.5 rounded-full bg-success-50 border border-success-200 px-3 py-1 text-xs font-medium text-success-400">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="truncate max-w-[200px]" title="${file.name}">${file.name} (${(file.size / 1024).toFixed(1)} KB)</span>
                <button type="button" class="remove-pending-file-btn ml-1 font-bold text-success-600 hover:text-red-500 transition leading-none focus:outline-none" data-file-index="${index}" title="Remove file">
                    &times;
                </button>
            </div>
        `).join('');
    };

    const resetComposer = () => {
        taskFilesState.currentEditingNoteId = null;
        taskFilesState.removedAttachmentIds = [];
        taskFilesState.pendingFiles = [];

        const input = document.getElementById('task-note-attachments-input') || attachmentsInput;
        if (input) {
            input.value = '';
        }

        const activeEditor = taskFilesState.editor || taskNoteEditor;
        if (activeEditor) {
            activeEditor.setContents([]);
        }

        const titleEl = document.getElementById('task-note-modal-title') || modalTitle;
        if (titleEl) {
            titleEl.textContent = 'Add Note & Files';
        }

        const subtitleEl = document.getElementById('task-note-modal-subtitle') || modalSubtitle;
        if (subtitleEl) {
            subtitleEl.textContent = 'Capture a task update and attach supporting files in one place.';
        }

        const btnEl = document.getElementById('saveTaskNote') || saveBtn;
        if (btnEl) {
            btnEl.textContent = 'Save';
        }

        const secEl = document.getElementById('existing-task-note-attachments-section') || existingAttachmentsSection;
        if (secEl) {
            secEl.classList.add('hidden');
        }

        const listEl = document.getElementById('existing-task-note-attachments-list') || existingAttachmentsList;
        if (listEl) {
            listEl.innerHTML = '';
        }

        renderSelectedFiles();
    };

    taskNoteModal.initialize(root, taskNoteEditor, resetComposer);

    if (openCreateBtn && openCreateBtn.dataset.openBound !== 'true') {
        openCreateBtn.addEventListener('click', () => {
            resetComposer();
        });
        openCreateBtn.dataset.openBound = 'true';
    }

    if (attachmentsInput) {
        if (attachmentsInput.dataset.taskFilesBound !== 'true') {
            attachmentsInput.addEventListener('change', () => {
                const newlySelected = Array.from(attachmentsInput.files || []);
                if (!newlySelected.length) return;

                taskFilesState.pendingFiles = newlySelected;
                syncInputFiles();
                renderSelectedFiles();
            });
            attachmentsInput.dataset.taskFilesBound = 'true';
        }
    }

    if (selectedFilesList) {
        if (selectedFilesList.dataset.removeBound !== 'true') {
            selectedFilesList.addEventListener('click', (e) => {
                const removePendingBtn = e.target.closest('.remove-pending-file-btn');
                if (removePendingBtn) {
                    e.preventDefault();
                    const index = parseInt(removePendingBtn.dataset.fileIndex, 10);
                    if (!isNaN(index) && index >= 0 && index < taskFilesState.pendingFiles.length) {
                        taskFilesState.pendingFiles.splice(index, 1);
                        syncInputFiles();
                        renderSelectedFiles();
                    }
                }
            });
            selectedFilesList.dataset.removeBound = 'true';
        }
    }

    if (existingAttachmentsList && existingAttachmentsList.dataset.removeBound !== 'true') {
        existingAttachmentsList.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-existing-note-attachment-btn');
            if (!removeBtn) return;
            e.preventDefault();

            const attId = parseInt(removeBtn.dataset.attachmentId, 10);
            if (!isNaN(attId)) {
                if (!taskFilesState.removedAttachmentIds.includes(attId)) {
                    taskFilesState.removedAttachmentIds.push(attId);
                }
                const itemEl = document.getElementById(`existing_task_note_att_${attId}`);
                if (itemEl) {
                    itemEl.remove();
                }
                const remaining = (document.getElementById('existing-task-note-attachments-list') || existingAttachmentsList).querySelectorAll('[id^="existing_task_note_att_"]');
                const existingSec = document.getElementById('existing-task-note-attachments-section') || existingAttachmentsSection;
                if (!remaining.length && existingSec) {
                    existingSec.classList.add('hidden');
                }
            }
        });
        existingAttachmentsList.dataset.removeBound = 'true';
    }

    async function openEditNoteModal(noteId) {
        if (!noteId) return;

        try {
            const response = await fetch(`/tasks/${taskId}/notes/${noteId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                Alert.error(result.message || 'Failed to load note details.');
                return;
            }

            const data = result.data;

            resetComposer();
            taskFilesState.currentEditingNoteId = data.id;

            const titleEl = document.getElementById('task-note-modal-title') || modalTitle;
            if (titleEl) {
                titleEl.textContent = 'Edit Note & Files';
            }
            const subtitleEl = document.getElementById('task-note-modal-subtitle') || modalSubtitle;
            if (subtitleEl) {
                subtitleEl.textContent = 'Update your note and manage supporting files.';
            }
            const btnEl = document.getElementById('saveTaskNote') || saveBtn;
            if (btnEl) {
                btnEl.textContent = 'Update';
            }

            const activeEditor = taskFilesState.editor || taskNoteEditor;
            if (activeEditor) {
                activeEditor.root.innerHTML = data.description || '';
            }

            const existingSec = document.getElementById('existing-task-note-attachments-section') || existingAttachmentsSection;
            const existingList = document.getElementById('existing-task-note-attachments-list') || existingAttachmentsList;

            if (existingSec && existingList) {
                if (data.attachments && data.attachments.length > 0) {
                    existingList.innerHTML = data.attachments.map((att) => {
                        const fileSizeKb = att.file_size ? (att.file_size / 1024).toFixed(1) : '0';
                        return `
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-bgray-200 bg-white p-2.5 dark:border-darkblack-400 dark:bg-darkblack-500" id="existing_task_note_att_${att.id}">
                                <a href="${att.url || '#'}" target="_blank" class="flex items-center gap-2.5 min-w-0 flex-1 hover:text-success-300">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-bgray-100 dark:bg-darkblack-600 overflow-hidden">
                                        ${att.is_image && att.url ? `<img src="${att.url}" alt="${att.original_name}" class="h-full w-full object-cover" />` : `
                                            <svg class="h-5 w-5 text-bgray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.5L14.5 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        `}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-bgray-900 dark:text-white" title="${att.original_name}">${att.original_name}</p>
                                        <p class="text-[10px] text-bgray-500">${fileSizeKb} KB</p>
                                    </div>
                                </a>
                                <button type="button" class="remove-existing-note-attachment-btn text-xs text-red-500 hover:text-red-700 hover:underline shrink-0 px-2 py-1" data-attachment-id="${att.id}">
                                    Remove
                                </button>
                            </div>
                        `;
                    }).join('');
                    existingSec.classList.remove('hidden');
                } else {
                    existingList.innerHTML = '';
                    existingSec.classList.add('hidden');
                }
            }

            taskNoteModal.open();
        } catch (error) {
            console.error('Error loading note details:', error);
            Alert.error(error.message || 'An error occurred while fetching note details.');
        }
    }

    taskFilesState.openEditNoteModal = openEditNoteModal;

    if (!taskFilesState.listenersBound) {
        bindTaskNoteListeners();
    }

    if (!saveBtn || !taskNoteEditor) {
        return;
    }

    if (saveBtn.dataset.taskFilesInitialized === 'true') {
        return;
    }

    saveBtn.addEventListener('click', async () => {
        const isEdit = Boolean(taskFilesState.currentEditingNoteId);
        const activeEditor = taskFilesState.editor || taskNoteEditor;
        const description = activeEditor ? activeEditor.root.innerHTML.trim() : '';
        const plainText = activeEditor ? activeEditor.getText().trim() : '';
        const existingList = document.getElementById('existing-task-note-attachments-list') || existingAttachmentsList;
        const remainingExistingCount = existingList
            ? existingList.querySelectorAll('[id^="existing_task_note_att_"]').length
            : 0;

        if (!plainText && !taskFilesState.pendingFiles.length && !remainingExistingCount) {
            Alert.error('Please add a note or attach at least one file.');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = isEdit ? 'Updating...' : 'Saving...';

        try {
            const formData = new FormData();
            formData.append('description', description);
            formData.append('notes_page', String(getCurrentNotesPage()));

            taskFilesState.pendingFiles.forEach(file => {
                formData.append('attachments[]', file);
            });

            if (isEdit) {
                formData.append('_method', 'PUT');
                taskFilesState.removedAttachmentIds.forEach(id => {
                    formData.append('removed_attachment_ids[]', String(id));
                });
            }

            const url = isEdit
                ? `/tasks/${taskId}/notes/${taskFilesState.currentEditingNoteId}`
                : `/tasks/${taskId}/notes`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || (isEdit ? 'Failed to update note.' : 'Failed to save note.'));
            }

            replaceNotesHistory(data.html, data.current_page);
            taskNoteModal.close();
            Alert.success(data.message);
        } catch (error) {
            console.error(error);
            Alert.error(error.message || 'Something went wrong. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = isEdit ? 'Update' : 'Save';
        }
    });

    saveBtn.dataset.taskFilesInitialized = 'true';

    function replaceNotesHistory(html, currentPage) {
        const notesHistory = document.getElementById('task-notes-history');

        if (!notesHistory || !html) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const newHistory = wrapper.querySelector('#task-notes-history') || (wrapper.firstElementChild?.id === 'task-notes-history' ? wrapper.firstElementChild : null);

        if (newHistory) {
            notesHistory.outerHTML = newHistory.outerHTML;
        } else {
            notesHistory.outerHTML = html;
        }

        updateNotesPageInUrl(currentPage);
    }

    function getCurrentNotesPage() {
        const params = new URLSearchParams(window.location.search);
        return Number(params.get('notes_page') || 1);
    }

    function updateNotesPageInUrl(currentPage) {
        if (!currentPage) {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('notes_page', String(currentPage));
        window.history.replaceState({}, '', url);
    }

    async function loadNotesPagination(target) {
        let pageNum = 1;
        if (typeof target === 'number') {
            pageNum = target;
        } else if (typeof target === 'string') {
            try {
                const parsed = new URL(target, window.location.origin);
                pageNum = parseInt(parsed.searchParams.get('notes_page'), 10) || 1;
            } catch (e) {
                pageNum = parseInt(target, 10) || 1;
            }
        }

        const notesHistory = document.getElementById('task-notes-history');
        if (notesHistory) {
            notesHistory.style.opacity = '0.5';
            notesHistory.style.pointerEvents = 'none';
        }

        const tabsUrlTemplate = tabsRoot?.dataset.tabsUrlTemplate || `/tasks/${taskId}/tabs/__TAB__`;
        const baseUrl = tabsUrlTemplate.replace('__TAB__', 'notes');
        const url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('notes_page', String(pageNum));
        url.searchParams.set('notes_only', '1');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Task-Notes-Pagination': 'true',
                },
            });

            const data = await response.json();

            if (!response.ok || (data.status !== undefined && !data.status) || (data.success !== undefined && !data.success)) {
                throw new Error(data.message || 'Failed to load notes page.');
            }

            const htmlContent = data.html || '';
            replaceNotesHistory(htmlContent, pageNum);
        } catch (error) {
            console.error('Error loading notes page:', error);
            Alert.error(error.message || 'Failed to load notes page.');
            if (notesHistory) {
                notesHistory.style.opacity = '';
                notesHistory.style.pointerEvents = '';
            }
        }
    }

    taskFilesState.loadNotesPagination = loadNotesPagination;

    async function deleteNote(button) {
        const noteId = button.dataset.noteId || button.closest('form')?.dataset.id;

        const result = await Alert.confirm({
            title: 'Delete Note',
            text: 'Are you sure you want to delete this note?',
            type: 'error',
        });

        if (!result.isConfirmed) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(`/tasks/${taskId}/notes/${noteId}?notes_page=${getCurrentNotesPage()}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to delete note.');
            }

            replaceNotesHistory(data.html, data.current_page);
            Alert.success(data.message);
        } catch (error) {
            console.error(error);
            Alert.error(error.message || 'Failed to delete note.');
            button.disabled = false;
        }
    }

    async function deleteNoteFile(button) {
        const noteId = button.dataset.noteId;
        const attachmentId = button.dataset.attachmentId;

        const result = await Alert.confirm({
            title: 'Remove File',
            text: 'Are you sure you want to remove this file?',
            type: 'error',
        });

        if (!result.isConfirmed) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(`/tasks/${taskId}/notes/${noteId}/attachments/${attachmentId}?notes_page=${getCurrentNotesPage()}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to remove file.');
            }

            replaceNotesHistory(data.html, data.current_page);
            Alert.success(data.message);
        } catch (error) {
            console.error(error);
            Alert.error(error.message || 'Failed to remove file.');
            button.disabled = false;
        }
    }

    function bindTaskNoteListeners() {
        document.addEventListener('click', async function (event) {
            const editButton = event.target.closest('.edit-task-note');
            const deleteNoteButton = event.target.closest('.delete-task-note');
            const deleteFileButton = event.target.closest('.delete-task-note-file');
            const paginationLink = event.target.closest('#task-notes-history [data-task-notes-pagination] a, #task-notes-history nav a, #task-notes-history .pagination a');

            if (editButton) {
                event.preventDefault();
                const noteId = editButton.dataset.noteId;
                if (noteId && typeof taskFilesState.openEditNoteModal === 'function') {
                    await taskFilesState.openEditNoteModal(noteId);
                }
                return;
            }

            if (deleteNoteButton) {
                event.preventDefault();
                await deleteNote(deleteNoteButton);
                return;
            }

            if (deleteFileButton) {
                event.preventDefault();
                await deleteNoteFile(deleteFileButton);
                return;
            }

            if (paginationLink) {
                event.preventDefault();
                await loadNotesPagination(paginationLink.href);
                return;
            }
        });
        taskFilesState.listenersBound = true;
    }
};

document.addEventListener('DOMContentLoaded', function () {
    initializeTaskFiles();
});

document.addEventListener('task-tab:loaded', function (event) {
    if (event.detail?.tab !== 'notes') {
        return;
    }

    initializeTaskFiles(event.detail.panel);
});
