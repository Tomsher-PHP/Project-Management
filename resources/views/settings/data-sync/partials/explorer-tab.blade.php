<div id="api-explorer-root" class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 -mt-2">
        <p class="text-xs text-bgray-600 dark:text-bgray-400">
            Browse available Data Sync modules, endpoints, and request examples. View required permission scopes, request payloads, and copyable cURL commands.
        </p>
        <span class="inline-flex items-center gap-1.5 text-xs text-bgray-500 dark:text-bgray-400">
            <span class="h-2 w-2 rounded-full bg-success-300"></span>
            <span>API v1.0 • Read & Write Sync</span>
        </span>
    </div>

    <!-- Explorer Two-Column Layout -->
    <div class="flex flex-col lg:flex-row gap-6 items-start w-full">
        <!-- =============================================================== -->
        <!-- LEFT PANEL: Module Navigation                                   -->
        <!-- =============================================================== -->
        <aside class="w-full lg:w-72 2xl:w-80 shrink-0 rounded-xl border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="mb-4 flex items-center justify-between border-b border-bgray-200 pb-3 dark:border-darkblack-400">
                <span class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                    API Modules
                </span>
                <span class="rounded bg-bgray-100 px-2 py-0.5 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-400 dark:text-bgray-300">
                    {{ count($explorerData['modules'] ?? []) }} Modules
                </span>
            </div>

            <nav class="space-y-5" aria-label="Explorer Modules">
                @foreach ($explorerData['categories'] ?? [] as $categoryKey => $category)
                    @php
                        $categoryModules = array_filter($explorerData['modules'] ?? [], fn($m) => ($m['category'] ?? '') === $categoryKey);
                    @endphp
                    <div>
                        <div class="px-2 mb-2 flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-bgray-400 dark:text-bgray-400">
                                {{ $category['name'] }}
                            </span>
                            <span class="text-[10px] text-bgray-400">
                                {{ count($categoryModules) }}
                            </span>
                        </div>
                        <ul class="space-y-1">
                            @foreach ($categoryModules as $modSlug => $module)
                                <li>
                                    <button type="button"
                                        class="explorer-module-btn w-full text-left flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium transition duration-150"
                                        data-module-slug="{{ $modSlug }}">
                                        <div class="flex items-center gap-2.5 truncate">
                                            @if ($modSlug === 'technologies')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                            @elseif ($modSlug === 'industries')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            @elseif ($modSlug === 'project_categories')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                            @elseif ($modSlug === 'project_statuses')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @elseif ($modSlug === 'projects')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            @elseif ($modSlug === 'customers')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            @elseif ($modSlug === 'meetings')
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @else
                                                <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            @endif
                                            <span class="truncate">{{ $module['name'] }}</span>
                                        </div>
                                        <span class="explorer-op-count text-[11px] px-1.5 py-0.5 rounded font-mono">
                                            {{ count($module['operations'] ?? []) }}
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        <!-- =============================================================== -->
        <!-- RIGHT PANEL: Endpoint & Operation Details                      -->
        <!-- =============================================================== -->
        <main class="flex-1 min-w-0 w-full flex flex-col space-y-5">
            <!-- Module Header Card -->
            <div class="rounded-xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-bgray-200 pb-4 dark:border-darkblack-400">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 id="explorer-module-name" class="text-lg font-bold text-bgray-900 dark:text-white">
                                <!-- Populated by JS -->
                            </h2>
                            <span id="explorer-module-category-badge" class="rounded px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-bgray-100 text-bgray-700 dark:bg-darkblack-400 dark:text-bgray-200">
                                <!-- Category -->
                            </span>
                        </div>
                        <p id="explorer-module-desc" class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">
                            <!-- Populated by JS -->
                        </p>
                    </div>
                </div>

                <!-- Operation Tabs / Pills Selector -->
                <div class="mt-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400 block mb-2">
                        Available Operations
                    </span>
                    <div id="explorer-operations-container" class="flex flex-wrap gap-2">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Operation Details Card -->
            <div class="rounded-xl border border-bgray-200 bg-white p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
                <!-- Operation Title & Description -->
                <div class="mb-4">
                    <h3 id="op-title" class="text-base font-bold text-bgray-900 dark:text-white">
                        <!-- Populated by JS -->
                    </h3>
                    <p id="op-description" class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">
                        <!-- Populated by JS -->
                    </p>
                </div>

                <!-- Endpoint URL & Method Bar -->
                <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 rounded-lg border border-bgray-200 bg-bgray-50 p-3 dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <span id="op-method-badge" class="px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0">
                            <!-- GET / POST / PUT / DELETE -->
                        </span>
                        <code id="op-full-url" class="font-mono text-xs sm:text-sm text-bgray-900 dark:text-white truncate flex-1">
                            <!-- Full URL -->
                        </code>
                    </div>
                    <button type="button" id="btn-copy-url" class="copy-action-btn inline-flex items-center justify-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:bg-darkblack-400 shrink-0">
                        <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="copy-label">Copy URL</span>
                    </button>
                </div>

                <!-- Scope & Authorization Box -->
                <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-purple-200 bg-purple-50/50 p-3.5 dark:border-purple-900/40 dark:bg-purple-950/20">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-purple-900 dark:text-purple-300">Required Scope:</span>
                        <span id="op-scope-badge" class="inline-flex items-center rounded bg-purple-100 px-2.5 py-0.5 font-mono text-xs font-bold text-purple-700 dark:bg-purple-900/60 dark:text-purple-200">
                            <!-- Scope -->
                        </span>
                        <span id="op-scope-desc" class="text-xs text-purple-800/80 dark:text-purple-300/80">
                            <!-- Scope description -->
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-bgray-500 dark:text-bgray-400 shrink-0">
                        <svg class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Authorization: Bearer YOUR_API_KEY</span>
                    </div>
                </div>

                <!-- Operation Sub-tabs Navigation -->
                <div class="border-b border-bgray-200 dark:border-darkblack-400 mb-4">
                    <nav class="flex space-x-6 overflow-x-auto" aria-label="Operation Details Tabs">
                        <button type="button" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2" data-subtab="headers">
                            Headers
                        </button>
                        <button type="button" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2" data-subtab="params">
                            Parameters
                        </button>
                        <button type="button" id="tab-btn-payload" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2" data-subtab="payload">
                            Request Payload
                        </button>
                        <button type="button" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2" data-subtab="responses">
                            Responses
                        </button>
                        <button type="button" class="op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2" data-subtab="curl">
                            cURL Command
                        </button>
                    </nav>
                </div>

                <!-- ======================================================= -->
                <!-- SUBTAB 1: HEADERS                                       -->
                <!-- ======================================================= -->
                <div id="pane-headers" class="op-subtab-pane">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                            Required & Optional Request Headers
                        </span>
                        <button type="button" id="btn-copy-headers" class="copy-action-btn inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-2.5 py-1 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">
                            <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span class="copy-label">Copy Headers</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-bgray-200 dark:border-darkblack-400">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-600 text-bgray-600 dark:text-bgray-300 font-semibold uppercase">
                                    <th class="py-2.5 px-3">Header</th>
                                    <th class="py-2.5 px-3">Value / Format</th>
                                    <th class="py-2.5 px-3">Required</th>
                                    <th class="py-2.5 px-3">Description</th>
                                </tr>
                            </thead>
                            <tbody id="table-headers-body" class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- SUBTAB 2: PARAMETERS                                    -->
                <!-- ======================================================= -->
                <div id="pane-params" class="op-subtab-pane hidden space-y-5">
                    <!-- Path Params -->
                    <div id="container-path-params">
                        <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300 block mb-2">
                            Path Parameters
                        </span>
                        <div class="overflow-x-auto rounded-lg border border-bgray-200 dark:border-darkblack-400">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-600 text-bgray-600 dark:text-bgray-300 font-semibold uppercase">
                                        <th class="py-2.5 px-3">Parameter</th>
                                        <th class="py-2.5 px-3">Type</th>
                                        <th class="py-2.5 px-3">Required</th>
                                        <th class="py-2.5 px-3">Example</th>
                                        <th class="py-2.5 px-3">Description</th>
                                    </tr>
                                </thead>
                                <tbody id="table-path-params-body" class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Query Params -->
                    <div id="container-query-params">
                        <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300 block mb-2">
                            Query Parameters
                        </span>
                        <div class="overflow-x-auto rounded-lg border border-bgray-200 dark:border-darkblack-400">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-600 text-bgray-600 dark:text-bgray-300 font-semibold uppercase">
                                        <th class="py-2.5 px-3">Parameter</th>
                                        <th class="py-2.5 px-3">Type</th>
                                        <th class="py-2.5 px-3">Default</th>
                                        <th class="py-2.5 px-3">Description</th>
                                    </tr>
                                </thead>
                                <tbody id="table-query-params-body" class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="params-empty-notice" class="hidden rounded-lg border border-dashed border-bgray-300 p-6 text-center dark:border-darkblack-400">
                        <p class="text-xs text-bgray-500 dark:text-bgray-400">
                            No path or query parameters are required for this endpoint.
                        </p>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- SUBTAB 3: REQUEST PAYLOAD                               -->
                <!-- ======================================================= -->
                <div id="pane-payload" class="op-subtab-pane hidden">
                    <div id="payload-content-wrapper">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                                Sample JSON Request Body (application/json)
                            </span>
                            <button type="button" id="btn-copy-payload" class="copy-action-btn inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-2.5 py-1 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">
                                <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span class="copy-label">Copy JSON</span>
                            </button>
                        </div>
                        <div class="relative rounded-lg bg-bgray-900 p-4 font-mono text-xs text-emerald-400 overflow-x-auto dark:bg-darkblack-600">
                            <pre><code id="op-payload-code"></code></pre>
                        </div>
                    </div>
                    <div id="payload-empty-notice" class="hidden rounded-lg border border-dashed border-bgray-300 p-8 text-center dark:border-darkblack-400">
                        <p class="text-xs text-bgray-500 dark:text-bgray-400">
                            This operation does not require a request body.
                        </p>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- SUBTAB 4: RESPONSES                                     -->
                <!-- ======================================================= -->
                <div id="pane-responses" class="op-subtab-pane hidden">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                                Status Code:
                            </span>
                            <div id="response-status-pills" class="flex flex-wrap gap-1.5">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                        <button type="button" id="btn-copy-response" class="copy-action-btn inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-2.5 py-1 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">
                            <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span class="copy-label">Copy Response</span>
                        </button>
                    </div>
                    <div class="relative rounded-lg bg-bgray-900 p-4 font-mono text-xs text-cyan-300 overflow-x-auto dark:bg-darkblack-600">
                        <pre><code id="op-response-code"></code></pre>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- SUBTAB 5: cURL COMMAND                                  -->
                <!-- ======================================================= -->
                <div id="pane-curl" class="op-subtab-pane hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-bgray-700 dark:text-bgray-300">
                            Executable cURL Snippet
                        </span>
                        <button type="button" id="btn-copy-curl" class="copy-action-btn inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-2.5 py-1 text-xs font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-200">
                            <svg class="copy-icon h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span class="copy-label">Copy cURL</span>
                        </button>
                    </div>
                    <div class="relative rounded-lg bg-bgray-900 p-4 font-mono text-xs text-amber-300 overflow-x-auto dark:bg-darkblack-600">
                        <pre><code id="op-curl-code"></code></pre>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ======================================================================= -->
