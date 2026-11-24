$(document).ready(function () {
    // Only run if we are on the Outbound Data Sync page
    if (!$('#create-connection-modal').length && !$('#history-details-modal').length) {
        return;
    }

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------------------------
    // Clipboard helper with fallback
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
    // Create Connection Modal
    // -------------------------------------------------------------------------
    $('#open-create-connection-btn').on('click', function () {
        const modal = $('#create-connection-modal');
        modal.find('form')[0].reset();
        modal.find('[data-error-for]').addClass('hidden').text('');
        modal.removeClass('hidden');
        $('#create-connection-name').focus();
    });

    // Submit Create Connection Form via AJAX
    $('#create-connection-form').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#create-connection-submit-btn');

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
                    $('#create-connection-modal').addClass('hidden');
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Outbound connection created successfully.',
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            window.location.href = response.redirect_url || window.location.href;
                        });
                    } else {
                        window.location.href = response.redirect_url || window.location.href;
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
                    const msg = xhr.responseJSON?.message || 'Failed to create connection.';
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
    // View History Details via AJAX
    // -------------------------------------------------------------------------
    $(document).on('click', '.view-history-btn', function () {
        const url = $(this).data('url');
        const modal = $('#history-details-modal');

        // Reset fields
        $('#history-modal-connection').text('Loading...');
        $('#history-modal-module-operation').text('Loading...');
        $('#history-modal-method').text('—');
        $('#history-modal-endpoint').text('—');
        $('#history-modal-request-url').text('Loading...');
        $('#history-modal-created-at').text('—');
        $('#history-modal-payload').text('Loading payload...');
        $('#history-modal-response-code').text('—');
        $('#history-modal-status-text').text('—');
        $('#history-modal-response-body').text('Loading response body...');
        $('#history-modal-status-badge').empty();
        $('#history-modal-response-code-badge').empty();
        $('#history-modal-error-box').addClass('hidden');
        $('#history-modal-error-message').text('');

        modal.removeClass('hidden');

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            success: function (res) {
                if (res.success && res.history) {
                    const h = res.history;
                    $('#history-modal-connection').text(h.connection_name);
                    $('#history-modal-module-operation').text(`${h.module} / ${h.operation}`);
                    $('#history-modal-method').text(h.http_method);
                    $('#history-modal-endpoint').text(h.endpoint);
                    $('#history-modal-request-url').text(h.request_url);
                    $('#history-modal-created-at').text(h.created_at);
                    $('#history-modal-payload').text(h.request_payload || 'No request payload sent.');

                    const codeText = h.response_code !== null ? h.response_code : '—';
                    $('#history-modal-response-code').text(codeText);
                    $('#history-modal-status-text').text(h.status ? (h.status.charAt(0).toUpperCase() + h.status.slice(1)) : '—');
                    $('#history-modal-response-body').text(h.response_body || 'No response body received.');

                    // Status badge
                    let statusBadge = '';
                    if (h.status === 'success') {
                        statusBadge = '<span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-bold text-success-400 dark:bg-success-900/30 dark:text-success-300">Success</span>';
                    } else if (h.status === 'failed') {
                        statusBadge = '<span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-bold text-error-400 dark:bg-error-900/30 dark:text-error-300">Failed</span>';
                    } else {
                        statusBadge = '<span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-500 dark:bg-amber-900/30 dark:text-amber-400">Pending</span>';
                    }
                    $('#history-modal-status-badge').html(statusBadge);

                    // Response code badge
                    if (h.response_code !== null) {
                        let codeBadgeClass = 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-400';
                        if (h.response_code >= 200 && h.response_code < 300) {
                            codeBadgeClass = 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300';
                        } else if (h.response_code >= 400) {
                            codeBadgeClass = 'bg-error-50 text-error-400 dark:bg-error-900/30 dark:text-error-300';
                        }
                        $('#history-modal-response-code-badge').html(`<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold font-mono ${codeBadgeClass}">HTTP ${h.response_code}</span>`);
                    }

                    // Error message
                    if (h.error_message) {
                        $('#history-modal-error-message').text(h.error_message);
                        $('#history-modal-error-box').removeClass('hidden');
                    }
                }
            },
            error: function () {
                $('#history-modal-payload').text('Failed to load history details.');
                $('#history-modal-response-body').text('Failed to load history details.');
            }
        });
    });

    // -------------------------------------------------------------------------
    // Copy Payload and Response
    // -------------------------------------------------------------------------
    $('#copy-history-payload-btn').on('click', function () {
        const text = $('#history-modal-payload').text();
        const btn = $(this);
        const originalHtml = btn.html();
        copyTextToClipboard(text, function () {
            btn.html('<span>Copied!</span>');
            setTimeout(() => btn.html(originalHtml), 2000);
        });
    });

    $('#copy-history-response-btn').on('click', function () {
        const text = $('#history-modal-response-body').text();
        const btn = $(this);
        const originalHtml = btn.html();
        copyTextToClipboard(text, function () {
            btn.html('<span>Copied!</span>');
            setTimeout(() => btn.html(originalHtml), 2000);
        });
    });
});

