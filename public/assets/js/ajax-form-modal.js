$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // OPEN CREATE
    $('.modal-open').on('click', function () {

        let modalId = $(this).data('target');
        let modal = $(modalId);

        let url = $(this).data('url');
        let method = $(this).data('method');
        let module = $(this).data('module');

        modal.find('.ajax-form').attr('action', url);
        modal.find('.form-method').val(method);

        modal.find('form')[0].reset();

        // Change title and button text
        modal.find('.modal-title').text(`Add ${module}`);
        modal.find('.submit-btn').text(`Create ${module}`);

        modal.removeClass('hidden');

        clearFormErrors(modal.find('.ajax-form'),);
    });

    // OPEN EDIT
    $('.edit-record').on('click', function () {
        let modalId = $(this).data('modal');
        let modal = $('#' + modalId);

        let url = $(this).data('url');
        let method = $(this).data('method');

        modal.find('.ajax-form').attr('action', url);
        modal.find('.form-method').val(method);

        $.each($(this).data(), function (key, value) {
            if (modal.find('[name="' + key + '"]').length) {
                modal.find('[name="' + key + '"]').val(value);
            }
        });

        // Change title and button text
        modal.find('.modal-title').text('Edit ' + $(this).data('module'));
        modal.find('.submit-btn').text('Update ' + $(this).data('module'));

        modal.removeClass('hidden');

        clearFormErrors(modal.find('.ajax-form'),);
    });

    // CLOSE MODAL
    $('.modal-close').on('click', function () {
        let modal = $(this).closest('.modal-form');

        modal.addClass('hidden');
        modal.find('form')[0].reset();
    });

    // SUBMIT FORM
    $('.ajax-form').on('submit', function (e) {

        e.preventDefault();

        let form = $(this);
        let url = form.attr('action');
        let formData = form.serialize();

        form.find('.error-text').remove();

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.status) {
                    Alert.success(response.message);
                    location.reload();
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, value) {
                        let input = form.find('[name="' + key + '"]');
                        input.after('<span class="text-red-500 error-text">' + value[0] + '</span>');

                    });
                }
            }

        });

    });

    const clearFormErrors = (form) => {
        form.find('.error-text').remove();
        form.find('input').removeClass('border-red-500');
        form.find('select').removeClass('border-red-500');
        form.find('textarea').removeClass('border-red-500');
    }

});