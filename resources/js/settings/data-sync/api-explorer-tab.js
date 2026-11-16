export const EXPLORER_SECTIONS = ['headers', 'params', 'payload', 'responses', 'curl'];

export function normalizeExplorerSearch(value) {
    return String(value || '').trim().replace(/\s+/g, ' ').toLocaleLowerCase();
}

export function getExplorerSearchMatches(modules, value) {
    const query = normalizeExplorerSearch(value);
    const matches = {};

    Object.entries(modules || {}).forEach(([moduleSlug, module]) => {
        const operations = module.operations || {};
        const moduleText = normalizeExplorerSearch([module.name, module.description].join(' '));
        const moduleMatches = !query || moduleText.includes(query);
        const operationKeys = Object.entries(operations)
            .filter(([, operation]) => {
                if (moduleMatches) return true;

                const operationText = normalizeExplorerSearch([
                    operation.name,
                    operation.description,
                    operation.method,
                    operation.endpoint,
                ].join(' '));

                return operationText.includes(query);
            })
            .map(([operationKey]) => operationKey);

        if (moduleMatches || operationKeys.length > 0) {
            matches[moduleSlug] = operationKeys;
        }
    });

    return matches;
}

export function resolveExplorerState(modules, searchParams, fallbackSection = 'headers') {
    const moduleKeys = Object.keys(modules || {});
    const requestedModule = searchParams.get('module');
    const moduleSlug = requestedModule && modules[requestedModule]
        ? requestedModule
        : (moduleKeys[0] || '');
    const operations = modules[moduleSlug]?.operations || {};
    const operationKeys = Object.keys(operations);
    const requestedOperation = searchParams.get('operation');
    const operationKey = requestedOperation && operations[requestedOperation]
        ? requestedOperation
        : (operationKeys[0] || '');
    const requestedSection = searchParams.get('section');
    const section = EXPLORER_SECTIONS.includes(requestedSection)
        ? requestedSection
        : fallbackSection;

    return { moduleSlug, operationKey, section };
}

export function buildExplorerUrl(currentUrl, explorerState) {
    const url = new URL(currentUrl);
    url.searchParams.set('module', explorerState.moduleSlug);
    url.searchParams.set('operation', explorerState.operationKey);
    url.searchParams.set('section', explorerState.section);

    return url;
}

