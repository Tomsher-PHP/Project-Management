import Alert from '../../alert';
import { initTimepicker } from '../../components/timepicker';

const MODAL_SELECTOR = '[data-break-work-request-modal]';
const FORM_SELECTOR = '[data-break-work-request-form]';
const TRIGGER_SELECTOR = '[data-break-work-request-trigger]';
const CLOSE_SELECTOR = '[data-break-work-request-close]';
const TIMELINE_CONTAINER_SELECTOR = '#workspace-daily-timeline-container';
const TIMELINE_CONTROLLER_KEY = '__workspaceTimelineController';

const MODAL_TITLES = {
    create: 'Request Break Time as Work',
    edit: 'Update Break Work Request',
};

const SUBMIT_LABELS = {
    create: 'Submit Request',
    edit: 'Update Request',
};

const getModal = () => document.querySelector(MODAL_SELECTOR);
const getForm = () => document.querySelector(FORM_SELECTOR);
const getTimelineContainer = () => document.querySelector(TIMELINE_CONTAINER_SELECTOR);
const getField = (name) => getForm()?.querySelector(`[name="${name}"]`) || null;

const isModalOpen = (modal) => modal && !modal.classList.contains('hidden');
const isSubmitting = (form) => form?.dataset.submitting === 'true';

const parseTimeValue = (value) => {
    if (!value || !/^\d{1,2}:\d{2}(?::\d{2})?$/.test(value)) {
        return null;
    }

    const [hours, minutes, seconds = 0] = value.split(':').map(Number);

    if (
        Number.isNaN(hours)
        || Number.isNaN(minutes)
        || Number.isNaN(seconds)
        || hours < 0
        || hours > 23
        || minutes < 0
        || minutes > 59
        || seconds < 0
        || seconds > 59
    ) {
        return null;
    }

    const date = new Date();
    date.setHours(hours, minutes, seconds, 0);

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
            field._flatpickr.setDate(parsedTime, true, field._flatpickr.config.dateFormat || 'H:i:S');
        } else {
            field._flatpickr.clear(false);
        }
    } else {
        field.value = normalizedValue;
    }

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

    modal.querySelectorAll(`${FORM_SELECTOR} input, ${FORM_SELECTOR} textarea`).forEach((field) => {
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

const setModalMode = (modal, form, mode, trigger = null) => {
    const normalizedMode = mode === 'edit' ? 'edit' : 'create';
    const titleNode = modal.querySelector('[data-break-work-request-title]');
    const submitButton = form.querySelector('[data-break-work-request-submit]');
    const methodField = modal.querySelector('[data-break-work-request-method]');
    const requestIdField = modal.querySelector('[data-break-work-request-id]');
    const requestModeField = modal.querySelector('[data-break-work-request-mode]');
    const storeUrl = form.dataset.storeUrl || form.getAttribute('action') || '';
    const updateUrl = trigger?.dataset.breakRequestUpdateUrl || '';

    form.dataset.mode = normalizedMode;
    form.action = normalizedMode === 'edit' && updateUrl ? updateUrl : storeUrl;
    form.dataset.submitLabel = SUBMIT_LABELS[normalizedMode];

    if (titleNode) {
        titleNode.textContent = MODAL_TITLES[normalizedMode];
    }

    if (submitButton) {
        submitButton.textContent = SUBMIT_LABELS[normalizedMode];
    }

    if (methodField) {
        methodField.value = normalizedMode === 'edit' ? 'PATCH' : '';
    }

    if (requestIdField) {
        requestIdField.value = normalizedMode === 'edit' ? (trigger?.dataset.breakRequestId || '') : '';
    }

    if (requestModeField) {
        requestModeField.value = normalizedMode;
    }
};

const openModal = (trigger) => {
    const modal = getModal();
    const form = getForm();

    if (!modal || !form) {
        return;
    }

    resetErrors(modal);
    form.reset();

    const mode = trigger.dataset.breakRequestMode === 'edit' ? 'edit' : 'create';
    setModalMode(modal, form, mode, trigger);

    const workDate = trigger.dataset.breakDate || '';
    const originalStartTime = trigger.dataset.breakStart || '';
    const originalEndTime = trigger.dataset.breakEnd || '';
    const requestedStartTime = mode === 'edit' ? (trigger.dataset.breakRequestStart || originalStartTime) : originalStartTime;
    const requestedEndTime = mode === 'edit' ? (trigger.dataset.breakRequestEnd || originalEndTime) : originalEndTime;
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
        originalStartField.value = originalStartTime;
    }

    if (originalEndField) {
        originalEndField.value = originalEndTime;
    }

    if (dateLabelNode) {
        dateLabelNode.textContent = dateLabel || '--';
    }

    setPickerValue(startField, requestedStartTime);
    setPickerValue(endField, requestedEndTime);

    if (descriptionField) {
        descriptionField.value = mode === 'edit' ? (trigger.dataset.breakRequestDescription || '') : '';
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
    setModalMode(modal, form, 'create');

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

const refreshTimelineFallback = async () => {
    const container = getTimelineContainer();

    if (!container?.dataset.refreshUrl) {
        return false;
    }

    const currentTimelineRoot = container.querySelector('[data-user-timeline-root]');
    const url = new URL(container.dataset.refreshUrl, window.location.origin);
    const selectedDate = window[TIMELINE_CONTROLLER_KEY]?.getSelectedDate?.()
        || currentTimelineRoot?.dataset.userTimelineSelectedDate
        || container.dataset.selectedDate
        || '';
    const userId = currentTimelineRoot?.dataset.userTimelineUserId || container.dataset.userId || '';

    if (selectedDate) {
        url.searchParams.set('date', selectedDate);
    }

    if (userId) {
        url.searchParams.set('user_id', userId);
    }

    try {
        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to refresh timeline.');
        }

        const payload = await response.json().catch(() => ({}));

        if (!payload.success || !payload.html) {
            throw new Error('Timeline response was incomplete.');
        }

        container.innerHTML = payload.html;
        container.dataset.selectedDate = selectedDate;

        if (userId) {
            container.dataset.userId = userId;
        }

        window[TIMELINE_CONTROLLER_KEY]?.bindCurrentRoot?.();

        return true;
    } catch (error) {
        return false;
    }
};

const refreshTimeline = async () => {
    const timelineController = window[TIMELINE_CONTROLLER_KEY];

    if (timelineController?.refreshSelectedDate) {
        const refreshed = await timelineController.refreshSelectedDate();

        if (refreshed) {
            return true;
        }
    }

    return refreshTimelineFallback();
};

const bindBreakWorkRequest = () => {
    const modal = getModal();
    const form = getForm();

    if (!modal || !form || modal.dataset.breakWorkRequestInitialized === 'true') {
        return;
    }

    initTimepicker('[data-break-work-request-time]', {
        enableSeconds: true,
        dateFormat: 'H:i:S',
    }, modal);

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
        const successMessage = form.dataset.mode === 'edit'
            ? 'Break work request updated successfully.'
            : 'Break work request submitted successfully.';

        form.dataset.submitting = 'true';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
        }

        try {
            const response = await fetch(form.action || form.dataset.storeUrl, {
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
            Alert.success(payload.message || successMessage);

            const refreshed = await refreshTimeline();

            if (!refreshed) {
                Alert.info('Request saved, but timeline could not be refreshed. Please refresh the page.', 'Warning');
            }
        } catch (error) {
            if (error?.message === 'Please correct the highlighted fields.') {
                return;
            }

            Alert.error(error?.message || 'Unable to submit the break work request.');
        } finally {
            form.dataset.submitting = 'false';

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = form.dataset.submitLabel || SUBMIT_LABELS.create;
            }
        }
    });

    modal.dataset.breakWorkRequestInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', bindBreakWorkRequest);
