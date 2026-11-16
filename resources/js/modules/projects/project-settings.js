import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', function () {
    // 1. Handle toggle switch clicks using event delegation
    document.body.addEventListener('click', function (e) {
        const switchBtn = e.target.closest('[data-notification-switch]');
        if (!switchBtn || switchBtn.disabled) {
            return;
        }

        e.preventDefault();

        const isChecked = switchBtn.getAttribute('aria-checked') === 'true';
        const newChecked = !isChecked;

        switchBtn.setAttribute('aria-checked', newChecked ? 'true' : 'false');
        switchBtn.classList.toggle('active', newChecked);

        const targetInputId = switchBtn.dataset.targetInput;
        if (targetInputId) {
            const input = document.getElementById(targetInputId);
            if (input) {
                input.value = newChecked ? '1' : '0';
            }
        }
    });

    // 2. Handle Settings Form submit via AJAX with SweetAlert
    document.body.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!form || form.id !== 'project-notification-settings-form') {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const saveBtn = document.getElementById('save-project-settings-btn');
        const spinner = document.getElementById('save-settings-spinner');
        const btnText = document.getElementById('save-settings-btn-text');

        if (saveBtn) {
            saveBtn.disabled = true;
        }
        if (spinner) {
            spinner.classList.remove('hidden');
        }
        if (btnText) {
            btnText.textContent = 'Saving...';
        }

        try {
            const url = form.getAttribute('action');
            const formData = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Alert.success(data.message || 'Notification settings updated successfully.');

                // Realtime update in DOM if returned
                if (data.notifications) {
                    Object.entries(data.notifications).forEach(([key, val]) => {
                        const isEnabled = Boolean(val);
                        const input = document.getElementById(`notification_input_${key}`);
                        const switchBtn = form.querySelector(`[data-target-input="notification_input_${key}"]`);

                        if (input) {
                            input.value = isEnabled ? '1' : '0';
                        }
                        if (switchBtn) {
                            switchBtn.setAttribute('aria-checked', isEnabled ? 'true' : 'false');
                            switchBtn.classList.toggle('active', isEnabled);
                        }
                    });
                }
            } else {
                Alert.error(data.message || 'Failed to update notification settings.');
            }
        } catch (err) {
            console.error('Error saving notification settings:', err);
            Alert.error('An unexpected error occurred while saving settings.');
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
            }
            if (spinner) {
                spinner.classList.add('hidden');
            }
            if (btnText) {
                btnText.textContent = 'Save Settings';
            }
        }
    });
});
