import Alert from '../../alert';
import { initDatepicker } from '../../components/datepicker';
import { initializeEstimatedTimeInputs } from '../../components/estimated-time-input';
import { initTomSelect } from '../../components/tom-select';

const LOADING_HTML = `
    <div class="rounded-[20px] border border-dashed border-bgray-300 px-6 py-10 text-center dark:border-darkblack-400">
        <p class="text-sm font-medium text-bgray-600 dark:text-bgray-300">Loading tasks...</p>
    </div>
`;

const ERROR_HTML = `
    <div class="rounded-[20px] border border-dashed border-red-200 bg-red-50 px-6 py-10 text-center dark:border-red-900/40 dark:bg-darkblack-500">
        <p class="text-sm font-medium text-red-500 dark:text-red-300">Unable to load tasks right now.</p>
    </div>
`;

const taskGroupPaginationObservers = new WeakMap();
const taskListPaginationObservers = new WeakMap();

const getGroupElements = (group) => ({
    icon: group.querySelector('[data-project-task-group-icon]'),
    panel: group.querySelector('[data-project-task-group-panel]'),
    body: group.querySelector('[data-project-task-group-body]'),
});

const ADVANCED_TASK_FIELDS = new Set([
    'description',
    'status_id',
    'parent_task_id',
    'task_type_id',
    'task_mode_id',
    'priority',
    'due_date',
    'tag_ids',
    'is_billable',
]);

const clearTaskFormErrors = (form) => {
    form.querySelectorAll('[data-project-task-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.classList.remove('border-red-500');
    });
};

const syncTaskFormSelectState = (form) => {
    if (!form) {
        return;
    }

    form.querySelectorAll('select').forEach((field) => {
        if (!field.tomselect) {
            return;
        }

        if (field.multiple) {
            const selectedValues = Array.from(field.selectedOptions).map((option) => option.value);
            field.tomselect.setValue(selectedValues, true);
            return;
        }

        field.tomselect.setValue(field.value || '', true);
    });
};

const setTaskModalAdvancedState = (root, expanded) => {
    const modal = root?.querySelector('[data-project-task-modal]');
    const panel = root?.querySelector('[data-project-task-modal-panel]');
    const form = root?.querySelector('[data-project-task-form]');
    const advancedSection = form?.querySelector('[data-project-task-advanced-section]');
    const toggleButton = form?.querySelector('[data-project-task-advanced-toggle]');

    if (!modal || !panel || !form || !advancedSection || !toggleButton) {
        return;
    }

    form.dataset.advanced = expanded ? 'true' : 'false';
    advancedSection.hidden = !expanded;
    panel.classList.toggle('max-w-lg', !expanded);
    panel.classList.toggle('max-w-5xl', expanded);
    toggleButton.textContent = expanded ? 'Hide Advanced' : 'Show Advanced';
};

const setTaskFormSprint = (form, sprintId) => {
    if (!form) {
        return;
    }

    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const normalizedSprintId = sprintId ? String(sprintId) : '';

    if (!sprintField) {
        return;
    }

    if (sprintField.tomselect) {
        sprintField.tomselect.setValue(normalizedSprintId, true);
        sprintField.dispatchEvent(new Event('change', { bubbles: true }));
        return;
    }

    sprintField.value = normalizedSprintId;
    sprintField.dispatchEvent(new Event('change', { bubbles: true }));
};

const prepareTaskModal = (root, sprintId = '') => {
    const form = root.querySelector('[data-project-task-form]');

    if (!form) {
        return;
    }

    initializeEstimatedTimeInputs(form);
    initDatepicker('.datepicker', {}, form);
    form.reset();
    clearTaskFormErrors(form);
    setTaskModalAdvancedState(root, false);
    syncTaskFormSelectState(form);
    setTaskFormSprint(form, sprintId || root.dataset.defaultSprintId || '');

    if (!form.querySelector('[name="project_sprint_id"]')) {
        loadParentTaskOptions(form).catch(() => {});
    }
};

