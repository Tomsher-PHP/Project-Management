import Alert from '../alert';
import { initDatepicker } from '../components/datepicker';
import { initializeEstimatedTimeInputs } from '../components/estimated-time-input';
import { initTomSelect } from '../components/tom-select';

const ADVANCED_TASK_FIELDS = new Set([
    'description',
    'status_id',
    'task_type_id',
    'task_mode_id',
    'priority',
    'due_date',
    'tag_ids',
    'is_billable',
]);

const parseDependencies = () => {
    const node = document.getElementById('task-create-dependencies');

    if (!node) {
        return {
            projects: {},
            status_options_by_flow: {},
            defaults: {},
            parent_options_url: '',
        };
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch (error) {
        return {
            projects: {},
            status_options_by_flow: {},
            defaults: {},
            parent_options_url: '',
        };
    }
};

const getFieldErrorTarget = (field) => field?.tomselect?.control || field;

const clearTaskCreateErrors = (form) => {
    form.querySelectorAll('[data-task-create-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        getFieldErrorTarget(field)?.classList.remove('border-red-500');
    });
};

const syncTaskCreateSelectState = (form) => {
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

const setTaskCreateAdvancedState = (root, expanded) => {
    const modal = root?.querySelector('[data-task-create-modal]');
    const panel = root?.querySelector('[data-task-create-modal-panel]');
    const form = root?.querySelector('[data-task-create-form]');
    const advancedSection = form?.querySelector('[data-task-create-advanced-section]');
    const toggleButton = form?.querySelector('[data-task-create-advanced-toggle]');

    if (!modal || !panel || !form || !advancedSection || !toggleButton) {
        return;
    }

    form.dataset.advanced = expanded ? 'true' : 'false';
    advancedSection.hidden = !expanded;
    panel.classList.toggle('max-w-lg', !expanded);
    panel.classList.toggle('max-w-5xl', expanded);
    toggleButton.textContent = expanded ? 'Hide Advanced' : 'Show Advanced';
};

const setFieldValue = (field, value = '') => {
    if (!field) {
        return;
    }

    const normalizedValue = value ?? '';

    field.value = normalizedValue;

    if (field._flatpickr) {
        if (normalizedValue) {
            field._flatpickr.setDate(normalizedValue, false);
            return;
        }

        field._flatpickr.clear();
    }
};

const setCheckboxValue = (field, checked) => {
    if (!field) {
        return;
    }

    field.checked = Boolean(checked);
};

const setEstimatedTimeValue = (form, totalMinutes = 0) => {
    if (!form) {
        return;
    }

    const totalMinutesInput = form.querySelector('[name="estimated_time_minutes"]');
    const wrapper = totalMinutesInput?.closest('[data-estimated-time]');

    if (!totalMinutesInput) {
        return;
    }

    totalMinutesInput.value = String(Math.max(0, Number.parseInt(totalMinutes || '0', 10) || 0));
    wrapper?.dispatchEvent(new Event('estimated-time:refresh'));
    totalMinutesInput.dispatchEvent(new Event('input', { bubbles: true }));
    totalMinutesInput.dispatchEvent(new Event('change', { bubbles: true }));
};

const setTaskCreateRequiredIndicators = (form, isAgile, hasSelectedModule = false) => {
    if (!form) {
        return;
    }

    form.querySelectorAll('[data-task-create-required-star]').forEach((node) => {
        const fieldName = node.dataset.taskCreateRequiredStar || '';
        const shouldShow = isAgile && hasSelectedModule && fieldName === 'project_sprint_id';

        node.classList.toggle('hidden', !shouldShow);
    });
};

const setTaskCreatePlacementHint = (form, projectMeta, { selectedModuleId = '', selectedSprintId = '' } = {}) => {
    const hintNode = form?.querySelector('[data-task-create-placement-hint]');

    if (!hintNode) {
        return;
    }

    if (!projectMeta || projectMeta.flow !== 'agile') {
        hintNode.textContent = '';
        hintNode.classList.add('hidden');
        return;
    }

    let message = 'Leave both module and sprint empty to place this task in the project backlog.';

    if (selectedModuleId && !selectedSprintId) {
        message = 'Select a sprint for the chosen module, or clear the module to use the project backlog.';
    } else if (selectedSprintId && !selectedModuleId) {
        message = 'This sprint can be saved without choosing a module. The backend will match the module automatically.';
    } else if (selectedSprintId) {
        message = '';
    }

    hintNode.textContent = message;
    hintNode.classList.toggle('hidden', !message);
};

const setSelectOptions = (field, options = [], { placeholder = 'Select option', disabled = false, value = '' } = {}) => {
    if (!field) {
        return;
    }

    const normalizedValue = Array.isArray(value)
        ? value.map((item) => String(item))
        : (value === null || value === undefined ? '' : String(value));

    if (field.tomselect) {
        field.tomselect.clear(true);
        field.tomselect.clearOptions();
        field.tomselect.settings.placeholder = placeholder;

        if (options.length) {
            field.tomselect.addOption(options);
        }

        field.tomselect.refreshOptions(false);
        field.tomselect.inputState();

        if (disabled) {
            field.tomselect.disable();
        } else {
            field.tomselect.enable();
        }

        if (field.multiple) {
            field.tomselect.setValue(normalizedValue, true);
            return;
        }

        if (normalizedValue !== '') {
            field.tomselect.setValue(normalizedValue, true);
            return;
        }

        field.tomselect.clear(true);
        return;
    }

    field.innerHTML = '';

    if (!field.multiple) {
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        placeholderOption.disabled = true;
        placeholderOption.hidden = true;
        field.appendChild(placeholderOption);
    }

    options.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        field.appendChild(optionElement);
    });

    field.disabled = disabled;

    if (field.multiple) {
        Array.from(field.options).forEach((option) => {
            option.selected = normalizedValue.includes(option.value);
        });

        return;
    }

    field.value = normalizedValue;
};

