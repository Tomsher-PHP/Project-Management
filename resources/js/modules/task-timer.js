export function initTaskTimer() {
    const buttons = document.querySelectorAll('.task-timer-btn');

    buttons.forEach((btn) => {
        btn.addEventListener('click', async () => {
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

                // Success UI + Alert
                if (isRunning) {
                    btn.innerText = 'Start';
                    btn.dataset.running = '0';

                    btn.classList.remove('bg-error-300', 'hover:bg-red-500');
                    btn.classList.add('bg-success-400', 'hover:bg-success-300');

                    Alert.success(data.message || 'Timer stopped successfully');
                } else {
                    btn.innerText = 'Stop';
                    btn.dataset.running = '1';

                    btn.classList.remove('bg-success-400', 'hover:bg-success-300');
                    btn.classList.add('bg-error-300', 'hover:bg-red-500');

                    Alert.success(data.message || 'Timer started successfully');
                }

            } catch (error) {
                console.error(error);

                Alert.error(error.message || 'Something went wrong');

                btn.innerText = originalText;
            } finally {
                btn.disabled = false;
            }
        });
    });
}