const applyTaskFormErrors = (form, errors = {}) => {
    clearTaskFormErrors(form);

    if (Object.keys(errors).some((fieldName) => ADVANCED_TASK_FIELDS.has(fieldName.split('.')[0]))) {
        const root = form.closest('[data-project-tasks-root]');
        setTaskModalAdvancedState(root, true);
    }

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const normalizedFieldName = fieldName.split('.')[0];
        const field = form.querySelector(`[name="${normalizedFieldName}"], [name="${normalizedFieldName}[]"]`);
        const errorNode = form.querySelector(`[data-project-task-error="${normalizedFieldName}"]`);

        field?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const setParentTaskOptions = (selectField, options = [], { placeholder = 'Select parent task', disabled = false } = {}) => {
    if (!selectField) {
        return;
    }

    if (selectField.tomselect) {
        selectField.tomselect.clear(true);
        selectField.tomselect.clearOptions();
        selectField.tomselect.addOption([{ value: '', text: placeholder }]);

        if (options.length) {
            selectField.tomselect.addOption(options);
        }

        selectField.tomselect.refreshOptions(false);
        selectField.tomselect.enable();

        if (disabled) {
            selectField.tomselect.disable();
        }

        return;
    }

    selectField.innerHTML = '';
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    selectField.appendChild(placeholderOption);

    options.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        selectField.appendChild(optionElement);
    });

    selectField.disabled = disabled;
};

const loadParentTaskOptions = async (form) => {
    if (!form) {
        return;
    }

    const parentTaskField = form.querySelector('[data-parent-task-select]');

    if (!parentTaskField) {
        return;
    }

    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const loadUrl = parentTaskField.dataset.parentTaskUrl || '';
    const sprintId = sprintField?.value || '';
    const isLinearFlow = !sprintField;

    if (!loadUrl) {
        return;
    }

    if (!isLinearFlow && !sprintId) {
        setParentTaskOptions(parentTaskField, [], {
            placeholder: 'Select sprint first',
            disabled: true,
        });
        return;
    }

    setParentTaskOptions(parentTaskField, [], {
        placeholder: 'Loading parent tasks...',
        disabled: true,
    });

    const requestUrl = new URL(loadUrl, window.location.origin);

    if (sprintId) {
        requestUrl.searchParams.set('project_sprint_id', sprintId);
    }

    const response = await fetch(requestUrl.toString(), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    const result = await response.json();

    if (!response.ok || !result.status) {
        throw new Error(result.message || 'Unable to load parent tasks.');
    }

    setParentTaskOptions(parentTaskField, result.options || [], {
        placeholder: (result.options || []).length ? 'Select parent task' : 'No parent tasks available',
        disabled: false,
    });
};

const clearTaskDetailFormErrors = (form) => {
    form.querySelectorAll('[data-project-task-detail-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.classList.remove('border-red-500');
    });
};

const applyTaskDetailFormErrors = (form, errors = {}) => {
    clearTaskDetailFormErrors(form);

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const field = form.querySelector(`[name="${fieldName}"], [name="${fieldName}[]"]`);
        const errorNode = form.querySelector(`[data-project-task-detail-error="${fieldName}"]`);

        field?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const closeTaskModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

const openTaskModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    window.requestAnimationFrame(() => {
        modal.querySelector('[name="name"]')?.focus();
    });
};

const openTaskDetailModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
};

const closeTaskDetailModal = (modal) => {
    if (!modal) {
        return;
    }

    const content = modal.querySelector('[data-project-task-detail-content]');

    modal.classList.add('hidden');

    if (content) {
        content.innerHTML = '';
    }
};

const showTaskDetailLoading = (modal) => {
    const content = modal?.querySelector('[data-project-task-detail-content]');

    if (!content) {
        return;
    }

    content.innerHTML = `
        <div class="overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-darkblack-600">
            <div class="flex h-[82vh] items-center justify-center px-6 py-12 text-sm font-medium text-bgray-500 dark:text-bgray-300">
                Loading task details...
            </div>
        </div>
    `;
};

const loadTaskDetailModal = async (root, loadUrl, groupKey = '') => {
    const modal = root.querySelector('[data-project-task-detail-modal]');
    const content = modal?.querySelector('[data-project-task-detail-content]');

    if (!modal || !content || !loadUrl) {
        return;
    }

    openTaskDetailModal(modal);
    showTaskDetailLoading(modal);

    try {
        const response = await fetch(loadUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load task details.');
        }

        content.innerHTML = result.html;
        initTomSelect(content);
        initDatepicker('.datepicker', {}, content);
        initializeEstimatedTimeInputs(content);

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(content);
        }

        const form = content.querySelector('[data-project-task-detail-form]');

        if (form) {
            form.dataset.groupKey = groupKey || '';
        }
    } catch (error) {
        closeTaskDetailModal(modal);
        Alert.errorModal(error.message || 'Unable to load task details.');
    }
};

