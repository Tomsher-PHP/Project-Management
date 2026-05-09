const DISPLAY_SELECTOR = '[data-task-timer-display]';
const TEXT_SELECTOR = '[data-task-timer-text]';
const ROOT_SELECTOR = '[data-task-timer-root]';
const BUTTON_SELECTOR = '.task-timer-btn';

const activeIntervals = new WeakMap();

const START_ICON = `
    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3 2.25V9.75L9.25 6L3 2.25Z" />
    </svg>
`;

const STOP_ICON = `
    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect x="2" y="2" width="8" height="8" rx="1.5" />
    </svg>
`;

const RUNNING_CLASS = 'task-timer-btn--running';

const parseSeconds = (value) => {
    const parsed = Number.parseInt(value || 0, 10);
    return Number.isNaN(parsed) ? 0 : Math.max(parsed, 0);
};

const formatTime = (seconds) => {
    const totalSeconds = Math.max(parseSeconds(seconds), 0);
    const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const s = String(totalSeconds % 60).padStart(2, '0');

    return `${h}:${m}:${s}`;
};

const getDisplays = (taskId = null) => [...document.querySelectorAll(DISPLAY_SELECTOR)]
    .filter((display) => taskId === null || String(display.dataset.taskId || '') === String(taskId));

const getButtons = (taskId) => [...document.querySelectorAll(`${BUTTON_SELECTOR}[data-task-id="${taskId}"]`)];

const getRoots = (taskId) => [...document.querySelectorAll(`${ROOT_SELECTOR}[data-task-id="${taskId}"]`)];

const getTextNode = (display) => display?.querySelector(TEXT_SELECTOR) || display?.querySelector('#timer-text');

const stopLiveTimer = (display) => {
    const activeInterval = activeIntervals.get(display);

    if (activeInterval) {
        window.clearInterval(activeInterval);
        activeIntervals.delete(display);
    }
};

const renderDisplay = (display, totalSeconds) => {
    const textNode = getTextNode(display);
    if (!textNode) {
        return;
    }

    textNode.innerText = formatTime(totalSeconds);

    if (display.dataset.compareEstimated === 'true') {
        const estimatedSeconds = parseSeconds(display.dataset.estimatedSeconds);

        display.classList.remove(
            'text-bgray-500',
            'dark:text-bgray-300',
            'text-success-400',
            'dark:text-success-300',
            'text-error-300',
            'dark:text-red-300'
        );

        if (estimatedSeconds <= 0) {
            display.classList.add('text-bgray-500', 'dark:text-bgray-300');
            return;
        }

        if (parseSeconds(totalSeconds) <= estimatedSeconds) {
            display.classList.add('text-success-400', 'dark:text-success-300');
            return;
        }

        display.classList.add('text-error-300', 'dark:text-red-300');
    }
};

const startLiveTimer = (display) => {
    const startedAt = display?.dataset.startedAt;
    if (!display || !startedAt) {
        return;
    }

    stopLiveTimer(display);

    const startedAtMs = new Date(startedAt).getTime();
    const baseSeconds = parseSeconds(display.dataset.totalSeconds);

    const tick = () => {
        const elapsedSeconds = Math.max(Math.floor((Date.now() - startedAtMs) / 1000), 0);
        renderDisplay(display, baseSeconds + elapsedSeconds);
    };

    tick();
    activeIntervals.set(display, window.setInterval(tick, 1000));
};

const syncDisplaysFromDom = () => {
    getDisplays().forEach((display) => {
        stopLiveTimer(display);
        renderDisplay(display, parseSeconds(display.dataset.totalSeconds));

        if (display.dataset.startedAt) {
            startLiveTimer(display);
        }
    });
};

const getCurrentTaskSeconds = (taskId) => {
    const activeDisplay = getDisplays(taskId)[0];
    if (activeDisplay) {
        const startedAt = activeDisplay.dataset.startedAt;
        const baseSeconds = parseSeconds(activeDisplay.dataset.totalSeconds);

        if (!startedAt) {
            return baseSeconds;
        }

        return baseSeconds + Math.max(Math.floor((Date.now() - new Date(startedAt).getTime()) / 1000), 0);
    }

    const button = getButtons(taskId)[0];
    return parseSeconds(button?.dataset.totalSeconds);
};

const ensureDisplay = (root) => {
    if (!root) {
        return null;
    }

    let display = root.querySelector(DISPLAY_SELECTOR);

    if (display) {
        return display;
    }

    display = document.createElement('div');
    display.className = 'text-[10px] font-semibold text-success-500';
    display.dataset.taskTimerDisplay = '';
    display.dataset.taskId = root.dataset.taskId || '';
    display.innerHTML = '<span data-task-timer-text>00:00:00</span>';

    const button = root.querySelector(BUTTON_SELECTOR);
    if (button) {
        root.insertBefore(display, button);
    } else {
        root.appendChild(display);
    }

    return display;
};

