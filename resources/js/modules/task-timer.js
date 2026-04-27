export function initTaskTimer() {

    // -----------------------------
    // LIVE TIMER DISPLAY
    // -----------------------------
    let interval = null;

    function formatTime(seconds) {
        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    function startLiveTimer(startedAt, baseSeconds = 0, textEl) {
        if (!textEl) return;

        const startTime = startedAt ? new Date(startedAt).getTime() : null;

        interval = setInterval(() => {
            let total = baseSeconds;

            if (startTime) {
                const now = Date.now();
                total += Math.floor((now - startTime) / 1000);
            }

            textEl.innerText = formatTime(total);
        }, 1000);
    }

    function stopLiveTimer() {
        if (interval) {
            clearInterval(interval);
            interval = null;
        }
    }

    function syncTimerFromDom() {
        stopLiveTimer();

        const timerEl = document.getElementById('task-timer-display');
        const timerText = document.getElementById('timer-text');

        if (!timerEl || !timerText) {
            return;
        }

        const baseSeconds = parseInt(timerEl.dataset.totalSeconds || 0, 10);
        const startedAt = timerEl.dataset.startedAt;

        timerText.innerText = formatTime(baseSeconds);
        startLiveTimer(startedAt, baseSeconds, timerText);
    }

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

    syncTimerFromDom();
    document.addEventListener('task-timer:refresh', syncTimerFromDom);

    // -----------------------------
    // START / STOP HANDLER
    // -----------------------------
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.task-timer-btn');
        if (!btn) return;

        const taskId = btn.dataset.taskId;
        const isRunning = btn.dataset.running === '1';

        const url = isRunning
            ? `/tasks/${taskId}/stop`
            : `/tasks/${taskId}/start`;

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        let originalText = btn.innerText;
        let payload = {};

        if (isRunning && requiresNonAssigneeConfirmation(btn)) {
            const confirmation = await confirmNonAssigneeStop(btn);

            if (!confirmation.isConfirmed) {
                return;
            }

            payload.confirmed_non_assignee_stop = true;
        }

        try {
            btn.disabled = true;
            btn.innerText = 'Processing...';

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
                const confirmation = await confirmNonAssigneeStop(btn, data.assignee_name || '');

                if (!confirmation.isConfirmed) {
                    btn.innerText = originalText;
                    return;
                }

                payload.confirmed_non_assignee_stop = true;
                ({ response, data } = await sendRequest(payload));
            }

            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            if (isRunning) {
                // STOP
                btn.innerText = 'Start';
                btn.dataset.running = '0';

                btn.classList.remove('bg-error-300', 'hover:bg-red-500');
                btn.classList.add('bg-success-400', 'hover:bg-success-300');

                stopLiveTimer();

                const timerContainer = document.getElementById('task-timer-display');
                if (timerContainer) timerContainer.remove();

                Alert.success(data.message || 'Timer stopped');
                document.dispatchEvent(new CustomEvent('task-history:changed', {
                    detail: { taskId },
                }));

            } else {
                // START
                btn.innerText = 'Stop';
                btn.dataset.running = '1';

                btn.classList.remove('bg-success-400', 'hover:bg-success-300');
                btn.classList.add('bg-error-300', 'hover:bg-red-500');

                // Create timer UI dynamically
                let container = document.getElementById('task-timer-display');

                if (!container) {
                    container = document.createElement('div');
                    container.id = 'task-timer-display';
                    container.className = 'flex items-center gap-2 text-sm font-semibold text-success-500';

                    container.innerHTML = `⏱ <span id="timer-text">00:00:00</span>`;

                    btn.parentElement.insertBefore(container, btn);
                }

                const now = new Date();
                container.dataset.startedAt = now.toISOString();

                startLiveTimer(now.toISOString());

                Alert.success(data.message || 'Timer started');
                document.dispatchEvent(new CustomEvent('task-history:changed', {
                    detail: { taskId },
                }));
            }

        } catch (error) {
            Alert.error(error.message || 'Something went wrong');
            btn.innerText = originalText;
        } finally {
            btn.disabled = false;
        }
    });
}
