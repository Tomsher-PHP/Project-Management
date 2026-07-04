import Alert from '../../alert';
import { initDatepicker } from '../../components/datepicker';

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
    const mode = readTriggerData(trigger, 'time_log_change_request_mode') === 'edit' ? 'edit' : 'create';
    const storeUrl = form.dataset.storeUrl || form.getAttribute('action') || '';
    const updateUrl = readTriggerData(trigger, 'time_log_change_request_update_url');

    form.action = mode === 'edit' && updateUrl ? updateUrl : storeUrl;
    form.dataset.requestMethod = mode === 'edit' ? 'PATCH' : 'POST';

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
    setFieldValue(reasonField, mode === 'edit'
        ? readTriggerData(trigger, 'time_log_change_request_reason')
        : '');
    syncDurationDisplay();
};

export const openTimeLogChangeRequest = (trigger) => {
    if (!trigger || trigger.disabled) {
        return;
    }

    populateFromTrigger(trigger);
    getModal()?.classList.remove('hidden');
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

const approvalModalSelectors = {
    modal: '[data-time-log-change-request-approve-modal]',
    form: '[data-time-log-change-request-approve-form]',
    open: '[data-time-log-change-request-approve-open]',
    close: '[data-time-log-change-request-approve-close]',
    submit: '[data-time-log-change-request-approve-submit]',
    userName: '[data-time-log-change-request-approve-user-name]',
    taskName: '[data-time-log-change-request-approve-task-name]',
    reason: '[data-time-log-change-request-approve-reason]',
    currentStart: '[data-time-log-change-request-current-start]',
    currentEnd: '[data-time-log-change-request-current-end]',
    startedAt: '[name="new_started_at"]',
    endedAt: '[name="new_ended_at"]',
    error: '[data-time-log-change-request-approve-error]',
};

const getApprovalModal = () => document.querySelector(approvalModalSelectors.modal);
const getApprovalForm = () => document.querySelector(approvalModalSelectors.form);

const resetApprovalErrors = () => {
    const form = getApprovalForm();

    form?.querySelectorAll(approvalModalSelectors.error).forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });
    form?.querySelectorAll('input').forEach((input) => {
        input.classList.remove('border-red-500');
        input._flatpickr?.altInput?.classList.remove('border-red-500');
    });
};

const setDateTimepickerValue = (input, value) => {
    if (!input) {
        return;
    }

    if (input._flatpickr) {
        input._flatpickr.setDate(value, true, 'Y-m-d H:i:S');
        return;
    }

    input.value = value || '';
};

const openApprovalModal = (trigger) => {
    const modal = getApprovalModal();
    const form = getApprovalForm();

    if (!modal || !form || !trigger) {
        return;
    }

    resetApprovalErrors();
    form.action = trigger.dataset.action || '#';

    modal.querySelector(approvalModalSelectors.userName).textContent = trigger.dataset.userName || 'Unknown User';
    modal.querySelector(approvalModalSelectors.taskName).textContent = trigger.dataset.taskName || 'Unknown Task';
    modal.querySelector(approvalModalSelectors.reason).textContent = trigger.dataset.reason || '--';
    modal.querySelector(approvalModalSelectors.currentStart).textContent = trigger.dataset.currentStart || '--';
    modal.querySelector(approvalModalSelectors.currentEnd).textContent = trigger.dataset.currentEnd || '--';

    setDateTimepickerValue(form.querySelector(approvalModalSelectors.startedAt), trigger.dataset.requestedStart);
    setDateTimepickerValue(form.querySelector(approvalModalSelectors.endedAt), trigger.dataset.requestedEnd);

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
};

const closeApprovalModal = () => {
    const modal = getApprovalModal();
    const form = getApprovalForm();

    modal?.classList.add('hidden');
    modal?.setAttribute('aria-hidden', 'true');
    form?.reset();
    resetApprovalErrors();
};

const showApprovalErrors = (errors = {}) => {
    const form = getApprovalForm();
    const unhandledMessages = [];

    resetApprovalErrors();

    Object.entries(errors).forEach(([field, messages]) => {
        const message = Array.isArray(messages) ? messages[0] : messages;
        const errorNode = form?.querySelector(`[data-time-log-change-request-approve-error="${field}"]`);
        const input = form?.querySelector(`[name="${field}"]`);

        input?.classList.add('border-red-500');
        input?._flatpickr?.altInput?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = message;
            errorNode.classList.remove('hidden');
        } else if (message) {
            unhandledMessages.push(message);
        }
    });

    if (unhandledMessages.length) {
        Alert.error(unhandledMessages[0]);
    }
};

const submitApprovalForm = async (form) => {
    const submitButton = form.querySelector(approvalModalSelectors.submit);

    if (form.dataset.submitting === 'true') {
        return;
    }

    resetApprovalErrors();
    form.dataset.submitting = 'true';
    submitButton.disabled = true;
    submitButton.textContent = 'Approving...';

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
            showApprovalErrors(result.errors || {});
            return;
        }

        if (!response.ok || result.status === false) {
            throw new Error(result.message || 'Unable to approve the time log change request.');
        }

        closeApprovalModal();
        Alert.success(result.message || 'Time log change request approved successfully.');
        window.setTimeout(() => window.location.reload(), 1000);
    } catch (error) {
        Alert.error(error.message || 'Unable to approve the time log change request.');
    } finally {
        form.dataset.submitting = 'false';
        submitButton.disabled = false;
        submitButton.textContent = 'Approve';
    }
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
            method: form.dataset.requestMethod || 'POST',
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
        openTimeLogChangeRequest(trigger);

        return;
    }

    const approvalTrigger = event.target.closest(approvalModalSelectors.open);

    if (approvalTrigger) {
        openApprovalModal(approvalTrigger);
        return;
    }

    if (event.target.closest(approvalModalSelectors.close)) {
        closeApprovalModal();
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
    const approvalForm = event.target.closest(approvalModalSelectors.form);

    if (approvalForm) {
        event.preventDefault();
        submitApprovalForm(approvalForm);
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
    const approvalModal = getApprovalModal();
    initDatepicker('.datepicker', {}, approvalModal || document);

    syncBulkActions();
    syncDurationDisplay();

    const newStartedAtField = document.querySelector(fieldSelectors.newStartedAt);
    const newEndedAtField = document.querySelector(fieldSelectors.newEndedAt);

    newStartedAtField?.addEventListener('input', syncDurationDisplay);
    newEndedAtField?.addEventListener('input', syncDurationDisplay);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !getApprovalModal()?.classList.contains('hidden')) {
        closeApprovalModal();
    }
});
