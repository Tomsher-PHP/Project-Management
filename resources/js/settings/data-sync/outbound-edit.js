$(document).ready(function () {
    // Only run if we are on the Outbound Edit Connection page
    if (!$('#edit-connection-form').length && !$('#create-api-modal').length && !$('#edit-api-modal').length) {
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
    // Create API Modal & Submission
    // -------------------------------------------------------------------------
    // Open Create API Modal
    $('#open-create-api-btn').on('click', function () {
        const modal = $('#create-api-modal');
        modal.find('form')[0].reset();
        modal.find('[data-error-for]').addClass('hidden').text('');
        $('#create-api-is-active').prop('checked', true);
        modal.removeClass('hidden');
    });

    // Submit Create API Form
    $('#create-api-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#create-api-submit-btn');

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
                    $('#create-api-modal').addClass('hidden');
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Added',
                            text: response.message || 'API configuration created.',
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => window.location.reload());
                    } else {
                        window.location.reload();
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
                    const msg = xhr.responseJSON?.message || 'Failed to add API.';
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
    // Edit API Modal & Submission
    // -------------------------------------------------------------------------
    // Open Edit API Modal
    $(document).on('click', '.edit-api-btn', function () {
        const btn = $(this);
        const modal = $('#edit-api-modal');
        const form = $('#edit-api-form');

        form.attr('action', btn.data('updateUrl'));
        form.find('[data-error-for]').addClass('hidden').text('');

        $('#edit-api-id').val(btn.data('id'));
        $('#edit-api-module').val(btn.data('module'));
        $('#edit-api-operation').val(btn.data('operation'));
        $('#edit-api-method').val(btn.data('method'));
        $('#edit-api-endpoint').val(btn.data('endpoint'));
        $('#edit-api-is-active').prop('checked', btn.data('isActive') == 1);

        modal.removeClass('hidden');
    });

    // Submit Edit API Form
    $('#edit-api-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#edit-api-submit-btn');

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
                    $('#edit-api-modal').addClass('hidden');
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: response.message || 'API configuration updated.',
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => window.location.reload());
                    } else {
                        window.location.reload();
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
                    const msg = xhr.responseJSON?.message || 'Failed to update API.';
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

