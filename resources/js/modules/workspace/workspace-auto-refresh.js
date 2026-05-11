const AUTO_REFRESH_INTERVAL_MS = 5 * 60 * 1000;
const CONTROLLER_KEY = '__workspaceAutoRefreshController';
const WORKSPACE_SELECTOR = '[data-user-workspace]';
const TIMELINE_CONTROLLER_KEY = '__workspaceTimelineController';
const KANBAN_CONTROLLER_KEY = '__workspaceKanbanBoardController';

const getWorkspaceRoot = () => document.querySelector(WORKSPACE_SELECTOR);

const getRefreshIndicator = () => getWorkspaceRoot()?.querySelector('[data-workspace-auto-refresh-indicator]') || null;

const isTodaySelected = () => {
    const timelineController = window[TIMELINE_CONTROLLER_KEY];
    const selectedDate = timelineController?.getSelectedDate?.();
    const todayDate = timelineController?.getTodayDate?.();

    return Boolean(selectedDate && todayDate && selectedDate === todayDate);
};

const isWorkspaceModalOpen = () => {
    const workspaceRoot = getWorkspaceRoot();

    if (!workspaceRoot) {
        return false;
    }

    return Boolean(
        workspaceRoot.querySelector('.modal:not(.hidden), [role="dialog"]:not(.hidden)')
    );
};

const isWorkspaceBusy = () => {
    const timelineController = window[TIMELINE_CONTROLLER_KEY];
    const kanbanController = window[KANBAN_CONTROLLER_KEY];

    return Boolean(
        timelineController?.isBusy?.()
        || kanbanController?.isBusy?.()
        || isWorkspaceModalOpen()
    );
};

const toggleRefreshIndicator = (isVisible) => {
    const indicator = getRefreshIndicator();

    if (!indicator) {
        return;
    }

    indicator.classList.toggle('hidden', !isVisible);
    indicator.classList.toggle('flex', isVisible);
};

const runWorkspaceRefresh = async (state) => {
    if (state.isRefreshing || !isTodaySelected() || isWorkspaceBusy()) {
        return;
    }

    const timelineController = window[TIMELINE_CONTROLLER_KEY];
    const kanbanController = window[KANBAN_CONTROLLER_KEY];

    if (!timelineController?.refreshSelectedDate || !kanbanController?.reload) {
        return;
    }

    state.isRefreshing = true;
    toggleRefreshIndicator(true);

    try {
        await Promise.allSettled([
            timelineController.refreshSelectedDate(),
            kanbanController.reload(),
        ]);
    } finally {
        state.isRefreshing = false;
        toggleRefreshIndicator(false);
    }
};

const destroyController = (controller) => {
    if (!controller) {
        return;
    }

    if (controller.intervalId) {
        window.clearInterval(controller.intervalId);
    }

    toggleRefreshIndicator(false);
}

const initWorkspaceAutoRefresh = () => {
    const workspaceRoot = getWorkspaceRoot();

    if (!workspaceRoot) {
        destroyController(window[CONTROLLER_KEY]);
        window[CONTROLLER_KEY] = null;
        return;
    }

    destroyController(window[CONTROLLER_KEY]);

    const state = {
        intervalId: null,
        isRefreshing: false,
    };

    state.intervalId = window.setInterval(() => {
        runWorkspaceRefresh(state);
    }, AUTO_REFRESH_INTERVAL_MS);

    window[CONTROLLER_KEY] = {
        ...state,
        destroy: () => destroyController(state),
    };
};

document.addEventListener('DOMContentLoaded', initWorkspaceAutoRefresh);
window.addEventListener('beforeunload', () => {
    destroyController(window[CONTROLLER_KEY]);
});
