import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-extend-request-approve-modal]');
    const form = document.querySelector('[data-extend-request-approve-form]');

    if (!modal || !form) return;

    const projectNameNode = modal.querySelector('[data-approve-project-name]');
    const taskNameNode = modal.querySelector('[data-approve-task-name]');
    const userNameNode = modal.querySelector('[data-approve-user-name]');
    const currentEstimateNode = modal.querySelector('[data-approve-current-estimate]');
    const reasonNode = modal.querySelector('[data-approve-reason]');
    const submitBtn = form.querySelector('[data-extend-request-approve-submit]');

    const resetErrors = () => {
        form.querySelectorAll('[data-extend-request-approve-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        form.querySelectorAll('input, [data-estimated-hours], [data-estimated-extra-minutes]').forEach(input => {
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        });
    };

    const showFieldError = (field, message) => {
        const errorEl = form.querySelector(`[data-extend-request-approve-error="${field}"]`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        }
        if (field === 'new_estimated_time_minutes') {
            const timeInputs = form.querySelectorAll('[data-estimated-hours], [data-estimated-extra-minutes]');
            timeInputs.forEach(el => {
                el.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            });
        }
    };

    const openModal = async (trigger) => {
        resetErrors();
        form.reset();

        const actionUrl = trigger.dataset.action || '';
        const detailsUrl = trigger.dataset.detailsUrl || '';

        form.action = actionUrl;

        const wrapper = form.querySelector('[data-estimated-time]');
        const totalInput = wrapper ? wrapper.querySelector('[data-estimated-total-minutes]') : null;
        const hoursInput = wrapper ? wrapper.querySelector('[data-estimated-hours]') : null;
        const minutesInput = wrapper ? wrapper.querySelector('[data-estimated-extra-minutes]') : null;

        // Reset to default/empty values before fetching
        if (totalInput) {
            totalInput.value = '0';
        }
        if (wrapper) {
            wrapper.dispatchEvent(new CustomEvent('estimated-time:refresh'));
        }

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        if (!detailsUrl) {
            return;
        }

        const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Approve';

        // Disable inputs and submit button during load
        if (hoursInput) hoursInput.disabled = true;
        if (minutesInput) minutesInput.disabled = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Loading...';
        }

        try {
            const response = await fetch(detailsUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            if (response.ok && result.status && result.data) {
                if (projectNameNode) projectNameNode.textContent = result.data.project_name || '--';
                if (taskNameNode) taskNameNode.textContent = result.data.task_name || '--';
                if (userNameNode) userNameNode.textContent = result.data.user_name || '--';
                if (currentEstimateNode) currentEstimateNode.textContent = result.data.current_estimate_formatted || '--';
                if (reasonNode) reasonNode.textContent = result.data.reason || '--';

                if (totalInput) {
                    totalInput.value = String(result.data.new_estimated_time_minutes);
                }
            } else {
                Alert.error(result.message || 'Failed to load request details.');
                closeModal();
            }
        } catch (err) {
            console.error('Error fetching request details:', err);
            Alert.error('A network error occurred.');
            closeModal();
        } finally {
            if (wrapper) {
                wrapper.dispatchEvent(new CustomEvent('estimated-time:refresh'));
            }
            if (hoursInput) hoursInput.disabled = false;
            if (minutesInput) minutesInput.disabled = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        resetErrors();
        form.reset();
    };

    // Event Delegation for dynamic/ajax loaded tables or standard triggers
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-extend-request-approve-open]');
        if (trigger) {
            openModal(trigger);
            return;
        }

        const closeBtn = e.target.closest('[data-extend-request-approve-close]');
        if (closeBtn) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (form.dataset.submitting === 'true') return;

        resetErrors();
        form.dataset.submitting = 'true';

        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Approving...';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        const submitUrl = form.action;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.status) {
                closeModal();
                if (window.Toast && window.Toast.success) {
                    window.Toast.success(data.message || 'Request approved successfully.');
                } else {
                    Alert.success(data.message || 'Request approved successfully.');
                }
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                    });
                } else {
                    Alert.error(data.message || 'An error occurred.');
                }
            }
        } catch (err) {
            console.error('Error submitting request:', err);
            Alert.error('A network error occurred.');
        } finally {
            form.dataset.submitting = 'false';
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});