const setDisplayState = (display, { startedAt = '', totalSeconds = 0, hidden = false }) => {
    if (!display) {
        return;
    }

    display.dataset.startedAt = startedAt || '';
    display.dataset.totalSeconds = String(parseSeconds(totalSeconds));
    display.classList.toggle('hidden', hidden);

    stopLiveTimer(display);
    renderDisplay(display, parseSeconds(totalSeconds));

    if (startedAt) {
        startLiveTimer(display);
    }
};

const setButtonRunningState = (button, { isRunning, totalSeconds }) => {
    if (!button) {
        return;
    }

    const isStartDisabled = button.dataset.startDisabled === '1';
    const canControlTimer = button.dataset.canControlTimer !== '0';
    const canShowRunningIndicator = button.dataset.enableRunningIndicator === '1';

    button.dataset.running = isRunning ? '1' : '0';
    button.dataset.totalSeconds = String(parseSeconds(totalSeconds));
    button.innerHTML = button.dataset.buttonStyle === 'icon'
        ? (isRunning ? STOP_ICON : START_ICON)
        : (isRunning ? 'Stop' : 'Start');
    button.dataset.timerStateSynced = '1';
    button.disabled = !canControlTimer || (!isRunning && isStartDisabled);
    button.setAttribute('aria-label', isRunning ? 'Stop timer' : 'Start timer');
    button.setAttribute('title', button.getAttribute('title') || (isRunning ? 'Stop timer' : 'Start timer'));

    button.classList.remove(
        'bg-success-400',
        'hover:bg-success-300',
        'bg-error-300',
        'hover:bg-red-500',
        'cursor-not-allowed',
        'bg-bgray-200',
        'text-bgray-500',
        'bg-bgray-300',
        'text-bgray-600',
        'dark:bg-darkblack-400',
        'dark:text-bgray-300',
        RUNNING_CLASS
    );

    if (isRunning && canShowRunningIndicator) {
        button.classList.add(RUNNING_CLASS);
    } else if (isRunning) {
        button.classList.add('bg-error-300', 'hover:bg-red-500');
    } else if (isStartDisabled) {
        button.classList.add('cursor-not-allowed');

        if (button.dataset.disabledVariant === 'strong') {
            button.classList.add('bg-bgray-300', 'text-bgray-600', 'dark:bg-darkblack-400', 'dark:text-bgray-300');
        } else {
            button.classList.add('bg-bgray-200', 'text-bgray-500', 'dark:bg-darkblack-400', 'dark:text-bgray-300');
        }
    } else {
        button.classList.add('bg-success-400', 'hover:bg-success-300');
    }
};

const setButtonsBusyState = (buttons, isBusy, fallbackText = '') => {
    buttons.forEach((button) => {
        if (isBusy) {
            button.dataset.originalDisabled = button.disabled ? '1' : '0';
            button.dataset.originalLabel = button.dataset.buttonStyle === 'icon' ? button.innerHTML : button.innerText;
            button.disabled = true;
            button.classList.add('opacity-70');

            if (button.dataset.buttonStyle !== 'icon') {
                button.innerText = 'Processing...';
            }
            return;
        }

        if (fallbackText) {
            if (button.dataset.buttonStyle === 'icon') {
                button.innerHTML = fallbackText;
            } else {
                button.innerText = fallbackText;
            }
        } else if (!button.dataset.timerStateSynced && button.dataset.originalLabel) {
            if (button.dataset.buttonStyle === 'icon') {
                button.innerHTML = button.dataset.originalLabel;
            } else {
                button.innerText = button.dataset.originalLabel;
            }
        }

        if (button.dataset.timerStateSynced !== '1') {
            button.disabled = button.dataset.originalDisabled === '1';
        }

        button.classList.remove('opacity-70');
        delete button.dataset.originalDisabled;
        delete button.dataset.originalLabel;
        delete button.dataset.timerStateSynced;
    });
};

const requiresNonAssigneeConfirmation = (button) => {
    const currentUserId = String(button?.dataset.currentUserId || '').trim();
    const assigneeId = String(button?.dataset.assigneeId || '').trim();

    return currentUserId !== '' && assigneeId !== '' && currentUserId !== assigneeId;
};

const confirmNonAssigneeStop = async (button, fallbackAssigneeName = '') => {
    const assigneeName = fallbackAssigneeName || button?.dataset.assigneeName || 'the assignee';
    const taskName = button?.dataset.taskName || 'this task';
    const shortTaskName = taskName.length > 15 ? `${taskName.slice(0, 15)}...` : taskName;

    return Alert.confirm({
        title: 'Stop Timer?',
        text: `This task is assigned to ${assigneeName}. Do you want to stop the running timer for ${shortTaskName}?`,
        confirmText: 'Yes, stop timer',
        cancelText: 'Cancel',
        requireText: 'STOP',
    });
};

const syncTaskTimerState = (taskId, nextState) => {
    getButtons(taskId).forEach((button) => {
        setButtonRunningState(button, nextState);
    });

    getRoots(taskId).forEach((root) => {
        const display = ensureDisplay(root);
        const shouldPersist = root.dataset.taskTimerPersistDisplay === 'true';

        if (!display) {
            return;
        }

        setDisplayState(display, {
            startedAt: nextState.startedAt || '',
            totalSeconds: nextState.totalSeconds,
            hidden: !shouldPersist && !nextState.startedAt && parseSeconds(nextState.totalSeconds) === 0,
        });

        if (!shouldPersist && !nextState.startedAt && parseSeconds(nextState.totalSeconds) > 0) {
            display.classList.add('hidden');
        }
    });
};