const getProjectMeta = (dependencies, projectId) => {
    if (!projectId) {
        return null;
    }

    return dependencies.projects?.[String(projectId)] || null;
};

const openTaskCreateModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    window.requestAnimationFrame(() => {
        const projectField = modal.querySelector('[name="project_id"]');

        if (projectField?.tomselect) {
            projectField.tomselect.focus();
            return;
        }

        projectField?.focus();
    });
};

const closeTaskCreateModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

const updateSprintOptions = (form, dependencies, { selectedSprintId = '', selectedModuleId = '' } = {}) => {
    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const projectField = form.querySelector('[name="project_id"]');
    const projectMeta = getProjectMeta(dependencies, projectField?.value || '');
    const normalizedModuleId = String(selectedModuleId || form.querySelector('[name="project_module_id"]')?.value || '');
    const normalizedSprintId = String(selectedSprintId || sprintField?.value || '');

    if (!sprintField) {
        return;
    }

    if (!projectMeta) {
        setSelectOptions(sprintField, [], {
            placeholder: 'Select project first',
            disabled: true,
        });
        return;
    }

    if (projectMeta.flow !== 'agile') {
        setSelectOptions(sprintField, [], {
            placeholder: 'Not used for linear projects',
            disabled: true,
        });
        setTaskCreateRequiredIndicators(form, false);
        setTaskCreatePlacementHint(form, projectMeta);
        return;
    }

    const availableSprints = (projectMeta.sprints || []).filter((sprint) => {
        if (!normalizedModuleId) {
            return true;
        }

        return String(sprint.project_module_id || '') === normalizedModuleId;
    });
    const resolvedSprintId = availableSprints.some((option) => option.value === normalizedSprintId)
        ? normalizedSprintId
        : '';

    setSelectOptions(sprintField, availableSprints, {
        placeholder: normalizedModuleId
            ? (availableSprints.length ? 'Select sprint' : 'No sprints in selected module')
            : (availableSprints.length ? 'Select sprint or leave empty for backlog' : 'No sprints available'),
        disabled: false,
        value: resolvedSprintId,
    });

    setTaskCreateRequiredIndicators(form, true, Boolean(normalizedModuleId));
    setTaskCreatePlacementHint(form, projectMeta, {
        selectedModuleId: normalizedModuleId,
        selectedSprintId: resolvedSprintId,
    });
};

