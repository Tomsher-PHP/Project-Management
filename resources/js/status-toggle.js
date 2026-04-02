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

        $.ajax({
            url: url,
            type: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            },

            success: function (response) {

                if (response.success) {

                    let newStatus = (response.is_active ?? response.status) == 1;

                    // Update switch UI
                    btn.attr('aria-checked', newStatus);
                    btn.toggleClass('active', newStatus);

                    let capitalizedEntity = entity.charAt(0).toUpperCase() + entity.slice(1);
                    Alert.success(`${capitalizedEntity} ${actionText}d successfully.`);
                }
                else {
                    Alert.error('Status update failed.');
                }
            },

            error: function () {
                Alert.error('Something went wrong.');
            },

            complete: function () {
                btn.data('processing', false);
            }
        });

    });

});
