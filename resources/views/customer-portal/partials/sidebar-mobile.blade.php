@php
    $isDashboardActive = request()->routeIs('customer.dashboard');
    $sidebarItemActiveClass = 'text-success-400 dark:text-success-300';
    $sidebarItemInactiveClass = 'text-bgray-900 dark:text-white';
@endphp

<aside class="relative hidden w-[96px] border-r border-bgray-200 bg-white dark:border-darkblack-500 dark:bg-black sm:block">
    <div class="sidebar-wrapper-collapse relative top-0 z-30 w-full">
        <div class="sidebar-header sticky top-0 z-20 flex h-[84px] w-full items-center justify-center border-b border-r border-bgray-200 bg-white dark:border-darkblack-500 dark:bg-darkblack-600">
            <a href="{{ route('customer.dashboard') }}">
                <img src="{{ asset(config('assets.icons.logo_short')) }}" class="block h-8 w-auto dark:hidden" alt="{{ config('app.name', 'Project Management') }}" />
                <img src="{{ asset(config('assets.icons.logo_short')) }}" class="hidden h-8 w-auto dark:block" alt="{{ config('app.name', 'Project Management') }}" />
            </a>
        </div>
        <div class="sidebar-body w-full pt-[14px]">
            <div class="flex flex-col items-center">
                <div class="nav-wrapper mb-[36px]">
                    <div class="item-wrapper mb-5">
                        <ul class="mt-2.5 flex flex-col items-center justify-center">
                            <li class="item px-[43px] py-[11px] {{ $isDashboardActive ? $sidebarItemActiveClass : $sidebarItemInactiveClass }}">
                                <a href="{{ route('customer.dashboard') }}" title="Dashboard">
                                    <span class="item-ico">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z" />
                                            <path d="M9 22V12h6v10" />
                                        </svg>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
