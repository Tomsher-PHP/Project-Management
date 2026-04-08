$(document).ready(function () {
    const normalizeCodeValue = (value = '') => String(value)
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_]+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_+|_+$/g, '');

    const refreshDescriptionCounter = (modal) => {
        const textarea = modal.find('textarea[name="description"][maxlength]').first();
        const counter = modal.find('[data-modal-description-count]').first();

        if (!textarea.length || !counter.length) {
            return;
        }

        counter.text(textarea.val().length);
    };

    const refreshEstimatedTimeInputs = (modal) => {
        modal.find('[data-estimated-time]').each(function () {
            this.dispatchEvent(new CustomEvent('estimated-time:refresh'));
        });
    };

    const updateLinkedSelectField = (modal, response) => {
        const fieldName = modal.data('selectTarget');
        const optionId = response?.data?.id;
        const optionLabel = response?.data?.name;

        if (!fieldName || optionId === undefined || optionId === null || !optionLabel) {
            return false;
        }

        const field = document.querySelector(`[name="${fieldName}"]`);

        if (!field) {
            return false;
        }

        const normalizedValue = String(optionId);

        if (field.tomselect) {
            field.tomselect.addOption({
                value: normalizedValue,
                text: optionLabel,
            });
            field.tomselect.refreshOptions(false);
            field.tomselect.setValue(normalizedValue, true);
        } else {
            let option = Array.from(field.options).find((item) => item.value === normalizedValue);

            if (!option) {
                option = document.createElement('option');
                option.value = normalizedValue;
                option.textContent = optionLabel;
                field.appendChild(option);
            }

            field.value = normalizedValue;
        }

        field.dispatchEvent(new Event('change', { bubbles: true }));

        return true;
    };

    const bumpModalTriggerSortOrder = (modal, response) => {
        const currentSortOrder = Number.parseInt(response?.data?.sort_order ?? '', 10);
        const modalId = modal.attr('id');

        if (!modalId || Number.isNaN(currentSortOrder)) {
            return;
        }

        const nextSortOrder = currentSortOrder + 1;

        $(`.modal-open[data-target="#${modalId}"]`).each(function () {
            $(this).data('sort_order', nextSortOrder);
            $(this).attr('data-sort_order', nextSortOrder);
        });
    };

    const syncTomSelectField = (field, value = null) => {
        if (!field) {
            return;
        }

        const nextValue = value ?? (field.multiple
            ? Array.from(field.selectedOptions || []).map((option) => option.value)
            : (field.value ?? ''));

        if (field.tomselect) {
            if (Array.isArray(nextValue)) {
                field.tomselect.setValue(nextValue, true);
            } else if (nextValue === null || nextValue === undefined || nextValue === '') {
                field.tomselect.clear(true);
            } else {
                field.tomselect.setValue(String(nextValue), true);
            }

            return;
        }

        field.value = nextValue;
    };

    const isTruthyCheckboxValue = (value) => (
        value === true
        || value === 1
        || value === '1'
        || value === 'true'
        || value === 'on'
    );

    const syncFieldValue = (field, value = null) => {
        if (!field) {
            return;
        }

        if (field.type === 'checkbox') {
            field.checked = isTruthyCheckboxValue(value);
            return;
        }

        if (field.tomselect) {
            syncTomSelectField(field, value);
            return;
        }

        field.value = value ?? '';
    };

    const resetTomSelectFields = (modal) => {
        modal.find('select.tom-select, input.tom-select, select.tom-select-no-search, input.tom-select-no-search, select.tom-select-multiple, input.tom-select-multiple').each(function () {
            syncTomSelectField(this, this.value);
        });
    };

    const initializeAutoCodeFields = (modal) => {
        const nameField = modal.find('[data-auto-code-source]').first()[0];
        const codeField = modal.find('[data-auto-code-target]').first()[0];

        if (!nameField || !codeField) {
            return;
        }

        const normalizedNameCode = normalizeCodeValue(nameField.value);
        const normalizedCode = normalizeCodeValue(codeField.value);

        if (codeField.value !== normalizedCode) {
            codeField.value = normalizedCode;
        }

        codeField.dataset.lastAutoCode = normalizedNameCode;
        codeField.dataset.codeManuallyEdited = normalizedCode !== '' && normalizedCode !== normalizedNameCode
            ? 'true'
            : 'false';
    };

    const syncCodeFromName = (modal, force = false) => {
        const nameField = modal.find('[data-auto-code-source]').first()[0];
        const codeField = modal.find('[data-auto-code-target]').first()[0];

        if (!nameField || !codeField) {
            return;
        }

        const nextCode = normalizeCodeValue(nameField.value);
        const lastAutoCode = codeField.dataset.lastAutoCode || '';
        const codeWasAutoGenerated = !codeField.dataset.codeManuallyEdited || codeField.dataset.codeManuallyEdited === 'false';

        if (!force && !codeWasAutoGenerated && codeField.value !== '' && codeField.value !== lastAutoCode) {
            return;
        }

        codeField.value = nextCode;
        codeField.dataset.lastAutoCode = nextCode;
        codeField.dataset.codeManuallyEdited = 'false';
    };

    const resetModalForm = (modal) => {
        modal.find('form')[0].reset();
        modal.removeData('selectTarget');
        resetTomSelectFields(modal);
        initializeAutoCodeFields(modal);
        refreshEstimatedTimeInputs(modal);
        refreshDescriptionCounter(modal);
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
        let selectTarget = $(this).data('selectTarget') || '';

        modal.find('.ajax-form').attr('action', url);
        modal.find('.form-method').val(method);
        modal.data('selectTarget', selectTarget);

        resetModalForm(modal);
        modal.data('selectTarget', selectTarget);

        $.each($(this).data(), function (key, value) {
            if (['target', 'url', 'method', 'module'].includes(key)) {
                return;
            }

            let field = modal.find('[name="' + key + '"]');

            if (!field.length) {
                return;
            }

            syncFieldValue(field[0], value);
        });

        initializeAutoCodeFields(modal);
        refreshEstimatedTimeInputs(modal);
        refreshDescriptionCounter(modal);

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
        modal.removeData('selectTarget');
        resetModalForm(modal);

        $.each($(this).data(), function (key, value) {

            let field = modal.find('[name="' + key + '"]');

            if (field.length) {
                syncFieldValue(field[0], value);
            }
        });

        initializeAutoCodeFields(modal);
        refreshEstimatedTimeInputs(modal);

        // Change title and button text
        modal.find('.modal-title').text('Edit ' + $(this).data('module'));
        modal.find('.submit-btn').text('Update ' + $(this).data('module'));
        refreshDescriptionCounter(modal);

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
                    const inlineSelectUpdated = updateLinkedSelectField(modal, response);
                    bumpModalTriggerSortOrder(modal, response);
                    modal.addClass('hidden');
                    resetModalForm(modal);

                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else if (inlineSelectUpdated) {
                        return;
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

    $(document).on('input', '.modal-form textarea[name="description"][maxlength]', function () {
        refreshDescriptionCounter($(this).closest('.modal-form'));
    });

    $(document).on('input', '.modal-form [data-auto-code-source]', function () {
        syncCodeFromName($(this).closest('.modal-form'));
    });

    $(document).on('input', '.modal-form [data-auto-code-target]', function () {
        const normalizedCode = normalizeCodeValue(this.value);

        if (this.value !== normalizedCode) {
            this.value = normalizedCode;
        }

        this.dataset.codeManuallyEdited = normalizedCode !== '' && normalizedCode !== (this.dataset.lastAutoCode || '')
            ? 'true'
            : 'false';
    });

});
