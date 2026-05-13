import '../../../css/modules/task-running-timer.css';

const ROOT_SELECTOR = '[data-running-task-timer]';
const CONTROLLER_KEY = '__navbarRunningTaskTimerController';

const PLAY_ICON = `
    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 2.25V9.75L9.25 6L3 2.25Z" />
    </svg>
`;

const STOP_ICON = `
    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <rect x="2" y="2" width="8" height="8" rx="1.5" />
    </svg>
`;

const parseSeconds = (value) => {
    const parsed = Number.parseInt(value || 0, 10);
    return Number.isNaN(parsed) ? 0 : Math.max(parsed, 0);
};

const formatTime = (seconds) => {
    const totalSeconds = parseSeconds(seconds);
    const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const remainingSeconds = String(totalSeconds % 60).padStart(2, '0');

    return `${hours}:${minutes}:${remainingSeconds}`;
};

const getElements = () => {
    const root = document.querySelector(ROOT_SELECTOR);

    if (!root) {
        return null;
    }

    return {
        root,
        taskName: root.querySelector('[data-running-task-name]'),
        timer: root.querySelector('[data-running-task-time]'),
        toggle: root.querySelector('[data-running-task-toggle]'),
        toggleIcon: root.querySelector('[data-running-task-toggle-icon]'),
    };
};

const getStateFromDataset = (root) => ({
    active: root.dataset.runningTaskActive === '1',
    taskId: root.dataset.runningTaskId || '',
    taskName: root.dataset.runningTaskName || '',
    seconds: parseSeconds(root.dataset.runningTaskSeconds),
    baseSeconds: parseSeconds(root.dataset.runningTaskBaseSeconds || root.dataset.runningTaskSeconds),
    estimatedSeconds: parseSeconds(root.dataset.runningTaskEstimatedSeconds),
    startedAt: root.dataset.runningTaskStartedAt || '',
    startUrl: root.dataset.runningTaskStartUrl || '',
    stopUrl: root.dataset.runningTaskStopUrl || '',
    state: root.dataset.runningTaskState === 'running' ? 'running' : 'stopped',
});

const normalizeNavbarState = (payload = {}) => ({
    active: payload.active === true || payload.active === '1' || payload.shouldShowTimer === true,
    taskId: payload.taskId ? String(payload.taskId) : '',
    taskName: payload.taskName || '',
    seconds: parseSeconds(payload.seconds),
    baseSeconds: parseSeconds(payload.baseSeconds ?? payload.seconds),
    estimatedSeconds: parseSeconds(payload.estimatedSeconds),
    startedAt: payload.startedAt || '',
    startUrl: payload.startUrl || '',
    stopUrl: payload.stopUrl || '',
    state: payload.state === 'running' ? 'running' : 'stopped',
});

const setDatasetState = (root, state) => {
    root.dataset.runningTaskActive = state.active ? '1' : '0';
    root.dataset.runningTaskId = state.taskId || '';
    root.dataset.runningTaskName = state.taskName || '';
    root.dataset.runningTaskSeconds = String(parseSeconds(state.seconds));
    root.dataset.runningTaskBaseSeconds = String(parseSeconds(state.baseSeconds ?? state.seconds));
    root.dataset.runningTaskEstimatedSeconds = String(parseSeconds(state.estimatedSeconds));
    root.dataset.runningTaskStartedAt = state.startedAt || '';
    root.dataset.runningTaskStartUrl = state.startUrl || '';
    root.dataset.runningTaskStopUrl = state.stopUrl || '';
    root.dataset.runningTaskState = state.state === 'running' ? 'running' : 'stopped';
};

