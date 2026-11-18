<aside class="w-full shrink-0 overflow-hidden rounded-xl border border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-500 lg:sticky lg:top-24 lg:w-64 lg:max-w-64 lg:basis-64">
    <div class="flex min-h-12 items-center justify-between border-b border-bgray-200 px-4 py-3 dark:border-darkblack-400">
        <span class="text-xs font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">
            API Modules
        </span>
        <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-bgray-600 dark:text-bgray-300">
                {{ count($explorerData['modules'] ?? []) }} Modules
            </span>
            <button type="button" id="explorer-mobile-nav-toggle" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-200 text-bgray-700 focus:outline-none focus:ring-2 focus:ring-success-300 dark:border-darkblack-400 dark:text-bgray-300 lg:hidden" aria-expanded="false" aria-controls="explorer-navigation-body" aria-label="Toggle API module navigation">
                <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m6 9 6 6 6-6" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>
    </div>

    <div id="explorer-navigation-body" class="hidden lg:block">
        <div class="border-b border-bgray-200 p-3 dark:border-darkblack-400">
            <label for="explorer-search" class="sr-only">Search API modules and endpoints</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 stroke-bgray-500" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke-width="1.7" />
                    <path d="m16.25 16.25 4 4" stroke-width="1.7" stroke-linecap="round" />
                </svg>
                <input id="explorer-search" type="search" autocomplete="off" class="h-10 w-full rounded-lg border border-bgray-200 bg-white py-2 pl-9 pr-9 text-xs text-bgray-900 placeholder:text-bgray-600 focus:border-success-300 focus:outline-none focus:ring-2 focus:ring-success-300/20 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300 dark:placeholder:text-bgray-300" placeholder="Search endpoints..." />
                <button type="button" id="explorer-search-clear" hidden class="absolute right-1.5 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded text-bgray-600 hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:text-bgray-300" aria-label="Clear endpoint search">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>

        <nav class="max-h-[28rem] space-y-5 overflow-y-auto p-3 lg:max-h-[calc(100vh-14rem)]" aria-label="API modules and endpoints">
            @foreach ($explorerData['categories'] ?? [] as $categoryKey => $category)
                @php
                    $categoryModules = array_filter($explorerData['modules'] ?? [], fn($m) => ($m['category'] ?? '') === $categoryKey);
                @endphp
                <div data-explorer-category>
                    <div class="px-2 mb-2 flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">
                            {{ $category['name'] }}
                        </span>
                        <span class="text-[10px] text-bgray-600 dark:text-bgray-300">
                            {{ count($categoryModules) }}
                        </span>
                    </div>
                    <ul class="space-y-1">
                        @foreach ($categoryModules as $modSlug => $module)
                            <li data-explorer-module-item data-module-slug="{{ $modSlug }}" data-module-search="{{ strtolower(($module['name'] ?? '') . ' ' . ($module['description'] ?? '')) }}">
                                <button type="button" class="explorer-module-btn flex min-h-10 w-full items-center justify-between rounded-lg border-l-2 border-transparent px-3 py-2.5 text-left text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-success-300" data-module-slug="{{ $modSlug }}" aria-expanded="false">
                                    <div class="flex items-center gap-2.5 truncate">
                                        @if ($modSlug === 'technologies')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                        @elseif ($modSlug === 'industries')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        @elseif ($modSlug === 'project_categories')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                        @elseif ($modSlug === 'project_statuses')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif ($modSlug === 'projects')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        @elseif ($modSlug === 'customers')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        @elseif ($modSlug === 'meetings')
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4 shrink-0 text-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        @endif
                                        <span class="truncate">{{ $module['name'] }}</span>
                                    </div>
                                    <span class="explorer-op-count text-[11px] px-1.5 py-0.5 rounded font-mono">
                                        {{ count($module['operations'] ?? []) }}
                                    </span>
                                </button>
                                <ul class="explorer-module-operations mt-1 hidden space-y-1 border-l border-bgray-200 pl-3 dark:border-darkblack-400" data-module-operations="{{ $modSlug }}">
                                    @foreach ($module['operations'] ?? [] as $operationKey => $operation)
                                        <li data-explorer-operation-item data-module-slug="{{ $modSlug }}" data-operation-key="{{ $operationKey }}" data-operation-search="{{ strtolower(($operation['name'] ?? '') . ' ' . ($operation['method'] ?? '') . ' ' . ($operation['endpoint'] ?? '') . ' ' . ($operation['description'] ?? '')) }}">
                                            <button type="button" class="explorer-sidebar-operation flex min-h-9 w-full items-center gap-2 rounded px-2 py-1.5 text-left text-xs text-bgray-600 transition hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:text-bgray-300" data-module-slug="{{ $modSlug }}" data-operation-key="{{ $operationKey }}">
                                                <span class="w-11 shrink-0 font-mono text-[10px] font-bold uppercase">{{ $operation['method'] }}</span>
                                                <span class="min-w-0 truncate">{{ $operation['name'] }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div id="explorer-search-empty" hidden class="border-t border-bgray-200 px-4 py-6 text-center dark:border-darkblack-400">
            <p class="text-xs font-medium text-bgray-700 dark:text-bgray-300">No matching endpoints found.</p>
            <button type="button" id="explorer-search-empty-clear" class="mt-2 text-xs font-semibold text-success-400 focus:outline-none focus:ring-2 focus:ring-success-300">
                Clear search
            </button>
        </div>
    </div>
</aside>

