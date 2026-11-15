<div id="create-client-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-2xl">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkblack-600">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                            Generate Inbound API Key
                        </h3>
                        <p class="text-xs text-bgray-600 dark:text-bgray-400 mt-0.5">
                            Create a secure credential for external application synchronization.
                        </p>
                    </div>

                    <button type="button" class="modal-close inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 transition hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        ✕
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form id="create-client-form" action="{{ route('settings.data-sync.clients.store') }}" method="POST" class="flex flex-col">
                    @csrf
                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-7 space-y-5">
                        <!-- Application Name -->
                        <div>
                            <label for="create-client-name" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Application Name <span class="text-error-300">*</span>
                            </label>
                            <input type="text" id="create-client-name" name="name" required maxlength="255" placeholder="e.g., HubSpot CRM, Internal Automation" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="name"></span>
                        </div>

                        <!-- Integration Direction (Informational & Fixed) -->
                        <div>
                            <label class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Integration Direction
                            </label>
                            <div class="flex items-center gap-2.5 rounded-lg border border-bgray-200 bg-bgray-50 px-3.5 py-2.5 text-sm text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold text-success-300 dark:text-success-400">
                                    Inbound
                                </span>
                                <span class="text-sm font-bold text-bgray-700 dark:text-bgray-300">External Application &rarr; PMS API endpoints</span>
                            </div>
                        </div>

                        <!-- Scopes / Permissions -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-bgray-900 dark:text-white">
                                    API Permission Scopes <span class="text-error-300">*</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-xs font-medium text-success-400 cursor-pointer hover:underline">
                                    <input type="checkbox" id="create-select-all-scopes" class="rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                    Select All Permissions
                                </label>
                            </div>

                            <div class="space-y-3 rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/50">
                                @foreach (config('constants.data_sync_api_permissions', []) as $permissionGroup)
                                    <div @class([
                                        'border-t border-bgray-200 pt-3 dark:border-darkblack-400' => !$loop->first,
                                    ])>
                                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">
                                            {{ $permissionGroup['label'] }}
                                        </h4>
                                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                            @foreach ($permissionGroup['permissions'] as $scope => $description)
                                                <label class="flex cursor-pointer items-start gap-2.5 rounded-md p-2 hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                                                    <input type="checkbox" name="scopes[]" value="{{ $scope }}" class="create-scope-checkbox mt-0.5 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                                                    <div class="text-xs">
                                                        <span class="font-semibold text-bgray-900 dark:text-white">{{ $scope }}</span>
                                                        <p class="text-bgray-700 dark:text-bgray-300">{{ $description }}</p>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="scopes"></span>
                        </div>

                        <!-- Expiration Options -->
                        <div>
                            <label class="block text-sm font-semibold text-bgray-900 dark:text-white mb-2">
                                Expiration
                            </label>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="expiration_type" value="never" checked class="create-expiration-radio text-success-300 focus:ring-success-300 dark:bg-darkblack-500">
                                    <span class="text-sm text-bgray-800 dark:text-bgray-300">Never Expires</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="expiration_type" value="custom" class="create-expiration-radio text-success-300 focus:ring-success-300 dark:bg-darkblack-500">
                                    <span class="text-sm text-bgray-800 dark:text-bgray-300">Set Expiration Date</span>
                                </label>
                            </div>

                            <div id="create-custom-expiration-container" class="mt-3 hidden">
                                <input type="text" name="expires_at" id="create-expires-at" data-min-date="today" placeholder="YYYY-MM-DD" class="datepicker w-full max-w-xs rounded-lg border border-bgray-300 px-4 py-2 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="expires_at"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="modal-close rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            Cancel
                        </button>

                        <button type="submit" id="create-client-submit-btn" class="inline-flex items-center justify-center rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-success-400">
                            Generate API Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