const setEmptyProjectState = (form) => {
    setTaskCreateRequiredIndicators(form, false);
    setTaskCreatePlacementHint(form, null);

    setSelectOptions(form.querySelector('[name="project_module_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="project_sprint_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="parent_task_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="current_assignee_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="status_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setEstimatedTimeValue(form, 0);
    setCheckboxValue(form.querySelector('[name="is_billable"]'), false);
};

const applyProjectDefaults = async (form, dependencies) => {
    const projectField = form.querySelector('[name="project_id"]');
    const moduleField = form.querySelector('[name="project_module_id"]');
    const assigneeField = form.querySelector('[name="current_assignee_id"]');
    const statusField = form.querySelector('[name="status_id"]');
    const priorityField = form.querySelector('[name="priority"]');
    const dueDateField = form.querySelector('[name="due_date"]');
    const billableField = form.querySelector('[name="is_billable"]');
    const projectMeta = getProjectMeta(dependencies, projectField?.value || '');

    if (!projectMeta) {
        setEmptyProjectState(form);
        return;
    }

    const isAgile = projectMeta.flow === 'agile';

    setSelectOptions(moduleField, projectMeta.modules || [], {
        placeholder: isAgile ? 'Select module or leave empty for backlog' : 'Not used for linear projects',
        disabled: !isAgile,
    });

    updateSprintOptions(form, dependencies);

    setSelectOptions(assigneeField, projectMeta.assignees || [], {
        placeholder: 'Select assignee',
        disabled: false,
    });

    setSelectOptions(statusField, dependencies.status_options_by_flow?.[projectMeta.flow] || [], {
        placeholder: 'Select status',
        disabled: false,
        value: projectMeta.default_status_id ? String(projectMeta.default_status_id) : '',
    });

    if (priorityField) {
        const defaultPriority = dependencies.defaults?.priority || '';

        if (priorityField.tomselect) {
            priorityField.tomselect.setValue(defaultPriority, true);
        } else {
            priorityField.value = defaultPriority;
        }
    }

    setFieldValue(dueDateField, dependencies.defaults?.due_date || '');
    setEstimatedTimeValue(form, projectMeta.default_task_estimate_minutes ?? 0);
    setCheckboxValue(billableField, projectMeta.default_billable);

    await loadParentTaskOptions(form, dependencies);
};

const loadParentTaskOptions = async (form, dependencies) => {
    const parentField = form.querySelector('[data-task-create-parent-select]');
    const projectField = form.querySelector('[name="project_id"]');
    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const projectMeta = getProjectMeta(dependencies, projectField?.value || '');

    if (!parentField) {
        return;
    }

    if (!projectMeta) {
        setSelectOptions(parentField, [], {
            placeholder: 'Select project first',
            disabled: true,
        });
        return;
    }

    if (projectMeta.flow === 'agile' && !sprintField?.value) {
        setSelectOptions(parentField, [], {
            placeholder: 'Select sprint first',
            disabled: true,
        });
        return;
    }

    if (!dependencies.parent_options_url) {
        setSelectOptions(parentField, [], {
            placeholder: 'No parent tasks available',
            disabled: true,
        });
        return;
    }

    setSelectOptions(parentField, [], {
        placeholder: 'Loading parent tasks...',
        disabled: true,
    });

    const requestUrl = new URL(dependencies.parent_options_url, window.location.origin);
    requestUrl.searchParams.set('project_id', String(projectMeta.id));

    if (sprintField?.value) {
        requestUrl.searchParams.set('project_sprint_id', String(sprintField.value));
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

    setSelectOptions(parentField, result.options || [], {
        placeholder: (result.options || []).length ? 'Select parent task' : 'No parent tasks available',
        disabled: false,
    });
};

const prepareTaskCreateModal = async (root, dependencies) => {
    const form = root.querySelector('[data-task-create-form]');

    if (!form) {
        return;
    }

    initTomSelect(root);
    initializeEstimatedTimeInputs(form);
    initDatepicker('.datepicker', {}, root);
    form.reset();
    clearTaskCreateErrors(form);
    setTaskCreateAdvancedState(root, false);
    syncTaskCreateSelectState(form);
    setEmptyProjectState(form);

    const projectField = form.querySelector('[name="project_id"]');

    if (projectField?.value) {
        await applyProjectDefaults(form, dependencies);
    }
};

const applyTaskCreateErrors = (root, form, errors = {}) => {
    clearTaskCreateErrors(form);

    if (Object.keys(errors).some((fieldName) => ADVANCED_TASK_FIELDS.has(fieldName.split('.')[0]))) {
        setTaskCreateAdvancedState(root, true);
    }

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const normalizedFieldName = fieldName.split('.')[0];
        const field = form.querySelector(`[name="${normalizedFieldName}"], [name="${normalizedFieldName}[]"]`);
        const errorNode = form.querySelector(`[data-task-create-error="${normalizedFieldName}"]`);

        getFieldErrorTarget(field)?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const initializeTaskCreateRoot = (root, dependencies) => {
    if (!root || root.dataset.taskCreateInitialized === 'true') {
        return;
    }

    root.dataset.taskCreateInitialized = 'true';
    initTomSelect(root);

    root.addEventListener('click', async (event) => {
        const openButton = event.target.closest('[data-task-create-open]');

        if (openButton) {
            openTaskCreateModal(root.querySelector('[data-task-create-modal]'));

            try {
                await prepareTaskCreateModal(root, dependencies);
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to load project task options.');
            }
            return;
        }

        const advancedToggle = event.target.closest('[data-task-create-advanced-toggle]');

        if (advancedToggle && root.contains(advancedToggle)) {
            const form = root.querySelector('[data-task-create-form]');
            setTaskCreateAdvancedState(root, form?.dataset.advanced !== 'true');
            return;
        }

        const closeButton = event.target.closest('[data-task-create-close]');

        if (closeButton && root.contains(closeButton)) {
            closeTaskCreateModal(root.querySelector('[data-task-create-modal]'));
        }
    });

    root.addEventListener('change', async (event) => {
        const form = root.querySelector('[data-task-create-form]');

        if (!form) {
            return;
        }

        const projectField = event.target.closest('[name="project_id"]');

        if (projectField && root.contains(projectField)) {
            try {
                await applyProjectDefaults(form, dependencies);
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to load project task options.');
            }

            return;
        }

        const moduleField = event.target.closest('[name="project_module_id"]');

        if (moduleField && root.contains(moduleField)) {
            updateSprintOptions(form, dependencies, {
                selectedModuleId: moduleField.value || '',
                selectedSprintId: form.querySelector('[name="project_sprint_id"]')?.value || '',
            });

            try {
                await loadParentTaskOptions(form, dependencies);
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to load parent tasks.');
            }

            return;
        }

        const sprintField = event.target.closest('[name="project_sprint_id"]');

        if (sprintField && root.contains(sprintField)) {
            const projectMeta = getProjectMeta(dependencies, form.querySelector('[name="project_id"]')?.value || '');
            const selectedSprint = (projectMeta?.sprints || []).find((option) => option.value === String(sprintField.value || ''));
            const moduleFieldInForm = form.querySelector('[name="project_module_id"]');

            if (selectedSprint && !moduleFieldInForm?.value) {
                if (moduleFieldInForm?.tomselect) {
                    moduleFieldInForm.tomselect.setValue(String(selectedSprint.project_module_id || ''), true);
                } else if (moduleFieldInForm) {
                    moduleFieldInForm.value = String(selectedSprint.project_module_id || '');
                }
            }

            updateSprintOptions(form, dependencies, {
                selectedModuleId: moduleFieldInForm?.value || '',
                selectedSprintId: sprintField.value || '',
            });

            try {
                await loadParentTaskOptions(form, dependencies);
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to load parent tasks.');
            }
        }
    });

    const form = root.querySelector('[data-task-create-form]');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearTaskCreateErrors(form);

        const submitButton = form.querySelector('[data-task-create-submit]');
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
                applyTaskCreateErrors(root, form, result.errors);
                throw new Error(result.message || 'Please correct the highlighted fields.');
            }

            if (!response.ok || !result.status) {
                throw new Error(result.message || 'Unable to save the task.');
            }

            window.location.reload();
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
};

document.addEventListener('DOMContentLoaded', () => {
    const dependencies = parseDependencies();

    document.querySelectorAll('[data-task-create-root]').forEach((root) => {
        initializeTaskCreateRoot(root, dependencies);
    });
});
