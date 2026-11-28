@php
    $portalCustomer = Auth::guard(config('customer_portal.authentication.guard', 'customer'))->user();
    $customerInitial = strtoupper(substr((string) ($portalCustomer?->name ?? 'C'), 0, 1));
@endphp

<header class="header-wrapper fixed z-30 hidden w-full md:block">
    <div class="relative flex h-[60px] w-full items-center justify-between border-b border-bgray-200 bg-white px-8 dark:border-darkblack-500 dark:bg-darkblack-600 xl:px-10 2xl:px-12">
        <button title="Toggle sidebar" type="button" class="drawer-btn absolute left-0 top-auto rotate-180 transform" aria-label="Toggle sidebar">
            <span>
                <svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 10C0 4.47715 4.47715 0 10 0H16V40H10C4.47715 40 0 35.5228 0 30V10Z" fill="#22C55E" />
                    <path d="M10 15L6 20.0049L10 25.0098" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </button>

        <!-- Page Title -->
        <div class="flex items-center gap-3">
            <div class="space-y-1">
                <h3 class="text-lg font-bold leading-tight text-bgray-900 dark:text-bgray-50 lg:text-[28px]">
                    @yield('title', 'Dashboard')
                </h3>
            </div>
        </div>

        <!-- Quick Access & Profile Dropdown -->
        <div id="navbar-quick-access" class="quick-access-wrapper relative" data-cp-profile-wrapper>
            <div class="flex items-center space-x-7">
                <div class="hidden items-center space-x-6 lg:flex">
                    <button type="button" id="theme-toggle" class="relative flex h-5 w-5 items-center justify-center" aria-label="Toggle theme" title="Toggle light/dark mode">
                        <span class="block dark:hidden">
                            <svg class="stroke-bgray-900" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18.3284 14.8687C13.249 14.8687 9.13135 10.751 9.13135 5.67163C9.13135 4.74246 9.26914 3.84548 9.5254 3C5.74897 4.14461 3 7.65276 3 11.803C3 16.8824 7.11765 21 12.197 21C16.3472 21 19.8554 18.251 21 14.4746C20.1545 14.7309 19.2575 14.8687 18.3284 14.8687Z" stroke-width="1.5" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="hidden dark:block">
                            <svg class="stroke-bgray-900 dark:stroke-bgray-50" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="5" stroke-width="1.5" />
                                <path d="M12 2V4" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M12 20V22" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M20.6602 7L18.9281 8" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M5.07178 16L3.33973 17" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M3.33984 7L5.07189 8" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M18.9282 16L20.6603 17" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </span>
                    </button>
                </div>

                <!-- Customer Avatar Trigger -->
                <button type="button" data-cp-profile-trigger aria-expanded="false" class="flex cursor-pointer items-center space-x-0 lg:space-x-3 focus:outline-none">
                    <div class="flex h-[40px] w-[40px] items-center justify-center rounded-xl border border-bgray-300 bg-success-50 text-sm font-bold text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-success-300">
                        {{ $customerInitial }}
                    </div>
                </button>
            </div>

            <!-- Profile Dropdown (Cloned from PMS Admin Design) -->
            <div class="profile-wrapper">
                <div data-cp-profile-outside class="profile-outside fixed left-0 top-0 hidden h-full w-full"></div>
                <div data-cp-profile-box style="filter: drop-shadow(12px 12px 40px rgba(0, 0, 0, 0.08));" class="profile-box absolute right-0 top-[68px] hidden w-[300px] overflow-hidden rounded-lg bg-white dark:bg-darkblack-600">
                    <div class="relative w-full px-3 py-2">
                        <div>
                            <ul>
                                <li class="w-full">
                                    <div class="rounded-lg px-[14px] py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-xl border border-bgray-300 bg-success-50 text-lg font-bold text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-success-300">
                                                {{ $customerInitial }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-bgray-900 dark:text-white">{{ $portalCustomer?->name ?? '-' }}</p>
                                                <div class="mt-0.5 flex items-center gap-2">
                                                    <p class="truncate text-xs text-bgray-800 dark:text-bgray-300">{{ $portalCustomer?->email ?? ($portalCustomer?->username ?? '-') }}</p>
                                                    @if (!empty($portalCustomer?->email))
                                                        <button type="button" data-cp-copy-email="{{ $portalCustomer->email }}" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" aria-label="Copy email" title="Copy email">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M8 7V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M6 8H14C15.1046 8 16 8.89543 16 10V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                                <p class="mt-1 truncate text-xs font-medium uppercase tracking-wide text-success-400">
                                                    {{ $portalCustomer?->customer_code ?? 'Customer' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="my-[14px] h-[1px] w-full bg-bgray-300 dark:bg-darkblack-400"></div>
                        <div>
                            <ul>
                                <li class="w-full">
                                    <form action="{{ route('customer.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left">
                                            <div class="flex items-center space-x-[18px] rounded-lg p-[14px] text-error-300 transition hover:bg-gray-100 dark:hover:bg-darkblack-500">
                                                <div class="w-[20px]">
                                                    <span>
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M15 10L13.7071 11.2929C13.3166 11.6834 13.3166 12.3166 13.7071 12.7071L15 14M14 12L22 12M6 20C3.79086 20 2 18.2091 2 16V8C2 5.79086 3.79086 4 6 4M6 20C8.20914 20 10 18.2091 10 16V8C10 5.79086 8.20914 4 6 4M6 20H14C16.2091 20 18 18.2091 18 16M6 4H14C16.2091 4 18 5.79086 18 8" stroke="#FB2C36" stroke-width="1.5" stroke-linecap="round" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="flex-1">
                                                    <span class="text-sm font-semibold">Log Out</span>
                                                </div>
                                            </div>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
