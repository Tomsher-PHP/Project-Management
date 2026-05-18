document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.querySelector('[data-user-restore-select-all]');
    const bulkButton = document.querySelector('[data-user-restore-bulk-button]');
    const bulkForm = document.querySelector('[data-user-restore-bulk-form]');
    const bulkHiddenInputs = document.querySelector('[data-user-restore-bulk-hidden-inputs]');
    const checkboxes = Array.from(document.querySelectorAll('[data-user-restore-checkbox]'));
    const restoreForms = Array.from(document.querySelectorAll('[data-user-restore-form]'));

    if (!bulkButton || !bulkForm || checkboxes.length === 0) {
        return;
    }

    const getSelectedUserIds = () => checkboxes
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const syncBulkState = () => {
        const selectedCount = getSelectedUserIds().length;

        bulkButton.toggleAttribute('disabled', selectedCount === 0);

        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        }
    };

    const setHiddenUserIds = (container, userIds = []) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        userIds.forEach((userId) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = userId;
            container.appendChild(input);
        });
    };

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });

        syncBulkState();
    });

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncBulkState);
    });

    restoreForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const result = await Alert.confirm({
                title: 'Restore this user?',
                text: 'This will restore the selected user account.',
                icon: 'warning',
                confirmText: 'Yes, restore',
                requireText: 'RESTORE',
            });

            if (result?.isConfirmed) {
                form.submit();
            }
        });
    });

    bulkButton.addEventListener('click', async () => {
        const selectedUserIds = getSelectedUserIds();

        if (selectedUserIds.length === 0) {
            return;
        }

        const result = await Alert.confirm({
            title: 'Restore selected users?',
            text: `This will restore ${selectedUserIds.length} selected user(s).`,
            icon: 'warning',
            confirmText: 'Yes, restore',
            requireText: 'RESTORE',
        });

        if (result?.isConfirmed) {
            setHiddenUserIds(bulkHiddenInputs, selectedUserIds);
            bulkForm.submit();
        }
    });

    syncBulkState();
});
