$(document).ready(function () {
    const refreshEstimatedTimeInputs = (modal) => {
        modal.find('[data-estimated-time]').each(function () {
            this.dispatchEvent(new CustomEvent('estimated-time:refresh'));
        });
    };

    const resetTomSelectFields = (modal) => {
        modal.find('select.tom-select').each(function () {
            if (this.tomselect) {
                this.tomselect.clear(true);
            } else {
                this.value = '';
            }
        });
    };

    const resetModalForm = (modal) => {
        modal.find('form')[0].reset();
        resetTomSelectFields(modal);
        refreshEstimatedTimeInputs(modal);
        clearFormErrors(modal.find('.ajax-form'));
    };

    const replaceRenderedSection = (response) => {
        if (!response.html || !response.render_target) {
            return false;
        }

        const currentTarget = document.querySelector(response.render_target);

        if (!currentTarget) {
            return false;
        }

        if (response.render_mode === 'replace_inner') {
            currentTarget.innerHTML = response.html;

            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                window.Alpine.initTree(currentTarget);
            }

            document.dispatchEvent(new CustomEvent('ajax-form:rendered', {
                detail: { root: currentTarget, selector: response.render_target },
            }));

            return true;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = response.html.trim();
        const newRoot = wrapper.firstElementChild;

        if (!newRoot) {
            return false;
        }

        currentTarget.replaceWith(newRoot);

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(newRoot);
        }

        document.dispatchEvent(new CustomEvent('ajax-form:rendered', {
            detail: { root: newRoot, selector: response.render_target },
        }));

        return true;
    };

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // OPEN CREATE
    $(document).on('click', '.modal-open', function () {

        let modalId = $(this).data('target');
        let modal = $(modalId);

        let url = $(this).data('url');
        let method = $(this).data('method');
        let module = $(this).data('module');

        modal.find('.ajax-form').attr('action', url);
        modal.find('.form-method').val(method);

        resetModalForm(modal);

        // Change title and button text
        modal.find('.modal-title').text(`Add ${module}`);
        modal.find('.submit-btn').text(`Create ${module}`);

        modal.removeClass('hidden');
    });

    // OPEN EDIT
    $(document).on('click', '.edit-record', function () {

        let modalId = $(this).data('modal');
        let modal = $('#' + modalId);

        let url = $(this).data('url');
        let method = $(this).data('method');

        modal.find('.ajax-form').attr('action', url);
        modal.find('.form-method').val(method);
        resetModalForm(modal);

        $.each($(this).data(), function (key, value) {

            let field = modal.find('[name="' + key + '"]');

            if (field.length) {

                // If it's a select with TomSelect
                if (field.hasClass('tom-select') && field[0].tomselect) {

                    field[0].tomselect.setValue(value);
                } else {
                    field.val(value);
                }

            }
        });

        refreshEstimatedTimeInputs(modal);

        // Change title and button text
        modal.find('.modal-title').text('Edit ' + $(this).data('module'));
        modal.find('.submit-btn').text('Update ' + $(this).data('module'));

        modal.removeClass('hidden');
    });

    // CLOSE MODAL
    $(document).on('click', '.modal-close', function () {
        let modal = $(this).closest('.modal-form');

        modal.addClass('hidden');
        resetModalForm(modal);
    });

    // SUBMIT FORM
    $(document).on('submit', '.ajax-form', function (e) {

        e.preventDefault();

        let form = $(this);
        let url = form.attr('action');
        let formData = new FormData(this);

        form.find('.error-text').remove();

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status) {
                    Alert.success(response.message);

                    const modal = form.closest('.modal-form');
                    modal.addClass('hidden');
                    resetModalForm(modal);

                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else if (!replaceRenderedSection(response)) {
                        location.reload();
                    }
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