export function initTaskTimer() {
    syncDisplaysFromDom();

    document.addEventListener('task-timer:refresh', syncDisplaysFromDom);
    document.addEventListener('task-timer:stopped-remotely', (event) => {
        const taskId = event.detail?.taskId;

        if (!taskId) {
            return;
        }

        syncTaskTimerState(String(taskId), {
            isRunning: false,
            startedAt: '',
            totalSeconds: parseSeconds(event.detail?.totalSeconds),
        });
    });

    if (!window.__taskTimerObserver) {
        window.__taskTimerObserver = new MutationObserver((mutations) => {
            const hasTimerMarkupChange = mutations.some((mutation) => [...mutation.addedNodes].some((node) => {
                if (!(node instanceof HTMLElement)) {
                    return false;
                }

                return node.matches?.(DISPLAY_SELECTOR)
                    || node.matches?.(ROOT_SELECTOR)
                    || node.querySelector?.(DISPLAY_SELECTOR)
                    || node.querySelector?.(ROOT_SELECTOR);
            }));

            if (hasTimerMarkupChange) {
                syncDisplaysFromDom();
            }
        });

        window.__taskTimerObserver.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    document.addEventListener('click', async (event) => {
        const button = event.target.closest(BUTTON_SELECTOR);
        if (!button) {
            return;
        }

        if (button.disabled) {
            event.preventDefault();
            return;
        }

        const taskId = button.dataset.taskId;
        const isRunning = button.dataset.running === '1';
        const url = isRunning ? `/tasks/${taskId}/stop` : `/tasks/${taskId}/start`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const relatedButtons = getButtons(taskId);

        let payload = {};

        if (isRunning && requiresNonAssigneeConfirmation(button)) {
            const confirmation = await confirmNonAssigneeStop(button);

            if (!confirmation.isConfirmed) {
                return;
            }

            payload.confirmed_non_assignee_stop = true;
        }

        try {
            setButtonsBusyState(relatedButtons, true);

            const sendRequest = async (requestPayload = {}) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(requestPayload),
                });

                const data = await response.json();
                return { response, data };
            };

            let { response, data } = await sendRequest(payload);

            if (
                isRunning
                && !response.ok
                && data?.requires_confirmation
                && !payload.confirmed_non_assignee_stop
            ) {
                const confirmation = await confirmNonAssigneeStop(button, data.assignee_name || '');

                if (!confirmation.isConfirmed) {
                    setButtonsBusyState(relatedButtons, false);
                    syncTaskTimerState(taskId, {
                        isRunning: true,
                        startedAt: getDisplays(taskId)[0]?.dataset.startedAt || '',
                        totalSeconds: getCurrentTaskSeconds(taskId),
                    });
                    return;
                }

                payload.confirmed_non_assignee_stop = true;
                ({ response, data } = await sendRequest(payload));
            }

            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            if (isRunning) {
                const nextTotalSeconds = parseSeconds(data?.data?.duration_seconds) + parseSeconds(button.dataset.totalSeconds);
                syncTaskTimerState(taskId, {
                    isRunning: false,
                    startedAt: '',
                    totalSeconds: nextTotalSeconds || getCurrentTaskSeconds(taskId),
                });

                if (String(button.dataset.currentUserId || '') === String(button.dataset.assigneeId || '')) {
                    document.dispatchEvent(new CustomEvent('navbar-running-task-timer:hide'));
                }

                Alert.success(data.message || 'Timer stopped');
            } else {
                const startedAt = new Date().toISOString();
                const nextTotalSeconds = parseSeconds(button.dataset.totalSeconds);

                syncTaskTimerState(taskId, {
                    isRunning: true,
                    startedAt,
                    totalSeconds: nextTotalSeconds,
                });

                document.dispatchEvent(new CustomEvent('navbar-running-task-timer:show', {
                    detail: {
                        active: true,
                        taskId,
                        taskName: button.dataset.taskName || '',
                        seconds: nextTotalSeconds,
                        baseSeconds: nextTotalSeconds,
                        estimatedSeconds: parseSeconds(getDisplays(taskId)[0]?.dataset.estimatedSeconds),
                        startedAt,
                        stopUrl: `/tasks/${taskId}/stop`,
                        state: 'running',
                    },
                }));

                Alert.success(data.message || 'Timer started');
            }

            document.dispatchEvent(new CustomEvent('task-history:changed', {
                detail: { taskId },
            }));
        } catch (error) {
            Alert.error(error.message || 'Something went wrong');
            syncTaskTimerState(taskId, {
                isRunning,
                startedAt: getDisplays(taskId)[0]?.dataset.startedAt || '',
                totalSeconds: getCurrentTaskSeconds(taskId),
            });
        } finally {
            setButtonsBusyState(relatedButtons, false);
        }
    });
}
