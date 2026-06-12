document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('[data-notifications-page]');

    if (!page) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const selectAllCheckbox = document.getElementById('select-all');
    const bulkDeleteButton = document.getElementById('bulk-delete-btn');
    const selectedCount = document.getElementById('selected-count');

    const getCheckboxes = () => Array.from(document.querySelectorAll('.notification-checkbox'));
    const getSelectedIds = () => getCheckboxes()
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

    const setButtonLoadingState = (button, isLoading, loadingText = 'Deleting...') => {
        if (!button) {
            return;
        }

        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = loadingText;
            return;
        }

        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }

        button.disabled = false;
    };

    const updateSelectionState = () => {
        const checkboxes = getCheckboxes();
        const selectedIds = getSelectedIds();
        const selectedTotal = selectedIds.length;

        if (selectedCount) {
            selectedCount.textContent = `${selectedTotal} selected`;
        }

        if (bulkDeleteButton) {
            bulkDeleteButton.disabled = selectedTotal === 0;
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkboxes.length > 0 && selectedTotal === checkboxes.length;
            selectAllCheckbox.indeterminate = selectedTotal > 0 && selectedTotal < checkboxes.length;
        }
    };

    const parseJsonResponse = async (response) => {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('Unexpected server response.');
        }

        const data = await response.json();

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Something went wrong. Please try again.');
        }

        return data;
    };

    const confirmDelete = (title, text, confirmText) => Alert.confirm({
        title,
        text,
        confirmText,
        cancelText: 'Cancel',
        icon: 'warning',
        confirmColor: '#ef4444',
        cancelColor: '#94a3b8',
    });

    const sendDeleteRequest = async (url, ids = []) => {
        const requestOptions = {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids }),
        };

        const response = await fetch(url, requestOptions);

        return parseJsonResponse(response);
    };

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            getCheckboxes().forEach((checkbox) => {
                checkbox.checked = this.checked;
            });

            updateSelectionState();
        });
    }

    page.addEventListener('change', function (event) {
        if (!event.target.classList.contains('notification-checkbox')) {
            return;
        }

        updateSelectionState();
    });

    if (bulkDeleteButton) {
        bulkDeleteButton.addEventListener('click', async function () {
            const selectedIds = getSelectedIds();

            if (selectedIds.length === 0) {
                return;
            }

            const result = await confirmDelete(
                'Delete Selected Notifications?',
                `You are about to delete ${selectedIds.length} notification(s).`,
                'Yes, delete them'
            );

            if (!result.isConfirmed) {
                return;
            }

            setButtonLoadingState(this, true, 'Deleting...');

            try {
                const data = await sendDeleteRequest(this.dataset.bulkDeleteUrl, selectedIds);
                await Alert.success(data.message || 'Selected notifications deleted successfully.');
                window.location.reload();
            } catch (error) {
                Alert.error(error.message || 'Failed to delete selected notifications.');
                setButtonLoadingState(this, false);
                updateSelectionState();
            }
        });
    }

    updateSelectionState();
});