const replaceTasksRoot = (currentRoot, html) => {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const newRoot = wrapper.firstElementChild;

    if (!newRoot || !currentRoot) {
        return null;
    }

    currentRoot.replaceWith(newRoot);
    initTomSelect(newRoot);

    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        window.Alpine.initTree(newRoot);
    }

    return newRoot;
};

const setExpandedState = (group, expanded) => {
    const { icon, panel } = getGroupElements(group);

    group.dataset.expanded = expanded ? 'true' : 'false';
    panel?.classList.toggle('hidden', !expanded);
    icon?.classList.toggle('rotate-90', expanded);
};

const loadMoreTaskGroups = async (root, groupList, page, loadUrl) => {
    if (!root || !groupList || !loadUrl || groupList.dataset.loading === 'true') {
        return;
    }

    groupList.dataset.loading = 'true';
    root.querySelector('[data-project-task-group-pagination-loading]')?.removeAttribute('hidden');

    try {
        const requestUrl = new URL(loadUrl, window.location.origin);
        requestUrl.searchParams.set('page', String(page));

        const response = await fetch(requestUrl.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load more sprints.');
        }

        if (result.html) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = result.html;
            const newChildren = Array.from(wrapper.children);

            newChildren.forEach((child) => {
                groupList.appendChild(child);
            });

            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                newChildren.forEach((child) => {
                    window.Alpine.initTree(child);
                });
            }
        }

        groupList.dataset.currentPage = String(result.pagination?.page || page);
        groupList.dataset.nextPage = result.pagination?.next_page ? String(result.pagination.next_page) : '';
        groupList.dataset.hasMorePages = result.pagination?.has_more_pages ? 'true' : 'false';

        if (groupList.dataset.hasMorePages !== 'true') {
            root.querySelector('[data-project-task-group-pagination-sentinel]')?.remove();
            root.querySelector('[data-project-task-group-pagination-loading]')?.remove();
        }
    } finally {
        delete groupList.dataset.loading;

        if (groupList.dataset.hasMorePages === 'true') {
            root.querySelector('[data-project-task-group-pagination-loading]')?.setAttribute('hidden', 'hidden');
        }
    }
};

const initializeTaskGroupPagination = (root) => {
    if (!root) {
        return;
    }

    const groupList = root.querySelector('[data-project-task-group-list]');
    const sentinel = root.querySelector('[data-project-task-group-pagination-sentinel]');
    const loadUrl = groupList?.dataset.loadUrl || '';
    const existingObserver = taskGroupPaginationObservers.get(root);

    if (existingObserver) {
        existingObserver.disconnect();
        taskGroupPaginationObservers.delete(root);
    }

    if (!groupList || !sentinel || !loadUrl || groupList.dataset.hasMorePages !== 'true') {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        const hasVisibleEntry = entries.some((entry) => entry.isIntersecting);

        if (!hasVisibleEntry) {
            return;
        }

        const nextPage = Number(groupList.dataset.nextPage || 0);

        if (!nextPage) {
            return;
        }

        loadMoreTaskGroups(root, groupList, nextPage, loadUrl)
            .then(() => {
                initializeTaskGroupPagination(root);
            })
            .catch((error) => {
                Alert.errorModal(error.message || 'Unable to load more sprints.');
            });
    }, {
        threshold: 0,
        rootMargin: '320px 0px',
    });

    observer.observe(sentinel);
    taskGroupPaginationObservers.set(root, observer);
};

const initializeTaskListPagination = (group) => {
    if (!group) {
        return;
    }

    const { body } = getGroupElements(group);
    const taskList = body?.querySelector('[data-project-task-group-task-list]');
    const scrollContainer = body?.querySelector('[data-project-task-group-scroll]');
    const sentinel = body?.querySelector('[data-project-task-group-tasks-sentinel]');
    const loadUrl = group.dataset.loadUrl || '';
    const existingObserver = body ? taskListPaginationObservers.get(body) : null;

    if (existingObserver) {
        existingObserver.disconnect();
        taskListPaginationObservers.delete(body);
    }

    if (!body || !taskList || !scrollContainer || !sentinel || !loadUrl || taskList.dataset.hasMorePages !== 'true') {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        const hasVisibleEntry = entries.some((entry) => entry.isIntersecting);

        if (!hasVisibleEntry || taskList.dataset.loading === 'true') {
            return;
        }

        const nextPage = Number(taskList.dataset.nextPage || 0);

        if (!nextPage) {
            return;
        }

        loadMoreGroupTasks(group, nextPage).catch((error) => {
            Alert.errorModal(error.message || 'Unable to load more tasks.');
        });
    }, {
        root: scrollContainer,
        threshold: 0,
        rootMargin: '220px 0px',
    });

    observer.observe(sentinel);
    taskListPaginationObservers.set(body, observer);
};

