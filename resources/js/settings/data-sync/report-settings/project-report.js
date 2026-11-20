import Alert from '../../../alert';

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

function initRecipientScopeForm() {
    const scope = document.getElementById('recipient-scope');
    const targetWrapper = document.getElementById('recipient-scope-id-wrapper');
    const targetSelect = document.getElementById('recipient-scope-id');
    const targetLabel = document.getElementById('recipient-scope-id-label');

    function updateRecipientScope() {
        if (!scope || !targetWrapper || !targetSelect || !targetLabel) {
            return;
        }

        const selectedScope = scope.value;
        const needsTarget = selectedScope !== 'global';

        targetWrapper.classList.toggle('hidden', !needsTarget);
        targetSelect.required = needsTarget;
        targetSelect.disabled = !needsTarget;

        targetLabel.textContent = selectedScope === 'team'
            ? 'Select team'
            : 'Select employee';

        Array.from(targetSelect.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.scope !== selectedScope;
        });

        const selectedOption = targetSelect.selectedOptions[0];

        if (
            selectedOption &&
            selectedOption.value &&
            selectedOption.dataset.scope !== selectedScope
        ) {
            targetSelect.value = '';
        }
    }

    if (scope && targetSelect) {
        scope.addEventListener('change', function () {
            targetSelect.value = '';
            updateRecipientScope();
        });

        updateRecipientScope();
    }
}

function initProjectReportModal() {
    const modal = $('#project-report-config-modal');
    const form = $('#project-report-config-form');
    if (!modal.length || !form.length) return;

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function updateFrequencyOptions(frequency) {
        const freq = (frequency || 'daily').toLowerCase();
        $('#modal-weekly-day-wrapper').toggleClass('hidden', freq !== 'weekly');
        $('#modal-monthly-day-wrapper').toggleClass('hidden', freq !== 'monthly');
    }

    $('#modal-report-frequency').on('change', function () {
        updateFrequencyOptions($(this).val());
    });

    // Select All / Clear All columns
    $('#modal-columns-select-all').on('click', function () {
        $('.modal-column-checkbox').prop('checked', true);
    });

    $('#modal-columns-clear-all').on('click', function () {
        $('.modal-column-checkbox').prop('checked', false);
    });

    // Close Modal triggers
    $(document).on('click', '#project-report-config-modal .modal-close, #project-report-config-modal .modal-overlay', function () {
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
        $('#modal-report-enabled').prop('checked', true);
        $('#modal-report-frequency').val('daily');
        $('#modal-delivery-time').val('09:00');
        if ($('#modal-delivery-time')[0]?._flatpickr) {
            $('#modal-delivery-time')[0]._flatpickr.setDate('09:00');
        }
        $('#modal-weekly-day').val('1');
        $('#modal-monthly-day').val('1');
        $('#modal-reporting-period').val('all_time');
        $('#modal-date-filter').val('none');
        $('#modal-report-timezone').val('Asia/Dubai');

        // Select all statuses by default
        $('#modal-project-statuses option').prop('selected', true);

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
                    $('#modal-report-enabled').prop('checked', !!data.enabled);

                    const freq = (data.frequency || 'daily').toLowerCase();
                    $('#modal-report-frequency').val(freq);
                    updateFrequencyOptions(freq);

                    const deliveryTime = data.delivery_time ? data.delivery_time.substring(0, 5) : '00:00';
                    $('#modal-delivery-time').val(deliveryTime);
                    if ($('#modal-delivery-time')[0]?._flatpickr) {
                        $('#modal-delivery-time')[0]._flatpickr.setDate(deliveryTime);
                    }

                    if (data.timezone) {
                        $('#modal-report-timezone').val(data.timezone);
                    }

                    if (data.weekly_day) {
                        $('#modal-weekly-day').val(data.weekly_day);
                    }

                    const monthlyDay = data.settings?.schedule?.monthly_day;
                    if (monthlyDay !== undefined && monthlyDay !== null) {
                        $('#modal-monthly-day').val(String(monthlyDay));
                    }

                    if (data.reporting_period) {
                        $('#modal-reporting-period').val(data.reporting_period);
                    }

                    const dateFilter = data.settings?.filters?.date_filter || 'none';
                    $('#modal-date-filter').val(dateFilter);

                    // Project Statuses
                    const statusIds = (data.settings?.filters?.project_status_ids || []).map(String);
                    if (statusIds.length) {
                        $('#modal-project-statuses option').each(function () {
                            $(this).prop('selected', statusIds.includes(String($(this).val())));
                        });
                    } else {
                        $('#modal-project-statuses option').prop('selected', true);
                    }

                    // Columns
                    const cols = data.settings?.columns || [];
                    if (cols.length) {
                        $('.modal-column-checkbox').each(function () {
                            $(this).prop('checked', cols.includes($(this).val()));
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
    initRecipientScopeForm();
    initProjectReportModal();
    initProjectReportTableActions();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectReportSettings);
} else {
    initProjectReportSettings();
}
