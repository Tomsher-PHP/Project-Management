import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-extend-time-modal]');
    const form = document.querySelector('[data-extend-time-form]');

    if (!modal || !form) return;

    const taskNameNode = modal.querySelector('[data-extend-time-task-name]');
    const currentEstimateNode = modal.querySelector('[data-extend-time-current-estimate]');
    const submitBtn = form.querySelector('[data-extend-time-submit]');

    const setFormAvailability = (disabled) => {
        form.dataset.hasExistingRequest = disabled ? 'true' : 'false';
        form.querySelectorAll('[data-estimated-hours], [data-estimated-extra-minutes], textarea[name="reason"]').forEach(input => {
            input.disabled = disabled;
        });
        if (submitBtn) submitBtn.disabled = disabled;
    };

    const resetErrors = () => {
        form.querySelectorAll('[data-extend-time-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        form.querySelectorAll('input, textarea, [data-estimated-hours], [data-estimated-extra-minutes]').forEach(input => {
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        });
    };

    const showFieldError = (field, message) => {
        const errorEl = form.querySelector(`[data-extend-time-error="${field}"]`);
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
        setFormAvailability(false);

        const taskName = trigger.dataset.taskName || '--';
        const currentEstimate = trigger.dataset.currentEstimate || '--';
        const currentEstimateMinutes = Math.max(0, Number.parseInt(trigger.dataset.currentEstimateMinutes || '0', 10) || 0);
        const storeUrl = trigger.dataset.storeUrl || '';
        const pendingUrl = trigger.dataset.pendingUrl || '';

        if (taskNameNode) {
            taskNameNode.textContent = taskName;
        }
        if (currentEstimateNode) {
            currentEstimateNode.textContent = currentEstimate;
        }
        form.action = storeUrl;

        const wrapper = form.querySelector('[data-estimated-time]');
        const totalInput = wrapper ? wrapper.querySelector('[data-estimated-total-minutes]') : null;
        const reasonTextarea = form.querySelector('[name="reason"]');

        // Default new requests to the task's current estimate. An existing request,
        // when present, is displayed read-only.
        if (totalInput) {
            totalInput.value = String(currentEstimateMinutes);
        }
        if (reasonTextarea) {
            reasonTextarea.value = '';
        }
        if (wrapper) {
            wrapper.dispatchEvent(new CustomEvent('estimated-time:refresh'));
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');

        if (!pendingUrl) {
            return;
        }

        const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit Request';

        // Disable inputs and submit button during load
        setFormAvailability(true);
        if (submitBtn) {
            submitBtn.innerHTML = 'Loading...';
        }

        let hasExistingRequest = false;

        try {
            const response = await fetch(pendingUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            if (response.ok && result.status && result.data) {
                hasExistingRequest = true;
                if (totalInput) {
                    totalInput.value = String(result.data.new_estimated_time_minutes);
                }
                if (reasonTextarea) {
                    reasonTextarea.value = result.data.reason || '';
                }
                const existingRequestMessage = result.data.request_status === 'rejected'
                    ? 'Your extension request was rejected. Only one request is allowed per task.'
                    : 'Only one extension request is permitted.';
                showFieldError('extend_request', existingRequestMessage);
            } else {
                if (totalInput) {
                    totalInput.value = String(currentEstimateMinutes);
                }
                if (reasonTextarea) {
                    reasonTextarea.value = '';
                }
            }
        } catch (err) {
            console.error('Error fetching pending request:', err);
            if (totalInput) {
                totalInput.value = String(currentEstimateMinutes);
            }
            if (reasonTextarea) {
                reasonTextarea.value = '';
            }
        } finally {
            if (wrapper) {
                wrapper.dispatchEvent(new CustomEvent('estimated-time:refresh'));
            }
            setFormAvailability(hasExistingRequest);
            if (submitBtn) {
                submitBtn.innerHTML = originalBtnText;
            }
        }
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        resetErrors();
        form.reset();
        setFormAvailability(false);
    };

    // Delegation for dynamic triggers (since detail-content is loaded via AJAX)
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-request-estimate-change-trigger]');
        if (trigger) {
            openModal(trigger);
            return;
        }

        const closeBtn = e.target.closest('[data-extend-time-modal-close]');
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

        if (form.dataset.hasExistingRequest === 'true') {
            showFieldError('extend_request', 'Only one extension request is permitted.');
            return;
        }

        if (form.dataset.submitting === 'true') return;

        resetErrors();
        form.dataset.submitting = 'true';

        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Submitting...';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        const submitUrl = form.action;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let duplicateRejected = false;

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
                    window.Toast.success(data.message || 'Request submitted successfully.');
                } else {
                    Alert.success(data.message || 'Request submitted successfully.');
                }
            } else {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        const message = field === 'extend_request'
                            ? 'Only one extension request is permitted.'
                            : (Array.isArray(messages) ? messages[0] : messages);
                        showFieldError(field, message);
                    });
                    duplicateRejected = Object.prototype.hasOwnProperty.call(data.errors, 'extend_request');
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
            setFormAvailability(duplicateRejected);
        }
    });
});
