document.addEventListener('DOMContentLoaded', () => {
    initPasswordVisibilityToggles();
    initPasswordMatchFeedback();
    initResetFormSubmission();
});

function initPasswordVisibilityToggles() {
    document.querySelectorAll('[data-password-field]').forEach((field) => {
        const input = field.querySelector('[data-password-input]');
        const button = field.querySelector('[data-password-toggle]');

        if (!input || !button) {
            return;
        }

        const syncState = () => {
            const isVisible = input.type === 'text';
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');

            button.querySelector('[data-password-icon="show"]')?.classList.toggle('hidden', isVisible);
            button.querySelector('[data-password-icon="hide"]')?.classList.toggle('hidden', !isVisible);
        };

        syncState();

        button.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            syncState();
        });
    });
}

function initPasswordMatchFeedback() {
    const passwordInput = document.querySelector('[data-cp-new-password]');
    const confirmInput = document.querySelector('[data-cp-confirm-password]');
    const feedback = document.querySelector('[data-cp-match-feedback]');

    if (!passwordInput || !confirmInput || !feedback) {
        return;
    }

    const updateFeedback = () => {
        if (!confirmInput.value) {
            feedback.classList.add('hidden');
            feedback.textContent = '';
            return;
        }

        feedback.classList.remove('hidden', 'text-emerald-700', 'text-rose-600');
        if (passwordInput.value === confirmInput.value) {
            feedback.classList.add('text-emerald-700');
            feedback.textContent = 'Passwords match.';
        } else {
            feedback.classList.add('text-rose-600');
            feedback.textContent = 'Passwords do not match.';
        }
    };

    passwordInput.addEventListener('input', updateFeedback);
    confirmInput.addEventListener('input', updateFeedback);
}

function initResetFormSubmission() {
    const form = document.querySelector('[data-cp-reset-form]');
    const submitButton = form?.querySelector('[data-cp-submit-button]');

    if (!form || !submitButton) {
        return;
    }

    const labelNode = submitButton.querySelector('[data-cp-submit-label]');
    const spinnerNode = submitButton.querySelector('[data-cp-submit-spinner]');
    const defaultLabel = submitButton.getAttribute('data-default-label') || 'Reset Password';
    const loadingLabel = submitButton.getAttribute('data-loading-label') || 'Resetting Password...';

    let isSubmitting = false;

    form.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        if (!form.checkValidity()) {
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        submitButton.classList.add('opacity-75', 'cursor-not-allowed');

        if (labelNode) {
            labelNode.textContent = loadingLabel;
        }
        spinnerNode?.classList.remove('hidden');
    });

    window.addEventListener('pageshow', () => {
        isSubmitting = false;
        submitButton.disabled = false;
        submitButton.removeAttribute('aria-busy');
        submitButton.classList.remove('opacity-75', 'cursor-not-allowed');

        if (labelNode) {
            labelNode.textContent = defaultLabel;
        }
        spinnerNode?.classList.add('hidden');
    });
}
