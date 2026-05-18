import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    $(document).on('click', '.project-delete-form button', function (e) {
        e.preventDefault();

        const button = this;
        const form = button.closest('form');
        if (!form) return;

        const deleteUrl = form.action;
        const summaryUrl = `${deleteUrl}/delete-summary`;

        // ⏳ Premium loading state using SweetAlert2
        Swal.fire({
            title: 'Analyzing Project...',
            html: 'Please wait while we compile the project dependency summary.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // 📡 Fetch dependency summary HTML
        $.get(summaryUrl)
            .done((response) => {
                Swal.close();

                if (!response.success || !response.html) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to retrieve the project summary. Please try again.',
                    });
                    return;
                }

                const hasRunningTimers = response.has_running_timers === true || response.has_running_timers === 'true';

                // 🔔 Trigger verification alert using returned HTML
                Alert.confirm({
                    title: 'Confirm Project Deletion',
                    html: response.html,
                    icon: hasRunningTimers ? 'error' : 'warning',
                    confirmText: 'Yes, delete it',
                    cancelText: hasRunningTimers ? 'Close' : 'Cancel',
                    showConfirmButton: !hasRunningTimers,
                    requireText: hasRunningTimers ? null : 'DELETE',
                }).then((result) => {
                    if (result?.isConfirmed && !hasRunningTimers) {
                        form.submit();
                    }
                });
            })
            .fail(() => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while analyzing the project. Please try again.',
                });
            });
    });
});
