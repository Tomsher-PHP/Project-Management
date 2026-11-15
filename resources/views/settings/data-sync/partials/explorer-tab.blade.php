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
