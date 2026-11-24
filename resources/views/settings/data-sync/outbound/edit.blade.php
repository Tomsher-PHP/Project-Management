@extends('layouts.master')

@section('page-content')
    <div class="space-y-6" data-data-sync-settings>
        <div class="grid grid-cols-12 gap-6">
            @include('settings.data-sync.aside', ['currentSection' => 'outbound'])

            <!-- RIGHT SETTINGS PANEL -->
            <div class="col-span-9 lg:col-span-9 min-w-0 space-y-6">
                <!-- TOP BREADCRUMB / BACK ACTION -->
                <div class="flex items-center justify-between">
                    <x-back-button :url="route('settings.data-sync.outbound.connections')" label="Back to Connections" />
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $connection->is_active ? 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                        {{ $connection->is_active ? 'Connection Active' : 'Connection Inactive' }}
                    </span>
                </div>

                <!-- CARD 1: CONNECTION SETTINGS FORM -->
                <div class="w-full rounded-lg bg-white px-6 py-6 shadow-sm dark:bg-darkblack-600">
                    <div class="mb-5 border-b border-bgray-200 pb-4 dark:border-darkblack-400">
                        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">
                            Connection Details: {{ $connection->name }}
                        </h3>
                    </div>

                    <form id="edit-connection-form" action="{{ route('settings.data-sync.outbound.connections.update', $connection) }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <!-- Connection Name -->
                            <div>
                                <label for="edit-connection-name" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                    Connection Name <span class="text-error-300">*</span>
                                </label>
                                <input type="text" id="edit-connection-name" name="name" value="{{ old('name', $connection->name) }}" required maxlength="255" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="name"></span>
                            </div>

                            <!-- Base URL -->
                            <div>
                                <label for="edit-connection-base-url" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                    Base URL <span class="text-error-300">*</span>
                                </label>
                                <input type="url" id="edit-connection-base-url" name="base_url" value="{{ old('base_url', $connection->base_url) }}" required maxlength="500" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-mono text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="base_url"></span>
                            </div>
                        </div>

                        <input type="hidden" name="auth_type" value="bearer">

                        <!-- Bearer Token -->
                        <div>
                            <label for="edit-connection-token" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Bearer Token
                            </label>
                            <input type="password" id="edit-connection-token" name="token" placeholder="••••••••••••••••••••••••" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                            <p class="text-xs text-bgray-700 dark:text-bgray-300 mt-1">Leave blank to keep the existing token.</p>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="token"></span>
                        </div>

                        <!-- Active Toggle & Submit -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-bgray-200 dark:border-darkblack-400">
                            <div class="flex items-center gap-3">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" id="edit-connection-is-active" name="is_active" value="1" {{ old('is_active', $connection->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                <label for="edit-connection-is-active" class="text-sm font-semibold text-bgray-900 dark:text-white">
                                    Connection is Active
                                </label>
                            </div>

                            @can('data_sync_settings.edit')
                                <button type="submit" id="edit-connection-submit-btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                                    Save Connection
                                </button>
                            @endcan
                        </div>
                    </form>
                </div>

                <!-- CARD 2: CONFIGURED APIS -->
                <div class="w-full rounded-lg bg-white px-6 py-6 shadow-sm dark:bg-darkblack-600">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-bgray-200 pb-4 dark:border-darkblack-400">
                        <div>
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">
                                Configured APIs
                            </h3>
                        </div>

                        @can('data_sync_settings.create')
                            <button type="button" id="open-create-api-btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add API
                            </button>
                        @endcan
                    </div>

                    <!-- APIS TABLE -->
                    <div class="table-content w-full overflow-x-auto">
                        <table class="w-full relative" id="outbound-apis-table">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <th class="pr-4 py-4 whitespace-nowrap text-left"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span></th>
                                    <th class="px-4 py-4 whitespace-nowrap text-left"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Module</span></th>
                                    <th class="px-4 py-4 whitespace-nowrap text-left"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Operation</span></th>
                                    <th class="px-4 py-4 whitespace-nowrap text-left"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">HTTP Method</span></th>
                                    <th class="px-4 py-4 whitespace-nowrap text-left"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Endpoint</span></th>
                                    <th class="px-4 py-4 whitespace-nowrap text-left"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span></th>
                                    <th class="pl-4 py-4 whitespace-nowrap text-right"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                                @forelse ($apis as $api)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}" data-api-row="{{ $api->id }}">
                                        <td class="pr-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-bgray-600 dark:text-bgray-50">{{ $loop->iteration }}</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex rounded-lg bg-bgray-100 px-2.5 py-1 text-xs font-bold text-bgray-800 capitalize dark:bg-darkblack-500 dark:text-bgray-300">
                                                {{ $availableModules[$api->module] ?? $api->module }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-bgray-900 capitalize dark:text-white">
                                                {{ $availableOperations[$api->operation] ?? $api->operation }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($api->method === 'POST')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold text-blue-700 dark:text-blue-300">POST</span>
                                            @elseif ($api->method === 'GET')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold text-green-700 dark:text-green-300">GET</span>
                                            @elseif ($api->method === 'PUT')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold text-amber-700 dark:text-amber-300">PUT</span>
                                            @elseif ($api->method === 'PATCH')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold text-purple-700 dark:text-purple-300">PATCH</span>
                                            @elseif ($api->method === 'DELETE')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold text-error-200">DELETE</span>
                                            @else
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold text-bgray-700 dark:text-bgray-300">{{ $api->method }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="font-mono text-xs text-bgray-700 dark:text-bgray-300">
                                                {{ $api->endpoint }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <x-status-toggle :model="$api" route="settings.data-sync.outbound.apis.toggle-status" :route-parameters="['connection' => $connection->id, 'api' => $api->id]" entity="API Configuration" permission="data_sync_settings.edit" />
                                        </td>
                                        <td class="pl-4 py-4 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @can('data_sync_settings.edit')
                                                    <x-edit-button action="javascript:void(0)" class="edit-api-btn" data-id="{{ $api->id }}" data-module="{{ $api->module }}" data-operation="{{ $api->operation }}" data-method="{{ $api->method }}" data-endpoint="{{ $api->endpoint }}" data-is-active="{{ $api->is_active ? '1' : '0' }}" data-update-url="{{ route('settings.data-sync.outbound.apis.update', ['connection' => $connection->id, 'api' => $api->id]) }}" title="Edit API" />
                                                @endcan

                                                @can('data_sync_settings.delete')
                                                    <x-delete-form :action="route('settings.data-sync.outbound.apis.destroy', ['connection' => $connection->id, 'api' => $api->id])" confirm-title="Delete API Configuration?" confirm-message="Are you sure you want to remove configuration for “{{ $api->module }} - {{ $api->operation }}”?" title="Delete API" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="empty-apis-row">
                                        <td colspan="7" class="py-10 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <p class="text-sm font-semibold text-bgray-700 dark:text-bgray-300">No APIs configured for this connection.</p>
                                                <p class="text-xs text-bgray-500 dark:text-bgray-400 mt-1">Click "+ Add API" above to configure module endpoints.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('settings.data-sync.outbound.modals.create-api-modal')
    @include('settings.data-sync.outbound.modals.edit-api-modal')
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

            // Submit Edit Connection Form via AJAX
            $('#edit-connection-form').on('submit', function(e) {
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
                    success: function(response) {
                        submitBtn.prop('disabled', false).removeClass('opacity-70');
                        if (response.success) {
                            if (window.Swal) {
                                window.Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: response.message || 'Connection updated successfully.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                });
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
                            const msg = xhr.responseJSON?.message || 'Failed to update connection.';
                            if (window.Swal) {
                                window.Swal.fire('Error', msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }
                });
            });

            // Open Create API Modal
            $('#open-create-api-btn').on('click', function() {
                const modal = $('#create-api-modal');
                modal.find('form')[0].reset();
                modal.find('[data-error-for]').addClass('hidden').text('');
                $('#create-api-is-active').prop('checked', true);
                modal.removeClass('hidden');
            });

            // Submit Create API Form
            $('#create-api-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = $('#create-api-submit-btn');

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
                            $('#create-api-modal').addClass('hidden');
                            if (window.Swal) {
                                window.Swal.fire({
                                    icon: 'success',
                                    title: 'Added',
                                    text: response.message || 'API configuration created.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                }).then(() => window.location.reload());
                            } else {
                                window.location.reload();
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
                            const msg = xhr.responseJSON?.message || 'Failed to add API.';
                            if (window.Swal) {
                                window.Swal.fire('Error', msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }
                });
            });

            // Open Edit API Modal
            $(document).on('click', '.edit-api-btn', function() {
                const btn = $(this);
                const modal = $('#edit-api-modal');
                const form = $('#edit-api-form');

                form.attr('action', btn.data('updateUrl'));
                form.find('[data-error-for]').addClass('hidden').text('');

                $('#edit-api-id').val(btn.data('id'));
                $('#edit-api-module').val(btn.data('module'));
                $('#edit-api-operation').val(btn.data('operation'));
                $('#edit-api-method').val(btn.data('method'));
                $('#edit-api-endpoint').val(btn.data('endpoint'));
                $('#edit-api-is-active').prop('checked', btn.data('isActive') == 1);

                modal.removeClass('hidden');
            });

            // Submit Edit API Form
            $('#edit-api-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = $('#edit-api-submit-btn');

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
                            $('#edit-api-modal').addClass('hidden');
                            if (window.Swal) {
                                window.Swal.fire({
                                    icon: 'success',
                                    title: 'Updated',
                                    text: response.message || 'API configuration updated.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                }).then(() => window.location.reload());
                            } else {
                                window.location.reload();
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
                            const msg = xhr.responseJSON?.message || 'Failed to update API.';
                            if (window.Swal) {
                                window.Swal.fire('Error', msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    }
                });
            });
        });
    </script>
@endpush
