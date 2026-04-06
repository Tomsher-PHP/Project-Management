import Alert from '../../alert';
import { initDatepicker } from '../../components/datepicker';
import { initializeEstimatedTimeInputs } from '../../components/estimated-time-input';
import { initTomSelect } from '../../components/tom-select';

const replaceRenderedSection = (response) => {
    if (!response.html || !response.render_target) {
        return false;
    }

    const currentTarget = document.querySelector(response.render_target);

    if (!currentTarget) {
        return false;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = response.html.trim();
    const newRoot = wrapper.firstElementChild;

    if (!newRoot) {
        return false;
    }

    currentTarget.replaceWith(newRoot);

    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        window.Alpine.initTree(newRoot);
    }

    document.dispatchEvent(new CustomEvent('ajax-form:rendered', {
        detail: { root: newRoot, selector: response.render_target },
    }));

    return true;
};

const escapeHtml = (value = '') => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const renderSelectOptions = (items, selectedValue, placeholder) => {
    const selected = selectedValue == null ? '' : String(selectedValue);
    const options = [`<option value="">${escapeHtml(placeholder)}</option>`];

    items.forEach((item) => {
        options.push(
            `<option value="${escapeHtml(item.id)}" ${selected === String(item.id) ? 'selected' : ''}>${escapeHtml(item.name)}</option>`
        );
    });

    return options.join('');
};

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const formatPickerDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const getTodayDate = () => {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
};

const getCurrentWeekRange = () => {
    const today = getTodayDate();
    const startOfWeek = new Date(today);
    const dayOfWeek = today.getDay();
    const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;

    startOfWeek.setDate(today.getDate() + diffToMonday);

    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    const effectiveStart = startOfWeek < today ? today : startOfWeek;

    return {
        minDate: formatPickerDate(today),
        startDate: formatPickerDate(effectiveStart),
        endDate: formatPickerDate(endOfWeek),
    };
};

const applyMinDateToRangeInput = (rangeInput) => {
    if (!rangeInput) {
        return;
    }

    const { minDate } = getCurrentWeekRange();
    rangeInput.dataset.minDate = minDate;

    if (rangeInput._flatpickr) {
        rangeInput._flatpickr.set('minDate', minDate);
    }
};

const renderEstimatedTimeInput = (totalMinutes = 0) => {
    const normalizedTotalMinutes = Math.max(0, Number.parseInt(totalMinutes || 0, 10) || 0);
    const hours = Math.floor(normalizedTotalMinutes / 60);
    const minutes = normalizedTotalMinutes % 60;

    return `
        <div class="flex flex-col gap-2" data-estimated-time>
            <input type="hidden" name="estimated_time_minutes" value="${escapeHtml(normalizedTotalMinutes)}" data-estimated-total-minutes>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-2 block text-left text-xs font-medium uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">Hours</label>
                    <input type="number" min="0" step="1" value="${escapeHtml(hours)}" data-estimated-hours class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>
                <div>
                    <label class="mb-2 block text-left text-xs font-medium uppercase tracking-[0.15em] text-bgray-500 dark:text-bgray-300">Minutes</label>
                    <input type="number" min="0" step="1" value="${escapeHtml(minutes)}" data-estimated-extra-minutes class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>
            </div>
            <p class="text-xs text-bgray-500 dark:text-bgray-300">Enter time naturally. We’ll convert it automatically for calculation.</p>
        </div>
    `;
};

const requestFormJson = async (url, formData, csrfToken = getCsrfToken()) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: formData,
    });

    const result = await response.json();

    if (!response.ok || result.status === false || result.success === false) {
        const error = new Error(result.message || 'Unable to save the library item.');
        error.payload = result;
        throw error;
    }

    return result;
};

const clearInlineFormErrors = (form, errorAttribute) => {
    if (!form) {
        return;
    }

    form.querySelectorAll(`[${errorAttribute}]`).forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.classList.remove('border-red-500');
    });
};

const applyInlineFormErrors = (form, errors, errorAttribute) => {
    clearInlineFormErrors(form, errorAttribute);

    Object.entries(errors || {}).forEach(([fieldName, messages]) => {
        const input = form.querySelector(`[name="${fieldName}"]`);
        const errorNode = form.querySelector(`[${errorAttribute}="${fieldName}"]`);

        input?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const highlightLibraryItem = (item, scrollContainer = null) => {
    if (!item) {
        return;
    }

    item.classList.add('ring-2', 'ring-success-300', 'dark:ring-success-900/40');
    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (scrollContainer) {
        scrollContainer.scrollTo({
            top: scrollContainer.scrollHeight,
            behavior: 'smooth',
        });
    }

    window.setTimeout(() => {
        item.classList.remove('ring-2', 'ring-success-300', 'dark:ring-success-900/40');
    }, 1800);
};

const projectModuleSprintPayloadCache = new Map();
const projectModuleSprintRequestCache = new Map();
const projectModuleSprintPaginationObservers = new WeakMap();

const getProjectModuleSectionRoot = () => document.querySelector('[data-project-module-section]');

const getProjectModuleSprintsPanel = (moduleId, root = getProjectModuleSectionRoot()) => {
    if (!root || !moduleId) {
        return null;
    }

    return root.querySelector(`[data-project-module-sprints-panel][data-module-id="${moduleId}"]`);
};

const getProjectModuleDeepLinkState = () => {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    const moduleId = Number(params.get('module') || 0) || null;
    const sprintId = Number(params.get('sprint') || 0) || null;

    return {
        tab,
        moduleId,
        sprintId,
    };
};

const waitForNextFrame = () => new Promise((resolve) => {
    window.requestAnimationFrame(() => resolve());
});

const handleProjectModuleDeepLink = async (section = getProjectModuleSectionRoot()) => {
    if (!section) {
        return;
    }

    const { tab, moduleId, sprintId } = getProjectModuleDeepLinkState();

    if (tab !== 'modules' || !moduleId) {
        return;
    }

    const deepLinkKey = `${moduleId}:${sprintId || ''}`;

    if (section.dataset.deepLinkHandled === deepLinkKey) {
        return;
    }

    const moduleCard = section.querySelector(`[data-project-module-card][data-module-id="${moduleId}"]`);
    const moduleToggle = section.querySelector(`[data-project-module-toggle][data-module-id="${moduleId}"]`);
    const sprintPanel = getProjectModuleSprintsPanel(moduleId, section);

    if (!moduleCard || !moduleToggle || !sprintPanel) {
        return;
    }

    section.dataset.deepLinkHandled = deepLinkKey;

    const panelContainer = sprintPanel.parentElement;
    const isExpanded = Boolean(panelContainer && panelContainer.offsetParent !== null);

    if (!isExpanded) {
        moduleToggle.click();
        await waitForNextFrame();
        await waitForNextFrame();
    }

    moduleCard.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });

    if (!sprintId) {
        return;
    }

    try {
        await fetchProjectModuleSprints(moduleId, {
            root: section,
            all: true,
        });

        await waitForNextFrame();

        const sprintCard = section.querySelector(`[data-project-sprint-card][data-project-sprint-id="${sprintId}"]`);

        sprintCard?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    } catch (error) {
        delete section.dataset.deepLinkHandled;
        throw error;
    }
};

const renderProjectModuleSprintsState = (message, extraClasses = '') => `
    <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-5 py-6 text-center dark:border-darkblack-400 dark:bg-darkblack-600 ${extraClasses}" data-project-module-sprints-state>
        <p class="text-sm font-medium text-bgray-600 dark:text-bgray-100">${escapeHtml(message)}</p>
    </div>
`;

const getProjectModuleSprintCacheKey = (moduleId, { page = 1, all = false } = {}) => `${Number(moduleId)}:${all ? 'all' : `page-${Number(page) || 1}`}`;

const clearProjectModuleSprintCache = (moduleId = null) => {
    if (moduleId == null) {
        projectModuleSprintPayloadCache.clear();
        projectModuleSprintRequestCache.clear();
        return;
    }

    const cachePrefix = `${Number(moduleId)}:`;

    Array.from(projectModuleSprintPayloadCache.keys()).forEach((key) => {
        if (key.startsWith(cachePrefix)) {
            projectModuleSprintPayloadCache.delete(key);
        }
    });

    Array.from(projectModuleSprintRequestCache.keys()).forEach((key) => {
        if (key.startsWith(cachePrefix)) {
            projectModuleSprintRequestCache.delete(key);
        }
    });
};

const syncProjectSprintListReorderState = (sprintList) => {
    if (!sprintList) {
        return;
    }

    const allPagesLoaded = sprintList.dataset.allPagesLoaded === 'true';

    sprintList.querySelectorAll('[data-project-sprint-drag-handle]').forEach((handle) => {
        handle.disabled = !allPagesLoaded;
        handle.classList.toggle('cursor-move', allPagesLoaded);
        handle.classList.toggle('cursor-not-allowed', !allPagesLoaded);
        handle.classList.toggle('opacity-50', !allPagesLoaded);

        if (allPagesLoaded) {
            handle.removeAttribute('title');
            return;
        }

        handle.setAttribute('title', 'Scroll to load all sprints before reordering');
    });
};

const updateProjectModuleSprintCount = (moduleId, count, root = getProjectModuleSectionRoot()) => {
    const card = root?.querySelector(`[data-project-module-card][data-module-id="${moduleId}"]`);

    card?.querySelector('[data-project-module-sprint-count]')?.replaceChildren(document.createTextNode(String(count)));
};

const initializeProjectModuleSprintPagination = (panel, root = getProjectModuleSectionRoot()) => {
    if (!panel) {
        return;
    }

    const existingObserver = projectModuleSprintPaginationObservers.get(panel);

    if (existingObserver) {
        existingObserver.disconnect();
        projectModuleSprintPaginationObservers.delete(panel);
    }

    const sentinel = panel.querySelector('[data-project-sprint-pagination-sentinel]');

    if (!sentinel || panel.dataset.hasMorePages !== 'true') {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        const hasVisibleEntry = entries.some((entry) => entry.isIntersecting);

        if (!hasVisibleEntry || panel.dataset.loadingMore === 'true') {
            return;
        }

        const nextPage = Number(panel.dataset.nextPage || 0);
        const moduleId = Number(panel.dataset.moduleId || 0);

        if (!nextPage || !moduleId) {
            return;
        }

        fetchProjectModuleSprints(moduleId, {
            page: nextPage,
            append: true,
            root,
        }).catch((error) => {
            Alert.error(error.message || 'Unable to load more sprints.');
        });
    }, {
        threshold: 0.1,
    });

    observer.observe(sentinel);
    projectModuleSprintPaginationObservers.set(panel, observer);
};

const applyProjectModuleSprintsPayload = (moduleId, payload, root = getProjectModuleSectionRoot(), { append = false } = {}) => {
    const panel = getProjectModuleSprintsPanel(moduleId, root);

    if (!panel) {
        return;
    }

    if (!append) {
        panel.innerHTML = payload.html || renderProjectModuleSprintsState('No sprints added under this module yet.');
    } else {
        const sprintList = panel.querySelector('[data-project-sprint-list]');

        if (sprintList && payload.items_html) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = payload.items_html;

            Array.from(wrapper.children).forEach((child) => {
                sprintList.appendChild(child);
            });
        }
    }

    panel.dataset.loaded = 'true';
    panel.dataset.currentPage = String(payload.pagination?.page || 1);
    panel.dataset.nextPage = payload.pagination?.next_page ? String(payload.pagination.next_page) : '';
    panel.dataset.hasMorePages = payload.pagination?.has_more_pages ? 'true' : 'false';
    delete panel.dataset.loadingMore;

    if (typeof payload.count === 'number') {
        updateProjectModuleSprintCount(moduleId, payload.count, root);
    }

    const sprintList = panel.querySelector('[data-project-sprint-list]');

    if (sprintList) {
        sprintList.dataset.currentPage = String(payload.pagination?.page || 1);
        sprintList.dataset.allPagesLoaded = payload.pagination?.all_pages_loaded ? 'true' : 'false';
    }

    if (panel.dataset.hasMorePages !== 'true') {
        panel.querySelector('[data-project-sprint-pagination-sentinel]')?.remove();
        panel.querySelector('[data-project-sprint-pagination-loading]')?.remove();
    }

    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        window.Alpine.initTree(panel);
    }

    panel.querySelectorAll('[data-project-sprint-list]').forEach((sprintList) => {
        syncProjectSprintListReorderState(sprintList);
        initializeProjectSprintList(sprintList);
    });

    initializeProjectModuleSprintPagination(panel, root);
};

