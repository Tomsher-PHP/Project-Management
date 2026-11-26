import Alert from '../../alert';
import { initTomSelect } from '../../components/tom-select';

$(document).ready(function () {
    // Only run if we are on the Outbound Edit Connection page
    if (!$('#edit-connection-form').length && !$('#api-modal').length) {
        return;
    }

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------------------------
    // Modal Backdrop & Dismiss Handlers
    // -------------------------------------------------------------------------
    $(document).on('click', '.modal', function (e) {
        if (!$(e.target).closest('.modal-content').length) {
            $(this).addClass('hidden');
        }
    });

    $(document).on('click', '.modal-close', function () {
        $(this).closest('.modal').addClass('hidden');
    });

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
                    Alert.success(response.message || 'Connection updated successfully.');
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
                    Alert.error(msg, 'Error');
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // API Modal (Unified Create & Edit with TomSelect & Operation Conflict Rules)
    // -------------------------------------------------------------------------
    const apiModal = $('#api-modal');
    const apiForm = $('#api-form');
    const apiSubmitBtn = $('#api-modal-submit-btn');
    const storeUrl = apiForm.data('storeUrl');

    if (apiModal.length) {
        initTomSelect(apiModal[0]);
    }

    const rawExistingApis = apiModal.data('existingApis');
    const existingApis = Array.isArray(rawExistingApis)
        ? rawExistingApis
        : (typeof rawExistingApis === 'string' ? JSON.parse(rawExistingApis || '[]') : []);

    const rawAvailableOps = apiModal.data('availableOperations');
    const availableOperations = (typeof rawAvailableOps === 'object' && rawAvailableOps !== null)
        ? rawAvailableOps
        : (typeof rawAvailableOps === 'string' ? JSON.parse(rawAvailableOps || '{}') : {
            'update': 'Update',
            'status_update': 'Status Update',
            'delete': 'Delete',
            'sync': 'Sync / Upsert'
        });

    const rawModuleOperations = apiModal.data('moduleOperations');
    const moduleOperations = (typeof rawModuleOperations === 'object' && rawModuleOperations !== null)
        ? rawModuleOperations
        : (typeof rawModuleOperations === 'string' ? JSON.parse(rawModuleOperations || '{}') : {
            'technologies': ['sync', 'delete'],
            'industries': ['sync', 'delete'],
            'project_categories': ['sync', 'delete'],
            'project_statuses': ['sync', 'delete'],
            'projects': ['status_update', 'update']
        });

    function setSelectValue(selector, value) {
        const el = $(selector)[0];
        if (el && el.tomselect) {
            el.tomselect.setValue(value);
        } else {
            $(selector).val(value);
        }
    }

    function clearSelectValue(selector, defaultValue = '') {
        const el = $(selector)[0];
        if (el && el.tomselect) {
            if (defaultValue) {
                el.tomselect.setValue(defaultValue);
            } else {
                el.tomselect.clear();
            }
        } else {
            $(selector).val(defaultValue);
        }
    }

    /**
     * Dynamically update Operation dropdown options based on module and conflict rules.
     * Only displays operations supported by the selected module.
     */
    function refreshOperationOptions(selectedModule, currentApiId, targetOperation = null) {
        const opSelect = $('#api-modal-operation');
        const opTs = opSelect[0]?.tomselect;
        const hintEl = $('#api-modal-operation-hint');

        if (!selectedModule) {
            hintEl.text('').addClass('hidden');
            opSelect.empty().append('<option value="">-- Select Module First --</option>');
            if (opTs) {
                opTs.clearOptions();
                opTs.addOption({ value: '', text: '-- Select Module First --' });
                opTs.clear();
                opTs.refreshOptions(false);
            }
            return;
        }

        const supportedOps = moduleOperations[selectedModule] || [];

        if (supportedOps.length === 0) {
            hintEl.text('No operations are available for the selected module.').removeClass('hidden');
            opSelect.empty().append('<option value="">-- No Operations Available --</option>');
            if (opTs) {
                opTs.clearOptions();
                opTs.addOption({ value: '', text: '-- No Operations Available --' });
                opTs.clear();
                opTs.refreshOptions(false);
            }
            return;
        }

        // Get configured operations for this module on this connection, excluding currentApiId
        const configuredOps = existingApis
            .filter(api => api.module === selectedModule && (!currentApiId || api.id != currentApiId))
            .map(api => api.operation);

        const hasSync = configuredOps.includes('sync');
        const hasUpdate = configuredOps.includes('update');

        // Formulate explanation text for conflict states
        let hintMessage = '';
        if (hasSync && supportedOps.includes('update')) {
            hintMessage = 'Sync / Upsert is already configured for this module. Update operations cannot be added.';
        } else if (hasUpdate && supportedOps.includes('sync')) {
            hintMessage = 'Update is already configured for this module. Sync / Upsert cannot be added.';
        }

        if (hintMessage) {
            hintEl.text(hintMessage).removeClass('hidden');
        } else {
            hintEl.text('').addClass('hidden');
        }

        // Build supported options list
        const optionsList = [];
        supportedOps.forEach(opKey => {
            const baseLabel = availableOperations[opKey] || opKey;
            let disabled = false;
            let reason = '';

            if (configuredOps.includes(opKey)) {
                disabled = true;
                reason = 'Disabled — Already configured';
            } else if (hasSync && opKey === 'update') {
                disabled = true;
                reason = 'Disabled — Conflicts with Sync / Upsert';
            } else if (opKey === 'sync' && hasUpdate) {
                disabled = true;
                reason = 'Disabled — Conflicts with Update';
            }

            optionsList.push({
                value: opKey,
                text: disabled ? `${baseLabel} (${reason})` : baseLabel,
                disabled: disabled
            });
        });

        // Update native select
        opSelect.empty().append('<option value="">-- Select Operation --</option>');
        optionsList.forEach(opt => {
            const optEl = $('<option></option>')
                .val(opt.value)
                .text(opt.text)
                .prop('disabled', opt.disabled);
            opSelect.append(optEl);
        });

        // Update TomSelect
        if (opTs) {
            opTs.clearOptions();
            opTs.addOption({ value: '', text: '-- Select Operation --' });
            optionsList.forEach(opt => {
                opTs.addOption(opt);
            });
            opTs.refreshOptions(false);

            if (targetOperation && supportedOps.includes(targetOperation)) {
                opTs.setValue(targetOperation);
                opSelect.val(targetOperation);
            } else {
                opTs.clear();
                opSelect.val('');
            }
        } else {
            if (targetOperation && supportedOps.includes(targetOperation)) {
                opSelect.val(targetOperation);
            } else {
                opSelect.val('');
            }
        }
    }

    // React to Module selection changes (resets Operation selection)
    $(document).on('change', '#api-modal-module', function () {
        const selectedModule = $(this).val();
        const currentApiId = $('#api-modal-id').val();
        refreshOperationOptions(selectedModule, currentApiId, null);
    });

    const moduleSelectEl = $('#api-modal-module')[0];
    if (moduleSelectEl?.tomselect) {
        moduleSelectEl.tomselect.on('change', function (val) {
            const currentApiId = $('#api-modal-id').val();
            refreshOperationOptions(val, currentApiId, null);
        });
    }

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

        clearSelectValue('#api-modal-module', '');
        clearSelectValue('#api-modal-method', 'POST');
        $('#api-modal-is-active').prop('checked', true);

        refreshOperationOptions('', null, null);
        apiModal.removeClass('hidden');
    });

    // Open Modal in Edit Mode
    $(document).on('click', '.edit-api-btn', function () {
        const btn = $(this);
        apiForm[0].reset();
        apiForm.find('[data-error-for]').addClass('hidden').text('');

        const updateUrl = btn.data('updateUrl');
        const apiId = btn.data('id');
        const moduleVal = btn.data('module');
        const operationVal = btn.data('operation');

        apiForm.attr('action', updateUrl);
        $('#api-modal-method-input').val('PUT');
        $('#api-modal-id').val(apiId);

        $('#api-modal-title').text('Edit API Configuration');
        $('#api-modal-subtitle').text('Update outbound API endpoint, operation, or HTTP method.');
        apiSubmitBtn.text('Save Changes');

        setSelectValue('#api-modal-module', moduleVal);
        refreshOperationOptions(moduleVal, apiId, operationVal);

        setSelectValue('#api-modal-method', btn.data('method'));
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
                    const defaultMsg = isEdit ? 'API configuration updated successfully.' : 'API configuration created successfully.';
                    Alert.success(response.message || defaultMsg).then(() => {
                        window.location.reload();
                    });
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
                    Alert.error(msg, 'Error');
                }
            }
        });
    });
});
