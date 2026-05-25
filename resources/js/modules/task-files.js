const taskFilesState = {
    editor: null,
    listenersBound: false,
};

const initializeTaskFiles = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const tabsRoot = document.querySelector('[data-task-tabs]');
    const taskId = tabsRoot?.dataset.taskId;
    const tabsUrlTemplate = tabsRoot?.dataset.tabsUrlTemplate;
    const modal = root.querySelector ? root.querySelector('[data-task-note-modal]') : document.querySelector('[data-task-note-modal]');
    const modalOpenButton = root.querySelector ? root.querySelector('[data-task-note-modal-open]') : document.querySelector('[data-task-note-modal-open]');
    const modalCloseButtons = modal ? Array.from(modal.querySelectorAll('[data-task-note-modal-close]')) : [];
    const saveBtn = root.querySelector ? root.querySelector('#saveTaskNote') : document.getElementById('saveTaskNote');
    const attachmentsInput = root.querySelector ? root.querySelector('#task-note-attachments-input') : document.getElementById('task-note-attachments-input');
    const selectedFilesList = root.querySelector ? root.querySelector('#selected-task-note-files') : document.getElementById('selected-task-note-files');
    let pendingFiles = [];

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

    const renderSelectedFiles = () => {
        if (!selectedFilesList) {
            return;
        }

        if (!pendingFiles.length) {
            selectedFilesList.innerHTML = '';
            return;
        }

        selectedFilesList.innerHTML = pendingFiles.map(file => `
            <div class="rounded-full bg-success-50 px-3 py-1 text-sm text-success-400">
                ${file.name}
            </div>
        `).join('');
    };

    const resetComposer = () => {
        pendingFiles = [];

        if (attachmentsInput) {
            attachmentsInput.value = '';
        }

        if (taskNoteEditor) {
            taskNoteEditor.setContents([]);
        }

        renderSelectedFiles();
    };

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetComposer();
    };

    const openModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        window.setTimeout(() => {
            taskNoteEditor?.focus();
        }, 50);
    };

    if (attachmentsInput && attachmentsInput.dataset.taskFilesBound !== 'true') {
        attachmentsInput.addEventListener('change', () => {
            pendingFiles = Array.from(attachmentsInput.files || []);
            renderSelectedFiles();
        });
        attachmentsInput.dataset.taskFilesBound = 'true';
    }

    if (modal && modal.dataset.taskNoteModalInitialized !== 'true') {
        modalOpenButton?.addEventListener('click', openModal);
        modalCloseButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        modal.dataset.taskNoteModalInitialized = 'true';
    }

    if (!saveBtn || !taskNoteEditor) {
        if (!taskFilesState.listenersBound) {
            bindTaskFileListeners();
        }

        return;
    }

    if (saveBtn.dataset.taskFilesInitialized === 'true') {
        if (!taskFilesState.listenersBound) {
            bindTaskFileListeners();
        }

        return;
    }

    saveBtn.addEventListener('click', async () => {
        const description = taskNoteEditor.root.innerHTML.trim();

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const formData = new FormData();
            formData.append('description', description);
            formData.append('notes_page', '1');

            pendingFiles.forEach(file => {
                formData.append('attachments[]', file);
            });

            const response = await fetch(`/tasks/${taskId}/notes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to save note.');
            }

            replaceNotesHistory(data.html, data.current_page);
            closeModal();
            Alert.success(data.message);
        } catch (error) {
            Alert.error(error.message || 'Something went wrong. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    });

    saveBtn.dataset.taskFilesInitialized = 'true';

    if (!taskFilesState.listenersBound) {
        bindTaskFileListeners();
    }

    function replaceNotesHistory(html, currentPage) {
        const notesHistory = document.getElementById('task-notes-history');

        if (!notesHistory) {
            return;
        }

        notesHistory.outerHTML = html;
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

    async function deleteNote(button) {
        const noteId = button.dataset.noteId;

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
            Alert.error(error.message || 'Failed to remove file.');
            button.disabled = false;
        }
    }

    async function loadNotesPage(pageUrl) {
        if (!tabsUrlTemplate) {
            window.location.assign(pageUrl);
            return;
        }

        const panel = tabsRoot?.querySelector('[data-task-tab-panel="notes"]');
        const requestUrl = new URL(tabsUrlTemplate.replace('__TAB__', 'notes'), window.location.origin);
        const targetUrl = new URL(pageUrl, window.location.origin);

        targetUrl.searchParams.forEach((value, key) => {
            requestUrl.searchParams.set(key, value);
        });

        if (panel) {
            panel.innerHTML = `
                <div class="flex items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 py-12 text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                    Loading Notes...
                </div>
            `;
        }

        const response = await fetch(requestUrl.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();

        if (!response.ok || !data.status) {
            throw new Error(data.message || 'Failed to load notes.');
        }

        if (!panel) {
            return;
        }

        panel.innerHTML = data.html;
        panel.dataset.loaded = 'true';

        document.dispatchEvent(new CustomEvent('task-tab:loaded', {
            detail: { tab: 'notes', panel },
        }));
    }

    function bindTaskFileListeners() {
        document.addEventListener('click', async function (event) {
            const deleteNoteButton = event.target.closest('.delete-task-note');
            const deleteFileButton = event.target.closest('.delete-task-note-file');
            const paginationLink = event.target.closest('#task-notes-history .pagination a, #task-notes-history nav a[rel]');

            if (deleteNoteButton) {
                await deleteNote(deleteNoteButton);
            }

            if (deleteFileButton) {
                await deleteNoteFile(deleteFileButton);
            }

            if (!paginationLink || !tabsUrlTemplate) {
                return;
            }

            event.preventDefault();

            try {
                await loadNotesPage(paginationLink.href);
            } catch (error) {
                Alert.error(error.message || 'Failed to load notes.');
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