const fetchProjectModuleSprints = async (moduleId, { force = false, loadUrl = '', root = getProjectModuleSectionRoot(), page = 1, append = false, all = false } = {}) => {
    const normalizedModuleId = Number(moduleId) || null;

    if (!normalizedModuleId) {
        throw new Error('Unable to determine which module should load sprints.');
    }

    const panel = getProjectModuleSprintsPanel(normalizedModuleId, root);
    const resolvedLoadUrl = loadUrl || panel?.dataset.loadUrl || '';

    if (!resolvedLoadUrl) {
        throw new Error('Unable to load sprints for this module.');
    }

    const cacheKey = getProjectModuleSprintCacheKey(normalizedModuleId, { page, all });

    if (!force && projectModuleSprintPayloadCache.has(cacheKey)) {
        const cachedPayload = projectModuleSprintPayloadCache.get(cacheKey);

        if (!append) {
            applyProjectModuleSprintsPayload(normalizedModuleId, cachedPayload, root);
        }

        return cachedPayload;
    }

    if (projectModuleSprintRequestCache.has(cacheKey)) {
        return projectModuleSprintRequestCache.get(cacheKey);
    }

    if (panel && !append && panel.dataset.loaded !== 'true') {
        panel.innerHTML = renderProjectModuleSprintsState('Loading sprints...');
    }

    if (panel && append) {
        panel.dataset.loadingMore = 'true';
        panel.querySelector('[data-project-sprint-pagination-loading]')?.removeAttribute('hidden');
    }

    const requestUrl = new URL(resolvedLoadUrl, window.location.origin);

    if (all) {
        requestUrl.searchParams.set('all', '1');
    } else {
        requestUrl.searchParams.set('page', String(page));
    }

    const request = fetch(requestUrl.toString(), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    }).then(async (response) => {
        const result = await response.json();

        if (!response.ok || result.status === false || result.success === false) {
            throw new Error(result.message || 'Unable to load project sprints.');
        }

        projectModuleSprintPayloadCache.set(cacheKey, result);
        applyProjectModuleSprintsPayload(normalizedModuleId, result, root, { append });

        return result;
    }).catch((error) => {
        if (panel && !append && panel.dataset.loaded !== 'true') {
            panel.innerHTML = renderProjectModuleSprintsState('Unable to load sprints right now.', 'border-red-200 bg-red-50 dark:border-red-900/30 dark:bg-darkblack-600');
        }

        throw error;
    }).finally(() => {
        if (panel && append) {
            panel.querySelector('[data-project-sprint-pagination-loading]')?.setAttribute('hidden', 'hidden');
            delete panel.dataset.loadingMore;
        }

        projectModuleSprintRequestCache.delete(cacheKey);
    });

    projectModuleSprintRequestCache.set(cacheKey, request);

    return request;
};

const renderModuleBuilderCard = (module, config, extraClass = '') => `
    <article class="select-text rounded-none border bg-white p-4 shadow-sm dark:bg-darkblack-600 ${extraClass}" style="border-color: ${escapeHtml(module.color || '#E5E7EB')};" data-project-module-builder-card data-module-id="${module.id ?? ''}" data-module-name="${escapeHtml(module.name || '')}" data-expanded="false" draggable="false">
        <input type="hidden" name="color" value="${escapeHtml(module.color || '#22C55E')}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <button type="button" class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-project-module-builder-drag-handle>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                    </svg>
                </button>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex h-3.5 w-3.5 rounded-sm" data-project-module-builder-color-dot style="background-color: ${escapeHtml(module.color || '#22C55E')}"></span>
                        <h5 class="text-base font-semibold text-bgray-900 dark:text-white" data-project-module-builder-title>${escapeHtml(module.name || 'New Module')}</h5>
                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200" data-project-module-builder-order>${escapeHtml(module.sort_order || '')}</span>
                    </div>
                    <p class="mt-2 text-xs font-medium text-bgray-500 dark:text-bgray-300" data-project-module-builder-status>Saved</p>
                </div>
            </div>

        <div class="flex items-center gap-2">
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-500 transition duration-200 hover:border-red-300 hover:bg-red-100 dark:border-red-900/40 dark:bg-darkblack-500 dark:text-red-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400" data-project-module-builder-delete aria-label="Delete module" title="Delete module">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-module-builder-toggle aria-label="Expand module" title="Expand module">
                <svg class="h-4 w-4 rotate-180 transition duration-200" viewBox="0 0 20 20" fill="currentColor" data-project-module-builder-toggle-icon>
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

        <div class="mt-4 hidden border-t border-bgray-100 pt-4 dark:border-darkblack-400" data-project-module-builder-body>
            <div class="grid gap-4 xl:grid-cols-2">
            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="${escapeHtml(module.name || '')}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Owner</label>
                <select name="owner_id" class="tom-select w-full" data-sort="0">
                    ${renderSelectOptions(config.owners || [], module.owner_id, 'Select owner')}
                </select>
            </div>

            <div>
                ${renderEstimatedTimeInput(module.estimated_time_minutes ?? 0)}
            </div>

            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Date Range</label>
                <input type="text" value="${escapeHtml([module.start_date, module.end_date].filter(Boolean).join(' to '))}" class="datepicker project-module-date-range w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="range" data-format="Y-m-d" data-min-date="${escapeHtml(getCurrentWeekRange().minDate)}" data-project-module-builder-date-range>
                <input type="hidden" name="start_date" value="${escapeHtml(module.start_date || '')}">
                <input type="hidden" name="end_date" value="${escapeHtml(module.end_date || '')}">
            </div>

            <div class="xl:col-span-2">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label class="block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Description</label>
                    <span class="text-[11px] font-medium text-bgray-400 dark:text-bgray-300"><span data-project-module-builder-description-count>${escapeHtml(String((module.description || '').length))}</span>/100</span>
                </div>
                <textarea name="description" rows="2" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">${escapeHtml(module.description || '')}</textarea>
            </div>
            </div>
        </div>
    </article>
`;

const renderModuleLibraryCard = (libraryModule, extraClass = '') => `
    <article class="cursor-grab rounded-none border border-bgray-200 bg-white p-4 shadow-sm transition duration-200 hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600 dark:hover:border-success-300 ${extraClass}" draggable="true" data-project-module-library-item data-library-module-id="${escapeHtml(libraryModule.id ?? '')}" data-name="${escapeHtml(libraryModule.name || '')}" data-color="${escapeHtml(libraryModule.color || '#22C55E')}" data-description="${escapeHtml(libraryModule.description || '')}" data-sort-order="${escapeHtml(libraryModule.sort_order ?? '')}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3.5 w-3.5 rounded-sm" style="background-color: ${escapeHtml(libraryModule.color || '#22C55E')}"></span>
                    <h5 class="truncate text-sm font-semibold text-bgray-900 dark:text-white">
                        ${escapeHtml(libraryModule.name || 'New Module')}
                    </h5>
                </div>
                <p class="mt-2 text-xs leading-5 text-bgray-500 dark:text-bgray-300">
                    ${escapeHtml(libraryModule.description || 'No library description added yet.')}
                </p>
            </div>

            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8M8 17h8M5 7h.01M5 12h.01M5 17h.01" />
                </svg>
            </span>
        </div>
    </article>
`;

