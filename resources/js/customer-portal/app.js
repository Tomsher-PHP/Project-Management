document.addEventListener('DOMContentLoaded', () => {
    initSidebarDrawer();
    initAccountMenu();
});

function initSidebarDrawer() {
    const sidebar = document.querySelector('[data-cp-sidebar]');
    const backdrop = document.querySelector('[data-cp-sidebar-backdrop]');
    const openButton = document.querySelector('[data-cp-sidebar-open]');
    const closeButton = document.querySelector('[data-cp-sidebar-close]');

    if (!sidebar) {
        return;
    }

    const setOpen = (isOpen) => {
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        backdrop?.classList.toggle('hidden', !isOpen);
        openButton?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    openButton?.addEventListener('click', () => setOpen(true));
    closeButton?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
}

function initAccountMenu() {
    const container = document.querySelector('[data-cp-account-menu]');
    const trigger = container?.querySelector('[data-cp-account-trigger]');
    const dropdown = container?.querySelector('[data-cp-account-dropdown]');

    if (!container || !trigger || !dropdown) {
        return;
    }

    const setExpanded = (expanded) => {
        dropdown.classList.toggle('hidden', !expanded);
        trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const isHidden = dropdown.classList.contains('hidden');
        setExpanded(isHidden);
    });

    document.addEventListener('click', (event) => {
        if (!container.contains(event.target)) {
            setExpanded(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setExpanded(false);
        }
    });
}