<!-- EMBEDDED REGISTRY DATA SCRIPT                                          -->
<!-- ======================================================================= -->
<script id="data-sync-explorer-data" type="application/json">
    {!! json_encode($explorerData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const registryEl = document.getElementById('data-sync-explorer-data');
    if (!registryEl) return;

    let explorerData = {};
    try {
        explorerData = JSON.parse(registryEl.textContent || '{}');
    } catch (e) {
        console.error('Failed to parse Explorer registry data', e);
        return;
    }

    const modules = explorerData.modules || {};
    let activeModuleSlug = 'technologies';
    let activeOperationKey = 'list';
    let activeSubtab = 'headers';
    let activeResponseCode = '200';

    // Clipboard copy helper
    function copyText(text, btn) {
        if (!text) return;
        const doSuccess = () => {
            const label = btn.querySelector('.copy-label');
            const icon = btn.querySelector('.copy-icon');
            if (label) {
                const orig = label.textContent;
                label.textContent = 'Copied!';
                btn.classList.add('text-success-300', 'border-success-300');
                setTimeout(() => {
                    label.textContent = orig;
                    btn.classList.remove('text-success-300', 'border-success-300');
                }, 1600);
            }
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(doSuccess).catch(() => fallbackCopy(text, doSuccess));
        } else {
            fallbackCopy(text, doSuccess);
        }
    }

    function fallbackCopy(text, cb) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            document.execCommand('copy');
            if (cb) cb();
        } catch (e) {}
        document.body.removeChild(ta);
    }

    // Set up module selection
    function selectModule(slug) {
        if (!modules[slug]) {
            slug = Object.keys(modules)[0];
        }
        activeModuleSlug = slug;
        const mod = modules[slug];

        // Highlight module in left sidebar
        document.querySelectorAll('.explorer-module-btn').forEach(btn => {
            const bSlug = btn.getAttribute('data-module-slug');
            const countBadge = btn.querySelector('.explorer-op-count');
            if (bSlug === slug) {
                btn.className = 'explorer-module-btn w-full text-left flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-semibold bg-success-300 text-white shadow-sm transition duration-150';
                if (countBadge) {
                    countBadge.className = 'explorer-op-count text-[11px] px-1.5 py-0.5 rounded font-mono bg-white/20 text-white';
                }
            } else {
                btn.className = 'explorer-module-btn w-full text-left flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium text-bgray-700 hover:bg-bgray-100 dark:text-bgray-200 dark:hover:bg-darkblack-600 transition duration-150';
                if (countBadge) {
                    countBadge.className = 'explorer-op-count text-[11px] px-1.5 py-0.5 rounded font-mono bg-bgray-100 text-bgray-600 dark:bg-darkblack-400 dark:text-bgray-300';
                }
            }
        });

        // Header info
        document.getElementById('explorer-module-name').textContent = mod.name;
        document.getElementById('explorer-module-desc').textContent = mod.description;
        document.getElementById('explorer-module-category-badge').textContent = mod.category;

        // Render operations pills
        const opContainer = document.getElementById('explorer-operations-container');
        opContainer.innerHTML = '';
        const opKeys = Object.keys(mod.operations || {});

        opKeys.forEach(opKey => {
            const op = mod.operations[opKey];
            const opBtn = document.createElement('button');
            opBtn.type = 'button';
            opBtn.setAttribute('data-operation-key', opKey);
            opBtn.className = 'explorer-op-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition duration-150';

            // Method pill mini badge
            const mSpan = document.createElement('span');
            mSpan.className = 'text-[10px] font-mono font-bold uppercase';
            mSpan.textContent = op.method;

            const nameSpan = document.createElement('span');
            nameSpan.textContent = op.name;

            opBtn.appendChild(mSpan);
            opBtn.appendChild(nameSpan);

            opBtn.addEventListener('click', function () {
                selectOperation(opKey);
            });

            opContainer.appendChild(opBtn);
        });

        // Default to first operation in the module
        selectOperation(opKeys[0] || '');
    }

    // Set up operation selection
    function selectOperation(opKey) {
        const mod = modules[activeModuleSlug];
        if (!mod || !mod.operations || !mod.operations[opKey]) return;

        activeOperationKey = opKey;
        const op = mod.operations[opKey];

        // Highlight active operation pill
        document.querySelectorAll('.explorer-op-pill').forEach(btn => {
            const k = btn.getAttribute('data-operation-key');
            if (k === opKey) {
                btn.className = 'explorer-op-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-success-300 text-white shadow-sm transition duration-150';
            } else {
                btn.className = 'explorer-op-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-bgray-100 text-bgray-700 hover:bg-bgray-200 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:bg-darkblack-400 transition duration-150';
            }
        });

        // Populate operation top details
        document.getElementById('op-title').textContent = op.name;
        document.getElementById('op-description').textContent = op.description;

        // Method badge colors
        const methodBadge = document.getElementById('op-method-badge');
        methodBadge.textContent = op.method;
        const method = (op.method || 'GET').toUpperCase();
        if (method === 'GET') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        } else if (method === 'POST') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        } else if (method === 'PUT' || method === 'PATCH') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        } else if (method === 'DELETE') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        }

        // Full URL
        document.getElementById('op-full-url').textContent = op.full_url;

        // Scope
        document.getElementById('op-scope-badge').textContent = op.scope || 'none';
        document.getElementById('op-scope-desc').textContent = op.scope_description || '';

        // 1. Headers Table
        const headersTbody = document.getElementById('table-headers-body');
        headersTbody.innerHTML = '';
        (op.headers || []).forEach(h => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-bgray-50 dark:hover:bg-darkblack-600/50';
            tr.innerHTML = `
                <td class="py-2.5 px-3 font-mono font-semibold text-bgray-900 dark:text-white">${h.name}</td>
                <td class="py-2.5 px-3 font-mono text-bgray-700 dark:text-bgray-300">${h.value}</td>
                <td class="py-2.5 px-3"><span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${h.required ? 'bg-error-50 text-error-400 dark:bg-error-900/30' : 'bg-bgray-100 text-bgray-500 dark:bg-darkblack-400'}">${h.required ? 'Required' : 'Optional'}</span></td>
                <td class="py-2.5 px-3 text-bgray-600 dark:text-bgray-300">${h.note || '-'}</td>
            `;
            headersTbody.appendChild(tr);
        });

        // 2. Parameters Tables
        const pathBody = document.getElementById('table-path-params-body');
        pathBody.innerHTML = '';
        const pathParams = op.path_params || [];
        if (pathParams.length > 0) {
            document.getElementById('container-path-params').classList.remove('hidden');
            pathParams.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="py-2.5 px-3 font-mono font-semibold text-bgray-900 dark:text-white">${p.name}</td>
                    <td class="py-2.5 px-3 font-mono text-bgray-600 dark:text-bgray-300">${p.type}</td>
                    <td class="py-2.5 px-3"><span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${p.required ? 'bg-error-50 text-error-400' : 'bg-bgray-100 text-bgray-500'}">${p.required ? 'Required' : 'Optional'}</span></td>
                    <td class="py-2.5 px-3 font-mono text-bgray-700 dark:text-bgray-300">${p.example || '-'}</td>
                    <td class="py-2.5 px-3 text-bgray-600 dark:text-bgray-300">${p.description}</td>
                `;
                pathBody.appendChild(tr);
            });
        } else {
            document.getElementById('container-path-params').classList.add('hidden');
        }

        const queryBody = document.getElementById('table-query-params-body');
        queryBody.innerHTML = '';
        const queryParams = op.query_params || [];
        if (queryParams.length > 0) {
            document.getElementById('container-query-params').classList.remove('hidden');
            queryParams.forEach(q => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="py-2.5 px-3 font-mono font-semibold text-bgray-900 dark:text-white">${q.name}</td>
                    <td class="py-2.5 px-3 font-mono text-bgray-600 dark:text-bgray-300">${q.type}</td>
                    <td class="py-2.5 px-3 font-mono text-bgray-700 dark:text-bgray-300">${q.default || '-'}</td>
                    <td class="py-2.5 px-3 text-bgray-600 dark:text-bgray-300">${q.description}</td>
                `;
                queryBody.appendChild(tr);
            });
        } else {
            document.getElementById('container-query-params').classList.add('hidden');
        }

        const noParams = pathParams.length === 0 && queryParams.length === 0;
        document.getElementById('params-empty-notice').classList.toggle('hidden', !noParams);

        // 3. Payload
        const hasPayload = !!op.formatted_payload;
        document.getElementById('payload-content-wrapper').classList.toggle('hidden', !hasPayload);
        document.getElementById('payload-empty-notice').classList.toggle('hidden', hasPayload);
        if (hasPayload) {
            document.getElementById('op-payload-code').textContent = op.formatted_payload;
        }

        // 4. Responses
        const pillsContainer = document.getElementById('response-status-pills');
        pillsContainer.innerHTML = '';

        // Success response
        const successStatus = (op.response_success && op.response_success.status) || 200;
        const succBtn = document.createElement('button');
        succBtn.type = 'button';
        succBtn.setAttribute('data-status-code', String(successStatus));
        succBtn.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-semibold transition';
        succBtn.textContent = `${successStatus} Success`;
        succBtn.addEventListener('click', () => switchResponseStatus(String(successStatus)));
        pillsContainer.appendChild(succBtn);

        // Error responses
        const errors = op.formatted_error_responses || {};
        Object.keys(errors).forEach(errCode => {
            const errBtn = document.createElement('button');
            errBtn.type = 'button';
            errBtn.setAttribute('data-status-code', String(errCode));
            errBtn.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-semibold transition';
            errBtn.textContent = `${errCode} Error`;
            errBtn.addEventListener('click', () => switchResponseStatus(String(errCode)));
            pillsContainer.appendChild(errBtn);
        });

        // Default to success response
        switchResponseStatus(String(successStatus));

        // 5. cURL Snippet
        document.getElementById('op-curl-code').textContent = op.curl_command || '';
    }

    function switchResponseStatus(statusCode) {
        activeResponseCode = statusCode;
        const mod = modules[activeModuleSlug];
        const op = mod.operations[activeOperationKey];

        document.querySelectorAll('.status-code-pill').forEach(btn => {
            const c = btn.getAttribute('data-status-code');
            if (c === statusCode) {
                if (c.startsWith('2')) {
                    btn.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-bold bg-success-300 text-white shadow-sm';
                } else {
                    btn.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-bold bg-error-300 text-white shadow-sm';
                }
            } else {
                btn.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-medium bg-bgray-100 text-bgray-600 hover:bg-bgray-200 dark:bg-darkblack-600 dark:text-bgray-300';
            }
        });

        const respCodeEl = document.getElementById('op-response-code');
        if (statusCode.startsWith('2')) {
            respCodeEl.textContent = op.formatted_success_response || '{}';
            respCodeEl.className = 'text-cyan-300';
        } else {
            respCodeEl.textContent = (op.formatted_error_responses && op.formatted_error_responses[statusCode]) || '{}';
            respCodeEl.className = 'text-rose-300';
        }
    }

    // Subtab switching
    function switchSubtab(tabName) {
        activeSubtab = tabName;
        document.querySelectorAll('.op-subtab-btn').forEach(btn => {
            const t = btn.getAttribute('data-subtab');
            if (t === tabName) {
                btn.className = 'op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 border-success-300 text-success-300 dark:text-success-300';
            } else {
                btn.className = 'op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 border-transparent text-bgray-500 hover:text-bgray-700 dark:text-bgray-400 dark:hover:text-bgray-200';
            }
        });

        document.querySelectorAll('.op-subtab-pane').forEach(pane => {
            if (pane.id === `pane-${tabName}`) {
                pane.classList.remove('hidden');
            } else {
                pane.classList.add('hidden');
            }
        });
    }

    // Attach subtab click events
    document.querySelectorAll('.op-subtab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            switchSubtab(this.getAttribute('data-subtab'));
        });
    });

    // Attach module buttons click events
    document.querySelectorAll('.explorer-module-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            selectModule(this.getAttribute('data-module-slug'));
        });
    });

    // Copy Handlers
    document.getElementById('btn-copy-url').addEventListener('click', function () {
        const url = document.getElementById('op-full-url').textContent;
        copyText(url, this);
    });

    document.getElementById('btn-copy-headers').addEventListener('click', function () {
        const mod = modules[activeModuleSlug];
        const op = mod.operations[activeOperationKey];
        const headersStr = (op.headers || []).map(h => `${h.name}: ${h.value}`).join('\n');
        copyText(headersStr, this);
    });

    document.getElementById('btn-copy-payload').addEventListener('click', function () {
        const payload = document.getElementById('op-payload-code').textContent;
        copyText(payload, this);
    });

    document.getElementById('btn-copy-response').addEventListener('click', function () {
        const resp = document.getElementById('op-response-code').textContent;
        copyText(resp, this);
    });

    document.getElementById('btn-copy-curl').addEventListener('click', function () {
        const curl = document.getElementById('op-curl-code').textContent;
        copyText(curl, this);
    });

    // Initial render
    selectModule('technologies');
    switchSubtab('headers');
});
</script>
@endpush
