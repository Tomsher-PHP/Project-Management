@php
    $portalCustomer = Auth::guard(config('customer_portal.authentication.guard', 'customer'))->user();
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button
            type="button"
            data-cp-sidebar-open
            aria-label="Open navigation menu"
            aria-expanded="false"
            class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 lg:hidden"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900">
                {{ $portalCustomer?->name ?? 'Customer Portal' }}
            </p>
            @if (!empty($portalCustomer?->customer_code))
                <p class="text-xs text-slate-500">{{ $portalCustomer->customer_code }}</p>
            @endif
        </div>
    </div>

    <div class="relative" data-cp-account-menu>
        <button
            type="button"
            data-cp-account-trigger
            aria-expanded="false"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
        >
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                {{ strtoupper(substr((string) ($portalCustomer?->name ?? 'C'), 0, 1)) }}
            </span>
            <span class="hidden max-w-[160px] truncate sm:inline">{{ $portalCustomer?->username ?? 'Account' }}</span>
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            data-cp-account-dropdown
            class="hidden absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg"
        >
            <div class="border-b border-slate-100 px-4 py-2.5">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $portalCustomer?->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $portalCustomer?->username }}</p>
            </div>

            <form method="POST" action="{{ route('customer.logout') }}" class="p-1.5">
                @csrf
                <button
                    type="submit"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</header>
