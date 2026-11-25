<div id="history-details-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-3xl">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkblack-600">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                                Outbound Sync Details
                            </h3>
                            <span id="history-modal-status-badge"></span>
                            <span id="history-modal-response-code-badge"></span>
                        </div>
                    </div>

                    <button type="button" class="modal-close inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 transition hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-7 space-y-6">
                    <!-- Error Message Alert (if present) -->
                    <div id="history-modal-error-box" class="hidden rounded-lg border border-error-200 bg-error-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-error-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-error-300">Execution Error</h4>
                                <p id="history-modal-error-message" class="mt-1 text-sm text-error-200 dark:text-error-200 font-mono whitespace-pre-wrap break-all"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Request Information -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 border-b border-bgray-200 pb-2 dark:border-darkblack-400">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                            <h4 class="text-sm font-bold uppercase tracking-wider text-bgray-900 dark:text-white">Request Information</h4>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 rounded-lg border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Connection</span>
                                <p id="history-modal-connection" class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">—</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Module & Operation</span>
                                <p id="history-modal-module-operation" class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">—</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Method & Endpoint</span>
                                <p class="mt-1 text-sm font-mono text-bgray-900 dark:text-white">
                                    <span id="history-modal-method" class="font-bold text-success-500">—</span>
                                    <span id="history-modal-endpoint" class="text-bgray-700 dark:text-bgray-300">—</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Execution Date</span>
                                <p id="history-modal-created-at" class="mt-1 text-sm text-bgray-900 dark:text-white">—</p>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Request URL</span>
                                <p id="history-modal-request-url" class="mt-1 break-all rounded border border-bgray-200 bg-white px-3 py-2 text-xs font-mono text-bgray-800 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">—</p>
                            </div>
                        </div>

                        <!-- Request Payload -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Request Payload</label>
                                <button type="button" id="copy-history-payload-btn" class="inline-flex items-center gap-1 text-xs text-success-400 hover:text-success-500 font-semibold transition">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Copy Payload</span>
                                </button>
                            </div>
                            <pre class="max-h-56 overflow-y-auto rounded-lg border border-bgray-200 bg-bgray-100 p-3 text-xs font-mono text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100"><code id="history-modal-payload">No request payload</code></pre>
                        </div>
                    </div>

                    <!-- Section: Response Information -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 border-b border-bgray-200 pb-2 dark:border-darkblack-400">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-success-500"></span>
                            <h4 class="text-sm font-bold uppercase tracking-wider text-bgray-900 dark:text-white">Response Information</h4>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 rounded-lg border border-bgray-200 bg-bgray-50/70 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Response Code</span>
                                <p id="history-modal-response-code" class="mt-1 text-sm font-semibold font-mono text-bgray-900 dark:text-white">—</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Delivery Status</span>
                                <p id="history-modal-status-text" class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">—</p>
                            </div>
                        </div>

                        <!-- Response Body -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Response Body</label>
                                <button type="button" id="copy-history-response-btn" class="inline-flex items-center gap-1 text-xs text-success-400 hover:text-success-500 font-semibold transition">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Copy Response</span>
                                </button>
                            </div>
                            <pre class="max-h-56 overflow-y-auto rounded-lg border border-bgray-200 bg-bgray-100 p-3 text-xs font-mono text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100"><code id="history-modal-response-body">No response body</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                    <button type="button" class="modal-close rounded-lg border border-bgray-300 bg-white px-5 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
