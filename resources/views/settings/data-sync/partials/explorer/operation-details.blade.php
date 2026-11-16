<div id="explorer-operation-details" class="rounded-xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
    <!-- Operation Title & Description -->
    <div class="mb-4">
        <h3 id="op-title" class="text-base font-bold text-bgray-900 dark:text-bgray-300">
            <!-- Populated by JS -->
        </h3>
        <p id="op-description" class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">
            <!-- Populated by JS -->
        </p>
    </div>

    <!-- Endpoint URL & Method Bar -->
    <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 rounded-lg border border-bgray-200 bg-bgray-50 p-3 dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center">
            <span id="op-method-badge" class="px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0">
                <!-- GET / POST / PUT / DELETE -->
            </span>
            <code id="op-full-url" class="min-w-0 flex-1 break-all font-mono text-xs leading-5 text-bgray-900 dark:text-bgray-300 sm:text-sm">
                <!-- Full URL -->
            </code>
        </div>
        <button type="button" id="btn-copy-url" aria-label="Copy endpoint URL" class="copy-action-btn inline-flex items-center justify-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 shrink-0">
            <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <span class="copy-label">Copy URL</span>
        </button>
    </div>

    <!-- Scope & Authorization Box -->
    <div class="mb-5 flex flex-col justify-between gap-3 rounded-lg border border-bgray-200 p-3.5 dark:border-darkblack-400 sm:flex-row sm:items-center">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-bgray-900 dark:text-bgray-300">Required Scope:</span>
            <span id="op-scope-badge" class="inline-flex items-center rounded border border-bgray-200 px-2.5 py-0.5 font-mono text-xs font-bold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                <!-- Scope -->
            </span>
            <span id="op-scope-desc" class="text-xs text-bgray-600 dark:text-bgray-300">
                <!-- Scope description -->
            </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-bgray-600 dark:text-bgray-300 shrink-0">
            <svg class="h-3.5 w-3.5 text-bgray-600 dark:text-bgray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span>Authorization: Bearer YOUR_API_KEY</span>
        </div>
    </div>

    <!-- Operation Sub-tabs Navigation -->
    <div class="border-b border-bgray-200 dark:border-darkblack-400 mb-4">
        <div class="flex space-x-6 overflow-x-auto" role="tablist" aria-label="Operation details">
            <button type="button" id="tab-headers" role="tab" aria-selected="false" aria-controls="pane-headers" tabindex="-1" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 focus:outline-none focus:ring-2 focus:ring-success-300" data-subtab="headers">
                Headers <span class="ml-1 font-mono text-[10px]" data-tab-count="headers"></span>
            </button>
            <button type="button" id="tab-params" role="tab" aria-selected="false" aria-controls="pane-params" tabindex="-1" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 focus:outline-none focus:ring-2 focus:ring-success-300" data-subtab="params">
                Parameters <span class="ml-1 font-mono text-[10px]" data-tab-count="params"></span>
            </button>
            <button type="button" id="tab-payload" role="tab" aria-selected="false" aria-controls="pane-payload" tabindex="-1" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 focus:outline-none focus:ring-2 focus:ring-success-300" data-subtab="payload">
                Request Payload <span class="ml-1 font-mono text-[10px]" data-tab-count="payload"></span>
            </button>
            <button type="button" id="tab-responses" role="tab" aria-selected="false" aria-controls="pane-responses" tabindex="-1" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 focus:outline-none focus:ring-2 focus:ring-success-300" data-subtab="responses">
                Responses <span class="ml-1 font-mono text-[10px]" data-tab-count="responses"></span>
            </button>
            <button type="button" id="tab-curl" role="tab" aria-selected="false" aria-controls="pane-curl" tabindex="-1" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 focus:outline-none focus:ring-2 focus:ring-success-300" data-subtab="curl">
                cURL Command
            </button>
        </div>
    </div>

    <!-- SUBTAB 1: HEADERS -->
    @include('settings.data-sync.partials.explorer.panes.headers')

    <!-- SUBTAB 2: PARAMETERS -->
    @include('settings.data-sync.partials.explorer.panes.params')

    <!-- SUBTAB 3: REQUEST PAYLOAD -->
    @include('settings.data-sync.partials.explorer.panes.payload')

    <!-- SUBTAB 4: RESPONSES -->
    @include('settings.data-sync.partials.explorer.panes.responses')

    <!-- SUBTAB 5: cURL COMMAND -->
    @include('settings.data-sync.partials.explorer.panes.curl')
</div>

