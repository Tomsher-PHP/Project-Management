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

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderExternalRow(index, name = '', email = '') {
        return `
            <div class="external-recipient-row flex flex-col sm:flex-row items-center gap-3" data-row-index="${index}">
                <div class="w-full sm:w-1/3">
                    <input type="text" name="external_recipients[${index}][name]" value="${escapeHtml(name)}" placeholder="Recipient Name (Optional)" maxlength="255" class="w-full rounded-lg border border-bgray-200 bg-white p-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>
                <div class="w-full sm:w-1/2">
                    <input type="email" name="external_recipients[${index}][email]" value="${escapeHtml(email)}" placeholder="Email Address *" required maxlength="254" class="w-full rounded-lg border border-bgray-200 bg-white p-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>
                <div>
                    <button type="button" class="btn-remove-external-recipient inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-600 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" title="Remove email">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }

    function renderTableRow(r, configId) {
        const isInternal = !!r.user_id;
        const typeBadge = isInternal
            ? '<span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-500">Internal</span>'
            : '<span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:text-purple-500">External</span>';

        const displayName = r.name || r.user?.name || '—';
        const activeClass = r.is_active ? 'active' : '';
        const toggleUrl = `/settings/project-reports/${configId}/recipients/${r.id}/toggle`;
        const deleteUrl = `/settings/project-reports/${configId}/recipients/${r.id}`;

        return `
            <tr class="border-b border-bgray-200 hover:bg-bgray-50 dark:border-darkblack-400 dark:hover:bg-darkblack-500" data-recipient-row="${r.id}">
                <td class="px-4 py-3 text-sm">${typeBadge}</td>
                <td class="px-4 py-3 text-sm font-medium text-bgray-900 dark:text-white">${escapeHtml(displayName)}</td>
                <td class="px-4 py-3 text-sm text-bgray-600 dark:text-bgray-300">${escapeHtml(r.email)}</td>
                <td class="px-4 py-3 text-center">
                    <button type="button"
                        class="project-recipient-status-toggle switch-btn ${activeClass} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none"
                        data-id="${r.id}"
                        data-config-id="${configId}"
                        data-url="${toggleUrl}"
                        role="switch"
                        aria-checked="${r.is_active ? 'true' : 'false'}"
                        title="${r.is_active ? 'Click to deactivate' : 'Click to activate'}">
                        <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button type="button"
                        class="btn-delete-project-recipient inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400"
                        data-id="${r.id}"
                        data-config-id="${configId}"
                        data-url="${deleteUrl}"
                        data-email="${escapeHtml(r.email)}"
                        title="Delete recipient">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    }

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

                // Render external recipients
                externalList.empty();
                const extList = res.external_recipients || [];
                nextRowIndex = 0;

                if (extList.length > 0) {
                    noExternalNote.addClass('hidden');
                    extList.forEach(item => {
                        externalList.append(renderExternalRow(nextRowIndex, item.name || '', item.email || ''));
                        nextRowIndex++;
                    });
                } else {
                    noExternalNote.removeClass('hidden');
                }

                // Render recipients table
                tableBody.empty();
                const allRecipients = res.recipients || [];
                const count = allRecipients.length;
                countBadge.text(`${count} ${count === 1 ? 'recipient' : 'recipients'}`);

                if (count > 0) {
                    allRecipients.forEach(r => {
                        tableBody.append(renderTableRow(r, configId));
                    });
                } else {
                    tableBody.append(`
                        <tr id="empty-recipients-row">
                            <td colspan="5" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                No recipients configured for this report.
                            </td>
                        </tr>
                    `);
                }
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

    // Add External Email
    $('#btn-add-external-email').on('click', function () {
        noExternalNote.addClass('hidden');
        const row = $(renderExternalRow(nextRowIndex));
        externalList.append(row);
        nextRowIndex++;
        row.find('input[type="email"]').focus();
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
                        const remaining = tableBody.find('tr[data-recipient-row]').length;
                        countBadge.text(`${remaining} ${remaining === 1 ? 'recipient' : 'recipients'}`);
                        if (remaining === 0) {
                            tableBody.append(`
                                <tr id="empty-recipients-row">
                                    <td colspan="5" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                        No recipients configured for this report.
                                    </td>
                                </tr>
                            `);
                        }
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
    initProjectReportRecipients();
    initProjectReportModal();
    initProjectReportTableActions();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectReportSettings);
} else {
    initProjectReportSettings();
}
