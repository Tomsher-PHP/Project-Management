/**
 * Project Analytics Header Filters
 * Handles User and Project filter dropdowns in the navbar.
 */

const USER_SELECT_SELECTOR = '[data-project-analytics-user-select]';
const PROJECT_SELECT_SELECTOR = '[data-project-analytics-project-select]';

export function initProjectAnalyticsFilters() {
    const userSelect = document.querySelector(USER_SELECT_SELECTOR);
    const projectSelect = document.querySelector(PROJECT_SELECT_SELECTOR);

    if (!userSelect || !projectSelect) {
        return;
    }

    const updateUrlParams = (userId, projectId) => {
        const url = new URL(window.location.href);

        if (userId) {
            url.searchParams.set('user_id', userId);
        } else {
            url.searchParams.delete('user_id');
        }

        if (projectId) {
            url.searchParams.set('project_id', projectId);
        } else {
            url.searchParams.delete('project_id');
        }

        window.history.replaceState({}, '', url);
    };

    const notifyFilterChange = (userId, projectId) => {
        updateUrlParams(userId, projectId);
        document.dispatchEvent(new CustomEvent('project-analytics:filter-changed', {
            detail: { userId, projectId }
        }));
    };

    const setBusyState = (isBusy) => {
        userSelect.disabled = isBusy;
        projectSelect.disabled = isBusy;

        if (userSelect.tomselect) {
            isBusy ? userSelect.tomselect.lock() : userSelect.tomselect.unlock();
        }

        if (projectSelect.tomselect) {
            isBusy ? projectSelect.tomselect.lock() : projectSelect.tomselect.unlock();
        }
    };

    let isValidating = false;

    // Handle user filter change
    userSelect.addEventListener('change', async () => {
        if (isValidating) return;

        const newUserId = String(userSelect.value || '').trim();

        // 1. Update the project dropdown's lazy-loading request parameters
        if (newUserId) {
            projectSelect.dataset.userId = newUserId;
        } else {
            delete projectSelect.dataset.userId;
        }

        const projectTs = projectSelect.tomselect;
        if (!projectTs) {
            notifyFilterChange(newUserId, String(projectSelect.value || '').trim());
            return;
        }

        // 2. Reset cached results so subsequent searches use the new user filter
        if (typeof projectTs.clearCache === 'function') {
            projectTs.clearCache();
        }

        const currentProjectId = String(projectTs.getValue() || '').trim();

        // When currently "All Projects", reset options and trigger change
        if (!currentProjectId) {
            projectTs.clearOptions();
            notifyFilterChange(newUserId, '');
            return;
        }

        // 3. User changed while a specific project was selected
        // When All Users is selected (newUserId is empty):
        // The project is already accessible to the logged-in user, so preserve it!
        if (!newUserId) {
            projectTs.clearCache();
            notifyFilterChange('', currentProjectId);
            return;
        }

        // When a specific user is selected:
        // Check if the current project is valid for the newly selected user
        isValidating = true;
        setBusyState(true);

        try {
            const checkUrl = `/project-analytics/search-projects?user_id=${encodeURIComponent(newUserId)}&project_id=${encodeURIComponent(currentProjectId)}`;
            const res = await fetch(checkUrl);
            if (!res.ok) {
                // Not authorized or invalid
                projectTs.clear();
                projectTs.clearOptions();
                notifyFilterChange(newUserId, '');
                return;
            }

            const data = await res.json();
            const items = Array.isArray(data) ? data : (data.data || []);
            const isValid = items.some(p => String(p.id ?? p.value) === currentProjectId);

            if (isValid) {
                // Preserve the selected project when it remains valid!
                const currentOpt = projectTs.options[currentProjectId];
                projectTs.clearOptions();
                if (currentOpt) {
                    projectTs.addOption(currentOpt);
                    projectTs.setValue(currentProjectId, true); // silent setValue
                }
                notifyFilterChange(newUserId, currentProjectId);
            } else {
                // Clear the selected project only when it is no longer valid for the newly selected user
                projectTs.clear();
                projectTs.clearOptions();
                notifyFilterChange(newUserId, '');
            }
        } catch (e) {
            console.error('Failed to validate project for user:', e);
            projectTs.clear();
            projectTs.clearOptions();
            notifyFilterChange(newUserId, '');
        } finally {
            setBusyState(false);
            isValidating = false;
        }
    });

    // Handle project filter change
    projectSelect.addEventListener('change', () => {
        if (isValidating) return;
        const currentUserId = String(userSelect.value || '').trim();
        const currentProjectId = String(projectSelect.value || '').trim();
        notifyFilterChange(currentUserId, currentProjectId);
    });
}

// Auto-initialize when ready
const start = () => {
    initProjectAnalyticsFilters();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
