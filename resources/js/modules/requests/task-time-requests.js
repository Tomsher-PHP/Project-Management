import '../tasks/time-log-change-request';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-time-log-change-request-cancel-modal]');

    if (!modal) {
        return;
    }

    const form = modal.querySelector('[data-time-log-change-request-cancel-form]');
    const taskName = modal.querySelector('[data-time-log-change-request-cancel-task-name]');
    const reason = modal.querySelector('#time-log-change-request-cancellation-reason');
    const openButtons = document.querySelectorAll('[data-time-log-change-request-cancel-open]');
    const closeButtons = document.querySelectorAll('[data-time-log-change-request-cancel-close]');

    const openModal = (button) => {
        form.action = button.dataset.action || '#';
        taskName.textContent = button.dataset.taskName || '--';
        reason.value = '';

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        window.setTimeout(() => reason.focus(), 50);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        form.action = '#';
        reason.value = '';
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
