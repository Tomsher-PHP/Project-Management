$(document).on('click', '.delete-form', function (e) {

    e.preventDefault();

    const form = this;
    const route = $(form).data('route');
    const isAjaxDelete = $(form).data('ajax-delete') === true || $(form).attr('data-ajax-delete') === 'true';

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

    const submitDelete = () => {
        if (!isAjaxDelete) {
            form.submit();
            return;
        }

        $.ajax({
            url: form.action,
            type: 'POST',
            data: $(form).serialize(),
            headers: {
                'Accept': 'application/json'
            },
            success: function (response) {
                if (response.status) {
                    Alert.success(response.message || 'Deleted successfully.');

                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else if (!replaceRenderedSection(response)) {
                        window.location.reload();
                    }
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Unable to delete this record.';
                Alert.error(message);
            }
        });
    };

    const confirmDelete = (message = 'Delete record?') => {
        const requiresDeleteTextConfirmation = [
            /\/users\/\d+(?:\/)?$/,
            /\/teams\/\d+(?:\/)?$/,
            /\/customers\/\d+(?:\/)?$/,
            /\/projects\/\d+(?:\/)?$/,
            /\/tasks\/\d+(?:\/)?$/,
        ].some((pattern) => pattern.test(form.action));

        Alert.confirm({
            title: 'Confirm Delete',
            text: message,
            confirmText: 'Yes, delete it',
            cancelText: 'Cancel',
            requireText: requiresDeleteTextConfirmation ? 'DELETE' : null,
        }).then(result => {
            if (result.isConfirmed) {
                submitDelete();
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