const renderState = (elements, state) => {
    setDatasetState(elements.root, state);

    elements.root.classList.toggle('hidden', !state.active);

    if (elements.taskName) {
        elements.taskName.textContent = state.taskName || 'Running task';
        elements.taskName.title = state.taskName || '';
    }

    if (elements.timer) {
        elements.timer.textContent = formatTime(state.seconds);
        elements.timer.classList.remove(
            'text-bgray-500',
            'dark:text-bgray-300',
            'text-success-400',
            'dark:text-success-300',
            'text-error-300',
            'dark:text-red-300'
        );

        if (parseSeconds(state.estimatedSeconds) <= 0) {
            elements.timer.classList.add('text-bgray-500', 'dark:text-bgray-300');
        } else if (parseSeconds(state.seconds) <= parseSeconds(state.estimatedSeconds)) {
            elements.timer.classList.add('text-success-400', 'dark:text-success-300');
        } else {
            elements.timer.classList.add('text-error-300', 'dark:text-red-300');
        }
    }

    if (elements.toggleIcon) {
        elements.toggleIcon.innerHTML = state.state === 'running' ? STOP_ICON : PLAY_ICON;
    }

    if (elements.toggle) {
        const canStop = state.state === 'running' && state.active && !!state.stopUrl;
        const canStart = state.state === 'stopped' && state.active && !!state.startUrl;

        elements.toggle.disabled = !(canStop || canStart);
        elements.toggle.setAttribute('aria-label', canStop ? 'Stop running task' : 'Start task timer');
        elements.toggle.setAttribute('title', canStop ? 'Stop running task' : 'Start task timer');
    }
};