const loadMoreGroupTasks = async (group, page) => {
    const { body } = getGroupElements(group);
    const taskList = body?.querySelector('[data-project-task-group-task-list]');
    const rows = body?.querySelector('[data-project-task-group-rows]');
    const loadUrl = group.dataset.loadUrl;

    if (!body || !taskList || !rows || !loadUrl || taskList.dataset.loading === 'true') {
        return;
    }

    taskList.dataset.loading = 'true';
    body.querySelector('[data-project-task-group-tasks-loading]')?.removeAttribute('hidden');

    try {
        const requestUrl = new URL(loadUrl, window.location.origin);
        requestUrl.searchParams.set('page', String(page));

        const response = await fetch(requestUrl.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load more tasks.');
        }

        if (result.items_html) {
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = result.items_html;
            const newChildren = Array.from(wrapper.children);

            newChildren.forEach((child) => {
                rows.appendChild(child);
            });

            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                newChildren.forEach((child) => {
                    window.Alpine.initTree(child);
                });
            }
        }

        taskList.dataset.currentPage = String(result.pagination?.page || page);
        taskList.dataset.nextPage = result.pagination?.next_page ? String(result.pagination.next_page) : '';
        taskList.dataset.hasMorePages = result.pagination?.has_more_pages ? 'true' : 'false';

        if (taskList.dataset.hasMorePages !== 'true') {
            body.querySelector('[data-project-task-group-tasks-sentinel]')?.remove();
            body.querySelector('[data-project-task-group-tasks-loading]')?.remove();
        }

        initializeTaskListPagination(group);
    } finally {
        delete taskList.dataset.loading;

        if (taskList.dataset.hasMorePages === 'true') {
            body.querySelector('[data-project-task-group-tasks-loading]')?.setAttribute('hidden', 'hidden');
        }
    }
};

const loadGroupTasks = async (group) => {
    const { body } = getGroupElements(group);
    const loadUrl = group.dataset.loadUrl;

    if (!body || !loadUrl) {
        return;
    }

    if (body.dataset.loaded === 'true' || body.dataset.loading === 'true') {
        return;
    }

    body.dataset.loading = 'true';
    body.innerHTML = LOADING_HTML;

    try {
        const response = await fetch(loadUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load sprint tasks.');
        }

        body.innerHTML = result.html;
        body.dataset.loaded = 'true';

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(body);
        }

        initializeTaskListPagination(group);
    } catch (error) {
        body.innerHTML = ERROR_HTML;
        Alert.errorModal(error.message || 'Unable to load sprint tasks.');
    } finally {
        delete body.dataset.loading;
    }
};

