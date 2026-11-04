import Alert from '../../alert';
import '../projects/project-tasks';

document.addEventListener('DOMContentLoaded', () => {
    const rejectModal = document.querySelector('[data-task-request-reject-modal]');
    const rejectForm = document.querySelector('[data-task-request-reject-form]');
    const rejectReason = document.getElementById('task-request-rejection-reason');
    const rejectTaskName = document.querySelector('[data-task-request-reject-task-name]');
    const rejectHiddenInputs = document.querySelector('[data-task-request-reject-hidden-inputs]');
    const bulkSelectAll = document.querySelector('[data-task-request-bulk-select-all]');
    const bulkApproveButton = document.querySelector('[data-task-request-bulk-approve]');
    const bulkApproveForm = document.querySelector('[data-task-request-bulk-approve-form]');
    const bulkApproveHiddenInputs = document.querySelector('[data-task-request-bulk-approve-hidden-inputs]');
    const bulkRejectButton = document.querySelector('[data-task-request-bulk-reject]');
    const bulkCheckboxes = Array.from(
        document.querySelectorAll('[data-task-request-bulk-checkbox]')
    );

    const getSelectedTaskIds = () => bulkCheckboxes
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const syncBulkActions = () => {
        const selectedCount = getSelectedTaskIds().length;

        bulkApproveButton?.toggleAttribute('disabled', selectedCount === 0);
        bulkRejectButton?.toggleAttribute('disabled', selectedCount === 0);

        if (bulkSelectAll) {
            bulkSelectAll.checked = bulkCheckboxes.length > 0
                && selectedCount === bulkCheckboxes.length;
            bulkSelectAll.indeterminate = selectedCount > 0
                && selectedCount < bulkCheckboxes.length;
        }
    };

    const setHiddenTaskIds = (container, taskIds = []) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        taskIds.forEach((taskId) => {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = 'task_ids[]';
            input.value = taskId;

            container.appendChild(input);
        });
    };

    const openRejectModal = (button) => {
        if (!rejectModal || !rejectForm) {
            return;
        }

        rejectForm.action = button.dataset.action || '#';
        rejectForm.reset();
        setHiddenTaskIds(rejectHiddenInputs, []);

        if (rejectTaskName) {
            rejectTaskName.textContent = button.dataset.taskName
                ? `Task: ${button.dataset.taskName}`
                : '';
        }

        rejectModal.classList.remove('hidden');
        rejectReason?.focus();
    };

    const closeRejectModal = () => {
        rejectModal?.classList.add('hidden');
    };

    document.querySelectorAll('[data-task-request-reject-open]').forEach((button) => {
        button.addEventListener('click', () => openRejectModal(button));
    });

    document.querySelectorAll('[data-task-request-reject-close]').forEach((button) => {
        button.addEventListener('click', closeRejectModal);
    });

    bulkSelectAll?.addEventListener('change', () => {
        bulkCheckboxes.forEach((checkbox) => {
            checkbox.checked = bulkSelectAll.checked;
        });

        syncBulkActions();
    });

    bulkCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncBulkActions);
    });

    bulkApproveButton?.addEventListener('click', async () => {
        const selectedTaskIds = getSelectedTaskIds();

        if (selectedTaskIds.length === 0) {
            return;
        }

        const result = await Alert.confirm({
            title: 'Approve selected task requests?',
            text: `This will approve ${selectedTaskIds.length} selected task request(s).`,
            icon: 'warning',
            confirmText: 'Yes, approve',
        });

        if (result?.isConfirmed) {
            setHiddenTaskIds(bulkApproveHiddenInputs, selectedTaskIds);
            bulkApproveForm?.submit();
        }
    });

    bulkRejectButton?.addEventListener('click', () => {
        const selectedTaskIds = getSelectedTaskIds();

        if (selectedTaskIds.length === 0 || !rejectModal || !rejectForm) {
            return;
        }

        rejectForm.action = bulkRejectButton.dataset.action || '#';
        rejectForm.reset();
        setHiddenTaskIds(rejectHiddenInputs, selectedTaskIds);

        if (rejectTaskName) {
            rejectTaskName.textContent = `${selectedTaskIds.length} selected task request(s)`;
        }

        rejectModal.classList.remove('hidden');
        rejectReason?.focus();
    });

    document.querySelectorAll('[data-task-request-action-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const result = await Alert.confirm({
                title: form.dataset.confirmTitle || 'Are you sure?',
                text: form.dataset.confirmText || 'Please confirm this action.',
                icon: form.dataset.confirmIcon || 'warning',
                confirmText: form.dataset.confirmTextButton || 'Yes',
            });

            if (result?.isConfirmed) {
                form.submit();
            }
        });
    });

    syncBulkActions();
});
