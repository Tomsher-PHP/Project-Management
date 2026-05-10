const SELECTOR = '[data-workspace-user-select]';
const TIMELINE_CONTROLLER_KEY = '__workspaceTimelineController';
const KANBAN_CONTROLLER_KEY = '__workspaceKanbanBoardController';

const getSelect = () => document.querySelector(SELECTOR);

const updateBrowserWorkspaceUser = (userId) => {
    const url = new URL(window.location.href);

    if (userId) {
        url.searchParams.set('user_id', userId);
    } else {
        url.searchParams.delete('user_id');
    }

    url.searchParams.delete('page');
    window.history.replaceState({}, '', url);
};

const setBusyState = (select, isBusy) => {
    if (!select) {
        return;
    }

    select.disabled = isBusy;

    if (!select.tomselect) {
        return;
    }

    if (isBusy) {
        select.tomselect.lock();
        select.tomselect.wrapper.classList.add('opacity-70');
        return;
    }

    select.tomselect.unlock();
    select.tomselect.wrapper.classList.remove('opacity-70');
};

const refreshWorkspaceContext = async () => {
    const timelineController = window[TIMELINE_CONTROLLER_KEY];
    const kanbanController = window[KANBAN_CONTROLLER_KEY];
    const tasks = [];

    if (timelineController?.refreshSelectedDate) {
        tasks.push(timelineController.refreshSelectedDate());
    }

    if (kanbanController?.reload) {
        tasks.push(kanbanController.reload());
    }

    if (!tasks.length) {
        window.location.reload();
        return;
    }

    await Promise.allSettled(tasks);
};

document.addEventListener('DOMContentLoaded', () => {
    const select = getSelect();

    if (!select) {
        return;
    }

    let isUpdating = false;

    select.addEventListener('change', async () => {
        if (isUpdating) {
            return;
        }

        isUpdating = true;
        setBusyState(select, true);
        updateBrowserWorkspaceUser(String(select.value || '').trim());

        try {
            await refreshWorkspaceContext();
        } finally {
            setBusyState(select, false);
            isUpdating = false;
        }
    });
});
