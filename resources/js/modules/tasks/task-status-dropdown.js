import Alert from '../../alert';

const parseJsonResponse = async (response) => {
    try {
        return await response.json();
    } catch (error) {
        return {};
    }
};

const closeAllTaskStatusMenus = (exceptDropdown = null) => {
    document.querySelectorAll('[data-task-status-dropdown]').forEach((dropdown) => {
        if (exceptDropdown && dropdown === exceptDropdown) {
            return;
        }

        dropdown.querySelector('[data-task-status-menu]')?.classList.add('hidden');
    });
};

const setTaskStatusOptionsDisabled = (dropdown, isDisabled) => {
    dropdown?.querySelectorAll('[data-task-status-option]').forEach((option) => {
        option.disabled = isDisabled;
    });
};

const syncAutoStoppedTimer = (payload = null, navbarTimer = null) => {
    const taskId = payload?.task_id ? String(payload.task_id) : '';

    if (!taskId) return;

    document.dispatchEvent(new CustomEvent('task-timer:stopped-remotely', {
        detail: {
            taskId,
            totalSeconds: payload?.total_seconds ?? 0,
        },
    }));

    document.dispatchEvent(new CustomEvent('task-timer:refresh'));

    const navbarState = window.navbarRunningTaskTimer?.getState?.();

    if (String(navbarState?.taskId || '') === taskId) {
        if (
            navbarTimer?.active === true ||
            navbarTimer?.active === '1' ||
            navbarTimer?.shouldShowTimer === true
        ) {
            document.dispatchEvent(new CustomEvent('navbar-running-task-timer:update', {
                detail: navbarTimer,
            }));
        } else {
            document.dispatchEvent(new CustomEvent('navbar-running-task-timer:hide'));
        }
    }
};

/**
 * Find the Developer Notes trigger belonging to the specified task.
 */
const findDeveloperNotesTrigger = (taskId, dropdown = null) => {
    const normalizedTaskId = String(taskId);

    // Prefer a trigger inside the current task container.
    const taskContainer = dropdown?.closest('[data-task-id]');

    const localTrigger = taskContainer?.querySelector(
        '[data-developer-notes-trigger]'
    );

    if (
        localTrigger &&
        String(localTrigger.dataset.taskId) === normalizedTaskId
    ) {
        return localTrigger;
    }

    // Fall back to a trigger elsewhere on the page for the same task.
    return Array.from(
        document.querySelectorAll('[data-developer-notes-trigger]')
    ).find(
        (button) => String(button.dataset.taskId) === normalizedTaskId
    ) || null;
};

/**
 * Open the existing Developer Notes modal.
 */
const openDeveloperNotes = (taskId, dropdown = null) => {
    const trigger = findDeveloperNotesTrigger(taskId, dropdown);

    if (!trigger) {
        Alert.error(
            'The Developer Notes button could not be found for this task.'
        );
        return;
    }

    trigger.click();
};

/**
 * Ask for a required reason before reopening a completed task.
 *
 * Resolves to a non-empty string when confirmed, or null when cancelled.
 */
const requestReopenRemark = () => {
    const modal = document.getElementById('reopen-task-remark-modal');
    const input = document.getElementById('reopen-task-remark-input');
    const error = document.getElementById('reopen-task-remark-error');
    const confirmButton = document.getElementById('confirm-reopen-task');
    const cancelButton = document.getElementById('cancel-reopen-task');

    if (!modal || !input || !error || !confirmButton || !cancelButton) {
        Alert.error('The task reopening modal could not be found.');
        return Promise.resolve(null);
    }

    // Prevent multiple simultaneous reopening prompts.
    if (modal.dataset.requestPending === 'true') {
        return Promise.resolve(null);
    }

    modal.dataset.requestPending = 'true';

    return new Promise((resolve) => {
        input.value = '';
        error.classList.add('hidden');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        input.focus();

        let resolved = false;

        const cleanup = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            delete modal.dataset.requestPending;

            confirmButton.removeEventListener('click', onConfirm);
            cancelButton.removeEventListener('click', onCancel);
        };

        const finish = (remarks) => {
            if (resolved) return;

            resolved = true;
            cleanup();
            resolve(remarks);
        };

        const onConfirm = () => {
            const remarks = input.value.trim();

            if (!remarks) {
                error.classList.remove('hidden');
                input.focus();
                return;
            }

            finish(remarks);
        };

        const onCancel = () => {
            finish(null);
        };

        confirmButton.addEventListener('click', onConfirm);
        cancelButton.addEventListener('click', onCancel);
    });
};

