import Alert from '../alert';

const OTP_RESEND_DELAY_SECONDS = 60;

document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggles();
    initForgotPasswordFlow();
});

function initPasswordToggles() {
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
}

function initForgotPasswordFlow() {
    const modal = document.getElementById('multi-step-modal');

    if (!modal) {
        return;
    }

    const forgotForm = document.getElementById('forgot-form');
    const otpForm = document.getElementById('otp-form');
    const resetPasswordForm = document.getElementById('reset-password-form');
    const forgotButton = document.getElementById('step-1-next');
    const verifyButton = document.getElementById('step-2-next');
    const resetButton = document.getElementById('step-3-next');
    const openForgotPasswordButton = document.getElementById('open-forgot-password');
    const resendOtpButton = document.getElementById('resend-otp-button');
    const storedEmailInput = document.getElementById('stored-email');
    const maskedEmail = document.getElementById('masked-email');
    const finalOtpInput = document.getElementById('final-otp');
    const resetNewPasswordInput = document.getElementById('reset-new-password');
    const resetConfirmPasswordInput = document.getElementById('reset-confirm-password');
    const otpInputs = Array.from(modal.querySelectorAll('.otp-input'));
    const closeButtons = ['step-1-cancel', 'step-2-cancel', 'step-3-cancel']
        .map((id) => document.getElementById(id))
        .filter(Boolean);

    if (!forgotForm || !otpForm || !resetPasswordForm || !forgotButton || !verifyButton || !resetButton || !openForgotPasswordButton || !resendOtpButton || !storedEmailInput || !maskedEmail || !finalOtpInput || !resetNewPasswordInput || !resetConfirmPasswordInput || otpInputs.length === 0) {
        return;
    }

    let forgotPasswordEmail = '';
    let resendTimerId = null;
    let resendCountdown = 0;

    const updateResendButtonState = () => {
        if (resendCountdown > 0) {
            resendOtpButton.disabled = true;
            resendOtpButton.textContent = `Resend OTP in ${resendCountdown}s`;
            return;
        }

        resendOtpButton.disabled = false;
        resendOtpButton.textContent = 'Resend OTP';
    };

    const startOtpResendTimer = (duration = OTP_RESEND_DELAY_SECONDS) => {
        resetOtpResendState(false);

        resendCountdown = duration;
        updateResendButtonState();

        resendTimerId = window.setInterval(() => {
            resendCountdown -= 1;

            if (resendCountdown <= 0) {
                resetOtpResendState();
                return;
            }

            updateResendButtonState();
        }, 1000);
    };

    function resetOtpResendState(resetButtonLabel = true) {
        if (resendTimerId) {
            window.clearInterval(resendTimerId);
            resendTimerId = null;
        }

        resendCountdown = 0;

        if (resetButtonLabel) {
            updateResendButtonState();
        }
    }

    const updateOtpValue = () => {
        finalOtpInput.value = otpInputs.map((input) => input.value).join('');
    };

    const maskEmail = (email) => {
        const [name = '', domain = ''] = email.split('@');

        if (name.length <= 3) {
            return `${name.charAt(0) || ''}****@${domain}`;
        }

        return `${name.slice(0, 3)}****@${domain}`;
    };

    const goToStep = (stepNumber) => {
        modal.querySelectorAll('.step-content').forEach((step) => step.classList.add('hidden'));

        const targetStep = modal.querySelector(`.step-${stepNumber}`);

        if (!targetStep) {
            return;
        }

        targetStep.classList.remove('hidden');

        window.setTimeout(() => {
            const firstVisibleInput = targetStep.querySelector('input:not([type="hidden"])');
            firstVisibleInput?.focus();
        }, 100);
    };

    const resetMultiStepModal = () => {
        forgotPasswordEmail = '';
        forgotForm.reset();
        otpForm.reset();
        resetPasswordForm.reset();
        storedEmailInput.value = '';
        finalOtpInput.value = '';
        maskedEmail.textContent = 'mail id';
        otpInputs.forEach((input) => {
            input.value = '';
        });

        resetOtpResendState();
        goToStep(1);

        window.setTimeout(() => {
            forgotForm.querySelector('input[name="email"]')?.focus();
        }, 100);
    };

    const setButtonLoading = (button, isLoading, loadingText = 'Processing...') => {
        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v8H4z"></path>
                    </svg>
                    ${loadingText}
                </span>
            `;
            return;
        }

        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    };

    const getErrorMessage = (error, fallbackMessage) => {
        return error?.response?.data?.message || error?.message || fallbackMessage;
    };

    const submitStepRequest = async ({
        form,
        data,
        button,
        onSuccess,
        loadingText = 'Processing...',
        keepButtonDisabled = false,
    }) => {
        setButtonLoading(button, true, loadingText);

        try {
            const response = await window.axios.post(form.action, data);
            const payload = response.data;

            if (payload.success) {
                await onSuccess(payload);
                return payload;
            }

            Alert.errorModal(payload.message || 'Something went wrong.', 'Error', 'multi-step-modal');
            return null;
        } catch (error) {
            Alert.errorModal(getErrorMessage(error, 'Something went wrong.'), 'Error', 'multi-step-modal');
            return null;
        } finally {
            if (!keepButtonDisabled) {
                setButtonLoading(button, false);
            }
        }
    };

    const requestForgotPasswordOtp = async (email, { button } = {}) => {
        const actionButton = button || forgotButton;

        return submitStepRequest({
            form: forgotForm,
            data: { email },
            button: actionButton,
            loadingText: 'Sending...',
            keepButtonDisabled: actionButton === resendOtpButton,
            onSuccess: async (response) => {
                forgotPasswordEmail = response.email;
                storedEmailInput.value = response.email;
                maskedEmail.textContent = maskEmail(response.email);

                Alert.successModal(response.message, 'Success', 'multi-step-modal');
                goToStep(2);
                startOtpResendTimer();
            },
        });
    };

    const resendForgotPasswordOtp = async () => {
        if (resendCountdown > 0 || !forgotPasswordEmail) {
            return;
        }

        resendOtpButton.disabled = true;

        const response = await requestForgotPasswordOtp(forgotPasswordEmail, { button: resendOtpButton });

        if (!response) {
            setButtonLoading(resendOtpButton, false);
            updateResendButtonState();
            return;
        }

        setButtonLoading(resendOtpButton, false);
        updateResendButtonState();
    };

    forgotButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const email = forgotForm.querySelector('input[name="email"]')?.value.trim() || '';

        if (!email) {
            Alert.errorModal('Email is required', 'Error', 'multi-step-modal');
            return;
        }

        await requestForgotPasswordOtp(email);
    });

    verifyButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const otp = finalOtpInput.value.trim();

        if (!otp) {
            Alert.errorModal('OTP is required', 'Error', 'multi-step-modal');
            return;
        }

        await submitStepRequest({
            form: otpForm,
            data: {
                email: storedEmailInput.value,
                otp,
            },
            button: verifyButton,
            onSuccess: async (response) => {
                Alert.successModal(response.message, 'Success', 'multi-step-modal');
                goToStep(3);
            },
        });
    });

    resetButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const password = resetNewPasswordInput.value;
        const confirmPassword = resetConfirmPasswordInput.value;

        if (!password || !confirmPassword) {
            Alert.errorModal('All fields are required', 'Error', 'multi-step-modal');
            return;
        }

        await submitStepRequest({
            form: resetPasswordForm,
            data: {
                email: storedEmailInput.value,
                password,
                password_confirmation: confirmPassword,
            },
            button: resetButton,
            onSuccess: async (response) => {
                modal.classList.add('hidden');
                resetMultiStepModal();
                Alert.success(response.message, 'Success');
            },
        });
    });

    resendOtpButton.addEventListener('click', async (event) => {
        event.preventDefault();
        await resendForgotPasswordOtp();
    });

    openForgotPasswordButton.addEventListener('click', () => {
        resetMultiStepModal();
        modal.classList.remove('hidden');
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            resetMultiStepModal();
        });
    });

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);

            if (input.value && otpInputs[index + 1]) {
                otpInputs[index + 1].focus();
            }

            updateOtpValue();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && otpInputs[index - 1]) {
                otpInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (event) => {
            const pastedText = event.clipboardData?.getData('text') || '';

            if (!/^\d{5}$/.test(pastedText)) {
                return;
            }

            otpInputs.forEach((otpInput, otpIndex) => {
                otpInput.value = pastedText[otpIndex] || '';
            });

            updateOtpValue();
            otpInputs[otpInputs.length - 1]?.focus();
            event.preventDefault();
        });
    });

    updateResendButtonState();
}
