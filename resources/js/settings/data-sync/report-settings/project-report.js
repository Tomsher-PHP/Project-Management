import Alert from '../../../alert';
import { initTomSelect } from '../../../components/tom-select';

function initReportTabs() {
    const tabs = document.querySelectorAll('[data-report-tab]');
    const panels = document.querySelectorAll('[data-report-panel]');

    if (!tabs.length || !panels.length) return;

    const activeClasses = ['bg-success-300', 'text-white', 'shadow-sm'];
    const inactiveClasses = [
        'border',
        'border-bgray-200',
        'bg-bgray-50',
        'text-bgray-700',
        'hover:border-success-300',
        'hover:text-success-400',
        'dark:border-darkblack-400',
        'dark:bg-darkblack-500',
        'dark:text-bgray-50',
        'dark:hover:border-success-300',
        'dark:hover:text-success-300',
    ];

    function activateTab(name) {
        tabs.forEach(function (tab) {
            const isActive = tab.dataset.reportTab === name;

            tab.classList.remove(...(isActive ? inactiveClasses : activeClasses));
            tab.classList.add(...(isActive ? activeClasses : inactiveClasses));
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.dataset.reportPanel !== name);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.dataset.reportTab);
        });
    });

    const firstTab = document.querySelector('[data-report-tab]');
    if (firstTab) {
        activateTab(firstTab.dataset.reportTab);
    }
}

function initProjectReportRecipients() {
    const configSelect = $('#select-report-recipient-config');
    const form = $('#project-report-recipients-form');
    if (!configSelect.length && !form.length) return;

    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const externalList = $('#external-recipients-list');
    const noExternalNote = $('#no-external-recipients-note');
    const tableBody = $('#project-report-recipients-tbody');
    const countBadge = $('#recipient-count-badge');
    let nextRowIndex = externalList.find('.external-recipient-row').length;

    function loadRecipientsForConfig(configId) {
        if (!configId) return;

        $('#recipient-config-id').val(configId);
        form.find('[data-recipient-error]').addClass('hidden').text('');

        $.ajax({
            url: `/settings/project-reports/${configId}/recipients`,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                if (!res.success) return;

                // Sync internal user IDs
                const internalSelect = $('#recipient-internal-users');
                const userIds = (res.internal_user_ids || []).map(String);

                if (internalSelect[0] && internalSelect[0].tomselect) {
                    internalSelect[0].tomselect.setValue(userIds);
                } else {
                    internalSelect.find('option').each(function () {
                        $(this).prop('selected', userIds.includes(String($(this).val())));
                    });
                }

                // Render external recipients using Blade HTML rendered by controller
                if (res.external_rows_html !== undefined) {
                    externalList.html(res.external_rows_html);
                    nextRowIndex = externalList.find('.external-recipient-row').length;
                }
                noExternalNote.toggleClass('hidden', !!res.has_external);

                // Render recipients table using Blade HTML rendered by controller
                if (res.table_html !== undefined) {
                    tableBody.html(res.table_html);
                }

                const count = res.count ?? res.recipients?.length ?? 0;
                countBadge.text(`${count} ${count === 1 ? 'recipient' : 'recipients'}`);
            },
            error: function (xhr) {
                Alert.error(xhr.responseJSON?.message || 'Failed to load recipients for this configuration.', 'Error');
            }
        });
    }

    // Config dropdown change
    configSelect.on('change', function () {
        loadRecipientsForConfig($(this).val());
    });

    // Add External Email from Blade Template
    $('#btn-add-external-email').on('click', function () {
        noExternalNote.addClass('hidden');
        const templateHtml = $('#external-recipient-row-template').html();
        if (templateHtml) {
            const rowHtml = templateHtml.replace(/__INDEX__/g, nextRowIndex);
            const row = $(rowHtml);
            externalList.append(row);
            nextRowIndex++;
            row.find('input[type="email"]').focus();
        }
    });

    // Remove External Email
    externalList.on('click', '.btn-remove-external-recipient', function () {
        $(this).closest('.external-recipient-row').remove();
        if (!externalList.find('.external-recipient-row').length) {
            noExternalNote.removeClass('hidden');
        }
    });

    // Form Submit
    form.on('submit', function (e) {
        e.preventDefault();
        const configId = $('#recipient-config-id').val() || configSelect.val();
        if (!configId) {
            Alert.error('Please select a project report configuration first.', 'Error');
            return;
        }

        form.find('[data-recipient-error]').addClass('hidden').text('');
        const saveBtn = $('#btn-save-project-report-recipients');
        const originalText = saveBtn.text();
        saveBtn.prop('disabled', true).addClass('opacity-70').text('Saving...');

        $.ajax({
            url: `/settings/project-reports/${configId}/recipients/sync`,
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function (res) {
                saveBtn.prop('disabled', false).removeClass('opacity-70').text(originalText);
                Alert.success(res.message || 'Recipients saved successfully.', 'Success');
                loadRecipientsForConfig(configId);
            },
            error: function (xhr) {
                saveBtn.prop('disabled', false).removeClass('opacity-70').text(originalText);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    let firstMsg = '';

                    $.each(errors, function (key, messages) {
                        if (!firstMsg) firstMsg = messages[0];
                        if (key.startsWith('user_ids')) {
                            $('[data-recipient-error="user_ids"]').text(messages[0]).removeClass('hidden');
                        } else if (key.startsWith('external_recipients')) {
                            $('[data-recipient-error="external_recipients"]').text(messages[0]).removeClass('hidden');
                        }
                    });

                    Alert.error(firstMsg || 'Please correct the validation errors.', 'Validation Error');
                } else {
                    Alert.error(xhr.responseJSON?.message || 'Failed to save recipients.', 'Error');
                }
            }
        });
    });

    // Toggle Recipient Active Status
    $(document).on('click', '.project-recipient-status-toggle', function () {
        const btn = $(this);
        if (btn.data('processing')) return;
        btn.data('processing', true);

        const url = btn.data('url');

        $.ajax({
            url: url,
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function (res) {
                btn.data('processing', false);
                if (res.success) {
                    btn.toggleClass('active', res.is_active);
                    btn.attr('aria-checked', res.is_active ? 'true' : 'false');
                    btn.attr('title', res.is_active ? 'Click to deactivate' : 'Click to activate');
                    Alert.success(res.message || 'Recipient status updated.', 'Success');
                }
            },
            error: function (xhr) {
                btn.data('processing', false);
                Alert.error(xhr.responseJSON?.message || 'Failed to update recipient status.', 'Error');
            }
        });
    });

    // Delete Recipient
    $(document).on('click', '.btn-delete-project-recipient', function () {
        const btn = $(this);
        const url = btn.data('url');
        const email = btn.data('email') || 'this recipient';
        const row = btn.closest('tr');
        const configId = $('#recipient-config-id').val() || configSelect.val();

        Alert.confirm({
            title: 'Remove Recipient?',
            text: `Are you sure you want to remove "${email}" from this report?`,
            confirmText: 'Yes, Remove',
            confirmColor: '#ef4444'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                success: function (res) {
                    Alert.success(res.message || 'Recipient removed.', 'Removed');
                    row.fadeOut(300, function () {
                        $(this).remove();
                        if (configId) {
                            loadRecipientsForConfig(configId);
                        }
                    });
                },
                error: function (xhr) {
                    Alert.error(xhr.responseJSON?.message || 'Failed to remove recipient.', 'Error');
                }
            });
        });
    });
}

