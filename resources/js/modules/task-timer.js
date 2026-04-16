export function initTaskTimer() {

    // -----------------------------
    // LIVE TIMER DISPLAY
    // -----------------------------
    const timerEl = document.getElementById('task-timer-display');
    const timerText = document.getElementById('timer-text');

    let interval = null;
    let baseSeconds = 0;
    let startedAt = null;

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

    // Initialize timer ONLY if exists
    if (timerEl && timerText) {
        baseSeconds = parseInt(timerEl.dataset.totalSeconds || 0);
        startedAt = timerEl.dataset.startedAt;

        timerText.innerText = formatTime(baseSeconds);
        startLiveTimer(startedAt, baseSeconds, timerText);
    }

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

        try {
            btn.disabled = true;
            btn.innerText = 'Processing...';

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

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
            }

        } catch (error) {
            Alert.error(error.message || 'Something went wrong');
            btn.innerText = originalText;
        } finally {
            btn.disabled = false;
        }
    });
}