const initializeTasksRoot = (root) => {
    if (!root || root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';
    initTomSelect(root);
    initializeTaskGroupPagination(root);

    root.addEventListener('click', async (event) => {
        const detailOpenButton = event.target.closest('[data-project-task-detail-open]');

        if (detailOpenButton && root.contains(detailOpenButton)) {
            await loadTaskDetailModal(
                root,
                detailOpenButton.dataset.projectTaskDetailUrl || '',
                detailOpenButton.dataset.projectTaskGroupKey || ''
            );
            return;
        }

        const openButton = event.target.closest('[data-project-task-modal-open]');

        if (openButton && root.contains(openButton)) {
            prepareTaskModal(root, openButton.dataset.projectTaskSprintId || '');
            openTaskModal(root.querySelector('[data-project-task-modal]'));
            return;
        }

        const advancedToggle = event.target.closest('[data-project-task-advanced-toggle]');

        if (advancedToggle && root.contains(advancedToggle)) {
            const form = root.querySelector('[data-project-task-form]');
            setTaskModalAdvancedState(root, form?.dataset.advanced !== 'true');
            return;
        }

        const closeButton = event.target.closest('[data-project-task-modal-close]');

        if (closeButton && root.contains(closeButton)) {
            closeTaskModal(root.querySelector('[data-project-task-modal]'));
            return;
        }

        const detailCloseButton = event.target.closest('[data-project-task-detail-close]');

        if (detailCloseButton && root.contains(detailCloseButton)) {
            closeTaskDetailModal(root.querySelector('[data-project-task-detail-modal]'));
            return;
        }

        const toggle = event.target.closest('[data-project-task-group-toggle]');

        if (!toggle || !root.contains(toggle)) {
            return;
        }

        const group = toggle.closest('[data-project-task-group]');

        if (!group) {
            return;
        }

        const isExpanded = group.dataset.expanded === 'true';

        if (isExpanded) {
            setExpandedState(group, false);
            return;
        }

        setExpandedState(group, true);
        await loadGroupTasks(group);
    });

    root.addEventListener('change', (event) => {
        const sprintField = event.target.closest('[name="project_sprint_id"]');

        if (!sprintField || !root.contains(sprintField)) {
            return;
        }

        const form = sprintField.closest('[data-project-task-form]');

        if (!form) {
            return;
        }

        loadParentTaskOptions(form).catch((error) => {
            Alert.errorModal(error.message || 'Unable to load parent tasks.');
        });
    });

    root.querySelectorAll('[data-project-task-group][data-expanded="true"]').forEach((group) => {
        const { body } = getGroupElements(group);

        if (body?.dataset.loaded !== 'true') {
            loadGroupTasks(group);
            return;
        }

        initializeTaskListPagination(group);
    });

    const form = root.querySelector('[data-project-task-form]');

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            clearTaskFormErrors(form);

            const submitButton = form.querySelector('[data-project-task-submit]');
            const modal = root.querySelector('[data-project-task-modal]');
            const storeUrl = form.dataset.storeUrl;

            if (!storeUrl) {
                Alert.errorModal('Unable to save the task right now.');
                return;
            }

            submitButton?.setAttribute('disabled', 'disabled');

            if (submitButton) {
                submitButton.textContent = 'Saving...';
            }

            try {
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: new FormData(form),
                });

                const result = await response.json();

                if (response.status === 422 && result.errors) {
                    applyTaskFormErrors(form, result.errors);
                    throw new Error(result.message || 'Please correct the highlighted fields.');
                }

                if (!response.ok || !result.status) {
                    throw new Error(result.message || 'Unable to save the task.');
                }

                const newRoot = replaceTasksRoot(root, result.html);

                closeTaskModal(modal);
                Alert.success(result.message || 'Task added successfully.');

                if (newRoot) {
                    initializeTasksRoot(newRoot);
                }
            } catch (error) {
                if (!(error.message || '').includes('highlighted fields')) {
                    Alert.errorModal(error.message || 'Unable to save the task.');
                }
            } finally {
                submitButton?.removeAttribute('disabled');

                if (submitButton) {
                    submitButton.textContent = 'Save Task';
                }
            }
        });
    }

    root.addEventListener('submit', async (event) => {
        const detailForm = event.target.closest('[data-project-task-detail-form]');
        
        if (!detailForm || !root.contains(detailForm)) {
            return;
        }

        event.preventDefault();
        clearTaskDetailFormErrors(detailForm);

        const submitButton = detailForm.querySelector('[data-project-task-detail-submit]');
        const modal = root.querySelector('[data-project-task-detail-modal]');
        const actionUrl = detailForm.getAttribute('action');
        
        if (!actionUrl) {
            Alert.errorModal('Unable to update the task right now.');
            return;
        }

        submitButton?.setAttribute('disabled', 'disabled');

        if (submitButton) {
            submitButton.textContent = 'Updating...';
        }

        try {
            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: new FormData(detailForm),
            });
            const result = await response.json();

            if (response.status === 422 && result.errors) {
                applyTaskDetailFormErrors(detailForm, result.errors);
                throw new Error(result.message || 'Please correct the highlighted fields.');
            }

            if (!response.ok || !result.status) {
                throw new Error(result.message || 'Unable to update the task.');
            }

            const newRoot = replaceTasksRoot(root, result.html);

            closeTaskDetailModal(modal);
            Alert.success(result.message || 'Task updated successfully.');

            if (newRoot) {
                initializeTasksRoot(newRoot);
            }
        } catch (error) {
            if (!(error.message || '').includes('highlighted fields')) {
                Alert.errorModal(error.message || 'Unable to update the task.');
            }
        } finally {
            submitButton?.removeAttribute('disabled');

            if (submitButton) {
                submitButton.textContent = 'Update Task';
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-project-tasks-root]').forEach((root) => {
        initializeTasksRoot(root);
    });
});

document.addEventListener('project-tab:loaded', (event) => {
    if (event.detail?.tab !== 'tasks') {
        return;
    }

    event.detail.panel?.querySelectorAll('[data-project-tasks-root]').forEach((root) => {
        initializeTasksRoot(root);
    });
});
