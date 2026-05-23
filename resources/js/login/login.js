document.addEventListener('DOMContentLoaded', () => {
    const updatePasswordToggle = (button, input) => {
        const isVisible = input.type === 'text';

        button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');

        const showIcon = button.querySelector('[data-password-icon="show"]');
        const hideIcon = button.querySelector('[data-password-icon="hide"]');

        showIcon?.classList.toggle('hidden', isVisible);
        hideIcon?.classList.toggle('hidden', !isVisible);
    };

    document.querySelectorAll('[data-password-field]').forEach((field) => {
        const input = field.querySelector('[data-password-input]');
        const button = field.querySelector('[data-password-toggle]');

        if (!input || !button) {
            return;
        }

        updatePasswordToggle(button, input);
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-password-toggle]');

        if (!button) {
            return;
        }

        const field = button.closest('[data-password-field]');
        const input = field?.querySelector('[data-password-input]');

        if (!input) {
            return;
        }

        input.type = input.type === 'password' ? 'text' : 'password';
        updatePasswordToggle(button, input);
    });
});