
import Alert from './alert';

$(document).on('click', '.status-toggle', function () {

    let btn = $(this);

    if (btn.data('processing')) return;
    btn.data('processing', true);

    let id = btn.data('id');
    let url = btn.data('url');
    let entity = btn.data('entity');

    let isActive = btn.attr('aria-checked') === 'true';
    let actionText = isActive ? 'deactivate' : 'activate';
    let newStatus = !isActive;

    Alert.confirm({
        title: 'Are you sure?',
        text: `You are about to ${actionText} this ${entity}.`,
        confirmText: `Yes, ${actionText} it`
    }).then(result => {

        if (!result.isConfirmed) {
            btn.data('processing', false);
            btn.toggleClass('active', isActive);
            return;
        }

        let requestData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            id: id
        };

        // Additional data for report-related toggles
        if (
            entity === 'daily-work-report-recipient' ||
            entity === 'user-report-preference' ||
            entity === 'user-individual-report-preference'
        ) {
            requestData.report_key =
                new URLSearchParams(window.location.search).get('report_key') || 'daily_work';
        }

        if (
            entity === 'user-report-preference' ||
            entity === 'user-individual-report-preference'
        ) {
            requestData.user_id = id;
        }

        if (entity === 'user-report-preference') {
            requestData.is_enabled = newStatus ? 1 : 0;
        }

        if (entity === 'user-individual-report-preference') {
            requestData.receive_individual_report = newStatus ? 1 : 0;
        }

        $.ajax({
            url: url,
            type: 'PATCH',
            data: requestData,

            success: function (response) {

                if (response.success) {

                    let updatedStatus = (response.is_active ?? response.status) == 1;

                    btn.attr('aria-checked', updatedStatus);
                    btn.toggleClass('active', updatedStatus);

                    const statusBadge = btn.closest('tr').find('[data-status-badge]');

                    if (statusBadge.length) {
                        statusBadge.text(updatedStatus ? 'Active' : 'Inactive');
                        statusBadge.toggleClass('bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-300', updatedStatus);
                        statusBadge.toggleClass('bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300', !updatedStatus);
                    }

                    let capitalizedEntity = entity.charAt(0).toUpperCase() + entity.slice(1);
                    Alert.success(`${capitalizedEntity} ${actionText}d successfully.`);
                }
                else {
                    Alert.error('Status update failed.');
                }
            },

            error: function (xhr) {
                Alert.error(xhr.responseJSON?.message || 'Something went wrong.');
            },

            complete: function () {
                btn.data('processing', false);
            }
        });

    });

});
