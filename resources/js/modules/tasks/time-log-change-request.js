import Alert from '../../alert';

const modalSelector = '#timeLogChangeRequestModal';
const formSelector = '#timeLogChangeRequestForm';

const getModal = () => document.querySelector(modalSelector);
const getForm = () => document.querySelector(formSelector);

const readTriggerData = (trigger, key) => {
    if (!trigger) {
        return '';
    }

    if (Object.prototype.hasOwnProperty.call(trigger.dataset, key)) {
        return trigger.dataset[key] || '';
    }

    return trigger.getAttribute(`data-${key}`) || '';
};

const fieldSelectors = {
    taskTimeLogId: '#timeLogChangeRequestTaskTimeLogId',
    taskId: '#timeLogChangeRequestTaskId',
    originalStartedAt: '#timeLogChangeRequestOriginalStartedAt',
    originalEndedAt: '#timeLogChangeRequestOriginalEndedAt',
    newStartedAt: '#timeLogChangeRequestNewStartedAt',
    newEndedAt: '#timeLogChangeRequestNewEndedAt',
    duration: '[data-time-log-change-request-duration]',
    reason: '#timeLogChangeRequestReason',
    userName: '#timeLogChangeRequestUserName',
    submit: '#timeLogChangeRequestSubmitButton',
};

const formatDuration = (totalSeconds) => {
    const seconds = Math.max(0, Number(totalSeconds) || 0);
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${String(minutes).padStart(2, '0')}m`;
    }

    if (minutes > 0) {
        return `${minutes}m ${String(remainingSeconds).padStart(2, '0')}s`;
    }

    return `${remainingSeconds}s`;
};

const parseDateTimeValue = (value) => {
    if (!value) {
        return null;
    }

    const normalizedValue = String(value).trim().replace(' ', 'T');
    const date = new Date(normalizedValue);

    return Number.isNaN(date.getTime()) ? null : date;
};

const syncDurationDisplay = () => {
    const durationNode = document.querySelector(fieldSelectors.duration);
    const newStartedAtField = document.querySelector(fieldSelectors.newStartedAt);
    const newEndedAtField = document.querySelector(fieldSelectors.newEndedAt);

    if (!durationNode || !newStartedAtField || !newEndedAtField) {
        return;
    }

    const startedAt = parseDateTimeValue(newStartedAtField.value);
    const endedAt = parseDateTimeValue(newEndedAtField.value);

    if (!startedAt || !endedAt || endedAt <= startedAt) {
        durationNode.textContent = 'Duration: --';
        return;
    }

    const totalSeconds = Math.floor((endedAt.getTime() - startedAt.getTime()) / 1000);
    durationNode.textContent = `Duration: ${formatDuration(totalSeconds)}`;
};

const setFieldValue = (field, value = '') => {
    if (!field) {
        return;
    }

    const normalizedValue = value ?? '';

    if (field._flatpickr) {
        const dateFormat = field._flatpickr.config.dateFormat || field.dataset.format || 'Y-m-d H:i';

        if (normalizedValue) {
            field._flatpickr.setDate(normalizedValue, true, dateFormat);
            field._flatpickr.jumpToDate(normalizedValue);
        } else {
            field._flatpickr.clear();
        }
    }

    field.value = normalizedValue;
};

const clearErrors = (form) => {
    if (!form) {
        return;
    }

    form.querySelectorAll('[data-time-log-change-request-error-for]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, textarea').forEach((field) => {
        field.classList.remove('border-red-500');
    });
};

const applyErrors = (form, errors = {}) => {
    if (!form) {
        return;
    }

    clearErrors(form);

    const unhandledMessages = [];

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const message = Array.isArray(messages) ? String(messages[0] || '') : String(messages || '');
        const field = form.querySelector(`[name="${fieldName}"]`);
        const errorNode = form.querySelector(`[data-time-log-change-request-error-for="${fieldName}"]`);

        if (field) {
            field.classList.add('border-red-500');
        }

        if (errorNode) {
            errorNode.textContent = message;
            errorNode.classList.remove('hidden');
            return;
        }

        if (message) {
            unhandledMessages.push(message);
        }
    });

    if (unhandledMessages.length) {
        Alert.error(unhandledMessages[0]);
    }
};

const populateFromTrigger = (trigger) => {
    const form = getForm();

    if (!form || !trigger) {
        return;
    }

    clearErrors(form);

    const userNameNode = document.querySelector(fieldSelectors.userName);
    const taskTimeLogIdField = document.querySelector(fieldSelectors.taskTimeLogId);
    const taskIdField = document.querySelector(fieldSelectors.taskId);
    const originalStartedAtField = document.querySelector(fieldSelectors.originalStartedAt);
    const originalEndedAtField = document.querySelector(fieldSelectors.originalEndedAt);
    const newStartedAtField = document.querySelector(fieldSelectors.newStartedAt);
    const newEndedAtField = document.querySelector(fieldSelectors.newEndedAt);
    const reasonField = document.querySelector(fieldSelectors.reason);

    if (userNameNode) {
        const userName = readTriggerData(trigger, 'time_log_user_name') || 'Unknown User';
        userNameNode.textContent = `${userName}'s selected time log values are loaded below.`;
    }

    setFieldValue(taskTimeLogIdField, readTriggerData(trigger, 'task_time_log_id'));
    setFieldValue(taskIdField, readTriggerData(trigger, 'task_id'));
    setFieldValue(originalStartedAtField, readTriggerData(trigger, 'original_started_at'));
    setFieldValue(originalEndedAtField, readTriggerData(trigger, 'original_ended_at'));
    setFieldValue(newStartedAtField, readTriggerData(trigger, 'new_started_at'));
    setFieldValue(newEndedAtField, readTriggerData(trigger, 'new_ended_at'));
    setFieldValue(reasonField, '');
    syncDurationDisplay();
};