function initProjectReportModal() {
    const modal = $('#project-report-config-modal');
    const form = $('#project-report-config-form');
    if (!modal.length || !form.length) return;

    // Initialize TomSelect inside modal
    initTomSelect(modal[0]);

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function updateFrequencyOptions(frequency) {
        const freq = (frequency || 'daily').toLowerCase();
        $('#modal-weekly-day-wrapper').toggleClass('hidden', freq !== 'weekly');
        $('#modal-monthly-day-wrapper').toggleClass('hidden', freq !== 'monthly');
    }

    function setSelectValue(selector, value) {
        const el = $(selector)[0];
        if (el && el.tomselect) {
            el.tomselect.setValue(value);
        } else {
            $(selector).val(value);
        }
    }

    function setTimezoneValue(timezone) {
        const el = $('#modal-report-timezone')[0];
        const tz = timezone || 'Asia/Dubai';
        if (el && el.tomselect) {
            el.tomselect.addOption({ value: tz, text: tz });
            el.tomselect.setValue(tz);
        } else {
            $('#modal-report-timezone').val(tz);
        }
    }

    function setProjectStatuses(statusIds) {
        const el = $('#modal-project-statuses')[0];
        const ids = (statusIds || []).map(String);
        if (el && el.tomselect) {
            el.tomselect.setValue(ids);
        } else {
            $('#modal-project-statuses').val(ids);
        }
    }

    $('#modal-report-frequency').on('change', function () {
        updateFrequencyOptions($(this).val());
    });

    const freqEl = $('#modal-report-frequency')[0];
    if (freqEl?.tomselect) {
        freqEl.tomselect.on('change', function (val) {
            updateFrequencyOptions(val);
        });
    }

    // Select All / Clear All columns
    $('#modal-columns-select-all').on('click', function () {
        $('.modal-column-checkbox').prop('checked', true);
    });

    $('#modal-columns-clear-all').on('click', function () {
        $('.modal-column-checkbox:not([data-locked])').prop('checked', false);
        $('.modal-column-checkbox[data-locked]').prop('checked', true);
    });

    $(document).on('click change', '.modal-column-checkbox[data-locked], .modal-column-checkbox[value="project_name"]', function () {
        $(this).prop('checked', true);
    });

    // Close Modal triggers
    $(document).on('click', '#project-report-config-modal .modal-close', function () {
        modal.addClass('hidden');
    });

    // Close modal when click outside of modal content
    $(document).on('click', '#project-report-config-modal', function (e) {
        if ($(e.target).closest('.modal-content').length) return;
        if ($(e.target).closest('.ts-dropdown, .ts-selected-items-popover, .flatpickr-calendar, .swal2-container').length) return;
        modal.addClass('hidden');
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hasClass('hidden')) {
            modal.addClass('hidden');
        }
    });

    // Open Add Modal
    $('#btn-add-project-report').on('click', function () {
        form[0].reset();
        form.attr('action', '/settings/project-reports');
        $('#modal-form-method').val('POST');
        $('#modal-config-id').val('');
        $('#modal-report-title').text('Add Project Report');
        $('#modal-submit-text').text('Create Report');

        form.find('[data-error-for]').addClass('hidden').text('');

        // Defaults
        setSelectValue('#modal-report-frequency', 'daily');
        $('#modal-delivery-time').val('09:00');
        if ($('#modal-delivery-time')[0]?._flatpickr) {
            $('#modal-delivery-time')[0]._flatpickr.setDate('09:00');
        }
        setSelectValue('#modal-weekly-day', '1');
        setSelectValue('#modal-monthly-day', '1');
        setSelectValue('#modal-reporting-period', 'all_time');
        setSelectValue('#modal-date-filter', 'status_changed_date');
        setTimezoneValue('Asia/Dubai');

        // Select statuses with is_completed = 1 by default
        const completedStatusIds = $('#modal-project-statuses option').filter(function () {
            return $(this).data('is-completed') == 1 || $(this).attr('data-is-completed') === '1';
        }).map(function () {
            return String($(this).val());
        }).get();
        setProjectStatuses(completedStatusIds.length ? completedStatusIds : [$('#modal-project-statuses option').first().val()]);

        // Select all columns by default
        $('.modal-column-checkbox').prop('checked', true);

        updateFrequencyOptions('daily');
        modal.removeClass('hidden');
        $('#modal-report-name').focus();
    });

    // Open Edit Modal
    $(document).on('click', '.btn-edit-project-report', function () {
        const id = $(this).data('id');
        if (!id) return;

        form[0].reset();
        form.attr('action', `/settings/project-reports/${id}`);
        $('#modal-form-method').val('PUT');
        $('#modal-config-id').val(id);
        $('#modal-report-title').text('Edit Project Report');
        $('#modal-submit-text').text('Save Configuration');

        form.find('[data-error-for]').addClass('hidden').text('');
        modal.removeClass('hidden');

        $.ajax({
            url: `/settings/project-reports/${id}`,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                if (res.success && res.data) {
                    const data = res.data;
                    $('#modal-report-name').val(data.report_name || 'Project Report');

                    const freq = (data.frequency || 'daily').toLowerCase();
                    setSelectValue('#modal-report-frequency', freq);
                    updateFrequencyOptions(freq);

                    const deliveryTime = data.delivery_time ? data.delivery_time.substring(0, 5) : '00:00';
                    $('#modal-delivery-time').val(deliveryTime);
                    if ($('#modal-delivery-time')[0]?._flatpickr) {
                        $('#modal-delivery-time')[0]._flatpickr.setDate(deliveryTime);
                    }

                    if (data.timezone) {
                        setTimezoneValue(data.timezone);
                    } else {
                        setTimezoneValue('Asia/Dubai');
                    }

                    if (data.weekly_day) {
                        setSelectValue('#modal-weekly-day', String(data.weekly_day));
                    } else {
                        setSelectValue('#modal-weekly-day', '1');
                    }

                    const monthlyDay = data.settings?.schedule?.monthly_day;
                    if (monthlyDay !== undefined && monthlyDay !== null) {
                        setSelectValue('#modal-monthly-day', String(monthlyDay));
                    } else {
                        setSelectValue('#modal-monthly-day', '1');
                    }

                    if (data.reporting_period) {
                        setSelectValue('#modal-reporting-period', data.reporting_period);
                    }

                    const dateFilter = data.settings?.filters?.date_filter || 'status_changed_date';
                    setSelectValue('#modal-date-filter', dateFilter);

                    // Project Statuses
                    const statusIds = (data.settings?.filters?.project_status_ids || []).map(String);
                    if (statusIds.length) {
                        setProjectStatuses(statusIds);
                    } else {
                        const completedStatusIds = $('#modal-project-statuses option').filter(function () {
                            return $(this).data('is-completed') == 1 || $(this).attr('data-is-completed') === '1';
                        }).map(function () {
                            return String($(this).val());
                        }).get();
                        setProjectStatuses(completedStatusIds);
                    }

                    // Columns
                    const cols = data.settings?.columns || [];
                    if (cols.length) {
                        $('.modal-column-checkbox').each(function () {
                            const isLocked = $(this).data('locked') || $(this).val() === 'project_name';
                            if (isLocked) {
                                $(this).prop('checked', true);
                            } else {
                                $(this).prop('checked', cols.includes($(this).val()));
                            }
                        });
                    } else {
                        $('.modal-column-checkbox').prop('checked', true);
                    }
                }
            },
            error: function (xhr) {
                Alert.error(xhr.responseJSON?.message || 'Failed to load configuration details.', 'Error');
            }
        });
    });

    // Form Submit
    form.on('submit', function (e) {
        e.preventDefault();
        const submitBtn = $('#modal-submit-btn');
        const submitText = $('#modal-submit-text');
        const originalText = submitText.text();

        form.find('[data-error-for]').addClass('hidden').text('');
        submitBtn.prop('disabled', true).addClass('opacity-70');
        submitText.text('Saving...');

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
                submitText.text(originalText);
                modal.addClass('hidden');

                Alert.success(response.message || 'Configuration saved successfully.', 'Success').then(() => {
                    window.location.reload();
                });
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).removeClass('opacity-70');
                submitText.text(originalText);

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, messages) {
                        let errorSpan = form.find(`[data-error-for="${key}"]`);
                        if (!errorSpan.length) {
                            // Match base dotted path
                            const parts = key.split('.');
                            while (parts.length > 1 && !errorSpan.length) {
                                parts.pop();
                                errorSpan = form.find(`[data-error-for="${parts.join('.')}"]`);
                            }
                        }
                        if (errorSpan.length) {
                            errorSpan.text(messages[0]).removeClass('hidden');
                        }
                    });
                } else {
                    Alert.error(xhr.responseJSON?.message || 'Failed to save configuration.', 'Validation Error');
                }
            }
        });
    });
}

