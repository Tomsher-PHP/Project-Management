<!-- Broadcasts Right-Side Drawer Shell -->
<div id="broadcast-drawer" class="fixed inset-0 z-50 hidden overflow-hidden" aria-labelledby="broadcast-drawer-title" role="dialog" aria-modal="true" data-broadcast-drawer>
    <!-- Backdrop overlay -->
    <div id="broadcast-drawer-backdrop" class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70 transition-opacity opacity-0 duration-300" data-broadcast-drawer-backdrop></div>

    <div class="fixed inset-y-0 right-0 flex max-w-full pl-6 sm:pl-10">
        <!-- Right Drawer Panel -->
        <div id="broadcast-drawer-panel" class="w-screen max-w-lg sm:max-w-xl md:max-w-2xl bg-white dark:bg-darkblack-600 shadow-2xl flex flex-col h-full transform transition-transform duration-300 ease-in-out translate-x-full" data-broadcast-drawer-panel>

            <!-- Drawer Header -->
            <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-2 dark:border-darkblack-400">
                <div>
                    <h2 id="broadcast-drawer-title" class="text-lg font-bold text-bgray-900 dark:text-white flex items-center gap-2">
                        <span>Broadcasts</span>
                    </h2>
                </div>
                <button type="button" id="close-broadcast-drawer-btn" data-broadcast-drawer-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 focus:outline-none dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" title="Close Broadcasts" aria-label="Close Broadcasts">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer Body Container -->
            <div id="broadcast-drawer-body" class="flex-1 min-h-0 overflow-y-auto p-5" data-broadcast-drawer-body>
                <!-- Initial Loading Placeholder -->
                <div id="broadcast-drawer-loading" class="flex flex-col items-center justify-center py-24 text-center" data-broadcast-drawer-loading>
                    <svg class="h-9 w-9 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="mt-4 text-sm font-medium text-bgray-700 dark:text-bgray-300">Loading Broadcasts...</span>
                </div>

                <!-- Error Container -->
                <div id="broadcast-drawer-error" class="hidden flex flex-col items-center justify-center py-20 px-4 text-center" data-broadcast-drawer-error>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400 mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h4 class="text-base font-semibold text-bgray-900 dark:text-white">Unable to Load Broadcasts</h4>
                    <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">Something went wrong while loading broadcasts.</p>
                    <button type="button" data-broadcast-drawer-retry class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-success-300 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-success-400 focus:outline-none">
                        Retry
                    </button>
                </div>

                <!-- Content Area -->
                <div id="broadcast-drawer-content" class="hidden" data-broadcast-drawer-content></div>
            </div>

        </div>
    </div>
</div>