const setSubmittingState = (isSubmitting) => {
    const submitButton = document.querySelector(fieldSelectors.submit);

    if (!submitButton) {
        return;
    }

    submitButton.disabled = isSubmitting;
    submitButton.classList.toggle('opacity-60', isSubmitting);
    submitButton.classList.toggle('cursor-not-allowed', isSubmitting);
    submitButton.textContent = isSubmitting ? 'Submitting...' : 'Submit';
};

const closeModal = () => {
    const modal = getModal();

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
};

const rejectListModal = {
    modal: '[data-time-log-change-request-reject-modal]',
    form: '[data-time-log-change-request-reject-form]',
    taskName: '[data-time-log-change-request-reject-task-name]',
    hiddenInputs: '[data-time-log-change-request-reject-hidden-inputs]',
    close: '[data-time-log-change-request-reject-close]',
    reason: '#time-log-change-request-rejection-reason',
};

const getRejectListModal = () => document.querySelector(rejectListModal.modal);
const getRejectListForm = () => document.querySelector(rejectListModal.form);

const bulkSelectors = {
    selectAll: '[data-time-log-change-request-bulk-select-all]',
    checkbox: '[data-time-log-change-request-bulk-checkbox]',
    approveButton: '[data-time-log-change-request-bulk-approve]',
    approveForm: '[data-time-log-change-request-bulk-approve-form]',
    approveHiddenInputs: '[data-time-log-change-request-bulk-approve-hidden-inputs]',
    rejectButton: '[data-time-log-change-request-bulk-reject]',
};

const getBulkCheckboxes = () => Array.from(document.querySelectorAll(bulkSelectors.checkbox));

const getSelectedChangeRequestIds = () => getBulkCheckboxes()
    .filter((checkbox) => checkbox.checked)
    .map((checkbox) => checkbox.value);

const setHiddenChangeRequestIds = (container, changeRequestIds = []) => {
    if (!container) {
        return;
    }

    container.innerHTML = '';

    changeRequestIds.forEach((changeRequestId) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'change_request_ids[]';
        input.value = changeRequestId;
        container.appendChild(input);
    });
};

const syncBulkActions = () => {
    const bulkCheckboxes = getBulkCheckboxes();
    const selectedCount = getSelectedChangeRequestIds().length;
    const selectAll = document.querySelector(bulkSelectors.selectAll);
    const approveButton = document.querySelector(bulkSelectors.approveButton);
    const rejectButton = document.querySelector(bulkSelectors.rejectButton);

    approveButton?.toggleAttribute('disabled', selectedCount === 0);
    rejectButton?.toggleAttribute('disabled', selectedCount === 0);

    if (selectAll) {
        selectAll.checked = bulkCheckboxes.length > 0 && selectedCount === bulkCheckboxes.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < bulkCheckboxes.length;
    }
};

const openRejectListModal = (button, changeRequestIds = []) => {
    const modal = getRejectListModal();
    const form = getRejectListForm();
    const taskName = document.querySelector(rejectListModal.taskName);
    const hiddenInputs = document.querySelector(rejectListModal.hiddenInputs);
    const reason = document.querySelector(rejectListModal.reason);

    if (!modal || !form || !button) {
        return;
    }

    form.action = button.dataset.action || '#';
    form.reset();
    setHiddenChangeRequestIds(hiddenInputs, changeRequestIds);

    if (taskName) {
        const requestUserName = button.dataset.requestUserName ? ` by ${button.dataset.requestUserName}` : '';
        taskName.textContent = changeRequestIds.length > 0
            ? `${changeRequestIds.length} selected time log change request(s)`
            : (button.dataset.taskName
                ? `Task: ${button.dataset.taskName}${requestUserName}`
                : '');
    }

    modal.classList.remove('hidden');
    reason?.focus();
};

