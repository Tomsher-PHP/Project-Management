<div id="filterDrawerWrapper" class="fixed inset-0 z-50 hidden">

    <!-- Overlay -->
    <div onclick="FilterDrawer.close()" class="absolute inset-0 bg-black/40"></div>

    <!-- Drawer -->
    <div id="filterDrawer" class="absolute right-0 top-0 h-full w-[420px] bg-white dark:bg-darkblack-600 shadow-xl transform translate-x-full transition-transform duration-300">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Filters</h3>

            <button onclick="FilterDrawer.close()">✕</button>
        </div>

        <form method="GET" class="p-6 space-y-5">
            {{ $slot }}
            <div class="flex justify-between pt-4 border-t">
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
