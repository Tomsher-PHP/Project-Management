function closeOneTimeKeyModal() {
    const input = document.getElementById('one-time-key-value');
    if (input) input.value = '';

    const modal = document.getElementById('one-time-key-modal');
    if (modal) modal.classList.add('hidden');

    window.location.reload();
}

document.addEventListener('click', function (event) {
    if (!document.querySelector('[data-data-sync-settings]')) return;

    const target = event.target;

    // One-Time Key Modal Outside Click
    const oneTimeModal = document.getElementById('one-time-key-modal');
    if (oneTimeModal && !oneTimeModal.classList.contains('hidden')) {
        if (!target.closest('.modal-content') && (target.closest('#one-time-key-modal') || target.classList.contains('modal-overlay'))) {
            closeOneTimeKeyModal();
            return;
        }
    }

    // Create Client Modal Outside Click
    const createModal = document.getElementById('create-client-modal');
    if (createModal && !createModal.classList.contains('hidden')) {
        if (!target.closest('.modal-content') && (target.closest('#create-client-modal') || target.classList.contains('modal-overlay'))) {
            createModal.classList.add('hidden');
            return;
        }
    }

    // Edit Client Modal Outside Click
    const editModal = document.getElementById('edit-client-modal');
    if (editModal && !editModal.classList.contains('hidden')) {
        if (!target.closest('.modal-content') && (target.closest('#edit-client-modal') || target.classList.contains('modal-overlay'))) {
            editModal.classList.add('hidden');
            return;
        }
    }

    // Generic Modal Outside / Overlay Click
    const openModal = target.closest('.modal:not(.hidden)');
    if (openModal && !target.closest('.modal-content')) {
        if (openModal.id === 'one-time-key-modal') {
            closeOneTimeKeyModal();
        } else {
            openModal.classList.add('hidden');
        }
        return;
    }

    // Modal Close Buttons (Cancel / x buttons)
    const closeButton = target.closest('.modal-close');
    if (closeButton) {
        const parentModal = closeButton.closest('.modal');
        if (parentModal) {
            if (parentModal.id === 'one-time-key-modal') {
                closeOneTimeKeyModal();
            } else {
                parentModal.classList.add('hidden');
            }
        }
        return;
    }

    // Done Button on One-Time Key Modal
    if (target.closest('#one-time-key-done-btn')) {
        closeOneTimeKeyModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (!document.querySelector('[data-data-sync-settings]')) return;
    if (event.key !== 'Escape' && event.keyCode !== 27) return;

    const visibleModals = document.querySelectorAll('.modal:not(.hidden)');
    visibleModals.forEach(modal => {
        if (modal.id === 'one-time-key-modal') {
            closeOneTimeKeyModal();
        } else {
            modal.classList.add('hidden');
        }
    });
});
