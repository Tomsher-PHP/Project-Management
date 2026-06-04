@php
    $authUser = auth()->user();
    $notifications = $authUser->unreadNotifications->take(10); // last 10 notifications unread
    $unreadCount = $authUser->unreadNotifications->count(); // unread badge
    $userRoleName = $authUser->role_name ?? 'No Role';
    $workspaceSelectableUsers = collect($workspaceSelectableUsers ?? []);
    $workspaceSelectedUserId = (string) ($workspaceSelectedUserId ?? '');
@endphp
<header class="header-wrapper fixed z-30 hidden w-full md:block">
    <div class="relative flex h-[60px] w-full items-center justify-between border-b border-bgray-100 bg-white px-8 dark:border-darkblack-500 dark:bg-darkblack-600 xl:px-10 2xl:px-12">
        <button title="Ctrl+b" type="button" class="drawer-btn absolute left-0 top-auto rotate-180 transform">
            <span>
                <svg width="16" height="40" viewBox="0 0 16 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 10C0 4.47715 4.47715 0 10 0H16V40H10C4.47715 40 0 35.5228 0 30V10Z" fill="#22C55E" />
                    <path d="M10 15L6 20.0049L10 25.0098" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </button>
        @include('layouts.partials.navbar._page-title')

        <!-- quick access-->
        <div id="navbar-quick-access" class="quick-access-wrapper relative">
            @include('layouts.partials.navbar._top-actions')
            @include('layouts.partials.navbar._notifications-dropdown')
            @include('layouts.partials.navbar._profile-dropdown')
        </div>
    </div>
</header>

@include('layouts.partials.navbar._scripts')

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const quickAccessWrapper = document.getElementById('navbar-quick-access');
        const notificationButton = document.getElementById('notification-btn');
        const notificationBox = document.getElementById('notification-box');

        if (!quickAccessWrapper || !notificationButton || !notificationBox) {
            return;
        }

        const positionNotificationDropdown = () => {
            const wrapperRect = quickAccessWrapper.getBoundingClientRect();
            const buttonRect = notificationButton.getBoundingClientRect();
            const boxWidth = parseFloat(window.getComputedStyle(notificationBox).width) || notificationBox.offsetWidth || 400;
            const viewportPadding = 16;

            const preferredLeft = buttonRect.right - wrapperRect.left - boxWidth;
            const minLeft = viewportPadding - wrapperRect.left;
            const maxLeft = window.innerWidth - wrapperRect.left - boxWidth - viewportPadding;
            const nextLeft = Math.min(Math.max(preferredLeft, minLeft), maxLeft);

            notificationBox.style.left = `${nextLeft}px`;
            notificationBox.style.top = '68px';
        };

        positionNotificationDropdown();
        window.addEventListener('resize', positionNotificationDropdown);

        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(() => {
                positionNotificationDropdown();
            });

            resizeObserver.observe(quickAccessWrapper);
            resizeObserver.observe(notificationButton);
        }
    });
</script>
