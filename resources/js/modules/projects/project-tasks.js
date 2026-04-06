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

const getGroupElements = (group) => ({
    icon: group.querySelector('[data-project-task-group-icon]'),
    panel: group.querySelector('[data-project-task-group-panel]'),
    body: group.querySelector('[data-project-task-group-body]'),
});

const clearTaskFormErrors = (form) => {
    form.querySelectorAll('[data-project-task-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select').forEach((field) => {
        field.classList.remove('border-red-500');
    });
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
        return;
    }

    sprintField.value = normalizedSprintId;
};

const prepareTaskModal = (root, sprintId = '') => {
    const form = root.querySelector('[data-project-task-form]');

    if (!form) {
        return;
    }

    initializeEstimatedTimeInputs(form);
    form.reset();
    clearTaskFormErrors(form);
    setTaskFormSprint(form, sprintId || root.dataset.defaultSprintId || '');
};

const applyTaskFormErrors = (form, errors = {}) => {
    clearTaskFormErrors(form);

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        const errorNode = form.querySelector(`[data-project-task-error="${fieldName}"]`);

        field?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
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
        modal.querySelector('[name="title"]')?.focus();
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
        Alert.error(error.message || 'Unable to load task details.');
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
    } catch (error) {
        body.innerHTML = ERROR_HTML;
        Alert.error(error.message || 'Unable to load sprint tasks.');
    } finally {
        delete body.dataset.loading;
    }
};

const initializeProjectTasksRoot = (root) => {
    if (!root || root.dataset.initialized === 'true') {
        return;
    }

    root.dataset.initialized = 'true';
    initTomSelect(root);

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

    root.querySelectorAll('[data-project-task-group][data-expanded="true"]').forEach((group) => {
        const { body } = getGroupElements(group);

        if (body?.dataset.loaded !== 'true') {
            loadGroupTasks(group);
        }
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
                Alert.error('Unable to save the task right now.');
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
                    initializeProjectTasksRoot(newRoot);
                }
            } catch (error) {
                if (!(error.message || '').includes('highlighted fields')) {
                    Alert.error(error.message || 'Unable to save the task.');
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
            Alert.error('Unable to update the task right now.');
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
                initializeProjectTasksRoot(newRoot);
            }
        } catch (error) {
            if (!(error.message || '').includes('highlighted fields')) {
                Alert.error(error.message || 'Unable to update the task.');
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
        initializeProjectTasksRoot(root);
    });
});

document.addEventListener('project-tab:loaded', (event) => {
    if (event.detail?.tab !== 'tasks') {
        return;
    }

    event.detail.panel?.querySelectorAll('[data-project-tasks-root]').forEach((root) => {
        initializeProjectTasksRoot(root);
    });
});
