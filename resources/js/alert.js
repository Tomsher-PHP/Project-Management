import Swal from 'sweetalert2';

const normalizeToastArgs = (titleOrOptions, maybeOptions = {}) => {
    if (titleOrOptions && typeof titleOrOptions === 'object' && !Array.isArray(titleOrOptions)) {
        return {
            title: titleOrOptions.title,
            options: titleOrOptions,
        };
    }

    return {
        title: titleOrOptions,
        options: maybeOptions,
    };
};

const Alert = {

    confirm(options) {
        const requireText = typeof options.requireText === 'string' && options.requireText.length
            ? options.requireText
            : null;

        return Swal.fire({
            target: options.target || document.body,
            title: options.title || 'Are you sure?',
            text: options.html ? undefined : (options.text || 'This action cannot be undone.'),
            html: options.html,
            icon: options.icon || 'warning',
            input: requireText ? 'text' : undefined,
            inputPlaceholder: requireText ? `Type "${requireText}" to confirm` : undefined,
            showConfirmButton: options.showConfirmButton !== undefined ? options.showConfirmButton : true,
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Yes',
            cancelButtonText: options.cancelText || 'Cancel',
            confirmButtonColor: options.confirmColor || '#22c55e',
            cancelButtonColor: options.cancelColor || '#ef4444',
            didOpen: () => {
                if (!requireText) {
                    return;
                }

                const popup = Swal.getPopup();
                const input = Swal.getInput();
                const confirmButton = Swal.getConfirmButton();

                if (!popup || !input || !confirmButton) {
                    return;
                }

                confirmButton.disabled = true;

                const syncValidationState = () => {
                    const value = input.value ?? '';
                    const isValid = value === requireText;

                    confirmButton.disabled = !isValid;

                    if (isValid || value === '') {
                        Swal.resetValidationMessage();
                        return;
                    }

                    Swal.showValidationMessage(`You must type "${requireText}" to confirm`);
                };

                input.addEventListener('input', syncValidationState);
                syncValidationState();
            },
            preConfirm: (value) => {
                if (!requireText) {
                    return value;
                }

                if (value !== requireText) {
                    Swal.showValidationMessage(`You must type "${requireText}" to confirm`);
                    return false;
                }

                return value;
            },
            customClass: {
                confirmButton: 'bg-success-300 hover:bg-success-400 text-white',
                cancelButton: 'bg-error-50 hover:bg-error-100 text-error-200 hover:text-white'
            }
        });
    },

    success(message, title = 'Success', options = {}) {
        const normalized = normalizeToastArgs(title, options);

        return Swal.fire({
            target: normalized.options.target || document.body,
            position: "bottom",
            icon: 'success',
            // title: normalized.title || 'Success',
            text: message,
            showConfirmButton: false,
            timer: 2500,
            toast: true,              // makes it small like a toast
            width: 350,
            padding: '0.5rem 0.75rem',
            customClass: {
                popup: 'small-alert small-alert-success',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },

    error(message, title = 'Error', options = {}) {
        const normalized = normalizeToastArgs(title, options);

        return Swal.fire({
            target: normalized.options.target || document.body,
            position: "bottom",
            icon: "error",
            // title: normalized.title || 'Error',
            text: message,
            showConfirmButton: false,
            timer: 3000,
            toast: true,              // makes it small like a toast
            width: 320,
            padding: '0.5rem 0.75rem',
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },

    info(message, title = 'Info', options = {}) {
        const normalized = normalizeToastArgs(title, options);

        return Swal.fire({
            target: normalized.options.target || document.body,
            position: "bottom",
            icon: 'info',
            // title: normalized.title || 'Info',
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 320,
            padding: '0.5rem 0.75rem',
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },

    // success alert for modal
    successModal(message, title = 'Success', modalId) {
        return Swal.fire({
            target: `#${modalId}`,
            position: "bottom",
            icon: 'success',
            // title: title,
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 320,
            padding: '0.5rem 0.75rem',
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },

    errorModal(message, title = 'Error', modalId) {
        return Swal.fire({
            target: `#${modalId}`,
            position: "bottom",
            icon: "error",
            // title: title,
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 320,
            padding: '0.5rem 0.75rem',
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },
};

export default Alert;
