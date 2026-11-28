document.addEventListener('DOMContentLoaded', () => {
    initPasswordVisibilityToggles();
    initLoginFormSubmission();
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

function initLoginFormSubmission() {
    document.querySelectorAll('[data-cp-login-form], [data-cp-auth-form]').forEach((form) => {
        const submitButton = form.querySelector('[data-cp-submit-button]');

        if (!submitButton) {
            return;
        }

        const labelNode = submitButton.querySelector('[data-cp-submit-label]');
        const spinnerNode = submitButton.querySelector('[data-cp-submit-spinner]');
        const defaultLabel = submitButton.getAttribute('data-default-label') || 'Submit';
        const loadingLabel = submitButton.getAttribute('data-loading-label') || 'Submitting...';

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
    });
}
