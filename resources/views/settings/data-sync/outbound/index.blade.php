@extends('layouts.master')

@php
    $tabs = [
        [
            'key' => 'connections',
            'label' => 'Connections',
            'url' => route('settings.data-sync.outbound.connections'),
            'permission' => 'data_sync_settings.view',
        ],
        [
            'key' => 'history',
            'label' => 'History',
            'url' => route('settings.data-sync.outbound.history'),
            'permission' => 'data_sync_settings.view',
        ],
    ];
@endphp

@section('page-content')
    <div class="space-y-6" data-data-sync-settings>
        <div class="grid grid-cols-12 gap-6">
            @include('settings.data-sync.aside', ['currentSection' => 'outbound'])

            <!-- RIGHT SETTINGS PANEL -->
            <div class="col-span-9 lg:col-span-9 min-w-0">
                <div class="w-full rounded-lg bg-white px-6 py-5 dark:bg-darkblack-600">

                    <!-- TABS & ACTIONS -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-bgray-300 pb-4 dark:border-darkblack-400">
                        <div class="flex flex-wrap items-center gap-3">
                            @foreach ($tabs as $tab)
                                @can($tab['permission'])
                                    @php
                                        $isActiveTab = ($currentTab ?? 'connections') === $tab['key'];
                                    @endphp
                                    <a href="{{ $tab['url'] }}" class="{{ $isActiveTab ? 'bg-success-300 text-white shadow-sm' : 'border border-bgray-200 bg-bgray-50 text-bgray-700 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50 dark:hover:border-success-300 dark:hover:text-success-300' }} inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition duration-200">
                                        {{ $tab['label'] }}
                                    </a>
                                @endcan
                            @endforeach
                        </div>

                        <div class="flex items-center gap-3">
                            @if (($currentTab ?? 'connections') === 'connections')
                                @can('data_sync_settings.create')
                                    <x-button.create-button type="button" id="open-create-connection-btn" label="Connection" />
                                @endcan
                                <x-filters.button />
                            @elseif ($currentTab === 'history')
                                <x-filters.button />
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col space-y-5">
                        @if (($currentTab ?? 'connections') === 'connections')
                            @include('settings.data-sync.outbound.partials.connections-tab')
                        @elseif ($currentTab === 'history')
                            @include('settings.data-sync.outbound.partials.history-tab')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (($currentTab ?? 'connections') === 'connections')
        @include('settings.data-sync.outbound.modals.create-connection-modal')

        <x-filters.drawer>
            <x-filters.input-search name="search" label="Connection Name or Base URL" />
            <x-filters.select name="status" label="Status" :options="[
                'active' => 'Active',
                'inactive' => 'Inactive',
            ]" />
        </x-filters.drawer>
    @elseif ($currentTab === 'history')
        @include('settings.data-sync.outbound.modals.history-details-modal')

        <x-filters.drawer>
            <x-filters.select name="connection_id" label="Connection" :options="isset($connections) ? $connections->pluck('name', 'id')->toArray() : []" />
            <x-filters.select name="module" label="Module" :options="$modules ?? []" />
            <x-filters.select name="operation" label="Operation" :options="$operations ?? []" />
            <x-filters.select name="status" label="Status" :options="$statuses ?? []" />
            <x-filters.date-range label="Date Range" startName="date_from" endName="date_to" />
        </x-filters.drawer>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/settings/data-sync/data-sync-settings.js')
    <script>
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Close modal when clicking outside modal-content (backdrop / overlay)
            $(document).on('click', '.modal', function(e) {
                if (!$(e.target).closest('.modal-content').length) {
                    $(this).addClass('hidden');
                }
            });

            // Close modal on modal-close button
            $(document).on('click', '.modal-close', function() {
                $(this).closest('.modal').addClass('hidden');
            });

            // Close modal on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    $('.modal:not(.hidden)').addClass('hidden');
                }
            });

            // Open Create Connection Modal
            $('#open-create-connection-btn').on('click', function() {
                const modal = $('#create-connection-modal');
                modal.find('form')[0].reset();
                modal.find('[data-error-for]').addClass('hidden').text('');
                modal.removeClass('hidden');
                $('#create-connection-name').focus();
            });

            // Submit Create Connection Form via AJAX
            $('#create-connection-form').on('submit', function(e) {
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
                    success: function(response) {
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
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).removeClass('opacity-70');
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, messages) {
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

            // View History Details via AJAX
            $(document).on('click', '.view-history-btn', function() {
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
                    success: function(res) {
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
                    error: function() {
                        $('#history-modal-payload').text('Failed to load history details.');
                        $('#history-modal-response-body').text('Failed to load history details.');
                    }
                });
            });

            // Copy Payload
            $('#copy-history-payload-btn').on('click', function() {
                const text = $('#history-modal-payload').text();
                navigator.clipboard.writeText(text).then(() => {
                    const btn = $(this);
                    const originalHtml = btn.html();
                    btn.html('<span>Copied!</span>');
                    setTimeout(() => btn.html(originalHtml), 2000);
                });
            });

            // Copy Response
            $('#copy-history-response-btn').on('click', function() {
                const text = $('#history-modal-response-body').text();
                navigator.clipboard.writeText(text).then(() => {
                    const btn = $(this);
                    const originalHtml = btn.html();
                    btn.html('<span>Copied!</span>');
                    setTimeout(() => btn.html(originalHtml), 2000);
                });
            });
        });
    </script>
@endpush
