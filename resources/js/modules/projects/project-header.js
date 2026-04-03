import Alert from '../../alert';

const getProjectHeaderStorageKey = (projectId) => `project-header-expanded:${projectId}`;

const applyProjectHeaderExpandedState = (headerCard, isExpanded) => {
    if (!headerCard) {
        return;
    }

    const expandable = headerCard.querySelector('[data-project-header-expandable]');
    const toggle = headerCard.querySelector('[data-project-header-collapse-toggle]');
    const icon = headerCard.querySelector('[data-project-header-collapse-icon]');

    if (expandable) {
        expandable.classList.toggle('hidden', !isExpanded);
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        toggle.setAttribute('aria-label', isExpanded ? 'Collapse project header details' : 'Expand project header details');
    }

    if (icon) {
        icon.style.transform = isExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
    }
};

const syncProjectHeaderExpandedState = (headerRoot = document.getElementById('project-header')) => {
    const headerCard = headerRoot?.querySelector('[data-project-header-card]');

    if (!headerCard) {
        return;
    }

    const projectId = headerCard.dataset.projectId;
    const savedState = projectId ? window.localStorage.getItem(getProjectHeaderStorageKey(projectId)) : null;
    const isExpanded = savedState === 'true';

    applyProjectHeaderExpandedState(headerCard, isExpanded);
};

const initializeProjectHeader = () => {
    if (document.body.dataset.projectHeaderInitialized === 'true') {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const closeAllMenus = (exceptDropdown = null) => {
        document.querySelectorAll('[data-project-header-dropdown]').forEach((dropdown) => {
            if (exceptDropdown && dropdown === exceptDropdown) {
                return;
            }

            dropdown.querySelector('[data-project-header-menu]')?.classList.add('hidden');
        });
    };

    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-project-header-trigger]');

        if (trigger) {
            const dropdown = trigger.closest('[data-project-header-dropdown]');
            const menu = dropdown?.querySelector('[data-project-header-menu]');

            if (!dropdown || !menu) {
                return;
            }

            const shouldOpen = menu.classList.contains('hidden');
            closeAllMenus(dropdown);
            menu.classList.toggle('hidden', !shouldOpen);
            return;
        }

        const collapseToggle = event.target.closest('[data-project-header-collapse-toggle]');

        if (collapseToggle) {
            const headerCard = collapseToggle.closest('[data-project-header-card]');

            if (!headerCard) {
                return;
            }

            const projectId = headerCard.dataset.projectId;
            const isExpanded = collapseToggle.getAttribute('aria-expanded') === 'true';
            const nextState = !isExpanded;

            applyProjectHeaderExpandedState(headerCard, nextState);

            if (projectId) {
                window.localStorage.setItem(getProjectHeaderStorageKey(projectId), nextState ? 'true' : 'false');
            }

            return;
        }

        const option = event.target.closest('[data-project-header-option]');

        if (option) {
            event.preventDefault();

            const dropdown = option.closest('[data-project-header-dropdown]');
            const field = option.dataset.field;
            const url = option.dataset.url;
            const value = option.dataset.value ?? '';
            const currentValue = option.dataset.currentValue ?? '';

            closeAllMenus();

            if (!field || !url || value === currentValue) {
                return;
            }

            option.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({
                        [field]: value === '' ? null : Number(value),
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to update this project value.');
                }

                const header = document.getElementById('project-header');

                if (header && data.project_header) {
                    header.innerHTML = data.project_header;
                    syncProjectHeaderExpandedState(header);
                }

                Alert.success(data.message || 'Project updated successfully.');
            } catch (error) {
                Alert.error(error.message || 'Unable to update this project value.');
            } finally {
                if (dropdown?.isConnected) {
                    option.disabled = false;
                }
            }

            return;
        }

        if (!event.target.closest('[data-project-header-dropdown]')) {
            closeAllMenus();
        }
    });

    document.body.dataset.projectHeaderInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', initializeProjectHeader);
document.addEventListener('DOMContentLoaded', () => syncProjectHeaderExpandedState());
