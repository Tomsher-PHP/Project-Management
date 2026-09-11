<!-- Meeting Quick Preview Right-Side Drawer Shell -->
<div id="meeting-preview-drawer" class="fixed inset-0 z-50 hidden overflow-hidden" aria-labelledby="meeting-preview-title" role="dialog" aria-modal="true">
    <!-- Backdrop overlay -->
    <div id="meeting-preview-backdrop" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity opacity-0 duration-300"></div>

    <div class="fixed inset-y-0 right-0 flex max-w-full pl-6 sm:pl-10 pointer-events-none">
        <!-- Right Drawer Panel -->
        <div id="meeting-preview-panel" class="pointer-events-auto w-screen max-w-lg sm:max-w-xl md:max-w-2xl bg-white dark:bg-darkblack-600 shadow-2xl flex flex-col h-full transform transition-transform duration-300 ease-in-out translate-x-full">
            <!-- Dynamic Drawer Body -->
            <div id="meeting-preview-body" class="flex-1 flex flex-col min-h-0 overflow-y-auto">
                <!-- Initial Loading State -->
                <div id="meeting-preview-loading" class="flex flex-col items-center justify-center py-24 text-center">
                    <svg class="h-9 w-9 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="mt-4 text-sm font-medium text-bgray-600 dark:text-bgray-300">Loading meeting details...</span>
                </div>
            </div>
        </div>
    </div>
</div>
