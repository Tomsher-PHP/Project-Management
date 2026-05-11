const KANBAN_CONTROLLER_KEY = '__workspaceKanbanBoardController';
const FILTER_FORM_SELECTOR = '#filterDrawer form';
const FILTER_SCOPE_SELECTOR = '[data-workspace-kanban-filters]';
const FILTER_BUTTON_SELECTOR = '[data-workspace-kanban-filter-button]';
const FILTER_COUNT_SELECTOR = '[data-workspace-kanban-filter-count]';
const SEARCH_INPUT_SELECTOR = '[data-workspace-kanban-search]';
const SEARCH_DEBOUNCE_MS = 250;

const FILTER_KEYS = [
    'search',
    'search_condition',
    'project_id',
    'project_milestone_id',
    'project_sprint_id',
    'priority',
];

const MULTI_VALUE_FILTER_KEYS = new Set([
    'project_id',
    'project_milestone_id',
    'project_sprint_id',
    'priority',
]);

const normalizeFieldName = (name) => name.endsWith('[]') ? name.slice(0, -2) : name;

const getFilterForm = () => document.querySelector(FILTER_FORM_SELECTOR);

const getFilterScope = () => document.querySelector(FILTER_SCOPE_SELECTOR);

const getFilterButton = () => document.querySelector(FILTER_BUTTON_SELECTOR);

const getSearchInput = () => document.querySelector(SEARCH_INPUT_SELECTOR);

const serializeFormFilters = (form) => {
    const params = new URLSearchParams();

    if (!form) {
        return params;
    }

    const formData = new FormData(form);

    for (const [rawName, rawValue] of formData.entries()) {
        const name = normalizeFieldName(rawName);
        const value = String(rawValue).trim();

        if (!FILTER_KEYS.includes(name) || value === '') {
            continue;
        }

        if (name === 'search_condition' && !String(formData.get('search') || '').trim()) {
            continue;
        }

        params.append(MULTI_VALUE_FILTER_KEYS.has(name) ? `${name}[]` : name, value);
    }

    return params;
};

const buildWorkspaceKanbanParams = (form) => {
    const params = serializeFormFilters(form);
    const searchValue = String(getSearchInput()?.value || '').trim();

    if (searchValue) {
        params.set('search', searchValue);
        params.set('search_condition', 'contains');
    } else {
        params.delete('search');
        params.delete('search_condition');
    }

    return params;
};

const countActiveFilters = (params) => {
    let count = 0;

    ['project_id', 'project_milestone_id', 'project_sprint_id', 'priority'].forEach((key) => {
        if (params.getAll(`${key}[]`).length || params.getAll(key).length) {
            count += 1;
        }
    });

    return count;
};

const syncFilterButtonState = (params) => {
    const button = getFilterButton();
    const countNode = document.querySelector(FILTER_COUNT_SELECTOR);

    if (!button || !countNode) {
        return;
    }

    const activeCount = countActiveFilters(params);
    const hasActiveFilters = activeCount > 0;

    button.classList.toggle('border-success-200', hasActiveFilters);
    button.classList.toggle('bg-success-50/80', hasActiveFilters);
    button.classList.toggle('text-success-400', hasActiveFilters);
    button.classList.toggle('dark:border-success-900/30', hasActiveFilters);
    button.classList.toggle('dark:text-success-300', hasActiveFilters);
    button.classList.toggle('border-[#e7ecf5]', !hasActiveFilters);
    button.classList.toggle('bg-white', !hasActiveFilters);
    button.classList.toggle('text-[#111653]', !hasActiveFilters);
    button.classList.toggle('dark:border-darkblack-400', !hasActiveFilters);
    button.classList.toggle('dark:text-bgray-50', !hasActiveFilters);

    countNode.textContent = String(activeCount);
    countNode.classList.toggle('hidden', !hasActiveFilters);
};

const updateBrowserFilters = (params) => {
    const url = new URL(window.location.href);

    FILTER_KEYS.forEach((key) => {
        url.searchParams.delete(key);
        url.searchParams.delete(`${key}[]`);
    });

    for (const [key, value] of params.entries()) {
        url.searchParams.append(key, value);
    }

    url.searchParams.delete('page');
    window.history.replaceState({}, '', url);
};

const reloadWorkspaceKanban = () => {
    const controller = window[KANBAN_CONTROLLER_KEY];

    if (!controller?.reload) {
        return Promise.resolve(false);
    }

    return controller.reload();
};

const resetTomSelectInput = (element) => {
    if (!element) {
        return;
    }

    if (element.tomselect) {
        element.tomselect.clear(true);
        return;
    }

    if (element.multiple) {
        Array.from(element.options).forEach((option) => {
            option.selected = false;
        });
        return;
    }

    element.value = '';
};

const resetWorkspaceFilterForm = (form) => {
    form.querySelectorAll('input[name="search"]').forEach((input) => {
        input.value = '';
    });

    form.querySelectorAll('select').forEach((select) => {
        if (normalizeFieldName(select.name) === 'search_condition') {
            select.value = 'all';
            return;
        }

        resetTomSelectInput(select);
    });

    form.querySelector('select[name="project_id[]"]')?.dispatchEvent(new Event('change', { bubbles: true }));
};

document.addEventListener('DOMContentLoaded', () => {
    const scope = getFilterScope();
    const form = getFilterForm();
    const button = getFilterButton();
    const searchInput = getSearchInput();

    if (!scope || !form || !button || !searchInput) {
        return;
    }

    const resetLink = form.closest('#filterDrawerWrapper')?.querySelector('.sticky a[href]');
    let searchDebounceTimer = null;

    button.addEventListener('click', () => {
        window.FilterDrawer?.open?.();
    });

    syncFilterButtonState(buildWorkspaceKanbanParams(form));

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const params = buildWorkspaceKanbanParams(form);
        updateBrowserFilters(params);
        syncFilterButtonState(params);
        window.FilterDrawer?.close?.();
        reloadWorkspaceKanban();
    });

    resetLink?.addEventListener('click', (event) => {
        event.preventDefault();

        resetWorkspaceFilterForm(form);

        const params = buildWorkspaceKanbanParams(form);
        updateBrowserFilters(params);
        syncFilterButtonState(params);
        window.FilterDrawer?.close?.();
        reloadWorkspaceKanban();
    });

    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchDebounceTimer);

        searchDebounceTimer = window.setTimeout(() => {
            const params = buildWorkspaceKanbanParams(form);
            updateBrowserFilters(params);
            syncFilterButtonState(params);
            reloadWorkspaceKanban();
        }, SEARCH_DEBOUNCE_MS);
    });
});
