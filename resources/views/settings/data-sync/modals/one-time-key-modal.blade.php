<div id="one-time-key-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-xl">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkblack-600">
                <!-- Modal Header -->
                <div class="border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-success-50 text-success-300 dark:bg-success-900/30">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h3 id="one-time-key-modal-title" class="text-xl font-bold text-bgray-900 dark:text-white">
                                API Key Generated Successfully
                            </h3>
                            <p id="one-time-key-app-name" class="text-xs text-bgray-600 dark:text-bgray-400 mt-0.5">
                                Application: --
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 sm:px-7 space-y-5">
                    <!-- API Key Box -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400 mb-1.5">
                            API Key (Bearer Token)
                        </label>
                        <div class="relative flex items-center rounded-lg border border-bgray-300 bg-bgray-50 p-2 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <input type="text" id="one-time-key-value" readonly class="w-full bg-transparent px-2 font-mono text-xs sm:text-sm text-bgray-900 focus:outline-none dark:text-white select-all" value="">
                            <button type="button" id="copy-one-time-key-btn" class="ml-2 inline-flex items-center gap-1.5 shrink-0 rounded-md bg-success-300 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-success-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Copy</span>
                            </button>
                        </div>
                    </div>

                    <!-- Permissions Summary -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400 mb-2">
                            Assigned Permissions
                        </h4>
                        <div id="one-time-key-scopes" class="flex flex-wrap gap-1.5">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <!-- Expiration -->
                    <div class="flex items-center justify-between text-xs text-bgray-600 dark:text-bgray-300">
                        <span class="font-semibold">Expiration:</span>
                        <span id="one-time-key-expiration">Never</span>
                    </div>

                    <!-- Critical Notice Box -->
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
                        <div class="flex items-start gap-2">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <strong class="font-bold">Important Notice:</strong>
                                <p class="mt-0.5">This API key will only be displayed once. Copy it and store it in your application's secure environment. It cannot be recovered once this window is closed.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <button type="button" id="one-time-key-done-btn" class="inline-flex items-center justify-center rounded-lg bg-bgray-900 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-bgray-800 dark:bg-white dark:text-bgray-900 dark:hover:bg-bgray-100">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