function initializeApiExplorer() {
    const root = document.getElementById('api-explorer-root');
    const registryElement = document.getElementById('data-sync-explorer-data');

    if (!root || !registryElement || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';

    let explorerData = {};
    try {
        explorerData = JSON.parse(registryElement.textContent || '{}');
    } catch (error) {
        console.error('Failed to parse Explorer registry data', error);
        return;
    }

    const modules = explorerData.modules || {};
    const categories = explorerData.categories || {};
    const moduleKeys = Object.keys(modules);
    const copyTimers = new WeakMap();

    const elements = {
        content: document.getElementById('explorer-content'),
        emptyState: document.getElementById('explorer-empty-state'),
        operationDetails: document.getElementById('explorer-operation-details'),
        operationEmpty: document.getElementById('explorer-operations-empty'),
        operationContainer: document.getElementById('explorer-operations-container'),
        mobileToggle: document.getElementById('explorer-mobile-nav-toggle'),
        navigationBody: document.getElementById('explorer-navigation-body'),
        searchInput: document.getElementById('explorer-search'),
        searchClear: document.getElementById('explorer-search-clear'),
        searchEmpty: document.getElementById('explorer-search-empty'),
        searchEmptyClear: document.getElementById('explorer-search-empty-clear'),
        copyStatus: document.getElementById('explorer-copy-status'),
        moduleName: document.getElementById('explorer-module-name'),
        moduleDescription: document.getElementById('explorer-module-desc'),
        moduleCategory: document.getElementById('explorer-module-category-badge'),
        operationTitle: document.getElementById('op-title'),
        operationDescription: document.getElementById('op-description'),
        methodBadge: document.getElementById('op-method-badge'),
        fullUrl: document.getElementById('op-full-url'),
        scopeBadge: document.getElementById('op-scope-badge'),
        scopeDescription: document.getElementById('op-scope-desc'),
        headersBody: document.getElementById('table-headers-body'),
        pathBody: document.getElementById('table-path-params-body'),
        queryBody: document.getElementById('table-query-params-body'),
        pathContainer: document.getElementById('container-path-params'),
        queryContainer: document.getElementById('container-query-params'),
        paramsEmpty: document.getElementById('params-empty-notice'),
        payloadWrapper: document.getElementById('payload-content-wrapper'),
        payloadEmpty: document.getElementById('payload-empty-notice'),
        payloadCode: document.getElementById('op-payload-code'),
        responsePills: document.getElementById('response-status-pills'),
        responseCode: document.getElementById('op-response-code'),
        curlCode: document.getElementById('op-curl-code'),
    };

    let state = resolveExplorerState(modules, new URLSearchParams(window.location.search));
    let activeResponseCode = '';
    let searchQuery = '';

    function announce(message) {
        if (!elements.copyStatus) return;

        elements.copyStatus.textContent = '';
        window.requestAnimationFrame(() => {
            elements.copyStatus.textContent = message;
        });
    }

    function setCopyFeedback(button, succeeded) {
        const label = button?.querySelector('.copy-label');
        if (!label) return;

        const originalLabel = button.dataset.copyLabel || label.textContent;
        button.dataset.copyLabel = originalLabel;
        label.textContent = succeeded ? 'Copied' : 'Copy failed';
        button.classList.toggle('border-success-300', succeeded);
        button.classList.toggle('text-success-400', succeeded);
        announce(succeeded ? `${originalLabel} copied.` : `Unable to copy ${originalLabel.toLowerCase()}.`);

        if (copyTimers.has(button)) {
            window.clearTimeout(copyTimers.get(button));
        }

        copyTimers.set(button, window.setTimeout(() => {
            label.textContent = originalLabel;
            button.classList.remove('border-success-300', 'text-success-400');
            copyTimers.delete(button);
        }, 1600));
    }

    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            console.error('Fallback copy failed', error);
        } finally {
            textarea.remove();
        }

        return copied;
    }

    async function copyText(text, button) {
        if (!text) {
            setCopyFeedback(button, false);
            return;
        }

        let copied = false;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                copied = true;
            } else {
                copied = fallbackCopy(text);
            }
        } catch (error) {
            copied = fallbackCopy(text);
        }

        setCopyFeedback(button, copied);
    }

    function createTextCell(value, className = '') {
        const cell = document.createElement('td');
        cell.className = `px-3 py-2.5 ${className}`;
        cell.textContent = value ?? '-';
        return cell;
    }

    function createRequirementCell(required) {
        const cell = document.createElement('td');
        cell.className = 'px-3 py-2.5';

        const label = document.createElement('span');
        label.className = required
            ? 'rounded border border-error-200 px-1.5 py-0.5 text-[10px] font-bold text-error-300 dark:border-error-900/50'
            : 'rounded border border-bgray-200 px-1.5 py-0.5 text-[10px] font-bold text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300';
        label.textContent = required ? 'Required' : 'Optional';
        cell.appendChild(label);

        return cell;
    }

    function appendEmptyTableRow(body, columnCount, message) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = columnCount;
        cell.className = 'px-3 py-5 text-center text-xs text-bgray-600 dark:text-bgray-300';
        cell.textContent = message;
        row.appendChild(cell);
        body.appendChild(row);
    }

    function renderJson(codeElement, json) {
        codeElement.replaceChildren();
        const source = String(json || '{}');
        const tokenPattern = /("(?:\\.|[^"\\])*")(?=\s*:)|("(?:\\.|[^"\\])*")|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\btrue\b|\bfalse\b|\bnull\b/g;
        let cursor = 0;
        let match;

        while ((match = tokenPattern.exec(source)) !== null) {
            if (match.index > cursor) {
                codeElement.appendChild(document.createTextNode(source.slice(cursor, match.index)));
            }

            const token = document.createElement('span');
            token.textContent = match[0];

            if (match[1]) {
                token.className = 'text-sky-300';
            } else if (match[2]) {
                token.className = 'text-emerald-300';
            } else if (match[0] === 'true' || match[0] === 'false') {
                token.className = 'text-violet-300';
            } else if (match[0] === 'null') {
                token.className = 'text-bgray-300';
            } else {
                token.className = 'text-amber-300';
            }

            codeElement.appendChild(token);
            cursor = tokenPattern.lastIndex;
        }

        if (cursor < source.length) {
            codeElement.appendChild(document.createTextNode(source.slice(cursor)));
        }
    }

    function methodBadgeClasses(method) {
        const base = 'shrink-0 rounded border px-2.5 py-1 font-mono text-xs font-bold uppercase tracking-wider';
        const styles = {
            GET: 'border-blue-200 text-blue-700 dark:border-blue-800 dark:text-blue-300',
            POST: 'border-success-300 text-success-300 dark:border-success-300 dark:text-success-300',
            PUT: 'border-amber-200 text-amber-700 dark:border-amber-800 dark:text-amber-300',
            PATCH: 'border-amber-200 text-amber-700 dark:border-amber-800 dark:text-amber-300',
            DELETE: 'border-rose-200 text-rose-700 dark:border-rose-800 dark:text-rose-300',
        };

        return `${base} ${styles[method] || 'border-bgray-200 text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300'}`;
    }

    function updateUrl(mode = 'push') {
        if (!state.moduleSlug || !state.operationKey) return;

        const url = buildExplorerUrl(window.location.href, state);

        const method = mode === 'replace' ? 'replaceState' : 'pushState';
        window.history[method]({ dataSyncExplorer: true }, '', url);
    }

    function closeMobileNavigation() {
        if (!window.matchMedia('(max-width: 1023px)').matches) return;

        elements.navigationBody?.classList.add('hidden');
        elements.mobileToggle?.setAttribute('aria-expanded', 'false');
    }

    function updateSidebarState() {
        const matches = getExplorerSearchMatches(modules, searchQuery);

        root.querySelectorAll('[data-explorer-module-item]').forEach((moduleItem) => {
            const moduleSlug = moduleItem.dataset.moduleSlug;
            const operationMatches = matches[moduleSlug] || [];
            const isVisible = Object.hasOwn(matches, moduleSlug);
            const isActive = moduleSlug === state.moduleSlug;
            const moduleButton = moduleItem.querySelector('.explorer-module-btn');
            const operationList = moduleItem.querySelector('.explorer-module-operations');

            moduleItem.hidden = !isVisible;
            moduleButton?.setAttribute('aria-expanded', String(Boolean(searchQuery) || isActive));
            moduleButton?.setAttribute('aria-current', isActive ? 'true' : 'false');
            operationList?.classList.toggle('hidden', !searchQuery && !isActive);

            if (moduleButton) {
                moduleButton.className = isActive
                    ? 'explorer-module-btn flex min-h-10 w-full items-center justify-between rounded-lg border-l-2 border-success-300 bg-bgray-50 px-3 py-2.5 text-left text-xs font-semibold text-bgray-900 transition focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-darkblack-600 dark:text-bgray-300'
                    : 'explorer-module-btn flex min-h-10 w-full items-center justify-between rounded-lg border-l-2 border-transparent px-3 py-2.5 text-left text-xs font-medium text-bgray-700 transition hover:bg-bgray-50 focus:outline-none focus:ring-2 focus:ring-success-300 dark:text-bgray-300 dark:hover:bg-darkblack-600';
            }

            moduleItem.querySelectorAll('[data-explorer-operation-item]').forEach((operationItem) => {
                const operationKey = operationItem.dataset.operationKey;
                const operationButton = operationItem.querySelector('.explorer-sidebar-operation');
                const operationVisible = !searchQuery || operationMatches.includes(operationKey);
                const operationActive = isActive && operationKey === state.operationKey;

                operationItem.hidden = !operationVisible;
                if (operationButton) {
                    operationButton.setAttribute('aria-current', operationActive ? 'true' : 'false');
                    operationButton.className = operationActive
                        ? 'explorer-sidebar-operation flex min-h-9 w-full items-center gap-2 rounded bg-bgray-50 px-2 py-1.5 text-left text-xs font-semibold text-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-darkblack-600'
                        : 'explorer-sidebar-operation flex min-h-9 w-full items-center gap-2 rounded px-2 py-1.5 text-left text-xs text-bgray-600 transition hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:text-bgray-300';
                }
            });
        });

        root.querySelectorAll('[data-explorer-category]').forEach((category) => {
            category.hidden = ![...category.querySelectorAll('[data-explorer-module-item]')]
                .some((item) => !item.hidden);
        });

        const hasMatches = Object.keys(matches).length > 0;
        if (elements.searchEmpty) elements.searchEmpty.hidden = hasMatches;
        if (elements.searchClear) elements.searchClear.hidden = !searchQuery;
    }

    function renderOperations(module) {
        elements.operationContainer.replaceChildren();
        const operationKeys = Object.keys(module.operations || {});
        elements.operationEmpty.hidden = operationKeys.length > 0;
        elements.operationDetails.hidden = operationKeys.length === 0;

        operationKeys.forEach((operationKey) => {
            const operation = module.operations[operationKey];
            const button = document.createElement('button');
            const method = document.createElement('span');
            const label = document.createElement('span');
            const isActive = operationKey === state.operationKey;

            button.type = 'button';
            button.dataset.operationKey = operationKey;
            button.setAttribute('aria-pressed', String(isActive));
            button.className = isActive
                ? 'explorer-op-pill inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-success-300 bg-bgray-50 px-3 py-1.5 text-xs font-semibold text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-darkblack-600 dark:text-bgray-300'
                : 'explorer-op-pill inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-bgray-200 px-3 py-1.5 text-xs font-medium text-bgray-700 transition hover:border-success-300 focus:outline-none focus:ring-2 focus:ring-success-300 dark:border-darkblack-400 dark:text-bgray-300';

            method.className = 'font-mono text-[10px] font-bold uppercase';
            method.textContent = operation.method;
            label.textContent = operation.name;

            button.append(method, label);
            button.addEventListener('click', () => {
                applyState({
                    moduleSlug: state.moduleSlug,
                    operationKey,
                    section: state.section,
                }, 'push');
            });
            elements.operationContainer.appendChild(button);
        });
    }

    function renderHeaders(headers) {
        elements.headersBody.replaceChildren();

        if (!headers.length) {
            appendEmptyTableRow(elements.headersBody, 4, 'No request headers are documented.');
            return;
        }

        headers.forEach((header) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-bgray-50 dark:hover:bg-darkblack-600/50';
            row.append(
                createTextCell(header.name, 'font-mono font-semibold text-bgray-900 dark:text-bgray-300'),
                createTextCell(header.value, 'font-mono text-bgray-700 dark:text-bgray-300'),
                createRequirementCell(Boolean(header.required)),
                createTextCell(header.note || '-', 'text-bgray-600 dark:text-bgray-300'),
            );
            elements.headersBody.appendChild(row);
        });
    }

    function renderParameters(operation) {
        const pathParameters = operation.path_params || [];
        const queryParameters = operation.query_params || [];

        elements.pathBody.replaceChildren();
        elements.queryBody.replaceChildren();
        elements.pathContainer.classList.toggle('hidden', pathParameters.length === 0);
        elements.queryContainer.classList.toggle('hidden', queryParameters.length === 0);
        elements.paramsEmpty.classList.toggle('hidden', pathParameters.length + queryParameters.length > 0);

        pathParameters.forEach((parameter) => {
            const row = document.createElement('tr');
            row.append(
                createTextCell(parameter.name, 'font-mono font-semibold text-bgray-900 dark:text-bgray-300'),
                createTextCell(parameter.type, 'font-mono text-bgray-600 dark:text-bgray-300'),
                createRequirementCell(Boolean(parameter.required)),
                createTextCell(parameter.example || '-', 'font-mono text-bgray-700 dark:text-bgray-300'),
                createTextCell(parameter.description, 'text-bgray-600 dark:text-bgray-300'),
            );
            elements.pathBody.appendChild(row);
        });

        queryParameters.forEach((parameter) => {
            const row = document.createElement('tr');
            row.append(
                createTextCell(parameter.name, 'font-mono font-semibold text-bgray-900 dark:text-bgray-300'),
                createTextCell(parameter.type, 'font-mono text-bgray-600 dark:text-bgray-300'),
                createTextCell(parameter.default || '-', 'font-mono text-bgray-700 dark:text-bgray-300'),
                createTextCell(parameter.description, 'text-bgray-600 dark:text-bgray-300'),
            );
            elements.queryBody.appendChild(row);
        });
    }

    function renderResponses(operation) {
        elements.responsePills.replaceChildren();
        const successStatus = String(operation.response_success?.status || 200);
        const statuses = [
            [successStatus, 'Success'],
            ...Object.keys(operation.formatted_error_responses || {}).map((code) => [String(code), 'Error']),
        ];

        statuses.forEach(([statusCode, label]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.statusCode = statusCode;
            button.className = 'status-code-pill rounded border px-2 py-1 font-mono text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-success-300';
            button.textContent = `${statusCode} ${label}`;
            button.addEventListener('click', () => switchResponseStatus(statusCode));
            elements.responsePills.appendChild(button);
        });

        switchResponseStatus(successStatus);
    }

    function updateTabCounts(operation) {
        const counts = {
            headers: (operation.headers || []).length,
            params: (operation.path_params || []).length + (operation.query_params || []).length,
            payload: operation.formatted_payload ? 1 : 0,
            responses: 1 + Object.keys(operation.formatted_error_responses || {}).length,
        };

        root.querySelectorAll('[data-tab-count]').forEach((element) => {
            const count = counts[element.dataset.tabCount];
            element.textContent = Number.isInteger(count) ? String(count) : '';
        });
    }

    function renderOperation(operation) {
        elements.operationTitle.textContent = operation.name || '';
        elements.operationDescription.textContent = operation.description || '';

        const method = String(operation.method || 'GET').toUpperCase();
        elements.methodBadge.textContent = method;
        elements.methodBadge.className = methodBadgeClasses(method);
        elements.fullUrl.textContent = operation.full_url || '';
        elements.scopeBadge.textContent = operation.scope || 'none';
        elements.scopeDescription.textContent = operation.scope_description || '';

        renderHeaders(operation.headers || []);
        renderParameters(operation);

        const hasPayload = Boolean(operation.formatted_payload);
        elements.payloadWrapper.classList.toggle('hidden', !hasPayload);
        elements.payloadEmpty.classList.toggle('hidden', hasPayload);
        renderJson(elements.payloadCode, operation.formatted_payload || '{}');

        renderResponses(operation);
        elements.curlCode.textContent = operation.curl_command || '';
        updateTabCounts(operation);
    }

    function renderCurrentState() {
        const module = modules[state.moduleSlug];
        const operation = module?.operations?.[state.operationKey];

        if (!module) return;

        elements.moduleName.textContent = module.name || '';
        elements.moduleDescription.textContent = module.description || '';
        elements.moduleCategory.textContent = categories[module.category]?.name || module.category || '';
        renderOperations(module);

        if (operation) {
            renderOperation(operation);
        }

        updateSidebarState();
        switchSubtab(state.section, false);
    }

    function applyState(nextState, historyMode = null) {
        const module = modules[nextState.moduleSlug];
        if (!module) return;

        const operationKeys = Object.keys(module.operations || {});
        const operationKey = module.operations?.[nextState.operationKey]
            ? nextState.operationKey
            : (operationKeys[0] || '');
        const section = EXPLORER_SECTIONS.includes(nextState.section)
            ? nextState.section
            : 'headers';

        state = { moduleSlug: nextState.moduleSlug, operationKey, section };
        renderCurrentState();

        if (historyMode) updateUrl(historyMode);
    }

    function switchResponseStatus(statusCode) {
        const operation = modules[state.moduleSlug]?.operations?.[state.operationKey];
        if (!operation) return;

        const successStatus = String(operation.response_success?.status || 200);
        const errors = operation.formatted_error_responses || {};
        const isSuccess = statusCode === successStatus;
        const response = isSuccess
            ? operation.formatted_success_response
            : errors[statusCode];

        if (!response) return;
        activeResponseCode = statusCode;

        elements.responsePills.querySelectorAll('.status-code-pill').forEach((button) => {
            const active = button.dataset.statusCode === activeResponseCode;
            const success = button.dataset.statusCode.startsWith('2');
            button.setAttribute('aria-pressed', String(active));
            button.className = active
                ? `status-code-pill rounded border px-2 py-1 font-mono text-xs font-bold focus:outline-none focus:ring-2 focus:ring-success-300 ${success ? 'border-success-300 text-success-400' : 'border-error-300 text-error-300'}`
                : 'status-code-pill rounded border border-bgray-200 px-2 py-1 font-mono text-xs font-medium text-bgray-600 transition hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:border-darkblack-400 dark:text-bgray-300';
        });

        renderJson(elements.responseCode, response);
    }

    function switchSubtab(section, updateHistory = true) {
        if (!EXPLORER_SECTIONS.includes(section)) section = 'headers';
        state.section = section;

        root.querySelectorAll('.op-subtab-btn').forEach((button) => {
            const active = button.dataset.subtab === section;
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
            button.className = active
                ? 'op-subtab-btn border-b-2 border-success-300 pb-3 text-xs font-semibold uppercase tracking-wider text-success-400 focus:outline-none focus:ring-2 focus:ring-success-300'
                : 'op-subtab-btn border-b-2 border-transparent pb-3 text-xs font-semibold uppercase tracking-wider text-bgray-600 transition hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:text-bgray-300';
        });

        root.querySelectorAll('.op-subtab-pane').forEach((pane) => {
            const active = pane.id === `pane-${section}`;
            pane.hidden = !active;
            pane.classList.toggle('hidden', !active);
        });

        if (updateHistory) updateUrl('push');
    }

    function filterNavigation() {
        searchQuery = normalizeExplorerSearch(elements.searchInput?.value);
        updateSidebarState();
    }

    function clearSearch() {
        if (!elements.searchInput) return;
        elements.searchInput.value = '';
        searchQuery = '';
        updateSidebarState();
        elements.searchInput.focus();
    }

    elements.mobileToggle?.addEventListener('click', () => {
        const expanded = elements.mobileToggle.getAttribute('aria-expanded') === 'true';
        elements.mobileToggle.setAttribute('aria-expanded', String(!expanded));
        elements.navigationBody?.classList.toggle('hidden', expanded);
    });

    elements.searchInput?.addEventListener('input', filterNavigation);
    elements.searchClear?.addEventListener('click', clearSearch);
    elements.searchEmptyClear?.addEventListener('click', clearSearch);

    root.querySelectorAll('.explorer-module-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const moduleSlug = button.dataset.moduleSlug;
            const module = modules[moduleSlug];
            if (!module) return;

            const matchedOperations = getExplorerSearchMatches(modules, searchQuery)[moduleSlug] || [];
            const currentOperationIsVisible = moduleSlug === state.moduleSlug
                && module.operations?.[state.operationKey]
                && (!searchQuery || matchedOperations.includes(state.operationKey));
            const operationKey = currentOperationIsVisible
                ? state.operationKey
                : (matchedOperations[0] || Object.keys(module.operations || {})[0] || '');
            applyState({ moduleSlug, operationKey, section: state.section }, 'push');
        });
    });

    root.querySelectorAll('.explorer-sidebar-operation').forEach((button) => {
        button.addEventListener('click', () => {
            applyState({
                moduleSlug: button.dataset.moduleSlug,
                operationKey: button.dataset.operationKey,
                section: state.section,
            }, 'push');
            closeMobileNavigation();
        });
    });

    const tabButtons = [...root.querySelectorAll('.op-subtab-btn')];
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', () => switchSubtab(button.dataset.subtab));
        button.addEventListener('keydown', (event) => {
            let nextIndex = null;

            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                nextIndex = (index + 1) % tabButtons.length;
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                nextIndex = (index - 1 + tabButtons.length) % tabButtons.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = tabButtons.length - 1;
            }

            if (nextIndex === null) return;
            event.preventDefault();
            tabButtons[nextIndex].focus();
            switchSubtab(tabButtons[nextIndex].dataset.subtab);
        });
    });

    document.getElementById('btn-copy-url')?.addEventListener('click', function () {
        copyText(elements.fullUrl.textContent, this);
    });
    document.getElementById('btn-copy-headers')?.addEventListener('click', function () {
        const operation = modules[state.moduleSlug]?.operations?.[state.operationKey];
        const headers = (operation?.headers || [])
            .map((header) => `${header.name}: ${header.value}`)
            .join('\n');
        copyText(headers, this);
    });
    document.getElementById('btn-copy-payload')?.addEventListener('click', function () {
        copyText(elements.payloadCode.textContent, this);
    });
    document.getElementById('btn-copy-response')?.addEventListener('click', function () {
        copyText(elements.responseCode.textContent, this);
    });
    document.getElementById('btn-copy-curl')?.addEventListener('click', function () {
        copyText(elements.curlCode.textContent, this);
    });

    window.addEventListener('popstate', () => {
        const nextState = resolveExplorerState(modules, new URLSearchParams(window.location.search));
        applyState(nextState);
    });

    const hasModules = moduleKeys.length > 0;
    elements.emptyState.hidden = hasModules;
    elements.content.hidden = !hasModules;

    if (hasModules) {
        applyState(state, 'replace');
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApiExplorer, { once: true });
    } else {
        initializeApiExplorer();
    }
}
