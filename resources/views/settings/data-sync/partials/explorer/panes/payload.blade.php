<div id="pane-payload" class="op-subtab-pane hidden" role="tabpanel" aria-labelledby="tab-payload" tabindex="0">
    <div id="payload-content-wrapper">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                Sample JSON Request Body (application/json)
            </span>
            <button type="button" id="btn-copy-payload" aria-label="Copy request JSON" class="copy-action-btn inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-2.5 py-1 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span class="copy-label">Copy JSON</span>
            </button>
        </div>
        <div class="relative max-h-[32rem] overflow-auto rounded-lg bg-bgray-900 p-4 font-mono text-xs leading-5 text-bgray-300 dark:bg-darkblack-600">
            <pre class="min-w-max whitespace-pre"><code id="op-payload-code"></code></pre>
        </div>
    </div>
    <div id="payload-empty-notice" class="hidden rounded-lg border border-dashed border-bgray-300 p-8 text-center dark:border-darkblack-400">
        <p class="text-xs text-bgray-600 dark:text-bgray-300">
            This operation does not require a request body.
        </p>
    </div>
</div>

