import Swal from 'sweetalert2';

const Alert = {

    confirm(options) {
        return Swal.fire({
            target: options.target || document.body,
            title: options.title || 'Are you sure?',
            text: options.text || 'This action cannot be undone.',
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Yes',
            cancelButtonText: options.cancelText || 'Cancel',
            confirmButtonColor: options.confirmColor || '#22c55e',
            cancelButtonColor: options.cancelColor || '#ef4444',
            customClass: {
                confirmButton: 'bg-success-300 hover:bg-success-400 text-white',
                cancelButton: 'bg-error-50 hover:bg-error-100 text-error-200 hover:text-white'
            }
        });
    },

    success(message, title = 'Success') {
        return Swal.fire({
            position: "top-end",
            icon: 'success',
            title: title,
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 400,               // smaller width
            padding: "0.75rem",       // reduce padding
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },

    error(message, title = 'Error') {
        return Swal.fire({
            position: "top-end",
            icon: "error",
            title: title,
            text: message,
            showConfirmButton: false,
            timer: 3000,
            toast: true,              // makes it small like a toast
            width: 400,               // smaller width
            padding: "0.75rem",       // reduce padding
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },

    info(message, title = 'Info') {
        return Swal.fire({
            position: "top-end",
            icon: 'info',
            title: title,
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 400,               // smaller width
            padding: "0.75rem",       // reduce padding
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
            position: "top-end",
            icon: 'success',
            title: title,
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 400,               // smaller width
            padding: "0.75rem",       // reduce padding
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
            position: "top-end",
            icon: "error",
            title: title,
            text: message,
            showConfirmButton: false,
            timer: 1500,
            toast: true,              // makes it small like a toast
            width: 400,               // smaller width
            padding: "0.75rem",       // reduce padding
            customClass: {
                popup: 'small-alert',
                title: 'small-alert-title',
                htmlContainer: 'small-alert-text'
            }
        });
    },
};

export default Alert;
