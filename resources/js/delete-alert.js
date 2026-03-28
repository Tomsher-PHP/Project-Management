$(document).on('click', '.delete-form', function (e) {

    e.preventDefault();

    const form = this;
    const route = $(form).data('route');

    const confirmDelete = (message = 'Delete record?') => {
        Alert.confirm({
            title: 'Confirm Delete',
            text: message,
            confirmText: 'Yes, delete it',
            cancelText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    };

    // If no check route, just confirm delete
    if (!route) {
        confirmDelete();
        return;
    }

    // If check route exists, validate first
    $.get(route)
        .done(function (response) {
            const message = response?.allocated
                ? response.message
                : 'Delete record?';

            confirmDelete(message);
        })
        .fail(function () {
            confirmDelete();
        });

});