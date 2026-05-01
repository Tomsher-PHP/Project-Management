import Alert from '../../alert';

const initializeProjectPayment = () => {
    if (document.body.dataset.projectPaymentInitialized === 'true') {
        return;
    }

    const getPaymentModal = () => document.getElementById('project-payment-modal');

    const setPaymentDateValue = (input, value) => {
        if (!input) return;
        if (input._flatpickr) {
            input._flatpickr.setDate(value || '', true, 'Y-m-d');
        } else {
            input.value = value || '';
        }
    };

    document.addEventListener('click', (event) => {
        const editBtn = event.target.closest('[data-project-payment-edit]');
        if (!editBtn) return;

        event.preventDefault();

        const modal = getPaymentModal();
        if (!modal) return;

        const form = modal.querySelector('[data-project-payment-form]');
        if (!form) return;

        // Reset any existing errors
        form.querySelectorAll('[data-project-payment-error-for]').forEach(node => {
            node.textContent = '';
            node.classList.add('hidden');
        });
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.classList.remove('border-red-500');
        });

        // Set action URL to update route
        form.dataset.actionUrl = editBtn.dataset.url;

        // Populate fields
        const amountField = modal.querySelector('[name="amount"]');
        if (amountField) amountField.value = editBtn.dataset.amount || '';

        setPaymentDateValue(modal.querySelector('[name="paid_date"]'), editBtn.dataset.paidDate);
        setPaymentDateValue(modal.querySelector('[name="coverage_start_date"]'), editBtn.dataset.coverageStartDate);
        setPaymentDateValue(modal.querySelector('[name="coverage_end_date"]'), editBtn.dataset.coverageEndDate);

        const notesField = modal.querySelector('[name="notes"]');
        if (notesField) notesField.value = editBtn.dataset.notes || '';

        const submitBtn = modal.querySelector('[data-project-payment-submit]');
        if (submitBtn) {
            submitBtn.textContent = 'Update Payment Status';
            submitBtn.disabled = false;
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            amountField?.focus();
        }, 50);
    });

    document.body.dataset.projectPaymentInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', initializeProjectPayment);
// Re-initialize if the payments tab is replaced
document.addEventListener('project-tab:replace', (event) => {
    if (event.detail?.tab === 'payments') {
        // Since we use event delegation for the click, we don't strictly need to re-init
        // but it's good practice if we had specific per-element bindings.
        // In this case, initializeProjectPayment only runs once.
    }
});
