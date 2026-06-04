import Echo from 'laravel-echo';

export function initNotifications(userId) {
    if (!userId) return;

    window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            addNotificationToUI(notification);
            incrementNotificationCount();
        });

    initBadgeState();
}

/* ---------------- UI ---------------- */

function addNotificationToUI(notification) {
    const list = document.getElementById('notification-list');
    if (!list) return;

    const empty = document.getElementById('no-notifications');
    if (empty) empty.remove();

    const li = document.createElement('li');
    li.className = "border-b border-bgray-200 py-4 pl-6 pr-[50px] hover:bg-bgray-100";

    li.innerHTML = `
        <div class="noti-item">
            <p class="mb-1 text-sm font-medium text-bgray-600">
                <strong>${notification.title ?? 'Notification'}</strong>
                ${notification.message ?? ''}
            </p>
            <span class="text-xs font-medium text-bgray-700">Just now</span>
        </div>
    `;

    list.prepend(li);
}

/* ---------------- Badge ---------------- */

function initBadgeState() {
    const badge = document.getElementById('notification-count');
    if (!badge) return;

    const count = parseInt(badge.innerText.trim() || '0', 10);

    setNotificationCount(count);
}

function incrementNotificationCount() {
    const badge = document.getElementById('notification-count');
    if (!badge) return;

    let count = parseInt(badge.innerText || '0', 10);
    setNotificationCount(count + 1);
}

function setNotificationCount(count) {
    const badge = document.getElementById('notification-count');
    if (!badge) return;

    badge.innerText = count;

    if (count > 0) {
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}