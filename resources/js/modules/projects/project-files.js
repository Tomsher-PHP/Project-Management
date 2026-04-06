const projectFilesState = {
    editor: null,
    listenersBound: false,
};

const initializeProjectFiles = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const projectId = window.ProjectApp?.id;
    const canCreateNotesFiles = Boolean(window.ProjectApp?.canCreateNotesFiles);
    const canRemoveNotesFiles = Boolean(window.ProjectApp?.canRemoveNotesFiles);
    const modal = root.querySelector ? root.querySelector('[data-project-note-modal]') : document.querySelector('[data-project-note-modal]');
    const modalOpenButton = root.querySelector ? root.querySelector('[data-project-note-modal-open]') : document.querySelector('[data-project-note-modal-open]');
    const modalCloseButtons = modal ? Array.from(modal.querySelectorAll('[data-project-note-modal-close]')) : [];
    const saveBtn = root.querySelector ? root.querySelector('#saveProjectNote') : document.getElementById('saveProjectNote');
    const attachmentsInput = root.querySelector ? root.querySelector('#note-attachments-input') : document.getElementById('note-attachments-input');
    const selectedFilesList = root.querySelector ? root.querySelector('#selected-note-files') : document.getElementById('selected-note-files');
    let pendingFiles = [];

    if (!projectId) {
        return;
    }

    const editorElement = root.querySelector ? root.querySelector('#project-note') : document.getElementById('project-note');
    const projectNoteEditor = editorElement && editorElement.dataset.quillInitialized !== 'true'
        ? new Quill(editorElement, {
            theme: 'snow',
            readOnly: !canCreateNotesFiles,
            placeholder: canCreateNotesFiles ? 'Write a project note...' : 'No note editor available.',
            modules: {
                toolbar: canCreateNotesFiles ? [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'header': [1, 2, 3, false] }],
                    ['link']
                ] : false
            }
        })
        : projectFilesState.editor;

    if (editorElement && editorElement.dataset.quillInitialized !== 'true' && projectNoteEditor) {
        editorElement.dataset.quillInitialized = 'true';
        projectFilesState.editor = projectNoteEditor;
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

        if (projectNoteEditor) {
            projectNoteEditor.setContents([]);
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
            projectNoteEditor?.focus();
        }, 50);
    };

    if (attachmentsInput) {
        if (attachmentsInput.dataset.projectFilesBound !== 'true') {
            attachmentsInput.addEventListener('change', () => {
                pendingFiles = Array.from(attachmentsInput.files || []);
                renderSelectedFiles();
            });
            attachmentsInput.dataset.projectFilesBound = 'true';
        }
    }

    if (modal && modal.dataset.projectNoteModalInitialized !== 'true') {
        modalOpenButton?.addEventListener('click', openModal);
        modalCloseButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        modal.dataset.projectNoteModalInitialized = 'true';
    }

    if (!saveBtn || !projectNoteEditor) {
        if (!projectFilesState.listenersBound && canRemoveNotesFiles) {
            bindProjectFileDeleteListeners(token, projectId);
        }

        return;
    }

    if (saveBtn.dataset.projectFilesInitialized === 'true') {
        if (!projectFilesState.listenersBound && canRemoveNotesFiles) {
            bindProjectFileDeleteListeners(token, projectId);
        }

        return;
    }

    saveBtn.addEventListener('click', async () => {
        const description = projectNoteEditor.root.innerHTML.trim();

        if (!canCreateNotesFiles) {
            Alert.error('You do not have permission to add notes.');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const formData = new FormData();
            formData.append('description', description);
            formData.append('notes_page', '1');

            if (canCreateNotesFiles) {
                pendingFiles.forEach(file => {
                    formData.append('attachments[]', file);
                });
            }

            const response = await fetch(`/projects/${projectId}/notes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to save note.');
            }

            replaceNotesHistory(data.html, data.current_page);
            closeModal();
            Alert.success(data.message);
        } catch (error) {
            console.error(error);
            Alert.error(error.message || 'Something went wrong. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    });

    saveBtn.dataset.projectFilesInitialized = 'true';

    if (!projectFilesState.listenersBound && canRemoveNotesFiles) {
        bindProjectFileDeleteListeners(token, projectId);
    }

    function replaceNotesHistory(html, currentPage) {
        const notesHistory = document.getElementById('project-notes-history');

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
            type: 'error'
        });

        if (!result.isConfirmed) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(`/projects/${projectId}/notes/${noteId}?notes_page=${getCurrentNotesPage()}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
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
            type: 'error'
        });

        if (!result.isConfirmed) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(`/projects/${projectId}/notes/${noteId}/attachments/${attachmentId}?notes_page=${getCurrentNotesPage()}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
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

    function bindProjectFileDeleteListeners(listenerToken, listenerProjectId) {
        document.addEventListener('click', async function (event) {
            const deleteNoteButton = event.target.closest('.delete-project-note');
            const deleteFileButton = event.target.closest('.delete-project-note-file');

            if (deleteNoteButton) {
                await deleteNote(deleteNoteButton);
            }

            if (deleteFileButton) {
                await deleteNoteFile(deleteFileButton);
            }
        });
        projectFilesState.listenersBound = true;
    }
};

document.addEventListener('DOMContentLoaded', function () {
    initializeProjectFiles();
});

document.addEventListener('project-tab:loaded', function (event) {
    if (event.detail?.tab !== 'notes') {
        return;
    }

    initializeProjectFiles(event.detail.panel);
});
