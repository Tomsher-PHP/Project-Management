<div id="filterDrawerWrapper" class="fixed inset-0 z-50 hidden">

    <!-- Overlay -->
    <div onclick="FilterDrawer.close()" class="absolute inset-0 bg-black/40"></div>

    <!-- Drawer -->
    <div id="filterDrawer" class="absolute right-0 top-0 flex h-full w-[420px] max-w-full flex-col bg-white shadow-xl transform translate-x-full transition-transform duration-300 dark:bg-darkblack-600">
        <div class="flex shrink-0 items-center justify-between border-b px-6 py-4">
            <h3 class="text-lg font-semibold text-bgray-900 dark:text-bgray-300">Filters</h3>

            <button onclick="FilterDrawer.close()" class="text-bgray-900 hover:text-bgray-700 dark:text-bgray-300 dark:hover:text-bgray-100">✕</button>
        </div>

        <form method="GET" class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6 pr-8">
                {{ $slot }}
            </div>

            <div class="sticky bottom-0 flex shrink-0 justify-between border-t bg-white px-6 py-4 shadow-[0_-8px_24px_rgba(0,0,0,0.06)] dark:bg-darkblack-600">
                <a href="{{ url()->current() }}" class="px-4 py-2 text-sm border rounded-md border-bgray-300 text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:bg-darkblack-500">
                    Reset
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-sm bg-success-300 text-sm text-white font-semibold hover:bg-success-400 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>
