const taskFilesState = {
    editor: null,
    listenersBound: false,
};

const initializeTaskFiles = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const tabsRoot = document.querySelector('[data-task-tabs]');
    const taskId = tabsRoot?.dataset.taskId;
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
            modules: {
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
            bindTaskFileDeleteListeners(token, taskId);
        }

        return;
    }

    if (saveBtn.dataset.taskFilesInitialized === 'true') {
        if (!taskFilesState.listenersBound) {
            bindTaskFileDeleteListeners(token, taskId);
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
        bindTaskFileDeleteListeners(token, taskId);
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

    function bindTaskFileDeleteListeners(listenerToken, listenerTaskId) {
        document.addEventListener('click', async function (event) {
            const deleteNoteButton = event.target.closest('.delete-task-note');
            const deleteFileButton = event.target.closest('.delete-task-note-file');

            if (deleteNoteButton) {
                await deleteNote(deleteNoteButton);
            }

            if (deleteFileButton) {
                await deleteNoteFile(deleteFileButton);
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