const initRunningTaskTimer = () => {
    if (window[CONTROLLER_KEY]?.cleanup) {
        window[CONTROLLER_KEY].cleanup();
    }

    const elements = getElements();

    if (!elements) {
        return;
    }

    let state = getStateFromDataset(elements.root);
    renderState(elements, state);
    let syncIntervalId = null;
    let isStopping = false;
    let isDestroyed = false;

    const update = (nextState = {}) => {
        state = {
            ...state,
            ...nextState,
            seconds: Object.prototype.hasOwnProperty.call(nextState, 'seconds')
                ? parseSeconds(nextState.seconds)
                : state.seconds,
            baseSeconds: Object.prototype.hasOwnProperty.call(nextState, 'baseSeconds')
                ? parseSeconds(nextState.baseSeconds)
                : state.baseSeconds,
            estimatedSeconds: Object.prototype.hasOwnProperty.call(nextState, 'estimatedSeconds')
                ? parseSeconds(nextState.estimatedSeconds)
                : state.estimatedSeconds,
        };

        renderState(elements, state);
    };

    const show = (nextState = {}) => {
        update({
            active: true,
            ...nextState,
        });
    };

    const hide = () => {
        update({
            active: false,
            taskId: '',
            taskName: '',
            seconds: 0,
            baseSeconds: 0,
            estimatedSeconds: 0,
            startedAt: '',
            startUrl: '',
            stopUrl: '',
            state: 'stopped',
        });
    };

    const clearLiveSync = () => {
        if (syncIntervalId) {
            window.clearInterval(syncIntervalId);
            syncIntervalId = null;
        }
    };

    const syncStickyTimerAfterTaskStatusChange = ({ taskId = '', statusType = null, navbarTimer = null } = {}) => {
        if (!taskId || String(state.taskId || '') !== String(taskId)) {
            return;
        }

        if (state.state === 'running') {
            return;
        }

        if (navbarTimer && typeof navbarTimer === 'object') {
            const nextNavbarState = normalizeNavbarState(navbarTimer);

            if (nextNavbarState.active) {
                update(nextNavbarState);
                return;
            }

            hide();
            clearLiveSync();
            return;
        }

        if (statusType && statusType !== 'active') {
            hide();
            clearLiveSync();
        }
    };

    const startLiveSync = () => {
        if (isDestroyed) {
            return;
        }

        clearLiveSync();

        if (!state.active || state.state !== 'running' || !state.startedAt) {
            return;
        }

        const tick = () => {
            if (!state.startedAt) {
                hide();
                clearLiveSync();
                return;
            }

            update({
                seconds: parseSeconds(state.baseSeconds) + Math.max(Math.floor((Date.now() - new Date(state.startedAt).getTime()) / 1000), 0),
            });
        };

        tick();
        syncIntervalId = window.setInterval(tick, 1000);
    };

    const toggleTaskFromNavbar = async () => {
        if (isDestroyed || isStopping || !state.active || !state.taskId) {
            return;
        }

        const isRunning = state.state === 'running';
        const requestUrl = isRunning ? state.stopUrl : state.startUrl;

        if (!requestUrl) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            isStopping = true;
            elements.toggle.disabled = true;
            const currentTaskId = state.taskId;
            const currentBaseSeconds = parseSeconds(state.baseSeconds);

            const response = await fetch(requestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to stop running task');
            }

            const nextNavbarState = normalizeNavbarState(data?.navbar_timer || {});

            clearLiveSync();
            if (nextNavbarState.active) {
                update(nextNavbarState);
                if (nextNavbarState.state === 'running') {
                    startLiveSync();
                }
            } else {
                hide();
            }

            if (isRunning) {
                const stoppedDurationSeconds = parseSeconds(data?.data?.duration_seconds);
                const nextTotalSeconds = currentBaseSeconds + stoppedDurationSeconds;

                document.dispatchEvent(new CustomEvent('task-timer:stopped-remotely', {
                    detail: {
                        taskId: currentTaskId,
                        totalSeconds: nextTotalSeconds,
                    },
                }));
                document.dispatchEvent(new CustomEvent('task-timer:refresh'));
                document.dispatchEvent(new CustomEvent('task-history:changed', {
                    detail: { taskId: currentTaskId },
                }));
            } else {
                document.dispatchEvent(new CustomEvent('task-timer:state-sync', {
                    detail: {
                        taskId: currentTaskId,
                        isRunning: nextNavbarState.state === 'running',
                        startedAt: nextNavbarState.startedAt || '',
                        totalSeconds: nextNavbarState.baseSeconds ?? nextNavbarState.seconds ?? currentBaseSeconds,
                    },
                }));
                document.dispatchEvent(new CustomEvent('task-timer:refresh'));
                document.dispatchEvent(new CustomEvent('task-history:changed', {
                    detail: { taskId: currentTaskId },
                }));
            }
        } catch (error) {
            if (window.Alert?.error) {
                window.Alert.error(error.message || 'Failed to update task timer');
            }
        } finally {
            isStopping = false;
            renderState(elements, state);
        }
    };

    elements.toggle?.addEventListener('click', toggleTaskFromNavbar);

    const handleNavbarUpdate = (event) => {
        update(event.detail || {});
        if ((event.detail || {}).active && (event.detail || {}).state === 'running') {
            startLiveSync();
            return;
        }

        clearLiveSync();
    };

    const handleNavbarShow = (event) => {
        show(event.detail || {});
        startLiveSync();
    };

    const handleNavbarHide = () => {
        hide();
        clearLiveSync();
    };

    const handleTaskStatusChanged = (event) => {
        syncStickyTimerAfterTaskStatusChange({
            taskId: event.detail?.taskId,
            statusType: event.detail?.response?.status_type,
            navbarTimer: event.detail?.response?.navbar_timer,
        });
    };

    const cleanup = () => {
        if (isDestroyed) {
            return;
        }

        isDestroyed = true;
        clearLiveSync();
        elements.toggle?.removeEventListener('click', toggleTaskFromNavbar);
        document.removeEventListener('navbar-running-task-timer:update', handleNavbarUpdate);
        document.removeEventListener('navbar-running-task-timer:show', handleNavbarShow);
        document.removeEventListener('navbar-running-task-timer:hide', handleNavbarHide);
        document.removeEventListener('task-status:changed', handleTaskStatusChanged);
        window.removeEventListener('pagehide', cleanup);
        window.removeEventListener('beforeunload', cleanup);

        if (window[CONTROLLER_KEY]?.cleanup === cleanup) {
            delete window[CONTROLLER_KEY];
        }
    };

    document.addEventListener('navbar-running-task-timer:update', handleNavbarUpdate);
    document.addEventListener('navbar-running-task-timer:show', handleNavbarShow);
    document.addEventListener('navbar-running-task-timer:hide', handleNavbarHide);
    document.addEventListener('task-status:changed', handleTaskStatusChanged);
    window.addEventListener('pagehide', cleanup);
    window.addEventListener('beforeunload', cleanup);

    if (state.active && state.state === 'running') {
        startLiveSync();
    }

    window[CONTROLLER_KEY] = {
        cleanup,
    };

    window.navbarRunningTaskTimer = {
        formatTime,
        getState: () => ({ ...state }),
        update,
        show,
        hide,
        isStopping: () => isStopping,
        stop: toggleTaskFromNavbar,
        toggle: toggleTaskFromNavbar,
        syncAfterTaskStatusChange: syncStickyTimerAfterTaskStatusChange,
        cleanup,
    };
};

document.addEventListener('DOMContentLoaded', initRunningTaskTimer);