function initProjectReportTableActions() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Enable/Disable Toggle
    $(document).on('click', '.project-report-status-toggle', function () {
        const btn = $(this);
        if (btn.data('processing')) return;
        btn.data('processing', true);

        const url = btn.data('url');

        $.ajax({
            url: url,
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function (res) {
                btn.data('processing', false);
                if (res.success) {
                    btn.toggleClass('active', res.enabled);
                    btn.attr('aria-checked', res.enabled ? 'true' : 'false');
                    btn.attr('title', res.enabled ? 'Click to disable' : 'Click to enable');
                    Alert.success(res.message || 'Status updated.', 'Success');
                }
            },
            error: function (xhr) {
                btn.data('processing', false);
                Alert.error(xhr.responseJSON?.message || 'Failed to update configuration status.', 'Error');
            }
        });
    });

    // Delete Button
    $(document).on('click', '.btn-delete-project-report', function () {
        const btn = $(this);
        const id = btn.data('id');
        const name = btn.data('name') || 'this report configuration';
        const url = btn.data('url');

        Alert.confirm({
            title: 'Delete Report Configuration?',
            text: `Are you sure you want to delete "${name}"? Any recipients associated with this configuration will also be removed.`,
            confirmText: 'Yes, Delete',
            confirmColor: '#ef4444'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                success: function (res) {
                    Alert.success(res.message || 'Configuration deleted successfully.', 'Deleted').then(() => {
                        const row = $(`[data-config-row="${id}"]`);
                        if (row.length) {
                            row.fadeOut(300, function () {
                                $(this).remove();
                                if (!$('#project-reports-table tbody tr').length) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            window.location.reload();
                        }
                    });
                },
                error: function (xhr) {
                    Alert.error(xhr.responseJSON?.message || 'Failed to delete configuration.', 'Error');
                }
            });
        });
    });
}

function initProjectReportSettings() {
    initReportTabs();
    initProjectReportRecipients();
    initProjectReportModal();
    initProjectReportTableActions();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectReportSettings);
} else {
    initProjectReportSettings();
}
