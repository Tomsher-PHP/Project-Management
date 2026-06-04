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

    if (!taskId) {
        return;
    }

    document.dispatchEvent(new CustomEvent('task-timer:stopped-remotely', {
        detail: {
            taskId,
            totalSeconds: payload?.total_seconds ?? 0,
        },
    }));
    document.dispatchEvent(new CustomEvent('task-timer:refresh'));

    const navbarState = window.navbarRunningTaskTimer?.getState?.();

    if (String(navbarState?.taskId || '') === taskId) {
        if (navbarTimer?.active === true || navbarTimer?.active === '1' || navbarTimer?.shouldShowTimer === true) {
            document.dispatchEvent(new CustomEvent('navbar-running-task-timer:update', {
                detail: navbarTimer,
            }));
        } else {
            document.dispatchEvent(new CustomEvent('navbar-running-task-timer:hide'));
        }
    }
};

const initializeTaskStatusDropdown = () => {
    if (document.body.dataset.taskStatusDropdownInitialized === 'true') {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-task-status-trigger]');

        if (trigger) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = trigger.closest('[data-task-status-dropdown]');
            const menu = dropdown?.querySelector('[data-task-status-menu]');

            if (!dropdown || !menu) {
                return;
            }

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
            const includeTaskDetail = option.dataset.includeTaskDetail === 'true';

            closeAllTaskStatusMenus();

            if (!taskId || !statusId || !transitionUrl || statusId === currentStatusId) {
                return;
            }

            setTaskStatusOptionsDisabled(dropdown, true);

            try {
                const response = await fetch(transitionUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        status_id: statusId,
                        moved_task_id: taskId,
                        task_ids: [taskId],
                        include_task_detail: includeTaskDetail,
                    }),
                });
                const result = await parseJsonResponse(response);

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to update the task status.');
                }

                syncAutoStoppedTimer(result.timer_stopped, result.navbar_timer);

                document.dispatchEvent(new CustomEvent('task-status:changed', {
                    detail: {
                        taskId,
                        statusId,
                        statusType: result.status_type,
                        response: result,
                    },
                }));

                Alert.success(result.message || 'Task status updated successfully.');
            } catch (error) {
                Alert.error(error.message || 'Unable to update the task status.');
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
