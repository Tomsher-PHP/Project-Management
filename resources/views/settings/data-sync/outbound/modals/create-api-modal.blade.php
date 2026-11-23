<div id="create-api-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-xl">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkblack-600">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                            Add API Configuration
                        </h3>
                        <p class="text-xs text-bgray-600 dark:text-bgray-400 mt-0.5">
                            Define an outbound endpoint for this connection.
                        </p>
                    </div>

                    <button type="button" class="modal-close inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 transition hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        ✕
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form id="create-api-form" action="{{ route('settings.data-sync.outbound.apis.store', $connection) }}" method="POST" class="flex flex-col">
                    @csrf
                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-7 space-y-5">
                        <!-- Module -->
                        <div>
                            <label for="create-api-module" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Module <span class="text-error-300">*</span>
                            </label>
                            <select id="create-api-module" name="module" required class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <option value="">-- Select Module --</option>
                                @foreach ($availableModules ?? [] as $modKey => $modLabel)
                                    <option value="{{ $modKey }}">{{ $modLabel }}</option>
                                @endforeach
                            </select>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="module"></span>
                        </div>

                        <!-- Operation -->
                        <div>
                            <label for="create-api-operation" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Operation <span class="text-error-300">*</span>
                            </label>
                            <select id="create-api-operation" name="operation" required class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                <option value="">-- Select Operation --</option>
                                @foreach ($availableOperations ?? [] as $opKey => $opLabel)
                                    <option value="{{ $opKey }}">{{ $opLabel }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-bgray-500 dark:text-bgray-400 mt-1">Combination of Module + Operation must be unique for this connection.</p>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="operation"></span>
                        </div>

                        <!-- Method -->
                        <div>
                            <label for="create-api-method" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                HTTP Method <span class="text-error-300">*</span>
                            </label>
                            <select id="create-api-method" name="method" required class="w-full rounded-lg border border-bgray-300 bg-white px-4 py-2.5 text-sm font-mono text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                @foreach ($availableMethods ?? [] as $mKey => $mLabel)
                                    <option value="{{ $mKey }}" {{ $mKey === 'POST' ? 'selected' : '' }}>{{ $mLabel }}</option>
                                @endforeach
                            </select>
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="method"></span>
                        </div>

                        <!-- Endpoint -->
                        <div>
                            <label for="create-api-endpoint" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                Endpoint Path <span class="text-error-300">*</span>
                            </label>
                            <input type="text" id="create-api-endpoint" name="endpoint" required maxlength="500" placeholder="/api/v1/projects" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm font-mono text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                            <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="endpoint"></span>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 pt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="create-api-is-active" name="is_active" value="1" checked class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <label for="create-api-is-active" class="text-sm font-semibold text-bgray-900 dark:text-white">
                                Active
                            </label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="modal-close rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            Cancel
                        </button>
                        <button type="submit" id="create-api-submit-btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                            Add API
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

