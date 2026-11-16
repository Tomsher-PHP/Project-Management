import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    const approveModal = document.querySelector('[data-break-request-approve-modal]');
    const approveForm = document.querySelector('[data-break-request-approve-form]');
    const approveSubmitButton = document.querySelector('[data-break-request-approve-submit]');
    const approveFields = {
        userName: document.querySelector('[data-break-request-approve-user-name]'),
        requestedAt: document.querySelector('[data-break-request-approve-requested-at]'),
        workDate: document.querySelector('[data-break-request-approve-work-date]'),
        startTime: document.querySelector('[data-break-request-approve-start-time]'),
        endTime: document.querySelector('[data-break-request-approve-end-time]'),
        duration: document.querySelector('[data-break-request-approve-duration]'),
        description: document.querySelector('[data-break-request-approve-description]'),
    };
    const rejectModal = document.querySelector('[data-break-request-reject-modal]');
    const rejectForm = document.querySelector('[data-break-request-reject-form]');
    const rejectReason = document.getElementById('break-request-rejection-reason');
    const rejectTitle = document.querySelector('[data-break-request-reject-title]');
    const rejectHiddenInputs = document.querySelector('[data-break-request-reject-hidden-inputs]');
    const bulkSelectAll = document.querySelector('[data-break-request-bulk-select-all]');
    const bulkApproveButton = document.querySelector('[data-break-request-bulk-approve]');
    const bulkApproveForm = document.querySelector('[data-break-request-bulk-approve-form]');
    const bulkApproveHiddenInputs = document.querySelector('[data-break-request-bulk-approve-hidden-inputs]');
    const bulkRejectButton = document.querySelector('[data-break-request-bulk-reject]');
    const bulkCheckboxes = Array.from(
        document.querySelectorAll('[data-break-request-bulk-checkbox]')
    );

    const getSelectedRequestIds = () => bulkCheckboxes
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const syncBulkActions = () => {
        const selectedCount = getSelectedRequestIds().length;

        bulkApproveButton?.toggleAttribute('disabled', selectedCount === 0);
        bulkRejectButton?.toggleAttribute('disabled', selectedCount === 0);

        if (bulkSelectAll) {
            bulkSelectAll.checked = bulkCheckboxes.length > 0
                && selectedCount === bulkCheckboxes.length;
            bulkSelectAll.indeterminate = selectedCount > 0
                && selectedCount < bulkCheckboxes.length;
        }
    };

    const setHiddenRequestIds = (container, requestIds = []) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        requestIds.forEach((requestId) => {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = 'break_request_ids[]';
            input.value = requestId;

            container.appendChild(input);
        });
    };

    const setApproveField = (field, value, fallback = '--') => {
        if (approveFields[field]) {
            approveFields[field].textContent = value || fallback;
        }
    };

    const openApproveModal = (button) => {
        if (!approveModal || !approveForm) {
            return;
        }

        approveForm.action = button.dataset.action || '#';
        approveForm.reset();

        setApproveField('userName', button.dataset.userName, 'Unknown User');
        setApproveField('requestedAt', button.dataset.requestedAt);
        setApproveField('workDate', button.dataset.workDate);
        setApproveField('startTime', button.dataset.startTime);
        setApproveField('endTime', button.dataset.endTime);
        setApproveField('duration', button.dataset.duration);
        setApproveField('description', button.dataset.description);

        approveModal.classList.remove('hidden');
        approveModal.setAttribute('aria-hidden', 'false');
    };

    const closeApproveModal = () => {
        approveModal?.classList.add('hidden');
        approveModal?.setAttribute('aria-hidden', 'true');
        approveForm?.reset();

        if (approveSubmitButton) {
            approveSubmitButton.disabled = false;
            approveSubmitButton.textContent = 'Approve';
        }
    };

    const openRejectModal = (button) => {
        if (!rejectModal || !rejectForm) {
            return;
        }

        rejectForm.action = button.dataset.action || '#';
        rejectForm.reset();
        setHiddenRequestIds(rejectHiddenInputs, []);

        if (rejectTitle) {
            rejectTitle.textContent = button.dataset.requestUserName
                ? `Requested by ${button.dataset.requestUserName}`
                : '';
        }

        rejectModal.classList.remove('hidden');
        rejectReason?.focus();
    };

    const closeRejectModal = () => {
        rejectModal?.classList.add('hidden');
    };

    document.querySelectorAll('[data-break-request-approve-open]').forEach((button) => {
        button.addEventListener('click', () => openApproveModal(button));
    });

    document.querySelectorAll('[data-break-request-approve-close]').forEach((button) => {
        button.addEventListener('click', closeApproveModal);
    });

    approveForm?.addEventListener('submit', () => {
        if (approveSubmitButton) {
            approveSubmitButton.disabled = true;
            approveSubmitButton.textContent = 'Approving...';
        }
    });

    document.querySelectorAll('[data-break-request-reject-open]').forEach((button) => {
        button.addEventListener('click', () => openRejectModal(button));
    });

    document.querySelectorAll('[data-break-request-reject-close]').forEach((button) => {
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
        const selectedRequestIds = getSelectedRequestIds();

        if (selectedRequestIds.length === 0) {
            return;
        }

        const result = await Alert.confirm({
            title: 'Approve selected break work requests?',
            text: `This will approve ${selectedRequestIds.length} selected break work request(s).`,
            icon: 'warning',
            confirmText: 'Yes, approve',
        });

        if (result?.isConfirmed) {
            setHiddenRequestIds(bulkApproveHiddenInputs, selectedRequestIds);
            bulkApproveForm?.submit();
        }
    });

    bulkRejectButton?.addEventListener('click', () => {
        const selectedRequestIds = getSelectedRequestIds();

        if (selectedRequestIds.length === 0 || !rejectModal || !rejectForm) {
            return;
        }

        rejectForm.action = bulkRejectButton.dataset.action || '#';
        rejectForm.reset();
        setHiddenRequestIds(rejectHiddenInputs, selectedRequestIds);

        if (rejectTitle) {
            rejectTitle.textContent = `${selectedRequestIds.length} selected break work request(s)`;
        }

        rejectModal.classList.remove('hidden');
        rejectReason?.focus();
    });

    document.querySelectorAll('[data-break-request-cancel-form]').forEach((form) => {
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

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (!approveModal?.classList.contains('hidden')) {
            closeApproveModal();
        }

        if (!rejectModal?.classList.contains('hidden')) {
            closeRejectModal();
        }
    });

    syncBulkActions();
});
