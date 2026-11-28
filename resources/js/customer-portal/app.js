initTheme();

document.addEventListener('DOMContentLoaded', () => {
    initSidebarDrawer();
    initThemeToggle();
    initProfileDropdown();
    initCopyEmail();
});

function initTheme() {
    const savedTheme = localStorage.getItem('customer_portal_theme') || 'light';
    applyTheme(savedTheme);
}

function applyTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('customer_portal_theme', theme);
}

function initThemeToggle() {
    const toggleButtons = document.querySelectorAll('#theme-toggle, [data-cp-theme-toggle]');

    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const isDark = document.documentElement.classList.contains('dark');
            applyTheme(isDark ? 'light' : 'dark');
        });
    });
}

function initSidebarDrawer() {
    const layoutWrapper = document.getElementById('layout-wrapper');
    const drawerButtons = document.querySelectorAll('.drawer-btn');
    const asideOverlay = document.querySelector('.aside-overlay');

    if (!layoutWrapper) {
        return;
    }

    if (localStorage.getItem('cp_sidebar_state') === 'collapsed') {
        layoutWrapper.classList.remove('active');
    }

    const toggleSidebar = () => {
        layoutWrapper.classList.toggle('active');
        const state = layoutWrapper.classList.contains('active') ? 'expanded' : 'collapsed';
        localStorage.setItem('cp_sidebar_state', state);
    };

    drawerButtons.forEach((button) => {
        button.addEventListener('click', toggleSidebar);
    });

    asideOverlay?.addEventListener('click', () => {
        layoutWrapper.classList.remove('active');
        localStorage.setItem('cp_sidebar_state', 'collapsed');
    });
}

function initProfileDropdown() {
    document.querySelectorAll('[data-cp-profile-wrapper]').forEach((wrapper) => {
        const trigger = wrapper.querySelector('[data-cp-profile-trigger]');
        const profileBox = wrapper.querySelector('[data-cp-profile-box]');
        const profileOutside = wrapper.querySelector('[data-cp-profile-outside]');

        if (!trigger || !profileBox) {
            return;
        }

        const setOpen = (isOpen) => {
            profileBox.classList.toggle('hidden', !isOpen);
            profileBox.classList.toggle('block', isOpen);
            profileOutside?.classList.toggle('hidden', !isOpen);
            profileOutside?.classList.toggle('block', isOpen);
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const isCurrentlyHidden = profileBox.classList.contains('hidden');
            setOpen(isCurrentlyHidden);
        });

        profileOutside?.addEventListener('click', () => setOpen(false));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });
    });
}

function initCopyEmail() {
    document.querySelectorAll('[data-cp-copy-email]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.stopPropagation();
            const email = button.getAttribute('data-cp-copy-email');
            if (!email || !navigator.clipboard) {
                return;
            }

            try {
                await navigator.clipboard.writeText(email);
                button.setAttribute('title', 'Copied!');
                window.setTimeout(() => {
                    button.setAttribute('title', 'Copy email');
                }, 1500);
            } catch {
                // Ignore clipboard permission errors.
            }
        });
    });
}
