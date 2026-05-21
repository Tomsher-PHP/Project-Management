import Alert from '../../alert';
import { initTimepicker } from '../../components/timepicker';

const MODAL_SELECTOR = '[data-break-work-request-modal]';
const FORM_SELECTOR = '[data-break-work-request-form]';
const TRIGGER_SELECTOR = '[data-break-work-request-trigger]';
const CLOSE_SELECTOR = '[data-break-work-request-close]';

const getModal = () => document.querySelector(MODAL_SELECTOR);
const getForm = () => document.querySelector(FORM_SELECTOR);

const getField = (name) => getForm()?.querySelector(`[name="${name}"]`) || null;

const isModalOpen = (modal) => modal && !modal.classList.contains('hidden');
const isSubmitting = (form) => form?.dataset.submitting === 'true';

const parseTimeValue = (value) => {
    if (!value || !/^\d{1,2}:\d{2}$/.test(value)) {
        return null;
    }

    const [hours, minutes] = value.split(':').map(Number);

    if (
        Number.isNaN(hours) ||
        Number.isNaN(minutes) ||
        hours < 0 ||
        hours > 23 ||
        minutes < 0 ||
        minutes > 59
    ) {
        return null;
    }

    const date = new Date();
    date.setHours(hours, minutes, 0, 0);

    return date;
};

const dispatchFieldEvents = (field) => {
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
};

const setPickerValue = (field, value) => {
    if (!field) {
        return;
    }

    const normalizedValue = value || '';
    const parsedTime = parseTimeValue(normalizedValue);

    if (field._flatpickr) {
        if (parsedTime) {
            field._flatpickr.setDate(parsedTime, false, field._flatpickr.config.dateFormat || 'H:i');
        } else {
            field._flatpickr.clear(false);
        }
    }

    field.value = normalizedValue;
    dispatchFieldEvents(field);
};

const resetErrors = (modal) => {
    if (!modal) {
        return;
    }

    modal.querySelectorAll('[data-break-work-request-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    modal.querySelectorAll('[data-break-work-request-form] input, [data-break-work-request-form] textarea').forEach((field) => {
        field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    });
};

const showFieldError = (modal, fieldName, message) => {
    const errorNode = modal?.querySelector(`[data-break-work-request-error="${fieldName}"]`);
    const field = getField(fieldName);

    if (errorNode) {
        errorNode.textContent = message;
        errorNode.classList.remove('hidden');
    }

    if (field) {
        field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    }
};

const formatBreakDateLabel = (value) => {
    if (!value) {
        return '--';
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    const date = new Date(year, month - 1, day);

    return new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
};

const openModal = (trigger) => {
    const modal = getModal();
    const form = getForm();

    if (!modal || !form) {
        return;
    }

    resetErrors(modal);
    form.reset();

    const workDate = trigger.dataset.breakDate || '';
    const startTime = trigger.dataset.breakStart || '';
    const endTime = trigger.dataset.breakEnd || '';
    const dateLabel = trigger.dataset.breakDateLabel || formatBreakDateLabel(workDate);

    const workDateField = modal.querySelector('[data-break-work-request-work-date]');
    const originalStartField = modal.querySelector('[data-break-work-request-original-start]');
    const originalEndField = modal.querySelector('[data-break-work-request-original-end]');
    const dateLabelNode = modal.querySelector('[data-break-work-request-date-label]');
    const descriptionField = modal.querySelector('[data-break-work-request-description]');
    const startField = modal.querySelector('[data-break-work-request-start-time]');
    const endField = modal.querySelector('[data-break-work-request-end-time]');

    if (workDateField) {
        workDateField.value = workDate;
    }

    if (originalStartField) {
        originalStartField.value = startTime;
    }

    if (originalEndField) {
        originalEndField.value = endTime;
    }

    if (dateLabelNode) {
        dateLabelNode.textContent = dateLabel || '--';
    }

    setPickerValue(startField, startTime);
    setPickerValue(endField, endTime);

    if (descriptionField) {
        descriptionField.value = '';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');

    window.setTimeout(() => {
        descriptionField?.focus();
    }, 50);
};

const closeModal = (options = {}) => {
    const { force = false } = options;
    const modal = getModal();
    const form = getForm();

    if (!modal || !form) {
        return;
    }

    if (!force && isSubmitting(form)) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    resetErrors(modal);
    form.reset();
    form.dataset.submitting = 'false';
    const dateLabelNode = modal.querySelector('[data-break-work-request-date-label]');

    if (dateLabelNode) {
        dateLabelNode.textContent = '--';
    }
};

const handleValidationErrors = (modal, errors = {}) => {
    Object.entries(errors).forEach(([field, messages]) => {
        const message = Array.isArray(messages) ? messages[0] : messages;

        if (message) {
            showFieldError(modal, field, message);
        }
    });
};

const bindBreakWorkRequest = () => {
    const modal = getModal();
    const form = getForm();

    if (!modal || !form || modal.dataset.breakWorkRequestInitialized === 'true') {
        return;
    }

    initTimepicker('[data-break-work-request-time]', {}, modal);

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(TRIGGER_SELECTOR);

        if (trigger) {
            if (isSubmitting(form)) {
                return;
            }

            openModal(trigger);
            return;
        }

        const closeButton = event.target.closest(CLOSE_SELECTOR);

        if (closeButton && closeButton.closest(MODAL_SELECTOR)) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isModalOpen(modal)) {
            closeModal();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting(form)) {
            return;
        }

        resetErrors(modal);

        const submitButton = form.querySelector('[data-break-work-request-submit]');
        const formData = new FormData(form);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        form.dataset.submitting = 'true';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
        }

        try {
            const response = await fetch(form.dataset.storeUrl || form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (response.status === 422) {
                handleValidationErrors(modal, payload.errors || {});
                throw new Error(payload.message || 'Please correct the highlighted fields.');
            }

            if (!response.ok || payload.status === false) {
                throw new Error(payload.message || 'Unable to submit the break work request.');
            }

            closeModal({ force: true });
            Alert.success(payload.message || 'Break work request submitted successfully.');
        } catch (error) {
            if (error?.message === 'Please correct the highlighted fields.') {
                return;
            }

            Alert.error(error?.message || 'Unable to submit the break work request.');
        } finally {
            form.dataset.submitting = 'false';

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Request';
            }
        }
    });

    modal.dataset.breakWorkRequestInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', bindBreakWorkRequest);
