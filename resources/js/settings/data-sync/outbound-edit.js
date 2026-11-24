$(document).ready(function () {
    // Only run if we are on the Outbound Edit Connection page
    if (!$('#edit-connection-form').length && !$('#api-modal').length) {
        return;
    }

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------------------------
    // Modal Backdrop & Dismiss Handlers
    // -------------------------------------------------------------------------
    // Close modal when clicking outside modal-content (backdrop / overlay)
    $(document).on('click', '.modal', function (e) {
        if (!$(e.target).closest('.modal-content').length) {
            $(this).addClass('hidden');
        }
    });

    // Close modal on modal-close button
    $(document).on('click', '.modal-close', function () {
        $(this).closest('.modal').addClass('hidden');
    });

    // Close modal on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            $('.modal:not(.hidden)').addClass('hidden');
        }
    });

    // -------------------------------------------------------------------------
    // Submit Edit Connection Form via AJAX
    // -------------------------------------------------------------------------
    $('#edit-connection-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#edit-connection-submit-btn');

        form.find('[data-error-for]').addClass('hidden').text('');
        submitBtn.prop('disabled', true).addClass('opacity-70');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function (response) {
                submitBtn.prop('disabled', false).removeClass('opacity-70');
                if (response.success) {
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Saved',
                            text: response.message || 'Connection updated successfully.',
                            timer: 1500,
                            showConfirmButton: false,
                        });
                    }
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).removeClass('opacity-70');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, messages) {
                        const errorSpan = form.find(`[data-error-for="${key}"]`);
                        if (errorSpan.length) {
                            errorSpan.text(messages[0]).removeClass('hidden');
                        }
                    });
                } else {
                    const msg = xhr.responseJSON?.message || 'Failed to update connection.';
                    if (window.Swal) {
                        window.Swal.fire('Error', msg, 'error');
                    } else {
                        alert(msg);
                    }
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // API Modal (Unified Create & Edit)
    // -------------------------------------------------------------------------
    const apiModal = $('#api-modal');
    const apiForm = $('#api-form');
    const apiSubmitBtn = $('#api-modal-submit-btn');
    const storeUrl = apiForm.data('storeUrl');

    // Open Modal in Create Mode
    $('#open-create-api-btn').on('click', function () {
        apiForm[0].reset();
        apiForm.attr('action', storeUrl);
        $('#api-modal-method-input').val('POST');
        $('#api-modal-id').val('');
        apiForm.find('[data-error-for]').addClass('hidden').text('');

        $('#api-modal-title').text('Add API Configuration');
        $('#api-modal-subtitle').text('Define an outbound endpoint for this connection.');
        apiSubmitBtn.text('Add API');
        $('#api-modal-method').val('POST');
        $('#api-modal-is-active').prop('checked', true);

        apiModal.removeClass('hidden');
    });

    // Open Modal in Edit Mode
    $(document).on('click', '.edit-api-btn', function () {
        const btn = $(this);
        apiForm[0].reset();
        apiForm.find('[data-error-for]').addClass('hidden').text('');

        const updateUrl = btn.data('updateUrl');
        apiForm.attr('action', updateUrl);
        $('#api-modal-method-input').val('PUT');
        $('#api-modal-id').val(btn.data('id'));

        $('#api-modal-title').text('Edit API Configuration');
        $('#api-modal-subtitle').text('Update outbound API endpoint, operation, or HTTP method.');
        apiSubmitBtn.text('Save Changes');

        $('#api-modal-module').val(btn.data('module'));
        $('#api-modal-operation').val(btn.data('operation'));
        $('#api-modal-method').val(btn.data('method'));
        $('#api-modal-endpoint').val(btn.data('endpoint'));
        $('#api-modal-is-active').prop('checked', btn.data('isActive') == 1);

        apiModal.removeClass('hidden');
    });

    // Submit API Form (handles both Create and Edit)
    apiForm.on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const isEdit = $('#api-modal-method-input').val() === 'PUT';

        form.find('[data-error-for]').addClass('hidden').text('');
        apiSubmitBtn.prop('disabled', true).addClass('opacity-70');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function (response) {
                apiSubmitBtn.prop('disabled', false).removeClass('opacity-70');
                if (response.success) {
                    apiModal.addClass('hidden');
                    const title = isEdit ? 'Updated' : 'Added';
                    const defaultMsg = isEdit ? 'API configuration updated.' : 'API configuration created.';
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: title,
                            text: response.message || defaultMsg,
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => window.location.reload());
                    } else {
                        window.location.reload();
                    }
                }
            },
            error: function (xhr) {
                apiSubmitBtn.prop('disabled', false).removeClass('opacity-70');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, messages) {
                        const errorSpan = form.find(`[data-error-for="${key}"]`);
                        if (errorSpan.length) {
                            errorSpan.text(messages[0]).removeClass('hidden');
                        }
                    });
                } else {
                    const msg = xhr.responseJSON?.message || (isEdit ? 'Failed to update API.' : 'Failed to add API.');
                    if (window.Swal) {
                        window.Swal.fire('Error', msg, 'error');
                    } else {
                        alert(msg);
                    }
                }
            }
        });
    });
});
