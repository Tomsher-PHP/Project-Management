document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.querySelector('[data-customer-restore-select-all]');
    const bulkButton = document.querySelector('[data-customer-restore-bulk-button]');
    const bulkForm = document.querySelector('[data-customer-restore-bulk-form]');
    const bulkHiddenInputs = document.querySelector('[data-customer-restore-bulk-hidden-inputs]');
    const checkboxes = Array.from(document.querySelectorAll('[data-customer-restore-checkbox]'));
    const restoreForms = Array.from(document.querySelectorAll('[data-customer-restore-form]'));

    if (!bulkButton || !bulkForm || checkboxes.length === 0) {
        return;
    }

    const getSelectedCustomerIds = () => checkboxes
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const syncBulkState = () => {
        const selectedCount = getSelectedCustomerIds().length;

        bulkButton.toggleAttribute('disabled', selectedCount === 0);

        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        }
    };

    const setHiddenCustomerIds = (container, customerIds = []) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        customerIds.forEach((customerId) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'customer_ids[]';
            input.value = customerId;
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
                title: 'Restore this customer?',
                text: 'This will restore the selected customer account and all associated contacts.',
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
        const selectedCustomerIds = getSelectedCustomerIds();

        if (selectedCustomerIds.length === 0) {
            return;
        }

        const result = await Alert.confirm({
            title: 'Restore selected customers?',
            text: `This will restore ${selectedCustomerIds.length} selected customer(s) and all associated contacts.`,
            icon: 'warning',
            confirmText: 'Yes, restore',
            requireText: 'RESTORE',
        });

        if (result?.isConfirmed) {
            setHiddenCustomerIds(bulkHiddenInputs, selectedCustomerIds);
            bulkForm.submit();
        }
    });

    syncBulkState();
});