const closeRejectListModal = () => {
    getRejectListModal()?.classList.add('hidden');
};

const submitForm = async () => {
    const form = getForm();

    if (!form) {
        return;
    }

    clearErrors(form);
    setSubmittingState(true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });

        const result = await response.json().catch(() => ({}));

        if (response.status === 422) {
            applyErrors(form, result.errors || {});
            return;
        }

        if (!response.ok || result.status === false) {
            throw new Error(result.message || 'Unable to submit the time change request.');
        }

        form.reset();
        closeModal();
        Alert.success(result.message || 'Time change request submitted successfully.');
        document.dispatchEvent(new CustomEvent('task-history:changed', {
            detail: { source: 'time-log-change-request' },
        }));
    } catch (error) {
        Alert.error(error.message || 'Unable to submit the time change request.');
    } finally {
        setSubmittingState(false);
    }
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-time-log-change-request-open]');

    if (trigger && !trigger.disabled) {
        window.setTimeout(() => {
            populateFromTrigger(trigger);
        }, 0);

        return;
    }

    const submitButton = event.target.closest('[data-time-log-change-request-submit]');

    if (submitButton) {
        submitForm();
        return;
    }

    const rejectButton = event.target.closest('[data-time-log-change-request-reject-open]');

    if (rejectButton) {
        openRejectListModal(rejectButton, []);
        return;
    }

    if (event.target.closest(rejectListModal.close)) {
        closeRejectListModal();
        return;
    }

    const bulkApproveButton = event.target.closest(bulkSelectors.approveButton);

    if (bulkApproveButton) {
        const selectedChangeRequestIds = getSelectedChangeRequestIds();

        if (selectedChangeRequestIds.length === 0) {
            return;
        }

        Alert.confirm({
            title: 'Approve selected time log change requests?',
            text: `This will approve ${selectedChangeRequestIds.length} selected time log change request(s).`,
            icon: 'warning',
            confirmText: 'Yes, approve',
        }).then((result) => {
            if (!result?.isConfirmed) {
                return;
            }

            const approveForm = document.querySelector(bulkSelectors.approveForm);
            const hiddenInputs = document.querySelector(bulkSelectors.approveHiddenInputs);

            setHiddenChangeRequestIds(hiddenInputs, selectedChangeRequestIds);
            approveForm?.submit();
        });

        return;
    }

    const bulkRejectButton = event.target.closest(bulkSelectors.rejectButton);

    if (bulkRejectButton) {
        const selectedChangeRequestIds = getSelectedChangeRequestIds();

        if (selectedChangeRequestIds.length === 0) {
            return;
        }

        openRejectListModal(bulkRejectButton, selectedChangeRequestIds);
    }
});

document.addEventListener('submit', async (event) => {
    const approvalForm = event.target.closest('[data-time-log-change-request-action-form]');

    if (approvalForm) {
        event.preventDefault();

        const result = await Alert.confirm({
            title: approvalForm.dataset.confirmTitle || 'Approve request?',
            text: approvalForm.dataset.confirmText || 'Please confirm this action.',
            icon: approvalForm.dataset.confirmIcon || 'warning',
            confirmText: approvalForm.dataset.confirmTextButton || 'Yes, approve',
        });

        if (result?.isConfirmed) {
            approvalForm.submit();
        }
    }
});

document.addEventListener('change', (event) => {
    const selectAll = event.target.closest(bulkSelectors.selectAll);

    if (selectAll) {
        getBulkCheckboxes().forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        syncBulkActions();
        return;
    }

    if (event.target.closest(bulkSelectors.checkbox)) {
        syncBulkActions();
        return;
    }

    if (
        event.target.matches(fieldSelectors.newStartedAt)
        || event.target.matches(fieldSelectors.newEndedAt)
    ) {
        syncDurationDisplay();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    syncBulkActions();
    syncDurationDisplay();

    const newStartedAtField = document.querySelector(fieldSelectors.newStartedAt);
    const newEndedAtField = document.querySelector(fieldSelectors.newEndedAt);

    newStartedAtField?.addEventListener('input', syncDurationDisplay);
    newEndedAtField?.addEventListener('input', syncDurationDisplay);
});