const initializeProjectModuleBuilderModal = () => {
    const modal = document.getElementById('project-module-modal');

    if (!modal || modal.dataset.projectModuleBuilderInitialized === 'true') {
        return;
    }

    const configNode = document.getElementById('project-module-builder-config');
    const workspace = modal.querySelector('[data-project-module-builder-workspace]');
    const library = modal.querySelector('[data-project-module-builder-library]');
    const libraryScrollContainer = modal.querySelector('[data-project-module-builder-library-scroll]');
    const searchInput = modal.querySelector('[data-project-module-builder-library-search]');
    const resetSearchButton = modal.querySelector('[data-project-module-builder-reset-search]');
    const countBadge = modal.querySelector('[data-project-module-builder-count]');
    const libraryCreateModal = document.getElementById('project-module-library-create-modal');
    const libraryCreateForm = libraryCreateModal?.querySelector('[data-project-module-library-create-form]') || null;
    const libraryCreateSubmitButton = libraryCreateModal?.querySelector('[data-project-module-library-create-submit]') || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!configNode || !workspace || !library || !csrfToken) {
        modal.dataset.projectModuleBuilderInitialized = 'true';
        return;
    }

    const config = JSON.parse(configNode.textContent || '{}');
    let draggedLibraryModule = null;
    let draggedWorkspaceCard = null;
    let handleCard = null;
    const cardTimers = new Map();
    const showModalSuccess = (message, title = 'Success') => Alert.success(message, title, { target: modal });
    const showModalError = (message, title = 'Error') => Alert.error(message, title, { target: modal });

    const getLibrarySortOrders = () => Array.from(library.querySelectorAll('[data-project-module-library-item]'))
        .map((item) => Number(item.dataset.sortOrder) || 0);

    const getNextLibrarySortOrder = () => Math.max(
        Number(config.nextLibrarySortOrder) || 0,
        (Math.max(0, ...getLibrarySortOrders()) || 0) + 1,
        1
    );

    const syncLibraryDescriptionCount = () => {
        if (!libraryCreateForm) {
            return;
        }

        const textarea = libraryCreateForm.querySelector('textarea[name="description"]');
        const countNode = libraryCreateForm.querySelector('[data-project-module-library-description-count]');

        if (!textarea || !countNode) {
            return;
        }

        countNode.textContent = String(textarea.value.length);
    };

    const resetLibraryCreateForm = () => {
        if (!libraryCreateForm) {
            return;
        }

        libraryCreateForm.reset();
        libraryCreateForm.querySelector('[name="color"]')?.setAttribute('value', '#22C55E');
        const colorInput = libraryCreateForm.querySelector('[name="color"]');

        if (colorInput) {
            colorInput.value = '#22C55E';
        }

        const sortOrderInput = libraryCreateForm.querySelector('[name="sort_order"]');

        if (sortOrderInput) {
            sortOrderInput.value = String(getNextLibrarySortOrder());
        }

        clearInlineFormErrors(libraryCreateForm, 'data-project-module-library-create-error');
        syncLibraryDescriptionCount();
    };

    const openLibraryCreateModal = () => {
        if (!libraryCreateModal) {
            return;
        }

        resetLibraryCreateForm();
        libraryCreateModal.classList.remove('hidden');
        libraryCreateForm?.querySelector('[name="name"]')?.focus();
    };

    const closeLibraryCreateModal = () => {
        if (!libraryCreateModal) {
            return;
        }

        libraryCreateModal.classList.add('hidden');
        resetLibraryCreateForm();
    };

    const appendLibraryModuleItem = (libraryModule) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = renderModuleLibraryCard(libraryModule);
        const item = wrapper.firstElementChild;

        if (!item) {
            return null;
        }

        library.appendChild(item);
        config.nextLibrarySortOrder = Math.max(
            Number(config.nextLibrarySortOrder) || 0,
            (Number(libraryModule.sort_order) || 0) + 1
        );

        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }

        highlightLibraryItem(item, libraryScrollContainer);

        return item;
    };

    const syncCardDateRange = (card) => {
        const rangeInput = card.querySelector('[data-project-module-builder-date-range]');
        const startInput = card.querySelector('[name="start_date"]');
        const endInput = card.querySelector('[name="end_date"]');

        if (!rangeInput || !startInput || !endInput) {
            return;
        }

        const value = rangeInput.value.trim();

        if (!value) {
            startInput.value = '';
            endInput.value = '';
            return;
        }

        const [startDate = '', endDate = ''] = value.split(' to ').map((item) => item.trim());
        startInput.value = startDate || '';
        endInput.value = endDate || '';
    };

    const initializeCardDatepicker = (card) => {
        applyMinDateToRangeInput(card.querySelector('[data-project-module-builder-date-range]'));
        initDatepicker('.project-module-date-range', {}, card);
        syncCardDateRange(card);
    };

    const initializeCardTomSelect = (card) => {
        initTomSelect(card);
    };

    const initializeCardEstimatedTime = (card) => {
        initializeEstimatedTimeInputs(card);
    };

    const getSectionModuleSource = () => {
        const sourceNode = document.querySelector('[data-project-module-section] [data-project-module-builder-source]');

        if (!sourceNode) {
            return null;
        }

        try {
            const parsed = JSON.parse(sourceNode.textContent || '[]');
            return Array.isArray(parsed) ? parsed : null;
        } catch (error) {
            return null;
        }
    };

    const openModal = () => {
        const latestModules = getSectionModuleSource();

        if (latestModules) {
            renderWorkspaceFromModules(latestModules);
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const getCards = () => Array.from(workspace.querySelectorAll('[data-project-module-builder-card]'));
    const getHelper = () => workspace.querySelector('[data-project-module-builder-helper]');
    const getDropzone = () => workspace.querySelector('[data-project-module-builder-dropzone]');
    const helperMarkup = `
        <div class="flex items-center gap-3 rounded-2xl border border-dashed border-success-200/80 bg-white/75 px-4 py-3 text-success-500 dark:border-success-900/40 dark:bg-darkblack-600/60 dark:text-success-300" data-project-module-builder-helper>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-success-50 text-success-500 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold">Drag here for more modules</p>
                <p class="text-xs text-bgray-500 dark:text-bgray-300">Drop another library item anywhere in this workspace to add it to the project.</p>
            </div>
        </div>
    `;
    const dropzoneMarkup = `
        <div class="h-24 rounded-2xl border border-dashed border-bgray-200/70 bg-bgray-50/40 dark:border-darkblack-400/60 dark:bg-darkblack-500/20" data-project-module-builder-dropzone></div>
    `;

    const createWorkspaceGuide = (markup) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = markup.trim();
        return wrapper.firstElementChild;
    };

    const syncWorkspaceGuides = () => {
        const cards = getCards();
        let helper = getHelper();
        let dropzone = getDropzone();

        if (!cards.length) {
            helper?.remove();
            dropzone?.remove();
            return;
        }

        if (!helper) {
            helper = createWorkspaceGuide(helperMarkup);
        }

        if (!dropzone) {
            dropzone = createWorkspaceGuide(dropzoneMarkup);
        }

        workspace.appendChild(helper);
        workspace.appendChild(dropzone);
    };

    const renderWorkspaceFromModules = (modules) => {
        workspace.innerHTML = '';

        modules.forEach((module) => {
            const cardWrapper = document.createElement('div');
            cardWrapper.innerHTML = renderModuleBuilderCard(module, config);
            const card = cardWrapper.firstElementChild;

            if (!card) {
                return;
            }

            appendCardToWorkspace(card);
            initializeCardDatepicker(card);
            initializeCardTomSelect(card);
            initializeCardEstimatedTime(card);
            syncDescriptionCount(card);
        });

        ensureEmptyState();
        getCards().forEach((card) => setCardExpanded(card, false));
    };

    const appendCardToWorkspace = (card) => {
        syncWorkspaceGuides();
        const dropzone = getDropzone();

        if (dropzone) {
            workspace.insertBefore(card, dropzone);
            return;
        }

        workspace.appendChild(card);
    };

    const updateCount = () => {
        if (countBadge) {
            countBadge.textContent = String(getCards().length);
        }
    };

    const ensureEmptyState = () => {
        const cards = getCards();
        const emptyState = workspace.querySelector('[data-project-module-builder-empty]');

        if (cards.length && emptyState) {
            emptyState.remove();
        }

        if (!cards.length && !emptyState) {
            workspace.innerHTML = `
                <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600" data-project-module-builder-empty>
                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                    <h5 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">No Modules Selected Yet</h5>
                    <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                        Drag one or more items from the module library to start building this project workspace.
                    </p>
                </div>
            `;
        }

        updateCount();
        syncOrderBadges();
        syncWorkspaceGuides();
    };

    const setCardStatus = (card, status, classes = '') => {
        const statusNode = card.querySelector('[data-project-module-builder-status]');

        if (!statusNode) {
            return;
        }

        statusNode.className = `mt-2 text-xs font-medium ${classes || 'text-bgray-500 dark:text-bgray-300'}`;
        statusNode.textContent = status;
    };

    const syncCardTitle = (card) => {
        const title = card.querySelector('[data-project-module-builder-title]');
        const nameInput = card.querySelector('[name="name"]');

        if (title && nameInput) {
            const nextTitle = nameInput.value.trim() || 'Untitled Module';
            title.textContent = nextTitle;
            card.dataset.moduleName = nextTitle;
        }
    };

    const syncDescriptionCount = (card) => {
        const countNode = card.querySelector('[data-project-module-builder-description-count]');
        const textarea = card.querySelector('[name="description"]');

        if (!countNode || !textarea) {
            return;
        }

        countNode.textContent = String(textarea.value.length);
    };

    const syncOrderBadges = () => {
        getCards().forEach((card, index) => {
            const badge = card.querySelector('[data-project-module-builder-order]');

            if (badge) {
                badge.textContent = String(index + 1);
            }
        });
    };

    const setCardExpanded = (card, expanded) => {
        const body = card.querySelector('[data-project-module-builder-body]');
        const icon = card.querySelector('[data-project-module-builder-toggle-icon]');
        const toggleButton = card.querySelector('[data-project-module-builder-toggle]');

        if (expanded) {
            getCards().forEach((item) => {
                if (item !== card) {
                    item.dataset.expanded = 'false';
                    item.querySelector('[data-project-module-builder-body]')?.classList.add('hidden');
                    item.querySelector('[data-project-module-builder-toggle-icon]')?.classList.add('rotate-180');
                    item.querySelector('[data-project-module-builder-toggle]')?.setAttribute('aria-label', 'Expand module');
                    item.querySelector('[data-project-module-builder-toggle]')?.setAttribute('title', 'Expand module');
                }
            });
        }

        card.dataset.expanded = expanded ? 'true' : 'false';

        if (body) {
            body.classList.toggle('hidden', !expanded);
        }

        if (icon) {
            icon.classList.toggle('rotate-180', !expanded);
        }

        if (toggleButton) {
            const label = expanded ? 'Collapse module' : 'Expand module';
            toggleButton.setAttribute('aria-label', label);
            toggleButton.setAttribute('title', label);
        }
    };

    const collectCardPayload = (card) => ({
        name: card.querySelector('[name="name"]')?.value.trim() || '',
        owner_id: card.querySelector('[name="owner_id"]')?.value || '',
        estimated_time_minutes: card.querySelector('[name="estimated_time_minutes"]')?.value || 0,
        color: card.querySelector('[name="color"]')?.value || '',
        description: card.querySelector('[name="description"]')?.value || '',
        start_date: card.querySelector('[name="start_date"]')?.value || '',
        end_date: card.querySelector('[name="end_date"]')?.value || '',
    });

    const normalizePayload = (payload) => {
        const normalized = { ...payload };

        if ('owner_id' in normalized) {
            normalized.owner_id = normalized.owner_id || null;
        }

        if ('estimated_time_minutes' in normalized) {
            normalized.estimated_time_minutes = Number.parseInt(normalized.estimated_time_minutes || 0, 10) || 0;
        }

        if ('start_date' in normalized) {
            normalized.start_date = normalized.start_date || null;
        }

        if ('end_date' in normalized) {
            normalized.end_date = normalized.end_date || null;
        }

        if ('status_id' in normalized) {
            normalized.status_id = normalized.status_id || null;
        }

        if ('completed_at' in normalized) {
            normalized.completed_at = normalized.completed_at || null;
        }

        if ('description' in normalized) {
            normalized.description = normalized.description || null;
        }

        return normalized;
    };

    const requestJson = async (url, method, payload) => {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (!response.ok || result.status === false || result.success === false) {
            const error = new Error(result.message || 'Unable to save the project module.');
            error.payload = result;
            throw error;
        }

        return result;
    };

    const buildUniqueModuleName = (baseName, ignoreCard = null) => {
        const existingNames = getCards()
            .filter((card) => card !== ignoreCard)
            .map((card) => (card.querySelector('[name="name"]')?.value || '').trim().toLowerCase())
            .filter(Boolean);

        if (!existingNames.includes(baseName.trim().toLowerCase())) {
            return baseName.trim();
        }

        let suffix = 2;
        let candidate = `${baseName} ${suffix}`;

        while (existingNames.includes(candidate.trim().toLowerCase())) {
            suffix += 1;
            candidate = `${baseName} ${suffix}`;
        }

        return candidate;
    };

    const hydrateCardFromModule = (card, module) => {
        card.dataset.moduleId = String(module.id);
        card.dataset.moduleName = module.name || '';
        card.querySelector('[name="name"]').value = module.name || '';
        const ownerSelect = card.querySelector('[name="owner_id"]');
        if (ownerSelect?.tomselect) {
            ownerSelect.tomselect.setValue(module.owner_id || '', true);
        } else if (ownerSelect) {
            ownerSelect.value = module.owner_id || '';
        }
        const estimatedTimeInput = card.querySelector('[name="estimated_time_minutes"]');
        const estimatedTimeWrapper = card.querySelector('[data-estimated-time]');

        if (estimatedTimeInput) {
            estimatedTimeInput.value = module.estimated_time_minutes ?? 0;
        }

        estimatedTimeWrapper?.dispatchEvent(new CustomEvent('estimated-time:refresh'));
        card.querySelector('[name="description"]').value = module.description || '';
        card.querySelector('[name="start_date"]').value = module.start_date || '';
        card.querySelector('[name="end_date"]').value = module.end_date || '';
        const rangeInput = card.querySelector('[data-project-module-builder-date-range]');
        const colorDot = card.querySelector('[data-project-module-builder-color-dot]');

        if (rangeInput) {
            rangeInput.value = [module.start_date, module.end_date].filter(Boolean).join(' to ');
        }

        if (colorDot) {
            colorDot.style.backgroundColor = module.color || '#22C55E';
        }

        const colorInput = card.querySelector('[name="color"]');

        if (colorInput) {
            colorInput.value = module.color || '#22C55E';
        }

        syncCardTitle(card);
        syncDescriptionCount(card);
        setCardStatus(card, 'Saved');
    };

    const createLibraryModuleCard = async (libraryModule) => {
        const currentWeekRange = getCurrentWeekRange();
        const payload = normalizePayload({
            name: buildUniqueModuleName(libraryModule.name || 'New Module'),
            color: libraryModule.color || '#22C55E',
            description: libraryModule.description || '',
            estimated_time_minutes: 0,
            owner_id: '',
            start_date: currentWeekRange.startDate,
            end_date: currentWeekRange.endDate,
        });

        const tempId = `temp-${Date.now()}`;
        const cardWrapper = document.createElement('div');
        cardWrapper.innerHTML = renderModuleBuilderCard({
            ...payload,
            id: '',
            sort_order: getCards().length + 1,
        }, config, 'ring-2 ring-success-200 dark:ring-success-900/30');

        const card = cardWrapper.firstElementChild;

        if (!card) {
            return;
        }

        card.dataset.tempId = tempId;
        appendCardToWorkspace(card);
        initializeCardDatepicker(card);
        initializeCardTomSelect(card);
        initializeCardEstimatedTime(card);
        syncDescriptionCount(card);
        setCardExpanded(card, true);
        ensureEmptyState();
        setCardStatus(card, 'Saving...', 'mt-2 text-xs font-medium text-success-500 dark:text-success-300');

        try {
            const result = await requestJson(config.storeUrl, 'POST', payload);
            hydrateCardFromModule(card, result.module || payload);
            card.classList.remove('ring-2', 'ring-success-200', 'dark:ring-success-900/30');
            replaceRenderedSection(result);
        } catch (error) {
            card.remove();
            ensureEmptyState();
            showModalError(error.message || 'Unable to create the project module.');
        }
    };

    const saveModuleCard = async (card) => {
        const moduleId = card.dataset.moduleId;

        if (!moduleId) {
            return;
        }

        syncCardDateRange(card);
        const payload = normalizePayload(collectCardPayload(card));

        if (!payload.name) {
            setCardStatus(card, 'Name is required', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');
            return;
        }

        setCardStatus(card, 'Saving...', 'mt-2 text-xs font-medium text-success-500 dark:text-success-300');

        try {
            const result = await requestJson(config.updateUrlTemplate.replace('__MODULE__', moduleId), 'PUT', payload);
            hydrateCardFromModule(card, result.module || payload);
            replaceRenderedSection(result);
        } catch (error) {
            setCardStatus(card, 'Save failed', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');
            showModalError(error.message || 'Unable to update the project module.');
        }
    };

    const queueModuleSave = (card, delay = 500) => {
        const existingTimer = cardTimers.get(card);

        if (existingTimer) {
            window.clearTimeout(existingTimer);
        }

        const timer = window.setTimeout(() => {
            cardTimers.delete(card);
            saveModuleCard(card);
        }, delay);

        cardTimers.set(card, timer);
    };

    const deleteModuleCard = async (card) => {
        const moduleId = card.dataset.moduleId;

        if (!moduleId) {
            card.remove();
            ensureEmptyState();
            return;
        }

        setCardStatus(card, 'Deleting...', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');

        try {
            const result = await requestJson(config.destroyUrlTemplate.replace('__MODULE__', moduleId), 'DELETE', {});
            card.remove();
            ensureEmptyState();
            replaceRenderedSection(result);
        } catch (error) {
            setCardStatus(card, 'Delete failed', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');
            showModalError(error.message || 'Unable to delete the project module.');
        }
    };

    const persistWorkspaceOrder = async () => {
        const moduleIds = getCards()
            .map((card) => Number(card.dataset.moduleId))
            .filter(Boolean);

        if (!moduleIds.length) {
            return;
        }

        try {
            const result = await requestJson(config.reorderUrl, 'PATCH', { module_ids: moduleIds });
            syncOrderBadges();
            replaceRenderedSection(result);
        } catch (error) {
            showModalError(error.message || 'Unable to reorder project modules.');
        }
    };

    const highlightCard = (card) => {
        card.classList.add('ring-2', 'ring-success-300', 'dark:ring-success-900/40');
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });

        window.setTimeout(() => {
            card.classList.remove('ring-2', 'ring-success-300', 'dark:ring-success-900/40');
        }, 1800);
    };

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-project-module-library-create-open]')) {
            openLibraryCreateModal();
            return;
        }

        if (event.target.closest('.project-module-builder-open')) {
            openModal();
            return;
        }

        const editTrigger = event.target.closest('.project-module-builder-edit');

        if (editTrigger) {
            openModal();

            const moduleId = editTrigger.dataset.moduleId;
            const card = workspace.querySelector(`[data-project-module-builder-card][data-module-id="${moduleId}"]`);

            if (card) {
                setCardExpanded(card, true);
                highlightCard(card);
            }

            return;
        }

        if (event.target.closest('[data-project-module-builder-close]')) {
            closeModal();
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target.closest('[data-project-module-builder-close]')) {
            closeModal();
        }
    });

    libraryCreateModal?.addEventListener('click', function (event) {
        if (event.target.closest('[data-project-module-library-create-close]')) {
            closeLibraryCreateModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && libraryCreateModal && !libraryCreateModal.classList.contains('hidden')) {
            closeLibraryCreateModal();
            return;
        }

        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    libraryCreateForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        clearInlineFormErrors(libraryCreateForm, 'data-project-module-library-create-error');
        libraryCreateSubmitButton?.setAttribute('disabled', 'disabled');

        try {
            const result = await requestFormJson(
                config.libraryStoreUrl,
                new FormData(libraryCreateForm),
                csrfToken
            );

            appendLibraryModuleItem(result.data || {});
            closeLibraryCreateModal();
            showModalSuccess(result.message || 'Agile module created successfully.');
        } catch (error) {
            if (error.payload?.errors) {
                applyInlineFormErrors(
                    libraryCreateForm,
                    error.payload.errors,
                    'data-project-module-library-create-error'
                );
            } else {
                showModalError(error.message || 'Unable to create the agile module.');
            }
        } finally {
            libraryCreateSubmitButton?.removeAttribute('disabled');
        }
    });

    libraryCreateForm?.querySelector('textarea[name="description"]')?.addEventListener('input', syncLibraryDescriptionCount);

    library.addEventListener('dragstart', function (event) {
        const item = event.target.closest('[data-project-module-library-item]');

        if (!item) {
            return;
        }

        draggedLibraryModule = {
            id: item.dataset.libraryModuleId,
            name: item.dataset.name || '',
            color: item.dataset.color || '#22C55E',
            description: item.dataset.description || '',
        };

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'copy';
        }
    });

    library.addEventListener('dragend', function () {
        draggedLibraryModule = null;
    });

    workspace.addEventListener('dragover', function (event) {
        if (draggedLibraryModule || draggedWorkspaceCard) {
            event.preventDefault();
        }

        const targetCard = event.target.closest('[data-project-module-builder-card]');
        const dropzone = event.target.closest('[data-project-module-builder-dropzone]');

        if (!draggedWorkspaceCard || !targetCard || targetCard === draggedWorkspaceCard) {
            if (draggedWorkspaceCard && dropzone) {
                appendCardToWorkspace(draggedWorkspaceCard);
            }

            return;
        }

        const bounds = targetCard.getBoundingClientRect();
        const shouldInsertAfter = event.clientY > bounds.top + (bounds.height / 2);

        if (shouldInsertAfter) {
            workspace.insertBefore(draggedWorkspaceCard, targetCard.nextElementSibling);
        } else {
            workspace.insertBefore(draggedWorkspaceCard, targetCard);
        }
    });

    workspace.addEventListener('drop', async function (event) {
        event.preventDefault();

        if (draggedLibraryModule) {
            await createLibraryModuleCard(draggedLibraryModule);
            draggedLibraryModule = null;
            return;
        }

        if (draggedWorkspaceCard) {
            draggedWorkspaceCard.classList.remove('opacity-60');
            draggedWorkspaceCard = null;
            syncOrderBadges();
            await persistWorkspaceOrder();
        }
    });

    workspace.addEventListener('mousedown', function (event) {
        const handle = event.target.closest('[data-project-module-builder-drag-handle]');

        if (!handle) {
            return;
        }

        const card = handle.closest('[data-project-module-builder-card]');

        if (!card) {
            return;
        }

        handleCard = card;
        card.setAttribute('draggable', 'true');
    });

    workspace.addEventListener('dragstart', function (event) {
        const card = event.target.closest('[data-project-module-builder-card]');

        if (!card || handleCard !== card) {
            event.preventDefault();
            return;
        }

        draggedWorkspaceCard = card;
        card.classList.add('opacity-60');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
        }
    });

    workspace.addEventListener('dragend', function () {
        if (draggedWorkspaceCard) {
            draggedWorkspaceCard.classList.remove('opacity-60');
            draggedWorkspaceCard.setAttribute('draggable', 'false');
            draggedWorkspaceCard = null;
        }

        if (handleCard) {
            handleCard.setAttribute('draggable', 'false');
            handleCard = null;
        }
    });

    workspace.addEventListener('input', function (event) {
        const card = event.target.closest('[data-project-module-builder-card]');

        if (!card) {
            return;
        }

        if (event.target.name === 'name') {
            syncCardTitle(card);
        }

        if (event.target.matches('[data-project-module-builder-date-range]')) {
            syncCardDateRange(card);
        }

        if (event.target.name === 'description') {
            syncDescriptionCount(card);
        }

        setCardStatus(card, 'Pending changes...', 'mt-2 text-xs font-medium text-warning-500 dark:text-warning-300');
    });

    workspace.addEventListener('change', function (event) {
        const card = event.target.closest('[data-project-module-builder-card]');

        if (!card) {
            return;
        }

        if (event.target.closest('[data-project-module-builder-toggle]')) {
            return;
        }

        if (event.target.matches('[data-project-module-builder-date-range]')) {
            syncCardDateRange(card);
        }

        setCardStatus(card, 'Pending changes...', 'mt-2 text-xs font-medium text-warning-500 dark:text-warning-300');
        queueModuleSave(card, 150);
    });

    workspace.addEventListener('click', function (event) {
        const toggleButton = event.target.closest('[data-project-module-builder-toggle]');

        if (!toggleButton) {
            const deleteButton = event.target.closest('[data-project-module-builder-delete]');

            if (!deleteButton) {
                return;
            }

            const card = deleteButton.closest('[data-project-module-builder-card]');

            if (!card) {
                return;
            }

            deleteModuleCard(card);
            return;
        }

        const card = toggleButton.closest('[data-project-module-builder-card]');

        if (!card) {
            return;
        }

        setCardExpanded(card, card.dataset.expanded !== 'true');
    });

    searchInput?.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();

        library.querySelectorAll('[data-project-module-library-item]').forEach((item) => {
            const haystack = `${item.dataset.name || ''} ${item.dataset.description || ''}`.toLowerCase();
            item.classList.toggle('hidden', !haystack.includes(query));
        });
    });

    resetSearchButton?.addEventListener('click', function () {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    });

    ensureEmptyState();
    getCards().forEach((card) => setCardExpanded(card, false));
    getCards().forEach((card) => initializeCardDatepicker(card));
    getCards().forEach((card) => initializeCardTomSelect(card));
    getCards().forEach((card) => initializeCardEstimatedTime(card));
    getCards().forEach((card) => syncDescriptionCount(card));
    modal.dataset.projectModuleBuilderInitialized = 'true';
};

