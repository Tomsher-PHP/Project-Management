document.addEventListener('DOMContentLoaded', () => {
    initOtpInput();
    initOtpForms();
    initResendCountdown();
});

function initOtpInput() {
    const input = document.querySelector('[data-cp-otp-input]');

    if (!input) {
        return;
    }

    const maxLength = parseInt(input.getAttribute('data-otp-length') || '6', 10);

    const sanitizeValue = () => {
        const digitsOnly = input.value.replace(/\D+/g, '').slice(0, maxLength);
        if (input.value !== digitsOnly) {
            input.value = digitsOnly;
        }
    };

    input.addEventListener('input', sanitizeValue);
    input.addEventListener('paste', () => {
        window.requestAnimationFrame(sanitizeValue);
    });
}

function initOtpForms() {
    document.querySelectorAll('[data-cp-otp-form]').forEach((form) => {
        const submitButton = form.querySelector('[data-cp-submit-button]');

        if (!submitButton) {
            return;
        }

        const labelNode = submitButton.querySelector('[data-cp-submit-label]');
        const spinnerNode = submitButton.querySelector('[data-cp-submit-spinner]');
        const defaultLabel = submitButton.getAttribute('data-default-label') || 'Verify OTP';
        const loadingLabel = submitButton.getAttribute('data-loading-label') || 'Verifying...';

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

function initResendCountdown() {
    const resendForm = document.querySelector('[data-cp-resend-form]');
    const resendButton = resendForm?.querySelector('[data-cp-resend-button]');
    const timerNode = resendForm?.querySelector('[data-cp-resend-timer]');

    if (!resendForm || !resendButton) {
        return;
    }

    const defaultLabel = resendButton.getAttribute('data-default-label') || 'Resend OTP';
    const loadingLabel = resendButton.getAttribute('data-loading-label') || 'Sending...';
    let remaining = Math.max(0, parseInt(resendButton.getAttribute('data-cooldown-seconds') || '0', 10));
    let intervalId = null;
    let isSubmitting = false;

    const renderState = () => {
        if (isSubmitting) {
            resendButton.disabled = true;
            resendButton.textContent = loadingLabel;
            return;
        }

        if (remaining > 0) {
            resendButton.disabled = true;
            resendButton.textContent = defaultLabel;
            if (timerNode) {
                timerNode.textContent = `You can request a new code in ${remaining}s`;
            }
        } else {
            resendButton.disabled = false;
            resendButton.textContent = defaultLabel;
            if (timerNode) {
                timerNode.textContent = '';
            }
        }
    };

    renderState();

    if (remaining > 0) {
        intervalId = window.setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                remaining = 0;
                window.clearInterval(intervalId);
                intervalId = null;
            }
            renderState();
        }, 1000);
    }

    resendForm.addEventListener('submit', (event) => {
        if (isSubmitting || remaining > 0) {
            event.preventDefault();
            return;
        }

        isSubmitting = true;
        renderState();
    });
}
