import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    // Developer Notes now lives inside the shared Task Activity modal.
    const sharedModal = document.getElementById('task-insights-modal');

    if (!sharedModal) {
        console.error('Task Activity modal was not found.');
        return;
    }

    const loading = sharedModal.querySelector('#developer-notes-loading');
    const notesList = sharedModal.querySelector('#developer-notes-list');
    const emptyState = sharedModal.querySelector('#developer-notes-empty');
    const errorBox = sharedModal.querySelector('#developer-notes-error');
    const readonlyMessage = sharedModal.querySelector('#developer-notes-readonly');
    const editableDevNotes = sharedModal.querySelector('#editable-dev-notes');
    const form = sharedModal.querySelector('#developer-notes-form');
    const noteInput = sharedModal.querySelector('#developer-note-input');
    const submitButton = sharedModal.querySelector('#developer-notes-submit');

    const noteLabel = sharedModal.querySelector(
        'label[for="developer-note-input"]'
    );

    if (
        !loading ||
        !notesList ||
        !emptyState ||
        !errorBox ||
        !readonlyMessage ||
        !editableDevNotes ||
        !form ||
        !noteInput ||
        !submitButton
    ) {
        console.error('Developer Notes content is missing required elements.');
        return;
    }

    let activeStoreUrl = null;
    let activeDeleteUrl = null;
    let activeTrigger = null;
    let isSubmitting = false;
    let hasExistingNote = false;

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content;

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function clearError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    /**
     * Update the Overview Developer Notes section without reloading.
     */
    function updateDeveloperNotesDisplay(content) {
        const taskId = String(activeTrigger?.dataset.taskId || '');

        if (!taskId) return;

        const displays = document.querySelectorAll(
            '[data-task-developer-notes-display]'
        );

        displays.forEach((display) => {
            const taskContainer = display.closest('[data-task-id]');

            if (
                taskContainer &&
                String(taskContainer.dataset.taskId) !== taskId
            ) {
                return;
            }

            const existingText = display.querySelector(
                '[data-task-developer-notes-text]'
            );

            const emptyMessage = display.querySelector(
                '[data-task-developer-notes-empty]'
            );

            const noteContent = content ?? '';

            if (noteContent.trim() !== '') {
                if (existingText) {
                    existingText.textContent = noteContent;
                } else {
                    const paragraph = document.createElement('p');

                    paragraph.className =
                        'text-sm text-bgray-600 dark:text-bgray-300 whitespace-pre-wrap break-words';

                    paragraph.setAttribute(
                        'data-task-developer-notes-text',
                        ''
                    );

                    paragraph.textContent = noteContent;

                    if (emptyMessage) {
                        emptyMessage.replaceWith(paragraph);
                    } else {
                        display.prepend(paragraph);
                    }
                }
            } else {
                existingText?.remove();

                if (!emptyMessage) {
                    const paragraph = document.createElement('p');

                    paragraph.className =
                        'text-sm italic text-bgray-600 dark:text-bgray-300';

                    paragraph.setAttribute(
                        'data-task-developer-notes-empty',
                        ''
                    );

                    paragraph.textContent =
                        'No developer notes added yet.';

                    display.prepend(paragraph);
                }
            }
        });
    }

    /**
     * Update the Overview Add/Edit Notes button and Task Activity button.
     */
    function updateOverviewNotesButton(hasNote) {
        const taskId = String(activeTrigger?.dataset.taskId || '');

        if (!taskId) return;

        document.querySelectorAll(
            '[data-developer-notes-trigger], [data-task-activity-trigger]'
        ).forEach((button) => {
            if (String(button.dataset.taskId) !== taskId) return;

            button.dataset.hasDeveloperNote = hasNote ? '1' : '0';

            button.setAttribute(
                'data-has-developer-note',
                hasNote ? '1' : '0'
            );

            const label = button.querySelector(
                '[data-developer-notes-label]'
            );

            if (label) {
                label.textContent = hasNote ? 'Edit Notes' : 'Add Notes';
            }
        });
    }

    /**
     * Update the task card's developer-note state without reloading.
     */
    function updateTaskCardDeveloperNoteState(hasNote) {
        const taskId = String(activeTrigger?.dataset.taskId || '');

        if (!taskId) return;

        const value = hasNote ? '1' : '0';

        // Update Kanban cards and other task containers.
        document.querySelectorAll('[data-task-id]').forEach((element) => {
            if (String(element.dataset.taskId) !== taskId) return;

            element.dataset.hasDeveloperNote = value;

            element.setAttribute(
                'data-has-developer-note',
                value
            );
        });

        // Update Developer Notes and Task Activity triggers.
        // document.querySelectorAll(
        //     '[data-developer-notes-trigger], [data-task-activity-trigger]'
        // ).forEach((button) => {
        //     if (String(button.dataset.taskId) !== taskId) return;

        //     button.dataset.hasDeveloperNote = value;

        //     button.setAttribute(
        //         'data-has-developer-note',
        //         value
        //     );

        //     button.classList.toggle(
        //         '!text-blue-600',
        //         hasNote
        //     );

        //     button.classList.toggle(
        //         'dark:!text-blue-400',
        //         hasNote
        //     );

        //     button.classList.toggle(
        //         'text-gray-500',
        //         !hasNote
        //     );

        //     button.classList.toggle(
        //         'dark:text-bgray-300',
        //         !hasNote
        //     );
        // });
        // Update Developer Notes and Task Activity triggers.
        document.querySelectorAll(
            '[data-developer-notes-trigger], [data-task-activity-trigger]'
        ).forEach((button) => {
            if (String(button.dataset.taskId) !== taskId) return;

            button.dataset.hasDeveloperNote = value;
            button.setAttribute('data-has-developer-note', value);

            button.classList.toggle('!text-blue-600', hasNote);
            button.classList.toggle('dark:!text-blue-400', hasNote);
            button.classList.toggle('text-gray-500', !hasNote);
            button.classList.toggle('dark:text-bgray-300', !hasNote);

            // Update the Developer Notes count and total Activity count.
            if (button.matches('[data-task-activity-trigger]')) {
                const developerNotesCount = hasNote ? 1 : 0;
                const commentsCount = Number(button.dataset.commentsCount || 0);

                button.dataset.developerNotesCount = String(developerNotesCount);

                const totalCount = commentsCount + developerNotesCount;

                const countElement = button.querySelector(
                    '[data-task-activity-count]'
                );

                if (countElement) {
                    countElement.textContent = String(totalCount);
                }

                button.title =
                    `Completion Notes: ${developerNotesCount} | Comments: ${commentsCount}`;

                button.setAttribute(
                    'aria-label',
                    `Task Activity: ${developerNotesCount} developer notes and ${commentsCount} comments`
                );
            }
        });

        // Keep the Task Status dropdown options synchronized.
        document.querySelectorAll(
            '[data-task-status-option]'
        ).forEach((option) => {
            if (String(option.dataset.taskId) !== taskId) return;

            option.dataset.hasDeveloperNote = value;

            option.setAttribute(
                'data-has-developer-note',
                value
            );
        });
    }

    /**
     * Reset the Developer Notes panel before loading another task.
     */
    function resetModalState() {
        clearError();

        loading.classList.remove('hidden');

        notesList.replaceChildren();
        notesList.classList.add('hidden');

        emptyState.classList.add('hidden');

        form.classList.add('hidden');
        readonlyMessage.classList.add('hidden');
        editableDevNotes.classList.remove('hidden');

        noteInput.value = '';
        noteInput.readOnly = false;

        noteInput.classList.remove(
            'pointer-events-none',
            'select-none'
        );

        submitButton.classList.remove('hidden');
        submitButton.disabled = false;
        submitButton.textContent = 'Save Note';

        if (noteLabel) {
            noteLabel.textContent = 'Developer Note';
        }

        hasExistingNote = false;
    }

    /**
     * Load Developer Notes using the existing modal endpoint.
     */
    async function loadNotes(button) {
        resetModalState();

        activeTrigger = button;
        activeStoreUrl = button.dataset.storeUrl || null;
        activeDeleteUrl = button.dataset.deleteUrl || null;

        const notesUrl = button.dataset.developerNotesUrl;

        if (!notesUrl) {
            showError('Developer Notes URL is missing.');
            loading.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(notesUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Unable to load developer notes.'
                );
            }

            const note = data.note || null;

            hasExistingNote = Boolean(note?.note);

            updateTaskCardDeveloperNoteState(hasExistingNote);

            const isReadOnly =
                Boolean(data.is_completed) || !data.can_edit;

            noteInput.value = note?.note || '';
            noteInput.readOnly = isReadOnly;

            if (isReadOnly) {
                if (noteLabel) {
                    noteLabel.textContent = 'Developer Note (Read-Only)';
                }

                readonlyMessage.classList.remove('hidden');
                readonlyMessage.classList.add('flex');

                form.classList.remove('hidden');
                submitButton.classList.add('hidden');

                if (data.is_completed) {
                    editableDevNotes.classList.add('hidden');
                }

                return;
            }

            if (noteLabel) {
                noteLabel.textContent = 'Developer Note';
            }

            form.classList.remove('hidden');
            submitButton.classList.remove('hidden');

            submitButton.textContent = hasExistingNote
                ? 'Update Note'
                : 'Save Note';

        } catch (error) {
            showError(
                error.message || 'Unable to load developer notes.'
            );
        } finally {
            loading.classList.add('hidden');
        }
    }

    /**
     * The shared modal dispatches this event when Developer Notes is opened.
     * This replaces the old standalone Developer Notes modal click handler.
     */
    document.addEventListener(
        'task-insights:developer-notes:open',
        (event) => {
            const trigger = event.detail?.trigger;

            if (!trigger) {
                showError('Unable to identify the selected task.');
                return;
            }

            loadNotes(trigger);
        }
    );

    // Save or update the single developer note.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting || !activeStoreUrl || noteInput.readOnly) {
            return;
        }

        clearError();

        const note = noteInput.value.trim();

        // If an existing note is cleared, ask for confirmation before deleting.
        if (hasExistingNote && note === '') {
            try {
                const result = await Alert.confirm({
                    title: 'Remove Developer Note?',
                    text: 'Are you sure you want to remove this developer note? Developer notes are required before completing the task.',
                    confirmText: 'Yes, remove',
                    cancelText: 'Cancel',
                });

                const confirmed =
                    result === true || result?.isConfirmed === true;

                if (!confirmed) {
                    noteInput.focus();
                    return;
                }

                await deleteDeveloperNote();

            } catch (error) {
                console.error(
                    'Developer note removal confirmation error:',
                    error
                );

                showError('Unable to confirm developer note removal.');
            }

            return;
        }

        if (!note) {
            showError('Please enter a developer note.');
            noteInput.focus();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        try {
            const response = await fetch(activeStoreUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ note }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.errors?.note?.[0] ||
                    data.message ||
                    'Unable to save the developer note.'
                );
            }

            const savedContent = data.note?.note ?? note;

            noteInput.value = savedContent;
            hasExistingNote = true;

            // Update the Overview section and Kanban card.
            updateDeveloperNotesDisplay(savedContent);
            updateOverviewNotesButton(true);
            updateTaskCardDeveloperNoteState(true);

            submitButton.textContent = 'Update Note';

            // Notify Kanban that the required Developer Note has been saved.
            // If this task was being dragged to a completed column,
            // Kanban will automatically resume that original drag.
            document.dispatchEvent(
                new CustomEvent('task-insights:developer-notes:saved', {
                    detail: {
                        taskId: String(activeTrigger?.dataset.taskId || ''),
                    },
                })
            );

            Alert.success('Developer note saved successfully.');

            // Close the shared Task Activity modal.
            document.dispatchEvent(
                new CustomEvent('task-insights:close')
            );

        } catch (error) {
            showError(
                error.message || 'Unable to save the developer note.'
            );
        } finally {
            isSubmitting = false;
            submitButton.disabled = false;

            if (submitButton.textContent === 'Saving...') {
                submitButton.textContent = hasExistingNote
                    ? 'Update Note'
                    : 'Save Note';
            }
        }
    });

    /**
     * Delete the existing developer note.
     */
    async function deleteDeveloperNote() {
        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Removing...';

        try {
            if (!activeDeleteUrl) {
                throw new Error('Developer note delete URL is missing.');
            }

            const response = await fetch(activeDeleteUrl, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Unable to remove the developer note.'
                );
            }

            noteInput.value = '';
            hasExistingNote = false;

            // Update the Overview section and Kanban card.
            updateDeveloperNotesDisplay('');
            updateOverviewNotesButton(false);
            updateTaskCardDeveloperNoteState(false);

            submitButton.textContent = 'Save Note';

            Alert.success('Developer note removed successfully.');

            // Close the shared Task Activity modal.
            document.dispatchEvent(
                new CustomEvent('task-insights:close')
            );

        } catch (error) {
            showError(
                error.message || 'Unable to remove the developer note.'
            );
        } finally {
            isSubmitting = false;
            submitButton.disabled = false;

            if (submitButton.textContent === 'Removing...') {
                submitButton.textContent = hasExistingNote
                    ? 'Update Note'
                    : 'Save Note';
            }
        }
    }
});