const syncSprintCardDateRange = (card) => {
    const rangeInput = card.querySelector('[data-project-sprint-builder-date-range]');
    const startInput = card.querySelector('[name="start_date"]');
    const endInput = card.querySelector('[name="end_date"]');

    if (!rangeInput || !startInput || !endInput) {
        return;
    }

    const value = rangeInput.value.trim();

    if (!value) {
        startInput.value = '';
        endInput.value = '';
        return;
    }

    const [startDate = '', endDate = ''] = value.split(' to ').map((item) => item.trim());
    startInput.value = startDate || '';
    endInput.value = endDate || '';
};

const initializeSprintCardDatepicker = (card) => {
    applyMinDateToRangeInput(card.querySelector('[data-project-sprint-builder-date-range]'));
    initDatepicker('.project-sprint-date-range', {}, card);
    syncSprintCardDateRange(card);
};

const initializeSprintCardEstimatedTime = (card) => {
    initializeEstimatedTimeInputs(card);
};

const normalizeDateOnly = (value) => {
    if (!value) {
        return '';
    }

    const stringValue = String(value).trim();

    if (!stringValue) {
        return '';
    }

    return stringValue.includes('T')
        ? stringValue.split('T')[0]
        : stringValue;
};

const renderSprintBuilderCard = (sprint, config, extraClass = '') => `
    <article class="select-text rounded-none border bg-white p-4 shadow-sm dark:bg-darkblack-600 ${extraClass}" style="border-color: ${escapeHtml(sprint.color || '#E5E7EB')};" data-project-sprint-builder-card data-sprint-id="${sprint.id ?? ''}" data-expanded="false" draggable="false">
        <input type="hidden" name="color" value="${escapeHtml(sprint.color || '#22C55E')}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <button type="button" class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300" data-project-sprint-builder-drag-handle>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                    </svg>
                </button>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex h-3.5 w-3.5 rounded-full" data-project-sprint-builder-color-dot style="background-color: ${escapeHtml(sprint.color || '#22C55E')}"></span>
                        <h5 class="text-base font-semibold text-bgray-900 dark:text-white" data-project-sprint-builder-title>${escapeHtml(sprint.name || 'New Sprint')}</h5>
                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200" data-project-sprint-builder-order>${escapeHtml(sprint.sort_order || '')}</span>
                        <span class="rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-200" data-project-sprint-builder-task-count>Tasks ${escapeHtml(sprint.task_count ?? 0)}</span>
                    </div>
                    <p class="mt-2 text-xs font-medium text-bgray-500 dark:text-bgray-300" data-project-sprint-builder-status>Saved</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                ${config?.canDelete ? `
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-500 transition duration-200 hover:border-red-300 hover:bg-red-100 dark:border-red-900/40 dark:bg-darkblack-500 dark:text-red-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400" data-project-sprint-builder-delete aria-label="Delete sprint" title="Delete sprint">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                ` : ''}
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-sprint-builder-toggle aria-label="Expand sprint" title="Expand sprint">
                    <svg class="h-4 w-4 rotate-180 transition duration-200" viewBox="0 0 20 20" fill="currentColor" data-project-sprint-builder-toggle-icon>
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="mt-4 hidden border-t border-bgray-100 pt-4 dark:border-darkblack-400" data-project-sprint-builder-body>
            <div class="grid gap-4 xl:grid-cols-2">
                <div>
                    <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="${escapeHtml(sprint.name || '')}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                </div>

                <div>
                    ${renderEstimatedTimeInput(sprint.estimated_time_minutes ?? 0)}
                </div>

                <div>
                    <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Date Range</label>
                    <input type="text" value="${escapeHtml([sprint.start_date, sprint.end_date].filter(Boolean).join(' to '))}" class="datepicker project-sprint-date-range w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="range" data-format="Y-m-d" data-min-date="${escapeHtml(getCurrentWeekRange().minDate)}" data-project-sprint-builder-date-range>
                    <input type="hidden" name="start_date" value="${escapeHtml(sprint.start_date || '')}">
                    <input type="hidden" name="end_date" value="${escapeHtml(sprint.end_date || '')}">
                </div>

                <div class="xl:col-span-2">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Description</label>
                        <span class="text-[11px] font-medium text-bgray-400 dark:text-bgray-300"><span data-project-sprint-builder-description-count>${escapeHtml(String((sprint.description || '').length))}</span>/100</span>
                    </div>
                    <textarea name="description" rows="3" maxlength="100" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">${escapeHtml(sprint.description || '')}</textarea>
                </div>
            </div>
        </div>
    </article>
