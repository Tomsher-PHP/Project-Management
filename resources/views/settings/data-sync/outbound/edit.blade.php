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
                        <p class="text-xs text-bgray-600 dark:text-bgray-400 mt-0.5">
                            Configure base URL and authentication credentials for this external application.
                        </p>
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

                        <!-- Authentication Type -->
                        <div>
                            <label class="block text-sm font-semibold text-bgray-900 dark:text-white mb-2">
                                Authentication Type <span class="text-error-300">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ($supportedAuthTypes as $typeKey => $typeLabel)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bgray-300 p-3 hover:border-success-300 dark:border-darkblack-400 dark:hover:border-success-300">
                                        <input type="radio" name="auth_type" value="{{ $typeKey }}" {{ old('auth_type', $connection->auth_type) === $typeKey ? 'checked' : '' }} class="h-4 w-4 border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                        <span class="text-xs font-semibold text-bgray-800 dark:text-bgray-200">{{ $typeLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="auth_type"></span>
                        </div>

                        <!-- Credentials: Bearer Token -->
                        <div class="auth-credential-group auth-fields-bearer {{ old('auth_type', $connection->auth_type) === 'bearer' ? '' : 'hidden' }} space-y-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                            <div>
                                <label for="edit-connection-token" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                    Bearer Token
                                    @if ($connection->auth_type === 'bearer' && !empty($connection->auth_config))
                                        <span class="text-xs font-normal text-bgray-500 lowercase">(saved — leave empty to keep unchanged)</span>
                                    @endif
                                </label>
                                <input type="password" id="edit-connection-token" name="token" placeholder="{{ $connection->auth_type === 'bearer' && !empty($connection->auth_config) ? '••••••••••••••••••••••••' : 'Enter bearer token' }}" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="token"></span>
                            </div>
                        </div>

                        <!-- Credentials: API Key -->
                        <div class="auth-credential-group auth-fields-api_key {{ old('auth_type', $connection->auth_type) === 'api_key' ? '' : 'hidden' }} space-y-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-connection-header-name" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                        Header Name
                                    </label>
                                    <input type="text" id="edit-connection-header-name" name="header_name" value="{{ old('header_name', $connection->auth_config['header_name'] ?? 'X-API-Key') }}" placeholder="e.g., X-API-Key, Authorization" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                    <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="header_name"></span>
                                </div>
                                <div>
                                    <label for="edit-connection-api-key" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                        API Key Value
                                        @if ($connection->auth_type === 'api_key' && !empty($connection->auth_config))
                                            <span class="text-xs font-normal text-bgray-500 lowercase">(saved — leave empty to keep unchanged)</span>
                                        @endif
                                    </label>
                                    <input type="password" id="edit-connection-api-key" name="api_key" placeholder="{{ $connection->auth_type === 'api_key' && !empty($connection->auth_config) ? '••••••••••••••••••••••••' : 'Enter API secret value' }}" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                    <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="api_key"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Credentials: Basic Auth -->
                        <div class="auth-credential-group auth-fields-basic {{ old('auth_type', $connection->auth_type) === 'basic' ? '' : 'hidden' }} space-y-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="edit-connection-username" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                        Username
                                    </label>
                                    <input type="text" id="edit-connection-username" name="username" value="{{ old('username', $connection->auth_config['username'] ?? '') }}" placeholder="Username" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                    <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="username"></span>
                                </div>
                                <div>
                                    <label for="edit-connection-password" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                        Password
                                        @if ($connection->auth_type === 'basic' && !empty($connection->auth_config))
                                            <span class="text-xs font-normal text-bgray-500 lowercase">(saved — leave empty to keep unchanged)</span>
                                        @endif
                                    </label>
                                    <input type="password" id="edit-connection-password" name="password" placeholder="{{ $connection->auth_type === 'basic' && !empty($connection->auth_config) ? '••••••••••••••••••••••••' : 'Enter password' }}" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                    <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="password"></span>
                                </div>
                            </div>
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
                            <p class="text-xs text-bgray-600 dark:text-bgray-400 mt-0.5">
                                Outbound module operations and endpoints mapped for this connection.
                            </p>
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
                                            <span class="inline-flex rounded-lg bg-bgray-100 px-2.5 py-1 text-xs font-bold text-bgray-800 capitalize dark:bg-darkblack-500 dark:text-bgray-200">
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
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">POST</span>
                                            @elseif ($api->method === 'GET')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300">GET</span>
                                            @elseif ($api->method === 'PUT')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">PUT</span>
                                            @elseif ($api->method === 'PATCH')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">PATCH</span>
                                            @elseif ($api->method === 'DELETE')
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300">DELETE</span>
                                            @else
                                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">{{ $api->method }}</span>
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
                                                    <button type="button" class="edit-api-btn inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 text-bgray-600 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-success-300" data-id="{{ $api->id }}" data-module="{{ $api->module }}" data-operation="{{ $api->operation }}" data-method="{{ $api->method }}" data-endpoint="{{ $api->endpoint }}" data-is-active="{{ $api->is_active ? '1' : '0' }}" data-update-url="{{ route('settings.data-sync.outbound.apis.update', ['connection' => $connection->id, 'api' => $api->id]) }}" title="Edit API">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                    </button>
                                                @endcan

                                                @can('data_sync_settings.delete')
                                                    <button type="button" class="delete-api-btn inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 text-bgray-600 transition hover:border-error-300 hover:text-error-400 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-error-300" data-id="{{ $api->id }}" data-label="{{ $api->module }} - {{ $api->operation }}" data-url="{{ route('settings.data-sync.outbound.apis.destroy', ['connection' => $connection->id, 'api' => $api->id]) }}" title="Delete API">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
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
        $(document).ready(function () {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Switch Auth Type in Edit Connection Form
            $(document).on('change', 'input[name="auth_type"]', function () {
                const val = $(this).val();
                const container = $('#edit-connection-form');
                container.find('.auth-credential-group').addClass('hidden');
                container.find(`.auth-fields-${val}`).removeClass('hidden');
            });

            // Submit Edit Connection Form via AJAX
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
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    success: function (response) {
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
            $('#open-create-api-btn').on('click', function () {
                const modal = $('#create-api-modal');
                modal.find('form')[0].reset();
                modal.find('[data-error-for]').addClass('hidden').text('');
                $('#create-api-is-active').prop('checked', true);
                modal.removeClass('hidden');
            });

            // Submit Create API Form
            $('#create-api-form').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = $('#create-api-submit-btn');

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
            $(document).on('click', '.edit-api-btn', function () {
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
            $('#edit-api-form').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = $('#edit-api-submit-btn');

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

            // Delete API with Confirmation
            $(document).on('click', '.delete-api-btn', function () {
                const btn = $(this);
                const label = btn.data('label');
                const url = btn.data('url');

                const performDelete = function () {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        success: function (response) {
                            if (window.Swal) {
                                window.Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: response.message || 'API configuration deleted.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                }).then(() => window.location.reload());
                            } else {
                                window.location.reload();
                            }
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Failed to delete API configuration.';
                            if (window.Swal) {
                                window.Swal.fire('Delete Failed', msg, 'error');
                            } else {
                                alert(msg);
                            }
                        }
                    });
                };

                if (window.Swal) {
                    window.Swal.fire({
                        title: 'Delete API Configuration?',
                        text: `Are you sure you want to remove configuration for "${label}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Delete',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#ef4444',
                    }).then(result => {
                        if (result.isConfirmed) {
                            performDelete();
                        }
                    });
                } else if (confirm(`Are you sure you want to remove "${label}"?`)) {
                    performDelete();
                }
            });
        });
    </script>
@endpush

