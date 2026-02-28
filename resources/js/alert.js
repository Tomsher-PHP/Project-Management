import Swal from 'sweetalert2';

const Alert = {

    confirm(options) {
        return Swal.fire({
            title: options.title || 'Are you sure?',
            text: options.text || 'This action cannot be undone.',
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Yes',
            cancelButtonText: options.cancelText || 'Cancel',
            confirmButtonColor: options.confirmColor || '#22c55e',
            cancelButtonColor: options.cancelColor || '#ef4444',
        });
    },

    success(message, title = 'Success') {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            timer: 1000,
            showConfirmButton: false
        });
    },

    error(message, title = 'Error') {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            timer: 1500,
        });
    },

    info(message, title = 'Info') {
        return Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            timer: 1500,
        });
    }
};

export default Alert;