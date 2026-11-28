@php
    $portalCustomer = Auth::guard(config('customer_portal.authentication.guard', 'customer'))->user();
    $isDashboardActive = request()->routeIs('customer.dashboard');
    $sidebarItemActiveClass = 'text-success-400 dark:text-success-300';
    $sidebarItemInactiveClass = 'text-bgray-900 dark:text-white';
@endphp

<aside class="sidebar-wrapper fixed top-0 z-30 block h-full border-r border-bgray-200 bg-white dark:border-darkblack-500 dark:bg-darkblack-600 sm:hidden xl:block">
    <div class="sidebar-header relative z-30 flex h-[60px] w-full items-center border-b border-r border-bgray-200 pl-6 pr-8 pb-4 dark:border-darkblack-400">
        <a href="{{ route('customer.dashboard') }}" class="flex items-center">
            <span class="relative inline-flex">
                <img src="{{ asset(config('assets.icons.logo')) }}" class="block h-8 w-auto max-w-[140px] object-contain dark:hidden" alt="{{ config('app.name', 'Project Management') }}" />
                <img src="{{ asset(config('assets.icons.logo_white')) }}" class="hidden h-8 w-auto max-w-[140px] object-contain dark:block" alt="{{ config('app.name', 'Project Management') }}" />

                <span class="absolute -bottom-4 -left-1 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                    Customer Portal
                </span>
            </span>
        </a>
        <button type="button" class="drawer-btn absolute right-0 top-[8px]" title="Toggle sidebar" aria-label="Toggle sidebar">
            <span>
                <svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 10C0 4.47715 4.47715 0 10 0H16V40H10C4.47715 40 0 35.5228 0 30V10Z" fill="#22C55E" />
                    <path d="M10 15L6 20.0049L10 25.0098" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </button>
    </div>

    <div class="sidebar-body overflow-style-none relative z-30 h-screen w-full overflow-y-scroll pb-[200px] pl-8 pt-3">
        <div class="nav-wrapper mb-[36px] pr-8">
            <div class="item-wrapper mb-5">
                <ul>
                    <li class="item py-[8px] {{ $isDashboardActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                        <a href="{{ route('customer.dashboard') }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="item-ico mr-3 scale-90 inline-flex items-center justify-center">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z" />
                                            <path d="M9 22V12h6v10" />
                                        </svg>
                                    </span>
                                    <span class="item-text text-base font-medium leading-none {{ $isDashboardActive ? $sidebarItemActiveClass : '' }}">Dashboard</span>
                                </div>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</aside>