`;

const renderSprintLibraryCard = (librarySprint, extraClass = '') => `
    <article class="cursor-grab rounded-none border border-bgray-200 bg-white p-4 shadow-sm transition duration-200 hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600 dark:hover:border-success-300 ${extraClass}" draggable="true" data-project-sprint-library-item data-library-sprint-id="${escapeHtml(librarySprint.id ?? '')}" data-name="${escapeHtml(librarySprint.name || '')}" data-color="${escapeHtml(librarySprint.color || '#22C55E')}" data-description="${escapeHtml(librarySprint.description || '')}" data-sort-order="${escapeHtml(librarySprint.sort_order ?? '')}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3.5 w-3.5 rounded-sm" style="background-color: ${escapeHtml(librarySprint.color || '#22C55E')}"></span>
                    <h5 class="truncate text-sm font-semibold text-bgray-900 dark:text-white">
                        ${escapeHtml(librarySprint.name || 'New Sprint')}
                    </h5>
                </div>
                <p class="mt-2 text-xs leading-5 text-bgray-500 dark:text-bgray-300">
                    ${escapeHtml(librarySprint.description || 'No library description added yet.')}
                </p>
            </div>

            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 12h8M8 17h8M5 7h.01M5 12h.01M5 17h.01" />
                </svg>
            </span>
        </div>
    </article>
