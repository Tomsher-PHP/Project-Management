import Alert from '../../alert';
import { initDatepicker } from '../../components/datepicker';
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

const renderModuleBuilderCard = (module, config, extraClass = '') => `
    <article class="select-text rounded-2xl border bg-white p-4 shadow-sm dark:bg-darkblack-600 ${extraClass}" style="border-color: ${escapeHtml(module.color || '#E5E7EB')};" data-project-module-builder-card data-module-id="${module.id ?? ''}" data-module-name="${escapeHtml(module.name || '')}" data-expanded="true" draggable="false">
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
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 text-sm font-medium text-red-500 transition duration-200 hover:border-red-300 hover:bg-red-100 dark:border-red-900/40 dark:bg-darkblack-500 dark:text-red-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400" data-project-module-builder-delete>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.5 3A1.5 1.5 0 007 4.5V5H4.75a.75.75 0 000 1.5h.538l.63 8.214A2.25 2.25 0 008.161 16.8h3.678a2.25 2.25 0 002.243-2.086l.63-8.214h.538a.75.75 0 000-1.5H13v-.5A1.5 1.5 0 0011.5 3h-3zm3 2V4.5h-3V5h3z" clip-rule="evenodd" />
                </svg>
                <span>Delete</span>
            </button>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-module-builder-toggle aria-label="Collapse module" title="Collapse module">
                <svg class="h-4 w-4 transition duration-200" viewBox="0 0 20 20" fill="currentColor" data-project-module-builder-toggle-icon>
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

        <div class="mt-4 border-t border-bgray-100 pt-4 dark:border-darkblack-400" data-project-module-builder-body>
            <div class="grid gap-4 xl:grid-cols-2">
            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Name</label>
                <input type="text" name="name" value="${escapeHtml(module.name || '')}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Owner</label>
                <select name="owner_id" class="tom-select w-full" data-sort="0">
                    ${renderSelectOptions(config.owners || [], module.owner_id, 'Select owner')}
                </select>
            </div>

            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Estimated Minutes</label>
                <input type="number" min="0" step="1" name="estimated_time_minutes" value="${escapeHtml(module.estimated_time_minutes ?? 0)}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
            </div>

            <div>
                <label class="mb-2 block text-left text-xs font-semibold uppercase tracking-wide text-bgray-500 dark:text-bgray-300">Date Range</label>
                <input type="text" value="${escapeHtml([module.start_date, module.end_date].filter(Boolean).join(' to '))}" class="datepicker project-module-date-range w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" data-mode="range" data-format="Y-m-d" data-project-module-builder-date-range>
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

const initializeProjectModuleBuilderModal = () => {
    const modal = document.getElementById('project-module-modal');

    if (!modal || modal.dataset.projectModuleBuilderInitialized === 'true') {
        return;
    }

    const configNode = document.getElementById('project-module-builder-config');
    const workspace = modal.querySelector('[data-project-module-builder-workspace]');
    const library = modal.querySelector('[data-project-module-builder-library]');
    const searchInput = modal.querySelector('[data-project-module-builder-library-search]');
    const resetSearchButton = modal.querySelector('[data-project-module-builder-reset-search]');
    const countBadge = modal.querySelector('[data-project-module-builder-count]');
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
        initDatepicker('.project-module-date-range', {}, card);
        syncCardDateRange(card);
    };

    const initializeCardTomSelect = (card) => {
        initTomSelect(card);
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
            syncDescriptionCount(card);
        });

        ensureEmptyState();
        getCards().forEach((card) => setCardExpanded(card, false));

        const firstCard = getCards()[0];

        if (firstCard) {
            setCardExpanded(firstCard, true);
        }
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
        card.querySelector('[name="estimated_time_minutes"]').value = module.estimated_time_minutes ?? 0;
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
        const payload = normalizePayload({
            name: buildUniqueModuleName(libraryModule.name || 'New Module'),
            color: libraryModule.color || '#22C55E',
            description: libraryModule.description || '',
            estimated_time_minutes: 0,
            owner_id: '',
            start_date: '',
            end_date: '',
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
            Alert.error(error.message || 'Unable to create the project module.');
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
            Alert.error(error.message || 'Unable to update the project module.');
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
            Alert.error(error.message || 'Unable to delete the project module.');
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
            Alert.error(error.message || 'Unable to reorder project modules.');
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

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

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
        queueModuleSave(card, event.target.tagName === 'TEXTAREA' ? 650 : 500);
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
    const firstCard = getCards()[0];
    if (firstCard) {
        setCardExpanded(firstCard, true);
    }
    getCards().forEach((card) => initializeCardDatepicker(card));
    getCards().forEach((card) => initializeCardTomSelect(card));
    getCards().forEach((card) => syncDescriptionCount(card));
    modal.dataset.projectModuleBuilderInitialized = 'true';
};

const initializeProjectSprintModal = () => {
    const modal = document.getElementById('project-sprint-modal');

    if (!modal || modal.dataset.projectSprintModalInitialized === 'true') {
        return;
    }

    const librarySelect = modal.querySelector('#library_sprint_id');
    const nameInput = modal.querySelector('[name="name"]');
    const colorInput = modal.querySelector('[name="color"]');
    const descriptionInput = modal.querySelector('[name="description"]');
    const parentModuleInput = modal.querySelector('#project_sprint_module_id');
    const form = modal.querySelector('form');
    const sprintStoreUrlTemplate = modal.querySelector('#project_sprint_store_url_template')?.value || '';
    const descriptionCount = modal.querySelector('[data-project-sprint-description-count]');

    if (!librarySelect || !nameInput || !colorInput || !descriptionInput || !parentModuleInput || !form || !sprintStoreUrlTemplate) {
        modal.dataset.projectSprintModalInitialized = 'true';
        return;
    }

    const updateDescriptionCount = () => {
        if (descriptionCount) {
            descriptionCount.textContent = String(descriptionInput.value.length);
        }
    };

    const fillFromLibraryOption = () => {
        const selectedOption = librarySelect.options[librarySelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            colorInput.value = colorInput.value || '#000000';
            updateDescriptionCount();
            return;
        }

        nameInput.value = selectedOption.dataset.name || '';
        colorInput.value = selectedOption.dataset.color || '#000000';
        descriptionInput.value = selectedOption.dataset.description || '';
        updateDescriptionCount();
    };

    descriptionInput.addEventListener('input', updateDescriptionCount);
    librarySelect.addEventListener('change', fillFromLibraryOption);
    updateDescriptionCount();

    const syncSprintFormAction = () => {
        if (modal.dataset.projectSprintMode !== 'create') {
            return;
        }

        const moduleId = parentModuleInput.value || '';

        if (!moduleId) {
            return;
        }

        form.setAttribute('action', sprintStoreUrlTemplate.replace('__MODULE__', moduleId));
    };

    parentModuleInput.addEventListener('change', syncSprintFormAction);

    document.addEventListener('click', function (event) {
        const createButton = event.target.closest('.modal-open[data-module-context="project-sprint"]');
        const editButton = event.target.closest('.edit-record[data-module-context="project-sprint"]');

        if (!createButton && !editButton) {
            return;
        }

        window.setTimeout(() => {
            librarySelect.value = '';

            if (librarySelect.tomselect) {
                librarySelect.tomselect.clear(true);
            }

            if (createButton) {
                modal.dataset.projectSprintMode = 'create';
                parentModuleInput.value = createButton.dataset.projectModuleId || '';

                if (parentModuleInput.tomselect) {
                    parentModuleInput.tomselect.setValue(createButton.dataset.projectModuleId || '', true);
                }

                syncSprintFormAction();
            }

            if (editButton) {
                modal.dataset.projectSprintMode = 'edit';
            }

            updateDescriptionCount();
        }, 0);
    });

    modal.dataset.projectSprintModalInitialized = 'true';
};

const initializeProjectModuleSection = (section = document.querySelector('[data-project-module-section]')) => {
    if (!section || section.dataset.projectModuleSectionInitialized === 'true') {
        return;
    }

    const moduleList = section.querySelector('[data-project-module-list]');
    const restoreModal = section.querySelector('[data-project-module-restore-modal]');
    const restoreOpenButton = section.querySelector('[data-project-module-restore-open]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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

    if (!moduleList || !moduleList.dataset.reorderUrl || !csrfToken) {
        section.dataset.projectModuleSectionInitialized = 'true';
        return;
    }

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

    section.dataset.projectModuleSectionInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', function () {
    initializeProjectModuleBuilderModal();
    initializeProjectSprintModal();
    initializeProjectModuleSection();
});

document.addEventListener('project-tab:loaded', function (event) {
    if (event.detail?.tab !== 'modules') {
        return;
    }

    initializeProjectModuleBuilderModal();
    initializeProjectSprintModal();
    initializeProjectModuleSection(event.detail.panel.querySelector('[data-project-module-section]'));
});

document.addEventListener('ajax-form:rendered', function (event) {
    if (event.detail?.selector !== '[data-project-module-section]') {
        return;
    }

    initializeProjectModuleSection(event.detail.root);
});
