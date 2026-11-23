<div id="create-connection-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-2xl">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkblack-600">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                            Add Outbound Connection
                        </h3>
                        <p class="text-xs text-bgray-600 dark:text-bgray-400 mt-0.5">
                            Configure an external system endpoint and authentication for outbound sync.
                        </p>
                    </div>

                    <button type="button" class="modal-close inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 transition hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        ✕
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form id="create-connection-form" action="{{ route('settings.data-sync.outbound.connections.store') }}" method="POST" class="flex flex-col">
                    @csrf
                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-7 space-y-5">
                        <!-- Connection Name -->
                        <div>
                            <label for="create-connection-name" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Connection Name <span class="text-error-300">*</span>
                            </label>
                            <input type="text" id="create-connection-name" name="name" required maxlength="255" placeholder="e.g., Zoho CRM, HR ERP, SAP Gateway" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="name"></span>
                        </div>

                        <!-- Base URL -->
                        <div>
                            <label for="create-connection-base-url" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Base URL <span class="text-error-300">*</span>
                            </label>
                            <input type="url" id="create-connection-base-url" name="base_url" required maxlength="500" placeholder="https://api.external-system.com/v1" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                            <p class="text-[11px] text-bgray-500 dark:text-bgray-400 mt-1">Must be an external HTTP/HTTPS URL. Local and internal addresses are blocked.</p>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="base_url"></span>
                        </div>

                        <!-- Authentication Type -->
                        <div>
                            <label class="block text-sm font-semibold text-bgray-900 dark:text-white mb-2">
                                Authentication Type <span class="text-error-300">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bgray-300 p-3 hover:border-success-300 dark:border-darkblack-400 dark:hover:border-success-300">
                                    <input type="radio" name="auth_type" value="none" checked class="h-4 w-4 border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                    <span class="text-xs font-semibold text-bgray-800 dark:text-bgray-200">None</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bgray-300 p-3 hover:border-success-300 dark:border-darkblack-400 dark:hover:border-success-300">
                                    <input type="radio" name="auth_type" value="bearer" class="h-4 w-4 border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                    <span class="text-xs font-semibold text-bgray-800 dark:text-bgray-200">Bearer Token</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bgray-300 p-3 hover:border-success-300 dark:border-darkblack-400 dark:hover:border-success-300">
                                    <input type="radio" name="auth_type" value="api_key" class="h-4 w-4 border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                    <span class="text-xs font-semibold text-bgray-800 dark:text-bgray-200">API Key</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-bgray-300 p-3 hover:border-success-300 dark:border-darkblack-400 dark:hover:border-success-300">
                                    <input type="radio" name="auth_type" value="basic" class="h-4 w-4 border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                    <span class="text-xs font-semibold text-bgray-800 dark:text-bgray-200">Basic Auth</span>
                                </label>
                            </div>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="auth_type"></span>
                        </div>

                        <!-- Credentials: Bearer Token -->
                        <div class="auth-credential-group auth-fields-bearer hidden space-y-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                            <div>
                                <label for="create-connection-token" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                    Bearer Token <span class="text-error-300">*</span>
                                </label>
                                <input type="password" id="create-connection-token" name="token" placeholder="eyJhbGciOi..." class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="token"></span>
                            </div>
                        </div>

                        <!-- Credentials: API Key -->
                        <div class="auth-credential-group auth-fields-api_key hidden space-y-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                            <div>
                                <label for="create-connection-header-name" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                    Header Name
                                </label>
                                <input type="text" id="create-connection-header-name" name="header_name" value="X-API-Key" placeholder="e.g., X-API-Key, Authorization" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="header_name"></span>
                            </div>
                            <div>
                                <label for="create-connection-api-key" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                    API Key Value <span class="text-error-300">*</span>
                                </label>
                                <input type="password" id="create-connection-api-key" name="api_key" placeholder="Enter API secret / key value" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="api_key"></span>
                            </div>
                        </div>

                        <!-- Credentials: Basic Auth -->
                        <div class="auth-credential-group auth-fields-basic hidden space-y-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/40">
                            <div>
                                <label for="create-connection-username" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                    Username <span class="text-error-300">*</span>
                                </label>
                                <input type="text" id="create-connection-username" name="username" placeholder="Username" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="username"></span>
                            </div>
                            <div>
                                <label for="create-connection-password" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300 mb-1.5">
                                    Password <span class="text-error-300">*</span>
                                </label>
                                <input type="password" id="create-connection-password" name="password" placeholder="Password" class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="password"></span>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 pt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="create-connection-is-active" name="is_active" value="1" checked class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <label for="create-connection-is-active" class="text-sm font-semibold text-bgray-900 dark:text-white">
                                Enable this connection immediately
                            </label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="modal-close rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            Cancel
                        </button>
                        <button type="submit" id="create-connection-submit-btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                            Create Connection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