`;

const initializeProjectSprintBuilderModal = () => {
    const modal = document.getElementById('project-sprint-modal');

    if (!modal || modal.dataset.projectSprintBuilderInitialized === 'true') {
        return;
    }

    const configNode = document.getElementById('project-sprint-builder-config');
    const workspace = modal.querySelector('[data-project-sprint-builder-workspace]');
    const library = modal.querySelector('[data-project-sprint-builder-library]');
    const libraryScrollContainer = modal.querySelector('[data-project-sprint-builder-library-scroll]');
    const searchInput = modal.querySelector('[data-project-sprint-builder-library-search]');
    const resetSearchButton = modal.querySelector('[data-project-sprint-builder-reset-search]');
    const countBadge = modal.querySelector('[data-project-sprint-builder-count]');
    const moduleNameNode = modal.querySelector('[data-project-sprint-builder-module-name]');
    const libraryCreateModal = document.getElementById('project-sprint-library-create-modal');
    const libraryCreateForm = libraryCreateModal?.querySelector('[data-project-sprint-library-create-form]') || null;
    const libraryCreateSubmitButton = libraryCreateModal?.querySelector('[data-project-sprint-library-create-submit]') || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!configNode || !workspace || !library || !moduleNameNode || !csrfToken) {
        modal.dataset.projectSprintBuilderInitialized = 'true';
        return;
    }

    const config = JSON.parse(configNode.textContent || '{}');
    let activeModuleId = null;
    let activeModuleName = '';
    let draggedLibrarySprint = null;
    let draggedWorkspaceCard = null;
    let handleCard = null;
    const cardTimers = new Map();
    const showModalSuccess = (message, title = 'Success') => Alert.success(message, title, { target: modal });
    const showModalError = (message, title = 'Error') => Alert.error(message, title, { target: modal });

    const getLibrarySortOrders = () => Array.from(library.querySelectorAll('[data-project-sprint-library-item]'))
        .map((item) => Number(item.dataset.sortOrder) || 0);

    const getNextLibrarySortOrder = () => Math.max(
        Number(config.nextLibrarySortOrder) || 0,
        (Math.max(0, ...getLibrarySortOrders()) || 0) + 1,
        1
    );

    const syncLibraryDescriptionCount = () => {
        if (!libraryCreateForm) {
            return;
        }

        const textarea = libraryCreateForm.querySelector('textarea[name="description"]');
        const countNode = libraryCreateForm.querySelector('[data-project-sprint-library-description-count]');

        if (!textarea || !countNode) {
            return;
        }

        countNode.textContent = String(textarea.value.length);
    };

    const resetLibraryCreateForm = () => {
        if (!libraryCreateForm) {
            return;
        }

        libraryCreateForm.reset();
        libraryCreateForm.querySelector('[name="color"]')?.setAttribute('value', '#22C55E');
        const colorInput = libraryCreateForm.querySelector('[name="color"]');

        if (colorInput) {
            colorInput.value = '#22C55E';
        }

        const sortOrderInput = libraryCreateForm.querySelector('[name="sort_order"]');

        if (sortOrderInput) {
            sortOrderInput.value = String(getNextLibrarySortOrder());
        }

        clearInlineFormErrors(libraryCreateForm, 'data-project-sprint-library-create-error');
        syncLibraryDescriptionCount();
    };

    const openLibraryCreateModal = () => {
        if (!libraryCreateModal) {
            return;
        }

        resetLibraryCreateForm();
        libraryCreateModal.classList.remove('hidden');
        libraryCreateForm?.querySelector('[name="name"]')?.focus();
    };

    const closeLibraryCreateModal = () => {
        if (!libraryCreateModal) {
            return;
        }

        libraryCreateModal.classList.add('hidden');
        resetLibraryCreateForm();
    };

    const appendLibrarySprintItem = (librarySprint) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = renderSprintLibraryCard(librarySprint);
        const item = wrapper.firstElementChild;

        if (!item) {
            return null;
        }

        library.appendChild(item);
        config.nextLibrarySortOrder = Math.max(
            Number(config.nextLibrarySortOrder) || 0,
            (Number(librarySprint.sort_order) || 0) + 1
        );

        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }

        highlightLibraryItem(item, libraryScrollContainer);

        return item;
    };

    const getCards = () => Array.from(workspace.querySelectorAll('[data-project-sprint-builder-card]'));
    const getHelper = () => workspace.querySelector('[data-project-sprint-builder-helper]');
    const getDropzone = () => workspace.querySelector('[data-project-sprint-builder-dropzone]');
    const helperMarkup = `
        <div class="flex items-center gap-3 rounded-2xl border border-dashed border-success-200/80 bg-white/75 px-4 py-3 text-success-500 dark:border-success-900/40 dark:bg-darkblack-600/60 dark:text-success-300" data-project-sprint-builder-helper>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-success-50 text-success-500 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold">Drag here for more sprints</p>
                <p class="text-xs text-bgray-500 dark:text-bgray-300">Drop another sprint from the library anywhere in this workspace to add it under the selected module.</p>
            </div>
        </div>
    `;
    const dropzoneMarkup = `
        <div class="h-24 rounded-2xl border border-dashed border-bgray-200/70 bg-bgray-50/40 dark:border-darkblack-400/60 dark:bg-darkblack-500/20" data-project-sprint-builder-dropzone></div>
    `;

    const createWorkspaceGuide = (markup) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = markup.trim();
        return wrapper.firstElementChild;
    };

    const updateModuleContext = () => {
        moduleNameNode.textContent = activeModuleName || 'Select a module';
    };

    const renderWorkspaceLoadingState = () => {
        workspace.innerHTML = `
            <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                    <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4m0 8v4m8-8h-4M8 12H4m13.657-5.657l-2.829 2.829M9.172 14.828l-2.829 2.829m0-11.314l2.829 2.829m5.656 5.656l2.829 2.829" />
                    </svg>
                </span>
                <h5 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">Loading Sprints</h5>
                <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                    Fetching the latest sprint list for this module work area.
                </p>
            </div>
        `;
        updateCount();
    };

    const openModal = async ({ moduleId, moduleName = '', sprintId = null, loadUrl = '' } = {}) => {
        activeModuleId = Number(moduleId) || null;
        activeModuleName = moduleName || '';
        updateModuleContext();

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        if (!activeModuleId) {
            renderWorkspaceFromSprints([]);
            return;
        }

        renderWorkspaceLoadingState();

        try {
            const payload = await fetchProjectModuleSprints(activeModuleId, { loadUrl, all: true });
            activeModuleName = moduleName || payload.module?.name || activeModuleName;
            updateModuleContext();
            renderWorkspaceFromSprints(payload.sprints || []);
        } catch (error) {
            renderWorkspaceFromSprints([]);
            showModalError(error.message || 'Unable to load project sprints.');
            return;
        }

        if (sprintId) {
            const targetCard = workspace.querySelector(`[data-project-sprint-builder-card][data-sprint-id="${sprintId}"]`);

            if (targetCard) {
                setCardExpanded(targetCard, true);
                highlightCard(targetCard);
            }
            return;
        }
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const syncWorkspaceGuides = () => {
        const cards = getCards();
        let helper = getHelper();
        let dropzone = getDropzone();

        if (!cards.length) {
            helper?.remove();
            dropzone?.remove();
            return;
        }

        if (!helper) {
            helper = createWorkspaceGuide(helperMarkup);
        }

        if (!dropzone) {
            dropzone = createWorkspaceGuide(dropzoneMarkup);
        }

        workspace.appendChild(helper);
        workspace.appendChild(dropzone);
    };

    const appendCardToWorkspace = (card) => {
        syncWorkspaceGuides();
        const dropzone = getDropzone();

        if (dropzone) {
            workspace.insertBefore(card, dropzone);
            return;
        }

        workspace.appendChild(card);
    };

    const updateCount = () => {
        if (countBadge) {
            countBadge.textContent = String(getCards().length);
        }
    };

    const ensureEmptyState = () => {
        const cards = getCards();
        const emptyState = workspace.querySelector('[data-project-sprint-builder-empty]');

        if (cards.length && emptyState) {
            emptyState.remove();
        }

        if (!cards.length && !emptyState) {
            workspace.innerHTML = `
                <div class="rounded-2xl border border-dashed border-bgray-300 bg-white px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600" data-project-sprint-builder-empty>
                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                    <h5 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">No Sprints Selected Yet</h5>
                    <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                        Drag one or more items from the sprint library to start building this module workspace.
                    </p>
                </div>
            `;
        }

        updateCount();
        syncOrderBadges();
        syncWorkspaceGuides();
    };

    const renderWorkspaceFromSprints = (sprints) => {
        workspace.innerHTML = '';

        sprints.forEach((sprint) => {
            const cardWrapper = document.createElement('div');
            cardWrapper.innerHTML = renderSprintBuilderCard(sprint, config);
            const card = cardWrapper.firstElementChild;

            if (!card) {
                return;
            }

            appendCardToWorkspace(card);
            initializeSprintCardDatepicker(card);
            initializeSprintCardEstimatedTime(card);
            syncDescriptionCount(card);
            syncSprintColor(card);
        });

        ensureEmptyState();
        getCards().forEach((card) => setCardExpanded(card, false));
    };

    const setCardStatus = (card, status, classes = '') => {
        const statusNode = card.querySelector('[data-project-sprint-builder-status]');

        if (!statusNode) {
            return;
        }

        statusNode.className = `mt-2 text-xs font-medium ${classes || 'text-bgray-500 dark:text-bgray-300'}`;
        statusNode.textContent = status;
    };

    const syncCardTitle = (card) => {
        const title = card.querySelector('[data-project-sprint-builder-title]');
        const nameInput = card.querySelector('[name="name"]');

        if (title && nameInput) {
            const nextTitle = nameInput.value.trim() || 'Untitled Sprint';
            title.textContent = nextTitle;
        }
    };

    const syncDescriptionCount = (card) => {
        const countNode = card.querySelector('[data-project-sprint-builder-description-count]');
        const textarea = card.querySelector('[name="description"]');

        if (!countNode || !textarea) {
            return;
        }

        countNode.textContent = String(textarea.value.length);
    };

    const syncSprintColor = (card) => {
        const colorValue = card.querySelector('[name="color"]')?.value || '#22C55E';
        const colorPicker = card.querySelector('[name="color_picker"]');
        const colorDot = card.querySelector('[data-project-sprint-builder-color-dot]');

        if (colorPicker && colorPicker.value !== colorValue) {
            colorPicker.value = colorValue;
        }

        if (colorDot) {
            colorDot.style.backgroundColor = colorValue;
        }

        card.style.borderColor = colorValue || '#E5E7EB';
    };

    const syncOrderBadges = () => {
        getCards().forEach((card, index) => {
            const badge = card.querySelector('[data-project-sprint-builder-order]');

            if (badge) {
                badge.textContent = String(index + 1);
            }
        });
    };

    const setCardExpanded = (card, expanded) => {
        const body = card.querySelector('[data-project-sprint-builder-body]');
        const icon = card.querySelector('[data-project-sprint-builder-toggle-icon]');
        const toggleButton = card.querySelector('[data-project-sprint-builder-toggle]');

        if (expanded) {
            getCards().forEach((item) => {
                if (item !== card) {
                    item.dataset.expanded = 'false';
                    item.querySelector('[data-project-sprint-builder-body]')?.classList.add('hidden');
                    item.querySelector('[data-project-sprint-builder-toggle-icon]')?.classList.add('rotate-180');
                    item.querySelector('[data-project-sprint-builder-toggle]')?.setAttribute('aria-label', 'Expand sprint');
                    item.querySelector('[data-project-sprint-builder-toggle]')?.setAttribute('title', 'Expand sprint');
                }
            });
        }

        card.dataset.expanded = expanded ? 'true' : 'false';

        if (body) {
            body.classList.toggle('hidden', !expanded);
        }

        if (icon) {
            icon.classList.toggle('rotate-180', !expanded);
        }

        if (toggleButton) {
            const label = expanded ? 'Collapse sprint' : 'Expand sprint';
            toggleButton.setAttribute('aria-label', label);
            toggleButton.setAttribute('title', label);
        }
    };

    const collectCardPayload = (card) => ({
        project_module_id: activeModuleId,
        name: card.querySelector('[name="name"]')?.value.trim() || '',
        estimated_time_minutes: card.querySelector('[name="estimated_time_minutes"]')?.value || 0,
        color: card.querySelector('[name="color"]')?.value || '',
        description: card.querySelector('[name="description"]')?.value || '',
        start_date: card.querySelector('[name="start_date"]')?.value || '',
        end_date: card.querySelector('[name="end_date"]')?.value || '',
    });

    const normalizePayload = (payload) => {
        const normalized = { ...payload };

        if ('project_module_id' in normalized) {
            normalized.project_module_id = Number(normalized.project_module_id) || null;
        }

        if ('estimated_time_minutes' in normalized) {
            normalized.estimated_time_minutes = Number.parseInt(normalized.estimated_time_minutes || 0, 10) || 0;
        }

        if ('color' in normalized) {
            normalized.color = normalized.color || null;
        }

        if ('description' in normalized) {
            normalized.description = normalized.description || null;
        }

        if ('start_date' in normalized) {
            normalized.start_date = normalized.start_date || null;
        }

        if ('end_date' in normalized) {
            normalized.end_date = normalized.end_date || null;
        }

        return normalized;
    };

    const requestJson = async (url, method, payload) => {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (!response.ok || result.status === false || result.success === false) {
            const error = new Error(result.message || 'Unable to save the project sprint.');
            error.payload = result;
            throw error;
        }

        return result;
    };

    const buildUniqueSprintName = (baseName, ignoreCard = null) => {
        const existingNames = getCards()
            .filter((card) => card !== ignoreCard)
            .map((card) => (card.querySelector('[name="name"]')?.value || '').trim().toLowerCase())
            .filter(Boolean);

        if (!existingNames.includes(baseName.trim().toLowerCase())) {
            return baseName.trim();
        }

        let suffix = 2;
        let candidate = `${baseName} ${suffix}`;

        while (existingNames.includes(candidate.trim().toLowerCase())) {
            suffix += 1;
            candidate = `${baseName} ${suffix}`;
        }

        return candidate;
    };

    const hydrateCardFromSprint = (card, sprint) => {
        const normalizedColor = sprint.color || '#22C55E';
        const normalizedStartDate = normalizeDateOnly(sprint.start_date);
        const normalizedEndDate = normalizeDateOnly(sprint.end_date);

        card.dataset.sprintId = String(sprint.id || card.dataset.sprintId || '');
        card.querySelector('[name="name"]').value = sprint.name || '';
        const estimatedTimeInput = card.querySelector('[name="estimated_time_minutes"]');
        const estimatedTimeWrapper = card.querySelector('[data-estimated-time]');

        if (estimatedTimeInput) {
            estimatedTimeInput.value = sprint.estimated_time_minutes ?? 0;
        }

        estimatedTimeWrapper?.dispatchEvent(new CustomEvent('estimated-time:refresh'));
        card.querySelector('[name="color"]').value = normalizedColor;
        card.querySelector('[name="description"]').value = sprint.description || '';
        card.querySelector('[name="start_date"]').value = normalizedStartDate;
        card.querySelector('[name="end_date"]').value = normalizedEndDate;
        const rangeInput = card.querySelector('[data-project-sprint-builder-date-range]');

        if (rangeInput) {
            rangeInput.value = [normalizedStartDate, normalizedEndDate].filter(Boolean).join(' to ');
        }

        const taskCountBadge = card.querySelector('[data-project-sprint-builder-task-count]');

        if (taskCountBadge) {
            taskCountBadge.textContent = `Tasks ${sprint.task_count ?? 0}`;
        }

        syncCardTitle(card);
        syncDescriptionCount(card);
        syncSprintCardDateRange(card);
        syncSprintColor(card);
        setCardStatus(card, 'Saved');
    };

    const createLibrarySprintCard = async (librarySprint) => {
        if (!activeModuleId) {
            showModalError('Select a project module before adding sprints.');
            return;
        }

        const currentWeekRange = getCurrentWeekRange();
        const payload = normalizePayload({
            project_module_id: activeModuleId,
            name: buildUniqueSprintName(librarySprint.name || 'New Sprint'),
            color: librarySprint.color || '#22C55E',
            description: librarySprint.description || '',
            estimated_time_minutes: 0,
            start_date: currentWeekRange.startDate,
            end_date: currentWeekRange.endDate,
        });

        const cardWrapper = document.createElement('div');
        cardWrapper.innerHTML = renderSprintBuilderCard({
            ...payload,
            id: '',
            sort_order: getCards().length + 1,
        }, config, 'ring-2 ring-success-200 dark:ring-success-900/30');

        const card = cardWrapper.firstElementChild;

        if (!card) {
            return;
        }

        appendCardToWorkspace(card);
        initializeSprintCardDatepicker(card);
        initializeSprintCardEstimatedTime(card);
        syncDescriptionCount(card);
        syncSprintColor(card);
        setCardExpanded(card, true);
        ensureEmptyState();
        setCardStatus(card, 'Saving...', 'mt-2 text-xs font-medium text-success-500 dark:text-success-300');

        try {
            const result = await requestJson(config.storeUrlTemplate.replace('__MODULE__', activeModuleId), 'POST', payload);
            hydrateCardFromSprint(card, {
                ...payload,
                ...(result.sprint || result.data || {}),
                estimated_time_minutes: result.sprint?.estimated_time_minutes ?? result.data?.estimated_time_minutes ?? payload.estimated_time_minutes,
            });
            card.classList.remove('ring-2', 'ring-success-200', 'dark:ring-success-900/30');
            clearProjectModuleSprintCache(activeModuleId);
            replaceRenderedSection(result);
        } catch (error) {
            card.remove();
            ensureEmptyState();
            showModalError(error.message || 'Unable to create the project sprint.');
        }
    };

    const saveSprintCard = async (card) => {
        const sprintId = card.dataset.sprintId;

        if (!sprintId) {
            return;
        }

        const payload = normalizePayload(collectCardPayload(card));

        if (!payload.name) {
            setCardStatus(card, 'Name is required', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');
            return;
        }

        setCardStatus(card, 'Saving...', 'mt-2 text-xs font-medium text-success-500 dark:text-success-300');

        try {
            const result = await requestJson(config.updateUrlTemplate.replace('__SPRINT__', sprintId), 'PUT', payload);
            hydrateCardFromSprint(card, {
                ...payload,
                ...(result.sprint || result.data || {}),
                estimated_time_minutes: result.sprint?.estimated_time_minutes ?? result.data?.estimated_time_minutes ?? payload.estimated_time_minutes,
            });
            clearProjectModuleSprintCache(activeModuleId);
            replaceRenderedSection(result);
        } catch (error) {
            setCardStatus(card, 'Save failed', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');
            showModalError(error.message || 'Unable to update the project sprint.');
        }
    };

    const queueSprintSave = (card, delay = 500) => {
        const existingTimer = cardTimers.get(card);

        if (existingTimer) {
            window.clearTimeout(existingTimer);
        }

        const timer = window.setTimeout(() => {
            cardTimers.delete(card);
            saveSprintCard(card);
        }, delay);

        cardTimers.set(card, timer);
    };

    const deleteSprintCard = async (card) => {
        const sprintId = card.dataset.sprintId;

        if (!sprintId) {
            card.remove();
            ensureEmptyState();
            return;
        }

        setCardStatus(card, 'Deleting...', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');

        try {
            const result = await requestJson(config.destroyUrlTemplate.replace('__SPRINT__', sprintId), 'DELETE', {});
            card.remove();
            ensureEmptyState();
            clearProjectModuleSprintCache(activeModuleId);
            replaceRenderedSection(result);
        } catch (error) {
            setCardStatus(card, 'Delete failed', 'mt-2 text-xs font-medium text-red-500 dark:text-red-300');
            showModalError(error.message || 'Unable to delete the project sprint.');
        }
    };

    const persistWorkspaceOrder = async () => {
        const sprintIds = getCards()
            .map((card) => Number(card.dataset.sprintId))
            .filter(Boolean);

        if (!activeModuleId || !sprintIds.length) {
            return;
        }

        try {
            await requestJson(config.reorderUrlTemplate.replace('__MODULE__', activeModuleId), 'PATCH', { sprint_ids: sprintIds });
            syncOrderBadges();
            clearProjectModuleSprintCache(activeModuleId);
            fetchProjectModuleSprints(activeModuleId, { force: true, all: true }).catch(() => {});
        } catch (error) {
            showModalError(error.message || 'Unable to reorder project sprints.');
        }
    };

    const highlightCard = (card) => {
        card.classList.add('ring-2', 'ring-success-300', 'dark:ring-success-900/40');
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });

        window.setTimeout(() => {
            card.classList.remove('ring-2', 'ring-success-300', 'dark:ring-success-900/40');
        }, 1800);
    };

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-project-sprint-library-create-open]')) {
            openLibraryCreateModal();
            return;
        }

        const createTrigger = event.target.closest('.project-sprint-builder-open');

        if (createTrigger) {
            openModal({
                moduleId: createTrigger.dataset.projectModuleId,
                moduleName: createTrigger.dataset.projectModuleName,
                loadUrl: createTrigger.dataset.projectSprintLoadUrl,
            });
            return;
        }

        const editTrigger = event.target.closest('.project-sprint-builder-edit');

        if (editTrigger) {
            openModal({
                moduleId: editTrigger.dataset.projectModuleId,
                moduleName: editTrigger.dataset.projectModuleName,
                sprintId: editTrigger.dataset.projectSprintId,
                loadUrl: editTrigger.dataset.projectSprintLoadUrl,
            });
            return;
        }

        if (event.target.closest('[data-project-sprint-builder-close]')) {
            closeModal();
        }
    });

    modal.addEventListener('click', function (event) {
        if (event.target.closest('[data-project-sprint-builder-close]')) {
            closeModal();
        }
    });

    libraryCreateModal?.addEventListener('click', function (event) {
        if (event.target.closest('[data-project-sprint-library-create-close]')) {
            closeLibraryCreateModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && libraryCreateModal && !libraryCreateModal.classList.contains('hidden')) {
            closeLibraryCreateModal();
            return;
        }

        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    libraryCreateForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        clearInlineFormErrors(libraryCreateForm, 'data-project-sprint-library-create-error');
        libraryCreateSubmitButton?.setAttribute('disabled', 'disabled');

        try {
            const result = await requestFormJson(
                config.libraryStoreUrl,
                new FormData(libraryCreateForm),
                csrfToken
            );

            appendLibrarySprintItem(result.data || {});
            closeLibraryCreateModal();
            showModalSuccess(result.message || 'Agile sprint created successfully.');
        } catch (error) {
            if (error.payload?.errors) {
                applyInlineFormErrors(
                    libraryCreateForm,
                    error.payload.errors,
                    'data-project-sprint-library-create-error'
                );
            } else {
                showModalError(error.message || 'Unable to create the agile sprint.');
            }
        } finally {
            libraryCreateSubmitButton?.removeAttribute('disabled');
        }
    });

    libraryCreateForm?.querySelector('textarea[name="description"]')?.addEventListener('input', syncLibraryDescriptionCount);

    library.addEventListener('dragstart', function (event) {
        const item = event.target.closest('[data-project-sprint-library-item]');

        if (!item) {
            return;
        }

        draggedLibrarySprint = {
            id: item.dataset.librarySprintId,
            name: item.dataset.name || '',
            color: item.dataset.color || '#22C55E',
            description: item.dataset.description || '',
        };

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'copy';
        }
    });

    library.addEventListener('dragend', function () {
        draggedLibrarySprint = null;
    });

    workspace.addEventListener('dragover', function (event) {
        if (draggedLibrarySprint || draggedWorkspaceCard) {
            event.preventDefault();
        }

        const targetCard = event.target.closest('[data-project-sprint-builder-card]');
        const dropzone = event.target.closest('[data-project-sprint-builder-dropzone]');

        if (!draggedWorkspaceCard || !targetCard || targetCard === draggedWorkspaceCard) {
            if (draggedWorkspaceCard && dropzone) {
                appendCardToWorkspace(draggedWorkspaceCard);
            }

            return;
        }

        const bounds = targetCard.getBoundingClientRect();
        const shouldInsertAfter = event.clientY > bounds.top + (bounds.height / 2);

        if (shouldInsertAfter) {
            workspace.insertBefore(draggedWorkspaceCard, targetCard.nextElementSibling);
        } else {
            workspace.insertBefore(draggedWorkspaceCard, targetCard);
        }
    });

    workspace.addEventListener('drop', async function (event) {
        event.preventDefault();

        if (draggedLibrarySprint) {
            await createLibrarySprintCard(draggedLibrarySprint);
            draggedLibrarySprint = null;
            return;
        }

        if (draggedWorkspaceCard) {
            draggedWorkspaceCard.classList.remove('opacity-60');
            draggedWorkspaceCard = null;
            syncOrderBadges();
            await persistWorkspaceOrder();
        }
    });

    workspace.addEventListener('mousedown', function (event) {
        const handle = event.target.closest('[data-project-sprint-builder-drag-handle]');

        if (!handle) {
            return;
        }

        const card = handle.closest('[data-project-sprint-builder-card]');

        if (!card) {
            return;
        }

        handleCard = card;
        card.setAttribute('draggable', 'true');
    });

    workspace.addEventListener('dragstart', function (event) {
        const card = event.target.closest('[data-project-sprint-builder-card]');

        if (!card || handleCard !== card) {
            event.preventDefault();
            return;
        }

        draggedWorkspaceCard = card;
        card.classList.add('opacity-60');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
        }
    });

    workspace.addEventListener('dragend', function () {
        if (draggedWorkspaceCard) {
            draggedWorkspaceCard.classList.remove('opacity-60');
            draggedWorkspaceCard.setAttribute('draggable', 'false');
            draggedWorkspaceCard = null;
        }

        if (handleCard) {
            handleCard.setAttribute('draggable', 'false');
            handleCard = null;
        }
    });

    workspace.addEventListener('input', function (event) {
        const card = event.target.closest('[data-project-sprint-builder-card]');

        if (!card) {
            return;
        }

        if (event.target.name === 'name') {
            syncCardTitle(card);
        }

        if (event.target.name === 'description') {
            syncDescriptionCount(card);
        }

        if (event.target.matches('[data-project-sprint-builder-date-range]')) {
            syncSprintCardDateRange(card);
        }

        setCardStatus(card, 'Pending changes...', 'mt-2 text-xs font-medium text-warning-500 dark:text-warning-300');
    });

    workspace.addEventListener('change', function (event) {
        const card = event.target.closest('[data-project-sprint-builder-card]');

        if (!card) {
            return;
        }

        if (event.target.closest('[data-project-sprint-builder-toggle]')) {
            return;
        }

        if (event.target.matches('[data-project-sprint-builder-date-range]')) {
            syncSprintCardDateRange(card);
        }

        setCardStatus(card, 'Pending changes...', 'mt-2 text-xs font-medium text-warning-500 dark:text-warning-300');
        queueSprintSave(card, 150);
    });

    workspace.addEventListener('click', function (event) {
        const toggleButton = event.target.closest('[data-project-sprint-builder-toggle]');

        if (!toggleButton) {
            const deleteButton = event.target.closest('[data-project-sprint-builder-delete]');

            if (deleteButton) {
                const card = deleteButton.closest('[data-project-sprint-builder-card]');

                if (!card) {
                    return;
                }

                deleteSprintCard(card);
            }

            return;
        }

        const card = toggleButton.closest('[data-project-sprint-builder-card]');

        if (!card) {
            return;
        }

        setCardExpanded(card, card.dataset.expanded !== 'true');
    });

    searchInput?.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();

        library.querySelectorAll('[data-project-sprint-library-item]').forEach((item) => {
            const haystack = `${item.dataset.name || ''} ${item.dataset.description || ''}`.toLowerCase();
            item.classList.toggle('hidden', !haystack.includes(query));
        });
    });

    resetSearchButton?.addEventListener('click', function () {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    });

    ensureEmptyState();
    updateModuleContext();
    modal.dataset.projectSprintBuilderInitialized = 'true';
};

const initializeProjectSprintList = (sprintList) => {
    if (!sprintList || sprintList.dataset.projectSprintListInitialized === 'true') {
        return;
    }

    syncProjectSprintListReorderState(sprintList);

    if (sprintList.dataset.allPagesLoaded !== 'true') {
        sprintList.dataset.projectSprintListInitialized = 'pending';
        return;
    }

    const reorderUrl = sprintList.dataset.reorderUrl;
    const csrfToken = getCsrfToken();

    if (!reorderUrl || !csrfToken) {
        sprintList.dataset.projectSprintListInitialized = 'true';
        return;
    }

    let pendingSprintCard = null;
    let draggingSprintCard = null;
    let dragStartOrder = [];
    let pointerStartX = 0;
    let pointerStartY = 0;

    const getSprintCards = () => Array.from(sprintList.querySelectorAll('[data-project-sprint-card]'));
    const getSprintIds = () => getSprintCards().map((card) => Number(card.dataset.projectSprintId));
    const animateSprintCardReorder = (mutation) => {
        const firstRects = new Map(
            getSprintCards().map((card) => [card, card.getBoundingClientRect()])
        );

        mutation();

        getSprintCards().forEach((card) => {
            const firstRect = firstRects.get(card);

            if (!firstRect) {
                return;
            }

            const lastRect = card.getBoundingClientRect();
            const deltaY = firstRect.top - lastRect.top;

            if (Math.abs(deltaY) < 1 || typeof card.animate !== 'function') {
                return;
            }

            card.animate(
                [
                    { transform: `translateY(${deltaY}px)` },
                    { transform: 'translateY(0)' },
                ],
                {
                    duration: 180,
                    easing: 'ease-out',
                }
            );
        });
    };

    const persistSprintOrder = async () => {
        const sprintIds = getSprintIds();

        try {
            const response = await fetch(reorderUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ sprint_ids: sprintIds }),
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                throw new Error(result.message || 'Unable to save the new sprint order.');
            }

            clearProjectModuleSprintCache(Number(sprintList.closest('[data-project-module-sprints-panel]')?.dataset.moduleId));
            Alert.success(result.message || 'Sprint order updated successfully.');
        } catch (error) {
            Alert.error(error.message || 'Unable to save the new sprint order.');
            window.location.reload();
        }
    };

    const setDraggingState = (card, isDragging) => {
        if (!card) {
            return;
        }

        card.classList.toggle('opacity-60', isDragging);
        card.classList.toggle('ring-2', isDragging);
        card.classList.toggle('ring-success-300', isDragging);
        card.classList.toggle('dark:ring-success-900/40', isDragging);
        card.style.boxShadow = isDragging
            ? '0 18px 35px -18px rgba(15, 23, 42, 0.35)'
            : '';
    };

    const resetSprintPointerState = () => {
        setDraggingState(draggingSprintCard, false);
        pendingSprintCard = null;
        draggingSprintCard = null;
        dragStartOrder = [];
        document.body.classList.remove('select-none');
    };

    const maybeMoveSprintCard = (clientX, clientY) => {
        if (!draggingSprintCard) {
            return;
        }

        const listBounds = sprintList.getBoundingClientRect();

        if (
            clientX >= listBounds.left
            && clientX <= listBounds.right
            && clientY >= listBounds.top
            && clientY <= listBounds.bottom
        ) {
            const targetCard = document.elementFromPoint(clientX, clientY)?.closest('[data-project-sprint-card]');

            if (!targetCard || !sprintList.contains(targetCard)) {
                if (sprintList.lastElementChild !== draggingSprintCard) {
                    animateSprintCardReorder(() => {
                        sprintList.appendChild(draggingSprintCard);
                    });
                }
                return;
            }

            if (targetCard === draggingSprintCard) {
                return;
            }

            const targetBounds = targetCard.getBoundingClientRect();
            const insertAfterTarget = clientY > targetBounds.top + (targetBounds.height / 2);

            if (insertAfterTarget) {
                if (draggingSprintCard.nextElementSibling !== targetCard.nextElementSibling) {
                    animateSprintCardReorder(() => {
                        sprintList.insertBefore(draggingSprintCard, targetCard.nextElementSibling);
                    });
                }
                return;
            }

            if (draggingSprintCard.nextElementSibling !== targetCard) {
                animateSprintCardReorder(() => {
                    sprintList.insertBefore(draggingSprintCard, targetCard);
                });
            }
        }
    };

    const handlePointerMove = (event) => {
        if (!pendingSprintCard && !draggingSprintCard) {
            return;
        }

        const movedX = Math.abs(event.clientX - pointerStartX);
        const movedY = Math.abs(event.clientY - pointerStartY);

        if (!draggingSprintCard) {
            if (Math.max(movedX, movedY) < 5) {
                return;
            }

            draggingSprintCard = pendingSprintCard;
            dragStartOrder = getSprintIds();
            setDraggingState(draggingSprintCard, true);
            document.body.classList.add('select-none');
        }

        event.preventDefault();
        maybeMoveSprintCard(event.clientX, event.clientY);
    };

    const handlePointerUp = async () => {
        if (!pendingSprintCard && !draggingSprintCard) {
            return;
        }

        const currentOrder = getSprintIds();
        const orderChanged = draggingSprintCard
            && JSON.stringify(currentOrder) !== JSON.stringify(dragStartOrder);

        resetSprintPointerState();

        if (orderChanged) {
            await persistSprintOrder();
        }
    };

    const handlePointerCancel = () => {
        resetSprintPointerState();
    };

    sprintList.addEventListener('pointerdown', function (event) {
        const handle = event.target.closest('[data-project-sprint-drag-handle]');

        if (!handle) {
            return;
        }

        const card = handle.closest('[data-project-sprint-card]');

        if (!card) {
            return;
        }

        pendingSprintCard = card;
        pointerStartX = event.clientX;
        pointerStartY = event.clientY;
        event.preventDefault();
    });

    document.addEventListener('pointermove', handlePointerMove);
    document.addEventListener('pointerup', handlePointerUp);
    document.addEventListener('pointercancel', handlePointerCancel);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            handlePointerCancel();
        }
    });

    sprintList.dataset.projectSprintListInitialized = 'true';
};

const initializeProjectModuleSection = (section = document.querySelector('[data-project-module-section]')) => {
    if (!section || section.dataset.projectModuleSectionInitialized === 'true') {
        return;
    }

    const moduleList = section.querySelector('[data-project-module-list]');
    const restoreModal = section.querySelector('[data-project-module-restore-modal]');
    const restoreOpenButton = section.querySelector('[data-project-module-restore-open]');
    const sprintRestoreOpenButtons = section.querySelectorAll('[data-project-sprint-restore-open]');
    const sprintRestoreModals = section.querySelectorAll('[data-project-sprint-restore-modal]');
    const csrfToken = getCsrfToken();
    let draggedModuleCard = null;
    let dragHandleCard = null;
    let dragStartOrder = [];

    const getModuleCards = () => Array.from(moduleList.querySelectorAll('[data-project-module-card]'));
    const getModuleIds = () => getModuleCards().map((card) => Number(card.dataset.moduleId));

    const openRestoreModal = () => {
        if (restoreModal) {
            restoreModal.classList.remove('hidden');
        }
    };

    const closeRestoreModal = () => {
        if (restoreModal) {
            restoreModal.classList.add('hidden');
        }
    };

    if (restoreOpenButton && restoreModal) {
        restoreOpenButton.addEventListener('click', openRestoreModal);

        restoreModal.querySelectorAll('[data-project-module-restore-close]').forEach((button) => {
            button.addEventListener('click', closeRestoreModal);
        });

        restoreModal.addEventListener('click', async function (event) {
            const restoreButton = event.target.closest('[data-project-module-restore-action]');

            if (!restoreButton) {
                return;
            }

            const moduleName = restoreButton.dataset.moduleName || 'this module';
            const restoreUrl = restoreButton.dataset.restoreUrl;

            const result = await Alert.confirm({
                target: restoreModal,
                title: 'Restore Module',
                text: `Restore ${moduleName}?`,
                confirmText: 'Yes, restore',
                cancelText: 'Cancel',
            });

            if (!result.isConfirmed || !restoreUrl) {
                return;
            }

            restoreButton.disabled = true;

            try {
                const response = await fetch(restoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json();

                if (!response.ok || !payload.status) {
                    throw new Error(payload.message || 'Unable to restore this module.');
                }

                closeRestoreModal();
                Alert.success(payload.message || 'Project module restored successfully.');

                if (!replaceRenderedSection(payload)) {
                    window.location.reload();
                }
            } catch (error) {
                restoreButton.disabled = false;
                Alert.error(error.message || 'Unable to restore this module.');
            }
        });
    }

    sprintRestoreOpenButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const moduleId = button.dataset.projectSprintRestoreOpen;
            const modal = section.querySelector(`[data-project-sprint-restore-modal="${moduleId}"]`);

            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    });

    sprintRestoreModals.forEach((modal) => {
        const closeSprintRestoreModal = () => {
            modal.classList.add('hidden');
        };

        modal.querySelectorAll('[data-project-sprint-restore-close]').forEach((button) => {
            button.addEventListener('click', closeSprintRestoreModal);
        });

        modal.addEventListener('click', async function (event) {
            const restoreButton = event.target.closest('[data-project-sprint-restore-action]');

            if (!restoreButton) {
                return;
            }

            const sprintName = restoreButton.dataset.sprintName || 'this sprint';
            const restoreUrl = restoreButton.dataset.restoreUrl;

            const result = await Alert.confirm({
                target: modal,
                title: 'Restore Sprint',
                text: `Restore ${sprintName}?`,
                confirmText: 'Yes, restore',
                cancelText: 'Cancel',
            });

            if (!result.isConfirmed || !restoreUrl) {
                return;
            }

            restoreButton.disabled = true;

            try {
                const response = await fetch(restoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json();

                if (!response.ok || !payload.status) {
                    throw new Error(payload.message || 'Unable to restore this sprint.');
                }

                closeSprintRestoreModal();
                Alert.success(payload.message || 'Project sprint restored successfully.');

                if (!replaceRenderedSection(payload)) {
                    window.location.reload();
                }
            } catch (error) {
                restoreButton.disabled = false;
                Alert.error(error.message || 'Unable to restore this sprint.');
            }
        });
    });

    section.querySelectorAll('[data-project-sprint-list]').forEach((sprintList) => {
        initializeProjectSprintList(sprintList);
    });

    section.addEventListener('click', function (event) {
        const toggleButton = event.target.closest('[data-project-module-toggle]');

        if (!toggleButton) {
            return;
        }

        const moduleId = Number(toggleButton.dataset.moduleId);
        const panel = getProjectModuleSprintsPanel(moduleId, section);

        if (!panel || panel.dataset.loaded === 'true') {
            return;
        }

        fetchProjectModuleSprints(moduleId, { root: section }).catch((error) => {
            Alert.error(error.message || 'Unable to load project sprints.');
        });
    });

    section.querySelectorAll('[data-project-module-sprints-panel][data-autoload="true"]').forEach((panel) => {
        const moduleId = Number(panel.dataset.moduleId);

        fetchProjectModuleSprints(moduleId, { root: section }).catch(() => {});
    });

    handleProjectModuleDeepLink(section).catch((error) => {
        Alert.error(error.message || 'Unable to open the requested sprint.');
    });

    if (moduleList && moduleList.dataset.reorderUrl && csrfToken) {
        const syncVisibleOrderBadges = () => {
            getModuleCards().forEach((card, index) => {
                const badge = card.querySelector('[data-project-module-order-badge]');

                if (badge) {
                    badge.textContent = String(index + 1);
                }
            });
        };

        const persistModuleOrder = async () => {
            const moduleIds = getModuleIds();

            try {
                const response = await fetch(moduleList.dataset.reorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ module_ids: moduleIds }),
                });

                const result = await response.json();

                if (!response.ok || !result.status) {
                    throw new Error(result.message || 'Unable to save the new module order.');
                }

                syncVisibleOrderBadges();
                replaceRenderedSection(result);
                Alert.success(result.message || 'Module order updated successfully.');
            } catch (error) {
                Alert.error(error.message || 'Unable to save the new module order.');
                window.location.reload();
            }
        };

        const resetDraggedCardState = () => {
            if (draggedModuleCard) {
                draggedModuleCard.classList.remove('opacity-60', 'scale-[0.99]');
                draggedModuleCard.setAttribute('draggable', 'false');
                draggedModuleCard.style.boxShadow = '';
            }

            if (dragHandleCard) {
                dragHandleCard.setAttribute('draggable', 'false');
            }

            draggedModuleCard = null;
            dragHandleCard = null;
        };

        moduleList.addEventListener('mousedown', function (event) {
            const handle = event.target.closest('[data-project-module-drag-handle]');

            if (!handle) {
                return;
            }

            const card = handle.closest('[data-project-module-card]');

            if (!card) {
                return;
            }

            dragHandleCard = card;
            card.setAttribute('draggable', 'true');
        });

        moduleList.addEventListener('mouseup', function () {
            if (!draggedModuleCard && dragHandleCard) {
                dragHandleCard.setAttribute('draggable', 'false');
                dragHandleCard = null;
            }
        });

        moduleList.addEventListener('mouseleave', function () {
            if (!draggedModuleCard && dragHandleCard) {
                dragHandleCard.setAttribute('draggable', 'false');
                dragHandleCard = null;
            }
        });

        moduleList.addEventListener('dragstart', function (event) {
            const card = event.target.closest('[data-project-module-card]');

            if (!card || card !== dragHandleCard) {
                event.preventDefault();
                return;
            }

            draggedModuleCard = card;
            dragStartOrder = getModuleIds();
            card.classList.add('opacity-60', 'scale-[0.99]');
            card.style.boxShadow = '0 18px 35px -18px rgba(15, 23, 42, 0.35)';

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.moduleId || '');
            }
        });

        moduleList.addEventListener('dragover', function (event) {
            if (draggedModuleCard) {
                event.preventDefault();
            }

            const targetCard = event.target.closest('[data-project-module-card]');

            if (!draggedModuleCard || !targetCard || targetCard === draggedModuleCard) {
                return;
            }

            const targetBounds = targetCard.getBoundingClientRect();
            const insertAfterTarget = event.clientY > targetBounds.top + (targetBounds.height / 2);

            if (insertAfterTarget) {
                moduleList.insertBefore(draggedModuleCard, targetCard.nextElementSibling);
                return;
            }

            moduleList.insertBefore(draggedModuleCard, targetCard);
        });

        moduleList.addEventListener('drop', async function (event) {
            if (!draggedModuleCard) {
                return;
            }

            event.preventDefault();

            const currentOrder = getModuleIds();

            if (JSON.stringify(currentOrder) === JSON.stringify(dragStartOrder)) {
                resetDraggedCardState();
                return;
            }

            syncVisibleOrderBadges();
            await persistModuleOrder();
        });

        moduleList.addEventListener('dragend', function () {
            resetDraggedCardState();
            dragStartOrder = [];
        });
    }

    section.dataset.projectModuleSectionInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', function () {
    initializeProjectModuleBuilderModal();
    initializeProjectSprintBuilderModal();
    initializeProjectModuleSection();
});

document.addEventListener('project-tab:loaded', function (event) {
    if (event.detail?.tab !== 'modules') {
        return;
    }

    clearProjectModuleSprintCache();
    initializeProjectModuleBuilderModal();
    initializeProjectSprintBuilderModal();
    initializeProjectModuleSection(event.detail.panel.querySelector('[data-project-module-section]'));
});

document.addEventListener('ajax-form:rendered', function (event) {
    if (event.detail?.selector !== '[data-project-module-section]') {
        return;
    }

    clearProjectModuleSprintCache();
    document.dispatchEvent(new CustomEvent('project-tab:invalidate', {
        detail: { tab: 'tasks' },
    }));
    initializeProjectModuleSection(event.detail.root);
});
