import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-exceed-time-modal]');
    const form = document.querySelector('[data-exceed-time-form]');
    
    if (!modal || !form) return;
    
    const taskNameNode = modal.querySelector('[data-exceed-time-task-name]');
    const currentEstimateNode = modal.querySelector('[data-exceed-time-current-estimate]');
    const submitBtn = form.querySelector('[data-exceed-time-submit]');
    
    const resetErrors = () => {
        form.querySelectorAll('[data-exceed-time-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        form.querySelectorAll('input, textarea').forEach(input => {
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        });
    };
    
    const showFieldError = (field, message) => {
        const errorEl = form.querySelector(`[data-exceed-time-error="${field}"]`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        }
    };
    
    const openModal = (trigger) => {
        resetErrors();
        form.reset();
        
        const taskName = trigger.dataset.taskName || '--';
        const currentEstimate = trigger.dataset.currentEstimate || '--';
        const storeUrl = trigger.dataset.storeUrl || '';
        
        if (taskNameNode) {
            taskNameNode.textContent = taskName;
        }
        if (currentEstimateNode) {
            currentEstimateNode.textContent = currentEstimate;
        }
        form.action = storeUrl;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
    };
    
    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        resetErrors();
        form.reset();
    };
    
    // Delegation for dynamic triggers (since detail-content is loaded via AJAX)
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-request-estimate-change-trigger]');
        if (trigger) {
            openModal(trigger);
            return;
        }
        
        const closeBtn = e.target.closest('[data-exceed-time-modal-close]');
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
        submitBtn.innerHTML = 'Submitting...';
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
                    window.Toast.success(data.message || 'Request submitted successfully.');
                } else {
                    Alert.success(data.message || 'Request submitted successfully.');
                }
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
