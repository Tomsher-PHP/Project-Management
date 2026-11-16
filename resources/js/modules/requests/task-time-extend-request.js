import '../tasks/approve-extend-time';

document.addEventListener('DOMContentLoaded', () => {
    const rejectModal = document.querySelector('[data-extend-request-reject-modal]');
    const rejectForm = document.querySelector('[data-extend-request-reject-form]');
    const rejectReason = document.getElementById('extend-request-rejection-reason');
    const rejectTitle = document.querySelector('[data-extend-request-reject-title]');
    const cancelModal = document.querySelector('[data-extend-request-cancel-modal]');
    const cancelForm = document.querySelector('[data-extend-request-cancel-form]');
    const cancelTaskName = document.querySelector('[data-extend-request-cancel-task-name]');
    const cancelReason = document.getElementById('extend-request-cancellation-reason');

    const openRejectModal = (button) => {
        if (!rejectModal || !rejectForm) {
            return;
        }

        rejectForm.action = button.dataset.action || '#';
        rejectForm.reset();

        if (rejectTitle) {
            rejectTitle.textContent = button.dataset.requestUserName
                ? `Requested by ${button.dataset.requestUserName}`
                : '';
        }

        rejectModal.classList.remove('hidden');
        rejectModal.setAttribute('aria-hidden', 'false');

        window.setTimeout(() => rejectReason?.focus(), 50);
    };

    const closeRejectModal = () => {
        rejectModal?.classList.add('hidden');
        rejectModal?.setAttribute('aria-hidden', 'true');
    };

    const openCancelModal = (button) => {
        if (!cancelModal || !cancelForm) {
            return;
        }

        cancelForm.action = button.dataset.action || '#';

        if (cancelTaskName) {
            cancelTaskName.textContent = button.dataset.taskName || '--';
        }

        if (cancelReason) {
            cancelReason.value = '';
        }

        cancelModal.classList.remove('hidden');
        cancelModal.setAttribute('aria-hidden', 'false');

        window.setTimeout(() => cancelReason?.focus(), 50);
    };

    const closeCancelModal = () => {
        cancelModal?.classList.add('hidden');
        cancelModal?.setAttribute('aria-hidden', 'true');

        if (cancelForm) {
            cancelForm.action = '#';
        }

        if (cancelReason) {
            cancelReason.value = '';
        }
    };

    document.querySelectorAll('[data-extend-request-reject-open]').forEach((button) => {
        button.addEventListener('click', () => openRejectModal(button));
    });

    document.querySelectorAll('[data-extend-request-reject-close]').forEach((button) => {
        button.addEventListener('click', closeRejectModal);
    });

    document.querySelectorAll('[data-extend-request-cancel-open]').forEach((button) => {
        button.addEventListener('click', () => openCancelModal(button));
    });

    document.querySelectorAll('[data-extend-request-cancel-close]').forEach((button) => {
        button.addEventListener('click', closeCancelModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (cancelModal && !cancelModal.classList.contains('hidden')) {
            closeCancelModal();
        }

        if (rejectModal && !rejectModal.classList.contains('hidden')) {
            closeRejectModal();
        }
    });
});
