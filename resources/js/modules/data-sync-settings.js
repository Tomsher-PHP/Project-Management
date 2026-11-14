import Alert from '../alert';

$(document).ready(function () {
    // Only run if we are on the Data Sync Settings page
    if (!$('#create-client-modal').length && !$('#one-time-key-modal').length) {
        return;
    }

    // CSRF header setup for AJAX
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------------------------
    // 1. Copy to Clipboard Utility
    // -------------------------------------------------------------------------
    function copyTextToClipboard(text, onSuccess) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onSuccess).catch(() => {
                fallbackCopyText(text, onSuccess);
            });
        } else {
            fallbackCopyText(text, onSuccess);
        }
    }

    function fallbackCopyText(text, onSuccess) {
        const tempInput = document.createElement('textarea');
        tempInput.value = text;
        tempInput.style.position = 'fixed';
        tempInput.style.opacity = '0';
        document.body.appendChild(tempInput);
        tempInput.focus();
        tempInput.select();
        try {
            document.execCommand('copy');
            if (typeof onSuccess === 'function') onSuccess();
        } catch (err) {
            console.error('Fallback copy failed', err);
        }
        document.body.removeChild(tempInput);
    }

    // Table row copy Key ID button
    $(document).on('click', '.copy-key-id-btn', function (e) {
        e.preventDefault();
        const btn = $(this);
        const keyId = btn.data('keyId');
        if (!keyId) return;

        copyTextToClipboard(keyId, function () {
            const originalHtml = btn.html();
            btn.html(`
                <svg class="h-3.5 w-3.5 text-success-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            `);
            setTimeout(() => btn.html(originalHtml), 1500);
        });
    });

    // -------------------------------------------------------------------------
    // 2. Open & Close Create Modal
    // -------------------------------------------------------------------------
    $('#open-create-client-btn').on('click', function () {
        const modal = $('#create-client-modal');
        modal.find('form')[0].reset();
        modal.find('[data-error-for]').addClass('hidden').text('');
        $('#create-custom-expiration-container').addClass('hidden');
        modal.removeClass('hidden');
        $('#create-client-name').focus();
    });

    // Select All Scopes in Create Modal
    $('#create-select-all-scopes').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.create-scope-checkbox').prop('checked', isChecked);
    });

    $('.create-scope-checkbox').on('change', function () {
        const total = $('.create-scope-checkbox').length;
        const checked = $('.create-scope-checkbox:checked').length;
        $('#create-select-all-scopes').prop('checked', total === checked);
    });

    // Expiration Type Radios in Create Modal
    $('.create-expiration-radio').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#create-custom-expiration-container').removeClass('hidden');
            $('#create-expires-at').focus();
        } else {
            $('#create-custom-expiration-container').addClass('hidden');
            $('#create-expires-at').val('');
        }
    });

    // Submit Create Form via AJAX
    $('#create-client-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#create-client-submit-btn');

        form.find('[data-error-for]').addClass('hidden').text('');
        submitBtn.prop('disabled', true).addClass('opacity-70');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (response) {
                submitBtn.prop('disabled', false).removeClass('opacity-70');
                if (response.success && response.plain_text_key) {
                    $('#create-client-modal').addClass('hidden');
                    form[0].reset();

                    // Open One-Time Key Display Modal
                    showOneTimeKeyModal({
                        title: 'API Key Generated Successfully',
                        appName: response.client.name,
                        key: response.plain_text_key,
                        scopes: response.client.scopes || [],
                        expiration: response.client.expires_at || 'Never',
                    });
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
                    const msg = xhr.responseJSON?.message || 'Failed to create API client.';
                    Alert.error(msg, 'Error');
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // 3. One-Time Key Display Modal
    // -------------------------------------------------------------------------
    function showOneTimeKeyModal(data) {
        const modal = $('#one-time-key-modal');
        $('#one-time-key-modal-title').text(data.title || 'API Key Generated Successfully');
        $('#one-time-key-app-name').text(`Application: ${data.appName}`);
        $('#one-time-key-value').val(data.key);
        $('#one-time-key-expiration').text(data.expiration);

        // Populate scope badges
        const scopesContainer = $('#one-time-key-scopes');
        scopesContainer.empty();
        (data.scopes || []).forEach(scope => {
            scopesContainer.append(`
                <span class="inline-flex items-center rounded border border-bgray-300 bg-bgray-100 px-2.5 py-0.5 text-xs font-mono font-medium text-bgray-800 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                    ${scope}
                </span>
            `);
        });

        modal.removeClass('hidden');
    }

    // Copy plaintext API Key in One-Time modal
    $('#copy-one-time-key-btn').on('click', function () {
        const key = $('#one-time-key-value').val();
        if (!key) return;

        copyTextToClipboard(key, function () {
            const btn = $('#copy-one-time-key-btn');
            const originalHtml = btn.html();
            btn.html('<span>Copied!</span>').addClass('bg-success-400');
            setTimeout(() => {
                btn.html(originalHtml).removeClass('bg-success-400');
            }, 2000);
        });
    });

    // Done button or Close button on One-Time modal
    $('#one-time-key-done-btn, #one-time-key-modal .modal-close').on('click', function () {
        // Security requirement: completely clear plaintext from DOM
        $('#one-time-key-value').val('');
        $('#one-time-key-modal').addClass('hidden');
        window.location.reload();
    });

    // -------------------------------------------------------------------------
    // 4. Edit API Client Modal
    // -------------------------------------------------------------------------
    $(document).on('click', '.edit-client-btn', function () {
        const clientId = $(this).data('clientId');
        const modal = $('#edit-client-modal');
        const form = $('#edit-client-form');

        form.find('[data-error-for]').addClass('hidden').text('');
        form.attr('action', `/settings/data-sync/clients/${clientId}`);
        $('#edit-client-id').val(clientId);

        $.ajax({
            url: `/settings/data-sync/clients/${clientId}`,
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (response) {
                if (response.success && response.client) {
                    const client = response.client;
                    $('#edit-client-name').val(client.name);
                    $('#edit-client-key-id-display').text(client.key_id);

                    // Scopes
                    $('.edit-scope-checkbox').prop('checked', false);
                    (client.scopes || []).forEach(scope => {
                        $(`.edit-scope-checkbox[value="${scope}"]`).prop('checked', true);
                    });

                    const totalScopes = $('.edit-scope-checkbox').length;
                    const checkedScopes = $('.edit-scope-checkbox:checked').length;
                    $('#edit-select-all-scopes').prop('checked', totalScopes === checkedScopes);

                    // Expiration
                    if (client.has_expiration && client.expires_at) {
                        $('#edit-exp-custom').prop('checked', true);
                        $('#edit-custom-expiration-container').removeClass('hidden');
                        $('#edit-expires-at').val(client.expires_at);
                    } else {
                        $('#edit-exp-never').prop('checked', true);
                        $('#edit-custom-expiration-container').addClass('hidden');
                        $('#edit-expires-at').val('');
                    }

                    modal.removeClass('hidden');
                }
            },
            error: function () {
                Alert.error('Failed to load API client details.', 'Error');
            }
        });
    });

    // Select All in Edit Modal
    $('#edit-select-all-scopes').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.edit-scope-checkbox').prop('checked', isChecked);
    });

    $('.edit-scope-checkbox').on('change', function () {
        const total = $('.edit-scope-checkbox').length;
        const checked = $('.edit-scope-checkbox:checked').length;
        $('#edit-select-all-scopes').prop('checked', total === checked);
    });

    // Expiration Type Radios in Edit Modal
    $('.edit-expiration-radio').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#edit-custom-expiration-container').removeClass('hidden');
            $('#edit-expires-at').focus();
        } else {
            $('#edit-custom-expiration-container').addClass('hidden');
            $('#edit-expires-at').val('');
        }
    });

    // Submit Edit Form via AJAX
    $('#edit-client-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#edit-client-submit-btn');

        form.find('[data-error-for]').addClass('hidden').text('');
        submitBtn.prop('disabled', true).addClass('opacity-70');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (response) {
                submitBtn.prop('disabled', false).removeClass('opacity-70');
                if (response.success) {
                    $('#edit-client-modal').addClass('hidden');
                    Alert.success(response.message || 'API client updated successfully.').then(() => {
                        window.location.reload();
                    });
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
                    const msg = xhr.responseJSON?.message || 'Failed to update API client.';
                    Alert.error(msg, 'Error');
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // 5. Rotate Secret Key
    // -------------------------------------------------------------------------
    $(document).on('click', '.rotate-client-btn', function () {
        const btn = $(this);
        const clientName = btn.data('clientName');
        const url = btn.data('url');

        Alert.confirm({
            title: 'Rotate API Secret?',
            text: `Rotating this API key will invalidate the existing secret for "${clientName}". Any connected external application using the old key will need to be updated. Are you sure you want to proceed?`,
            confirmText: 'Yes, Rotate Key',
            cancelText: 'Cancel',
            confirmColor: '#22c55e',
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (response) {
                    if (response.success && response.plain_text_key) {
                        showOneTimeKeyModal({
                            title: 'API Key Rotated Successfully',
                            appName: response.client.name,
                            key: response.plain_text_key,
                            scopes: response.client.scopes || [],
                            expiration: response.client.expires_at || 'Never',
                        });
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to rotate API key.';
                    Alert.error(msg, 'Rotation Failed');
                }
            });
        });
    });

    // -------------------------------------------------------------------------
    // 6. Revoke API Client
    // -------------------------------------------------------------------------
    $(document).on('click', '.revoke-client-btn', function () {
        const btn = $(this);
        const clientName = btn.data('clientName');
        const url = btn.data('url');

        Alert.confirm({
            title: 'Revoke API Client?',
            text: `Revoking this API key will immediately disable authentication for "${clientName}". Existing record mappings will be preserved. Are you sure you want to revoke this client?`,
            confirmText: 'Yes, Revoke Client',
            cancelText: 'Cancel',
            confirmColor: '#ef4444',
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (response) {
                    if (response.success) {
                        Alert.success(response.message || 'API client revoked successfully.').then(() => {
                            window.location.reload();
                        });
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to revoke API client.';
                    Alert.error(msg, 'Revocation Failed');
                }
            });
        });
    });

    // Generic Modal Close Handler for Data Sync Modals
    $(document).on('click', '.modal-close', function () {
        $(this).closest('.modal').addClass('hidden');
    });
});

