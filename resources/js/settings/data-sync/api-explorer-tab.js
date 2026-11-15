document.addEventListener('DOMContentLoaded', function () {
    const registryEl = document.getElementById('data-sync-explorer-data');
    if (!registryEl) return;

    let explorerData = {};
    try {
        explorerData = JSON.parse(registryEl.textContent || '{}');
    } catch (error) {
        console.error('Failed to parse Explorer registry data', error);
        return;
    }

    const modules = explorerData.modules || {};
    let activeModuleSlug = 'technologies';
    let activeOperationKey = 'list';
    let activeSubtab = 'headers';
    let activeResponseCode = '200';

    // Clipboard copy helper
    function copyText(text, button) {
        if (!text) return;

        const handleSuccess = () => {
            const label = button.querySelector('.copy-label');
            if (label) {
                const originalLabel = label.textContent;
                label.textContent = 'Copied!';
                button.classList.add('text-success-300', 'border-success-300');
                setTimeout(() => {
                    label.textContent = originalLabel;
                    button.classList.remove('text-success-300', 'border-success-300');
                }, 1600);
            }
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(handleSuccess).catch(() => fallbackCopy(text, handleSuccess));
        } else {
            fallbackCopy(text, handleSuccess);
        }
    }

    function fallbackCopy(text, onSuccess) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            document.execCommand('copy');
            if (onSuccess) onSuccess();
        } catch (error) {
            console.error('Fallback copy failed', error);
        }

        document.body.removeChild(textarea);
    }

    // Set up module selection
    function selectModule(slug) {
        if (!modules[slug]) {
            slug = Object.keys(modules)[0];
        }

        activeModuleSlug = slug;
        const module = modules[slug];
        if (!module) return;

        // Highlight module in left sidebar
        document.querySelectorAll('.explorer-module-btn').forEach(button => {
            const buttonSlug = button.getAttribute('data-module-slug');
            const countBadge = button.querySelector('.explorer-op-count');

            if (buttonSlug === slug) {
                button.className = 'explorer-module-btn w-full text-left flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-semibold bg-success-300 text-white shadow-sm transition duration-150';
                if (countBadge) {
                    countBadge.className = 'explorer-op-count text-[11px] px-1.5 py-0.5 rounded font-mono bg-white/20 text-white';
                }
            } else {
                button.className = 'explorer-module-btn w-full text-left flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium text-bgray-700 hover:bg-bgray-100 dark:text-bgray-200 dark:hover:bg-darkblack-600 transition duration-150';
                if (countBadge) {
                    countBadge.className = 'explorer-op-count text-[11px] px-1.5 py-0.5 rounded font-mono bg-bgray-100 text-bgray-600 dark:bg-darkblack-400 dark:text-bgray-300';
                }
            }
        });

        // Header info
        document.getElementById('explorer-module-name').textContent = module.name;
        document.getElementById('explorer-module-desc').textContent = module.description;
        document.getElementById('explorer-module-category-badge').textContent = module.category;

        // Render operations pills
        const operationContainer = document.getElementById('explorer-operations-container');
        operationContainer.innerHTML = '';
        const operationKeys = Object.keys(module.operations || {});

        operationKeys.forEach(operationKey => {
            const operation = module.operations[operationKey];
            const operationButton = document.createElement('button');
            operationButton.type = 'button';
            operationButton.setAttribute('data-operation-key', operationKey);
            operationButton.className = 'explorer-op-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition duration-150';

            const methodLabel = document.createElement('span');
            methodLabel.className = 'text-[10px] font-mono font-bold uppercase';
            methodLabel.textContent = operation.method;

            const nameLabel = document.createElement('span');
            nameLabel.textContent = operation.name;

            operationButton.appendChild(methodLabel);
            operationButton.appendChild(nameLabel);
            operationButton.addEventListener('click', function () {
                selectOperation(operationKey);
            });

            operationContainer.appendChild(operationButton);
        });

        // Default to first operation in the module
        selectOperation(operationKeys[0] || '');
    }

    // Set up operation selection
    function selectOperation(operationKey) {
        const module = modules[activeModuleSlug];
        if (!module || !module.operations || !module.operations[operationKey]) return;

        activeOperationKey = operationKey;
        const operation = module.operations[operationKey];

        // Highlight active operation pill
        document.querySelectorAll('.explorer-op-pill').forEach(button => {
            const key = button.getAttribute('data-operation-key');
            if (key === operationKey) {
                button.className = 'explorer-op-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-success-300 text-white shadow-sm transition duration-150';
            } else {
                button.className = 'explorer-op-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-bgray-100 text-bgray-700 hover:bg-bgray-200 dark:bg-darkblack-600 dark:text-bgray-300 dark:hover:bg-darkblack-400 transition duration-150';
            }
        });

        // Populate operation top details
        document.getElementById('op-title').textContent = operation.name;
        document.getElementById('op-description').textContent = operation.description;

        // Method badge colors
        const methodBadge = document.getElementById('op-method-badge');
        methodBadge.textContent = operation.method;
        const method = (operation.method || 'GET').toUpperCase();
        if (method === 'GET') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800';
        } else if (method === 'POST') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
        } else if (method === 'PUT' || method === 'PATCH') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
        } else if (method === 'DELETE') {
            methodBadge.className = 'px-2.5 py-1 text-xs font-bold rounded font-mono uppercase tracking-wider shrink-0 bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
        }

        document.getElementById('op-full-url').textContent = operation.full_url;
        document.getElementById('op-scope-badge').textContent = operation.scope || 'none';
        document.getElementById('op-scope-desc').textContent = operation.scope_description || '';

        // Headers table
        const headersBody = document.getElementById('table-headers-body');
        headersBody.innerHTML = '';
        (operation.headers || []).forEach(header => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-bgray-50 dark:hover:bg-darkblack-600/50';
            row.innerHTML = `
                <td class="py-2.5 px-3 font-mono font-semibold text-bgray-900 dark:text-white">${header.name}</td>
                <td class="py-2.5 px-3 font-mono text-bgray-700 dark:text-bgray-300">${header.value}</td>
                <td class="py-2.5 px-3"><span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${header.required ? 'bg-error-50 text-error-400 dark:bg-error-900/30' : 'bg-bgray-100 text-bgray-500 dark:bg-darkblack-400'}">${header.required ? 'Required' : 'Optional'}</span></td>
                <td class="py-2.5 px-3 text-bgray-600 dark:text-bgray-300">${header.note || '-'}</td>
            `;
            headersBody.appendChild(row);
        });

        // Parameters tables
        const pathBody = document.getElementById('table-path-params-body');
        pathBody.innerHTML = '';
        const pathParams = operation.path_params || [];
        if (pathParams.length > 0) {
            document.getElementById('container-path-params').classList.remove('hidden');
            pathParams.forEach(parameter => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="py-2.5 px-3 font-mono font-semibold text-bgray-900 dark:text-white">${parameter.name}</td>
                    <td class="py-2.5 px-3 font-mono text-bgray-600 dark:text-bgray-300">${parameter.type}</td>
                    <td class="py-2.5 px-3"><span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${parameter.required ? 'bg-error-50 text-error-400' : 'bg-bgray-100 text-bgray-500'}">${parameter.required ? 'Required' : 'Optional'}</span></td>
                    <td class="py-2.5 px-3 font-mono text-bgray-700 dark:text-bgray-300">${parameter.example || '-'}</td>
                    <td class="py-2.5 px-3 text-bgray-600 dark:text-bgray-300">${parameter.description}</td>
                `;
                pathBody.appendChild(row);
            });
        } else {
            document.getElementById('container-path-params').classList.add('hidden');
        }

        const queryBody = document.getElementById('table-query-params-body');
        queryBody.innerHTML = '';
        const queryParams = operation.query_params || [];
        if (queryParams.length > 0) {
            document.getElementById('container-query-params').classList.remove('hidden');
            queryParams.forEach(parameter => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="py-2.5 px-3 font-mono font-semibold text-bgray-900 dark:text-white">${parameter.name}</td>
                    <td class="py-2.5 px-3 font-mono text-bgray-600 dark:text-bgray-300">${parameter.type}</td>
                    <td class="py-2.5 px-3 font-mono text-bgray-700 dark:text-bgray-300">${parameter.default || '-'}</td>
                    <td class="py-2.5 px-3 text-bgray-600 dark:text-bgray-300">${parameter.description}</td>
                `;
                queryBody.appendChild(row);
            });
        } else {
            document.getElementById('container-query-params').classList.add('hidden');
        }

        const noParams = pathParams.length === 0 && queryParams.length === 0;
        document.getElementById('params-empty-notice').classList.toggle('hidden', !noParams);

        // Payload
        const hasPayload = !!operation.formatted_payload;
        document.getElementById('payload-content-wrapper').classList.toggle('hidden', !hasPayload);
        document.getElementById('payload-empty-notice').classList.toggle('hidden', hasPayload);
        if (hasPayload) {
            document.getElementById('op-payload-code').textContent = operation.formatted_payload;
        }

        // Responses
        const pillsContainer = document.getElementById('response-status-pills');
        pillsContainer.innerHTML = '';

        const successStatus = (operation.response_success && operation.response_success.status) || 200;
        const successButton = document.createElement('button');
        successButton.type = 'button';
        successButton.setAttribute('data-status-code', String(successStatus));
        successButton.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-semibold transition';
        successButton.textContent = `${successStatus} Success`;
        successButton.addEventListener('click', () => switchResponseStatus(String(successStatus)));
        pillsContainer.appendChild(successButton);

        const errors = operation.formatted_error_responses || {};
        Object.keys(errors).forEach(errorCode => {
            const errorButton = document.createElement('button');
            errorButton.type = 'button';
            errorButton.setAttribute('data-status-code', String(errorCode));
            errorButton.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-semibold transition';
            errorButton.textContent = `${errorCode} Error`;
            errorButton.addEventListener('click', () => switchResponseStatus(String(errorCode)));
            pillsContainer.appendChild(errorButton);
        });

        switchResponseStatus(String(successStatus));
        document.getElementById('op-curl-code').textContent = operation.curl_command || '';
    }

    function switchResponseStatus(statusCode) {
        activeResponseCode = statusCode;
        const module = modules[activeModuleSlug];
        const operation = module.operations[activeOperationKey];

        document.querySelectorAll('.status-code-pill').forEach(button => {
            const code = button.getAttribute('data-status-code');
            if (code === statusCode) {
                if (code.startsWith('2')) {
                    button.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-bold bg-success-300 text-white shadow-sm';
                } else {
                    button.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-bold bg-error-300 text-white shadow-sm';
                }
            } else {
                button.className = 'status-code-pill px-2 py-0.5 rounded text-xs font-mono font-medium bg-bgray-100 text-bgray-600 hover:bg-bgray-200 dark:bg-darkblack-600 dark:text-bgray-300';
            }
        });

        const responseCode = document.getElementById('op-response-code');
        if (statusCode.startsWith('2')) {
            responseCode.textContent = operation.formatted_success_response || '{}';
            responseCode.className = 'text-cyan-300';
        } else {
            responseCode.textContent = (operation.formatted_error_responses && operation.formatted_error_responses[statusCode]) || '{}';
            responseCode.className = 'text-rose-300';
        }
    }

    // Subtab switching
    function switchSubtab(tabName) {
        activeSubtab = tabName;
        document.querySelectorAll('.op-subtab-btn').forEach(button => {
            const tab = button.getAttribute('data-subtab');
            if (tab === tabName) {
                button.className = 'op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 border-success-300 text-success-300 dark:text-success-300';
            } else {
                button.className = 'op-subtab-btn pb-3 text-xs font-semibold uppercase tracking-wider transition border-b-2 border-transparent text-bgray-500 hover:text-bgray-700 dark:text-bgray-400 dark:hover:text-bgray-200';
            }
        });

        document.querySelectorAll('.op-subtab-pane').forEach(pane => {
            pane.classList.toggle('hidden', pane.id !== `pane-${tabName}`);
        });
    }

    document.querySelectorAll('.op-subtab-btn').forEach(button => {
        button.addEventListener('click', function () {
            switchSubtab(this.getAttribute('data-subtab'));
        });
    });

    document.querySelectorAll('.explorer-module-btn').forEach(button => {
        button.addEventListener('click', function () {
            selectModule(this.getAttribute('data-module-slug'));
        });
    });

    // Copy handlers
    document.getElementById('btn-copy-url').addEventListener('click', function () {
        copyText(document.getElementById('op-full-url').textContent, this);
    });

    document.getElementById('btn-copy-headers').addEventListener('click', function () {
        const module = modules[activeModuleSlug];
        const operation = module.operations[activeOperationKey];
        const headers = (operation.headers || []).map(header => `${header.name}: ${header.value}`).join('\n');
        copyText(headers, this);
    });

    document.getElementById('btn-copy-payload').addEventListener('click', function () {
        copyText(document.getElementById('op-payload-code').textContent, this);
    });

    document.getElementById('btn-copy-response').addEventListener('click', function () {
        copyText(document.getElementById('op-response-code').textContent, this);
    });

    document.getElementById('btn-copy-curl').addEventListener('click', function () {
        copyText(document.getElementById('op-curl-code').textContent, this);
    });

    // Initial render
    selectModule('technologies');
    switchSubtab('headers');
});
