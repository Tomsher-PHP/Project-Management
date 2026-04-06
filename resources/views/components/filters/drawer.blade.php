<div id="filterDrawerWrapper" class="fixed inset-0 z-50 hidden">

    <!-- Overlay -->
    <div onclick="FilterDrawer.close()" class="absolute inset-0 bg-black/40"></div>

    <!-- Drawer -->
    <div id="filterDrawer" class="absolute right-0 top-0 flex h-full w-[420px] max-w-full flex-col bg-white shadow-xl transform translate-x-full transition-transform duration-300 dark:bg-darkblack-600">
        <div class="flex shrink-0 items-center justify-between border-b px-6 py-4">
            <h3 class="text-lg font-semibold">Filters</h3>

            <button onclick="FilterDrawer.close()">✕</button>
        </div>

        <form method="GET" class="flex flex-1 flex-col overflow-hidden p-6">
            <div class="space-y-5 overflow-y-auto pr-2">
                {{ $slot }}
            </div>

            <div class="mt-5 flex shrink-0 justify-between border-t pt-4">
                <a href="{{ url()->current() }}" class="px-4 py-2 text-sm border rounded-md">
                    Reset
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-sm bg-success-300 text-sm text-white font-semibold hover:bg-success-400 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>
