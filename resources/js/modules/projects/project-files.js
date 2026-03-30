document.addEventListener('DOMContentLoaded', function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const projectId = window.ProjectApp?.id;
    const canCreateNotesFiles = Boolean(window.ProjectApp?.canCreateNotesFiles);
    const canRemoveNotesFiles = Boolean(window.ProjectApp?.canRemoveNotesFiles);
    const saveBtn = document.getElementById('saveProjectNote');
    const attachmentsInput = document.getElementById('note-attachments-input');
    const selectedFilesList = document.getElementById('selected-note-files');
    const notesList = document.getElementById('project-notes-list');
    const notesCount = document.getElementById('project-notes-count');
    let pendingFiles = [];

    if (!projectId) {
        return;
    }

    const projectNoteEditor = document.getElementById('project-note')
        ? new Quill('#project-note', {
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
        : null;

    if (attachmentsInput) {
        attachmentsInput.addEventListener('change', () => {
            pendingFiles = Array.from(attachmentsInput.files || []);
            renderSelectedFiles();
        });
    }

    if (!saveBtn || !projectNoteEditor) {
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

            document.getElementById('project-notes-empty-state')?.remove();
            notesList?.insertAdjacentHTML('afterbegin', data.html);
            updateNotesCount();
            projectNoteEditor.setContents([]);
            pendingFiles = [];

            if (attachmentsInput) {
                attachmentsInput.value = '';
            }

            renderSelectedFiles();
            Alert.success(data.message);
        } catch (error) {
            console.error(error);
            Alert.error(error.message || 'Something went wrong. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    });

    if (canRemoveNotesFiles) {
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
    }

    function renderSelectedFiles() {
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
    }

    function updateNotesCount() {
        if (!notesList || !notesCount) {
            return;
        }

        const noteCards = notesList.querySelectorAll('article').length;
        notesCount.textContent = `${noteCards} Notes`;
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
            const response = await fetch(`/projects/${projectId}/notes/${noteId}`, {
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

            button.closest('article')?.remove();
            updateNotesCount();
            ensureEmptyNotesState();
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
            const response = await fetch(`/projects/${projectId}/notes/${noteId}/attachments/${attachmentId}`, {
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

            const noteCard = button.closest('article');
            button.closest('[data-note-attachment-id]')?.remove();
            updateAttachmentCount(noteCard);
            ensureAttachmentSectionState(noteCard);
            Alert.success(data.message);
        } catch (error) {
            console.error(error);
            Alert.error(error.message || 'Failed to remove file.');
            button.disabled = false;
        }
    }

    function updateAttachmentCount(noteCard) {
        if (!noteCard) {
            return;
        }

        const fileCount = noteCard.querySelectorAll('.note-attachments-section a').length;
        const countBadge = noteCard.querySelector('.note-attachments-count');

        if (countBadge) {
            countBadge.textContent = `${fileCount} File${fileCount === 1 ? '' : 's'}`;
        }
    }

    function ensureAttachmentSectionState(noteCard) {
        if (!noteCard) {
            return;
        }

        const attachmentsSection = noteCard.querySelector('.note-attachments-section');

        if (!attachmentsSection) {
            return;
        }

        const remainingFiles = attachmentsSection.querySelectorAll('a').length;

        if (!remainingFiles) {
            attachmentsSection.remove();
        }
    }

    function ensureEmptyNotesState() {
        if (!notesList || notesList.querySelector('article')) {
            return;
        }

        if (!document.getElementById('project-notes-empty-state')) {
            const emptyState = document.createElement('div');
            emptyState.id = 'project-notes-empty-state';
            emptyState.className = 'rounded-xl border border-dashed border-bgray-300 px-6 py-10 text-center text-sm text-gray-400 dark:border-darkblack-400';
            emptyState.textContent = 'No project notes and files added yet.';
            notesList.appendChild(emptyState);
        }
    }
});
