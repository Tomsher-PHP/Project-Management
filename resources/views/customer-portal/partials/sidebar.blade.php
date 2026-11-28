<div
    data-cp-sidebar-backdrop
    class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"
    aria-hidden="true"
></div>

<aside
    data-cp-sidebar
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:static lg:translate-x-0"
>
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
        <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-2.5">
            <img src="{{ asset(config('assets.icons.logo')) }}" class="h-8 w-auto" alt="{{ config('app.name', 'Project Management') }}" />
        </a>

        <button
            type="button"
            data-cp-sidebar-close
            aria-label="Close navigation menu"
            class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 lg:hidden"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Customer Portal navigation">
        <a
            href="{{ route('customer.dashboard') }}"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('customer.dashboard') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}"
        >
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span>Dashboard</span>
        </a>
    </nav>

    <div class="border-t border-slate-200 p-3">
        <form method="POST" action="{{ route('customer.logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-rose-50 hover:text-rose-700"
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
