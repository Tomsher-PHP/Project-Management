document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.querySelector('[data-project-restore-select-all]');
    const bulkButton = document.querySelector('[data-project-restore-bulk-button]');
    const bulkForm = document.querySelector('[data-project-restore-bulk-form]');
    const bulkHiddenInputs = document.querySelector('[data-project-restore-bulk-hidden-inputs]');
    const checkboxes = Array.from(document.querySelectorAll('[data-project-restore-checkbox]'));
    const restoreForms = Array.from(document.querySelectorAll('[data-project-restore-form]'));

    if (!bulkButton || !bulkForm || checkboxes.length === 0) {
        // Fallback for single forms in case checkboxes are absent or empty
        restoreForms.forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const result = await Alert.confirm({
                    title: 'Confirm Project Restoration',
                    text: 'Are you sure you want to restore this project?',
                    icon: 'warning',
                    confirmText: 'Yes, restore it',
                    requireText: 'RESTORE',
                });

                if (result?.isConfirmed) {
                    form.submit();
                }
            });
        });
        return;
    }

    const getSelectedProjectIds = () => checkboxes
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const syncBulkState = () => {
        const selectedCount = getSelectedProjectIds().length;

        bulkButton.toggleAttribute('disabled', selectedCount === 0);

        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        }
    };

    const setHiddenProjectIds = (container, projectIds = []) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';

        projectIds.forEach((projectId) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'project_ids[]';
            input.value = projectId;
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
                title: 'Confirm Project Restoration',
                text: 'Are you sure you want to restore this project?',
                icon: 'warning',
                confirmText: 'Yes, restore it',
                requireText: 'RESTORE',
            });

            if (result?.isConfirmed) {
                form.submit();
            }
        });
    });

    bulkButton.addEventListener('click', async () => {
        const selectedProjectIds = getSelectedProjectIds();

        if (selectedProjectIds.length === 0) {
            return;
        }

        const result = await Alert.confirm({
            title: 'Restore Selected Projects?',
            text: `Are you sure you want to restore ${selectedProjectIds.length} selected project(s)?`,
            icon: 'warning',
            confirmText: 'Yes, restore them',
            requireText: 'RESTORE',
        });

        if (result?.isConfirmed) {
            setHiddenProjectIds(bulkHiddenInputs, selectedProjectIds);
            bulkForm.submit();
        }
    });

    syncBulkState();
});