const initializeTaskStatusDropdown = () => {
    if (document.body.dataset.taskStatusDropdownInitialized === 'true') {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest('[data-task-status-trigger]');

        if (trigger) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = trigger.closest('[data-task-status-dropdown]');
            const menu = dropdown?.querySelector('[data-task-status-menu]');

            if (!dropdown || !menu) return;

            const shouldOpen = menu.classList.contains('hidden');

            closeAllTaskStatusMenus(dropdown);
            menu.classList.toggle('hidden', !shouldOpen);

            return;
        }

        const option = event.target.closest('[data-task-status-option]');

        if (option) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = option.closest('[data-task-status-dropdown]');
            const taskId = Number(option.dataset.taskId || 0);
            const statusId = Number(option.dataset.statusId || 0);
            const currentStatusId = Number(option.dataset.currentStatusId || 0);
            const transitionUrl = option.dataset.transitionUrl || '';
            const includeTaskDetail =
                option.dataset.includeTaskDetail === 'true';

            closeAllTaskStatusMenus();

            if (
                !taskId ||
                !statusId ||
                !transitionUrl ||
                statusId === currentStatusId
            ) {
                return;
            }

            setTaskStatusOptionsDisabled(dropdown, true);

            try {
                const isCurrentlyCompleted =
                    option.dataset.isCompleted === '1' ||
                    option.dataset.isCompleted === 'true';

                const willBeCompleted =
                    option.dataset.targetIsCompleted === '1' ||
                    option.dataset.targetIsCompleted === 'true';

                let remarks = null;

                // Incomplete -> Completed: a Developer Note is required.
                if (!isCurrentlyCompleted && willBeCompleted) {
                    const hasDeveloperNote =
                        option.dataset.hasDeveloperNote === '1' ||
                        option.dataset.hasDeveloperNote === 'true';

                    if (!hasDeveloperNote) {
                        Alert.error(
                            'Please add a developer note before moving this task to a completed status.'
                        );

                        // Open the existing modal for this exact task.
                        window.setTimeout(() => {
                            openDeveloperNotes(taskId, dropdown);
                        }, 300);

                        return;
                    }
                }

                // Completed -> Incomplete: a reopening reason is required.
                if (isCurrentlyCompleted && !willBeCompleted) {
                    remarks = await requestReopenRemark();

                    // Cancel means no request and no status change.
                    if (remarks === null) {
                        return;
                    }
                }

                const payload = {
                    status_id: statusId,
                    moved_task_id: taskId,
                    task_ids: [taskId],
                    include_task_detail: includeTaskDetail,
                };

                if (remarks !== null) {
                    payload.remarks = remarks;
                }

                const response = await fetch(transitionUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const result = await parseJsonResponse(response);

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to update the task status.'
                    );
                }

                syncAutoStoppedTimer(
                    result.timer_stopped,
                    result.navbar_timer
                );

                document.dispatchEvent(new CustomEvent('task-status:changed', {
                    detail: {
                        taskId,
                        statusId,
                        statusType: result.status_type,
                        response: result,
                    },
                }));

                Alert.success(
                    result.message || 'Task status updated successfully.'
                );
            } catch (error) {
                Alert.error(
                    error.message || 'Unable to update the task status.'
                );
            } finally {
                setTaskStatusOptionsDisabled(dropdown, false);
            }

            return;
        }

        if (!event.target.closest('[data-task-status-dropdown]')) {
            closeAllTaskStatusMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllTaskStatusMenus();
        }
    });

    document.body.dataset.taskStatusDropdownInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', initializeTaskStatusDropdown);
