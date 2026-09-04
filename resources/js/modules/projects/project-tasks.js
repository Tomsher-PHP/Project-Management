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
let taskRowMenuDocumentListenerBound = false;
const projectTaskEditors = new WeakMap();
const projectTaskDetailEditors = new WeakMap();

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
    'tag_ids',
    'is_billable',
]);

const getFieldErrorTarget = (field) => field?.tomselect?.control || field;

const clearTaskFormErrors = (form) => {
    form.querySelectorAll('[data-project-task-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        getFieldErrorTarget(field)?.classList.remove('border-red-500');
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

const parseTaskPlacementOptions = (form) => {
    if (!form?.dataset.taskPlacement) {
        return {
            milestones: [],
            sprints: [],
        };
    }

    try {
        return JSON.parse(form.dataset.taskPlacement);
    } catch (error) {
        return {
            milestones: [],
            sprints: [],
        };
    }
};

const parseTaskMovePlacementOptions = (form) => {
    if (!form?.dataset.taskMovePlacement) {
        return {
            milestones: [],
            sprints: [],
        };
    }

    try {
        return JSON.parse(form.dataset.taskMovePlacement);
    } catch (error) {
        return {
            milestones: [],
            sprints: [],
        };
    }
};

const setSelectValue = (field, value = '') => {
    if (!field) {
        return;
    }

    const normalizedValue = value === null || value === undefined ? '' : String(value);

    if (field.tomselect) {
        if (normalizedValue) {
            field.tomselect.setValue(normalizedValue, true);
            return;
        }

        field.tomselect.clear(true);
        return;
    }

    field.value = normalizedValue;
};

const setPlacementSelectOptions = (field, options = [], { placeholder = 'Select option', disabled = false, value = '' } = {}) => {
    if (!field) {
        return;
    }

    const normalizedValue = value === null || value === undefined ? '' : String(value);
    const shouldDisable = Boolean(disabled);

    if (field.tomselect) {
        field.disabled = shouldDisable;
        field.tomselect.clear(true);
        field.tomselect.clearOptions();
        field.tomselect.settings.placeholder = placeholder;

        if (options.length) {
            field.tomselect.addOption(options);
        }

        field.tomselect.refreshOptions(false);
        field.tomselect.inputState();

        if (shouldDisable) {
            field.tomselect.disable();
        } else {
            field.tomselect.enable();
        }

        if (normalizedValue) {
            field.tomselect.setValue(normalizedValue, true);
            return;
        }

        field.tomselect.clear(true);
        return;
    }

    field.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    field.appendChild(placeholderOption);

    options.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        field.appendChild(optionElement);
    });

    field.disabled = shouldDisable;
    field.value = normalizedValue;
};

const setTaskPlacementHint = (form, selector, { selectedMilestoneId = '', selectedSprintId = '' } = {}) => {
    const hintNode = form?.querySelector(selector);

    if (!hintNode) {
        return;
    }

    let message = 'Leave both milestone and sprint empty to use the project backlog.';

    if (selectedMilestoneId && !selectedSprintId) {
        message = 'Select a sprint for the chosen milestone, or clear the milestone to use the project backlog.';
    } else if (selectedSprintId && !selectedMilestoneId) {
        message = 'This sprint can be saved without choosing a milestone. The backend will match the milestone automatically.';
    } else if (selectedSprintId) {
        message = '';
    }

    hintNode.textContent = message;
    hintNode.classList.toggle('hidden', !message);
};

const setTaskRequiredIndicators = (form, hasSelectedModule) => {
    form?.querySelectorAll('[data-project-task-required-star]').forEach((node) => {
        const fieldName = node.dataset.projectTaskRequiredStar || '';
        const shouldShow = fieldName === 'project_sprint_id' && Boolean(hasSelectedModule);

        node.classList.toggle('hidden', !shouldShow);
    });
};

const getTaskPlacementHintSelector = (form) => (
    form?.matches('[data-project-task-detail-form]')
        ? '[data-project-task-detail-placement-hint]'
        : '[data-project-task-placement-hint]'
);

const syncProjectTaskPlacement = (
    form,
    { selectedMilestoneId = null, selectedSprintId = null, syncModuleFromSprint = false } = {}
) => {
    const milestoneField = form?.querySelector('[name="project_milestone_id"]');
    const sprintField = form?.querySelector('[name="project_sprint_id"]');

    if (!form || !milestoneField || !sprintField) {
        return;
    }

    const placement = parseTaskPlacementOptions(form);
    let milestoneId = selectedMilestoneId === null ? String(milestoneField.value || '') : String(selectedMilestoneId || '');
    let sprintId = selectedSprintId === null ? String(sprintField.value || '') : String(selectedSprintId || '');
    const selectedSprint = placement.sprints.find((option) => option.value === sprintId);

    if (syncModuleFromSprint && selectedSprint && !milestoneId) {
        milestoneId = String(selectedSprint.project_milestone_id || '');
        setSelectValue(milestoneField, milestoneId);
    }

    const availableSprints = placement.sprints.filter((option) => {
        if (!milestoneId) {
            return true;
        }

        return String(option.project_milestone_id || '') === milestoneId;
    });
    const resolvedSprintId = availableSprints.some((option) => option.value === sprintId) ? sprintId : '';

    setPlacementSelectOptions(sprintField, availableSprints, {
        placeholder: milestoneId
            ? (availableSprints.length ? 'Select sprint' : 'No sprints in selected milestone')
            : (placement.sprints.length ? 'Select sprint or leave empty for backlog' : 'No sprints available'),
        disabled: false,
        value: resolvedSprintId,
    });

    setTaskRequiredIndicators(form, milestoneId);
    setTaskPlacementHint(form, getTaskPlacementHintSelector(form), {
        selectedMilestoneId: milestoneId,
        selectedSprintId: resolvedSprintId,
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
    const hasSprintOption = normalizedSprintId
        ? Array.from(sprintField?.options || []).some((option) => String(option.value) === normalizedSprintId)
        : false;
    const resolvedSprintId = hasSprintOption ? normalizedSprintId : '';

    if (!sprintField) {
        return;
    }

    if (sprintField.tomselect) {
        sprintField.tomselect.setValue(resolvedSprintId, true);
        sprintField.dispatchEvent(new Event('change', { bubbles: true }));
        return;
    }

    sprintField.value = resolvedSprintId;
    sprintField.dispatchEvent(new Event('change', { bubbles: true }));
};

const prepareTaskModal = async (root, {
    sprintId = '',
    milestoneId = '',
    parentTaskId = '',
} = {}) => {
    const form = root.querySelector('[data-project-task-form]');

    if (!form) {
        return;
    }

    initializeEstimatedTimeInputs(form);
    initDatepicker('.datepicker', {}, form);
    form.reset();
    const editor = projectTaskEditors.get(root);
    if (editor) {
        editor.setContents([]);
    }
    const descInput = form.querySelector('#project_task_description_input');
    if (descInput) {
        descInput.value = '';
    }
    clearTaskFormErrors(form);
    setTaskModalAdvancedState(root, false);
    syncTaskFormSelectState(form);

    const shouldUseDefaultSprint = !sprintId && !milestoneId && !parentTaskId;
    const resolvedSprintId = sprintId || (shouldUseDefaultSprint ? (root.dataset.defaultSprintId || '') : '');
    const milestoneField = form.querySelector('[name="project_milestone_id"]');
    const parentTaskField = form.querySelector('[name="parent_task_id"]');

    if (milestoneField) {
        setSelectValue(milestoneField, milestoneId || '');
    }

    syncProjectTaskPlacement(form, {
        selectedMilestoneId: milestoneId || '',
        selectedSprintId: resolvedSprintId,
        syncModuleFromSprint: true,
    });

    if (resolvedSprintId) {
        setTaskFormSprint(form, resolvedSprintId);
    }

    if (parentTaskField) {
        setSelectValue(parentTaskField, '');
    }

    await loadParentTaskOptions(form, {
        sprintId: resolvedSprintId,
        selectedParentTaskId: parentTaskId || '',
    }).catch(() => { });
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

        getFieldErrorTarget(field)?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const clearTaskMoveFormErrors = (form) => {
    form?.querySelectorAll('[data-project-task-move-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form?.querySelectorAll('input, select, textarea').forEach((field) => {
        getFieldErrorTarget(field)?.classList.remove('border-red-500');
    });
};

const applyTaskMoveFormErrors = (form, errors = {}) => {
    clearTaskMoveFormErrors(form);

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const normalizedFieldName = fieldName.split('.')[0];
        const field = form.querySelector(`[name="${normalizedFieldName}"], [name="${normalizedFieldName}[]"]`);
        const errorNode = form.querySelector(`[data-project-task-move-error="${normalizedFieldName}"]`);

        getFieldErrorTarget(field)?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const closeAllTaskRowMenus = (exceptDropdown = null) => {
    document.querySelectorAll('[data-project-task-row-dropdown]').forEach((dropdown) => {
        if (exceptDropdown && dropdown === exceptDropdown) {
            return;
        }

        const menu = dropdown.querySelector('[data-project-task-row-menu]');

        if (menu) {
            menu.classList.add('hidden');
            menu.style.position = '';
            menu.style.top = '';
            menu.style.left = '';
            menu.style.right = '';
            menu.style.bottom = '';
            menu.style.width = '';
            menu.style.minWidth = '';
            menu.style.visibility = '';
        }

        dropdown.querySelector('[data-project-task-row-menu-trigger]')?.setAttribute('aria-expanded', 'false');
    });
};

const positionTaskRowMenu = (trigger, menu) => {
    if (!trigger || !menu) {
        return;
    }

    const triggerRect = trigger.getBoundingClientRect();
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const gutter = 8;
    menu.style.position = 'fixed';
    menu.style.right = 'auto';
    menu.style.bottom = 'auto';
    menu.style.top = '0px';
    menu.style.left = '0px';
    menu.style.minWidth = `${Math.round(Math.max(148, triggerRect.width + 100))}px`;
    menu.style.width = 'max-content';

    const measuredRect = menu.getBoundingClientRect();
    const menuWidth = Math.max(measuredRect.width || 0, 148);
    const menuHeight = measuredRect.height || 0;

    let left = triggerRect.right - menuWidth;
    let top = triggerRect.top + triggerRect.height - 2;

    if (left < gutter) {
        left = gutter;
    }

    if ((left + menuWidth) > (viewportWidth - gutter)) {
        left = Math.max(gutter, viewportWidth - menuWidth - gutter);
    }

    if ((top + menuHeight) > (viewportHeight - gutter)) {
        top = Math.max(gutter, triggerRect.top - menuHeight - gutter);
    }

    menu.style.left = `${Math.round(left)}px`;
    menu.style.top = `${Math.round(top)}px`;
};

const openTaskMoveModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    window.requestAnimationFrame(() => {
        modal.querySelector('[name="project_sprint_id"]')?.tomselect?.focus();
        modal.querySelector('[name="project_sprint_id"]')?.focus();
    });
};

const closeTaskMoveModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

const loadedMoveProjectDependencies = {};

const fetchMoveProjectDependencies = async (form, projectId) => {
    if (!projectId) return null;
    const key = String(projectId);
    if (loadedMoveProjectDependencies[key]) {
        return loadedMoveProjectDependencies[key];
    }

    const urlTemplate = form?.dataset?.dependenciesUrlTemplate || '/projects/__PROJECT_ID__/task-create-dependencies';
    const requestUrl = urlTemplate.replace('__PROJECT_ID__', encodeURIComponent(key)).replace(':id', encodeURIComponent(key));

    const response = await fetch(requestUrl, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const result = await response.json();
    if (!response.ok || !result.status || !result.data) {
        throw new Error(result.message || 'Unable to load project options.');
    }

    loadedMoveProjectDependencies[key] = result.data;
    return result.data;
};

const syncTaskMoveUI = async (
    form,
    { selectedMilestoneId = null, selectedSprintId = null, syncModuleFromSprint = false } = {}
) => {
    if (!form) return;

    const toggleCheckbox = form.querySelector('[data-project-task-move-another-project-toggle]');
    const projectContainer = form.querySelector('[data-project-task-move-project-container]');
    const projectSelect = form.querySelector('[data-project-task-move-project-select]');
    const milestoneField = form.querySelector('[name="project_milestone_id"]');
    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const subtitleNode = form.querySelector('[data-project-task-move-subtitle-text]');
    const sprintStarNode = form.querySelector('[data-project-task-move-sprint-star]');
    const milestoneHintNode = form.querySelector('[data-project-task-move-milestone-hint]');
    const sprintHintNode = form.querySelector('[data-project-task-move-sprint-hint]');
    const targetFlowContainer = form.querySelector('[data-project-task-move-target-flow-container]');
    const targetFlowText = form.querySelector('[data-project-task-move-target-flow-text]');
    const flowNotice = form.querySelector('[data-project-task-move-flow-notice]');
    const sourceFlowLabel = form.querySelector('[data-project-task-move-source-flow-label]');
    const targetFlowLabel = form.querySelector('[data-project-task-move-target-flow-label]');
    const targetFlowLabel2 = form.querySelector('[data-project-task-move-target-flow-label-2]');
    const targetStatusName = form.querySelector('[data-project-task-move-target-status-name]');

    const isAnotherProject = Boolean(toggleCheckbox?.checked);

    if (projectContainer) {
        projectContainer.classList.toggle('hidden', !isAnotherProject);
    }

    if (!isAnotherProject) {
        if (targetFlowContainer) targetFlowContainer.classList.add('hidden');
        if (flowNotice) flowNotice.classList.add('hidden');

        // --- Same Project Move Mode ---
        if (subtitleNode) subtitleNode.textContent = 'to another sprint';

        if (projectSelect) {
            setSelectValue(projectSelect, '');
            if (projectSelect.tomselect) {
                projectSelect.tomselect.disable();
            } else {
                projectSelect.disabled = true;
            }
        }

        const placement = parseTaskMovePlacementOptions(form);
        const milestoneId = selectedMilestoneId === null ? String(milestoneField?.value || '') : String(selectedMilestoneId || '');
        let sprintId = selectedSprintId === null ? String(sprintField?.value || '') : String(selectedSprintId || '');

        const milestoneOptions = (placement.milestones || []).map(m => ({ value: String(m.id || m.value), text: m.name || m.text }));
        setPlacementSelectOptions(milestoneField, milestoneOptions, {
            placeholder: 'All milestones',
            disabled: false,
            value: milestoneId,
        });

        const availableSprints = (placement.sprints || []).filter((option) => {
            if (!milestoneId) return true;
            return String(option.project_milestone_id || '') === milestoneId;
        });

        if (syncModuleFromSprint && sprintId && !milestoneId) {
            const selectedSprintObj = (placement.sprints || []).find(s => String(s.value) === sprintId);
            if (selectedSprintObj && selectedSprintObj.project_milestone_id) {
                const matchedMilestoneId = String(selectedSprintObj.project_milestone_id);
                setSelectValue(milestoneField, matchedMilestoneId);
                return syncTaskMoveUI(form, { selectedMilestoneId: matchedMilestoneId, selectedSprintId: sprintId });
            }
        }

        const resolvedSprintId = availableSprints.some((option) => String(option.value) === sprintId) ? sprintId : '';

        setPlacementSelectOptions(sprintField, availableSprints, {
            placeholder: milestoneId
                ? (availableSprints.length ? 'Select sprint' : 'No sprints in selected milestone')
                : (placement.sprints.length ? 'Select sprint' : 'No sprints available'),
            disabled: false,
            value: resolvedSprintId,
        });

        if (sprintStarNode) sprintStarNode.classList.remove('hidden');
        if (milestoneHintNode) {
            milestoneHintNode.textContent = 'Optional. Choose a milestone to narrow the sprint list.';
            milestoneHintNode.classList.remove('hidden');
        }
        if (sprintHintNode) sprintHintNode.classList.add('hidden');
        return;
    }

    // --- Move to Another Project Mode ---
    if (subtitleNode) subtitleNode.textContent = 'to another project';

    if (projectSelect) {
        projectSelect.removeAttribute('disabled');
        if (projectSelect.tomselect) {
            projectSelect.tomselect.enable();
        } else {
            projectSelect.disabled = false;
        }
    }

    const targetProjectId = String(projectSelect?.value || '');

    if (!targetProjectId) {
        if (targetFlowContainer) targetFlowContainer.classList.add('hidden');
        if (flowNotice) flowNotice.classList.add('hidden');

        setPlacementSelectOptions(milestoneField, [], {
            placeholder: 'Select project to load milestones',
            disabled: false,
            value: '',
        });
        setPlacementSelectOptions(sprintField, [], {
            placeholder: 'Select project to load sprints',
            disabled: false,
            value: '',
        });

        if (sprintStarNode) sprintStarNode.classList.add('hidden');
        if (milestoneHintNode) {
            milestoneHintNode.textContent = 'Select a project to view available milestones and sprints.';
            milestoneHintNode.classList.remove('hidden');
        }
        if (sprintHintNode) sprintHintNode.classList.add('hidden');
        return;
    }

    try {
        const projectDeps = await fetchMoveProjectDependencies(form, targetProjectId);
        if (!projectDeps) return;

        const sourceProjectFlow = String(form.dataset.sourceProjectFlow || 'agile').toLowerCase();
        const targetProjectFlow = String(projectDeps.flow || (projectDeps.is_linear ? 'linear' : 'agile')).toLowerCase();

        if (targetFlowContainer && targetFlowText) {
            if (targetProjectFlow === 'agile') {
                targetFlowText.innerHTML = '<span class="inline-flex items-center gap-1.5 text-purple-600 dark:text-purple-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg> Agile</span>';
            } else {
                targetFlowText.innerHTML = '<span class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg> Linear</span>';
            }
            targetFlowContainer.classList.remove('hidden');
        }

        const isFlowDifferent = sourceProjectFlow !== targetProjectFlow;

        if (flowNotice) {
            if (isFlowDifferent) {
                if (sourceFlowLabel) sourceFlowLabel.textContent = sourceProjectFlow.charAt(0).toUpperCase() + sourceProjectFlow.slice(1);
                if (targetFlowLabel) targetFlowLabel.textContent = targetProjectFlow.charAt(0).toUpperCase() + targetProjectFlow.slice(1);
                if (targetFlowLabel2) targetFlowLabel2.textContent = targetProjectFlow.charAt(0).toUpperCase() + targetProjectFlow.slice(1);
                if (targetStatusName) targetStatusName.textContent = projectDeps.default_status_name || (targetProjectFlow === 'linear' ? 'Pending' : 'Pending');
                flowNotice.classList.remove('hidden');
            } else {
                flowNotice.classList.add('hidden');
            }
        }

        const isLinear = targetProjectFlow === 'linear';

        if (isLinear) {
            setPlacementSelectOptions(milestoneField, [], {
                placeholder: 'Not applicable for Linear projects',
                disabled: true,
                value: '',
            });
            setPlacementSelectOptions(sprintField, [], {
                placeholder: 'Not applicable for Linear projects',
                disabled: true,
                value: '',
            });

            if (sprintStarNode) sprintStarNode.classList.add('hidden');
            if (milestoneHintNode) {
                milestoneHintNode.textContent = 'Linear project: Milestone and Sprint selections are disabled.';
                milestoneHintNode.classList.remove('hidden');
            }
            if (sprintHintNode) sprintHintNode.classList.add('hidden');
            return;
        }

        // Agile project
        const milestones = (projectDeps.milestones || []).map(m => ({ value: String(m.value || m.id), text: m.text || m.name }));
        const allSprints = (projectDeps.sprints || []).map(s => ({
            value: String(s.value || s.id),
            text: s.text || s.name,
            project_milestone_id: String(s.project_milestone_id || ''),
        }));

        let milestoneId = selectedMilestoneId === null ? String(milestoneField?.value || '') : String(selectedMilestoneId || '');
        let sprintId = selectedSprintId === null ? String(sprintField?.value || '') : String(selectedSprintId || '');

        if (syncModuleFromSprint && sprintId && !milestoneId) {
            const selectedSprintObj = allSprints.find(s => s.value === sprintId);
            if (selectedSprintObj && selectedSprintObj.project_milestone_id) {
                milestoneId = selectedSprintObj.project_milestone_id;
                setSelectValue(milestoneField, milestoneId);
            }
        }

        setPlacementSelectOptions(milestoneField, milestones, {
            placeholder: 'All milestones',
            disabled: false,
            value: milestoneId,
        });

        const availableSprints = allSprints.filter(s => {
            if (!milestoneId) return true;
            return s.project_milestone_id === milestoneId;
        });

        const resolvedSprintId = availableSprints.some(s => s.value === sprintId) ? sprintId : '';

        const isSprintRequired = Boolean(milestoneId);

        setPlacementSelectOptions(sprintField, availableSprints, {
            placeholder: milestoneId
                ? (availableSprints.length ? 'Select sprint (* Required)' : 'No sprints in selected milestone')
                : (allSprints.length ? 'Select sprint or leave empty for backlog' : 'No sprints available'),
            disabled: false,
            value: resolvedSprintId,
        });

        if (sprintStarNode) {
            sprintStarNode.classList.toggle('hidden', !isSprintRequired);
        }

        if (milestoneHintNode) {
            milestoneHintNode.textContent = milestoneId
                ? 'Sprint is required when a milestone is selected.'
                : 'Optional. Choose a milestone to narrow the sprint list, or leave empty for backlog.';
            milestoneHintNode.classList.remove('hidden');
        }

        if (sprintHintNode) {
            if (!milestoneId) {
                sprintHintNode.textContent = 'If left empty, task will be placed into the target project backlog.';
                sprintHintNode.classList.remove('hidden');
            } else {
                sprintHintNode.classList.add('hidden');
            }
        }
    } catch (error) {
        Alert.errorModal(error.message || 'Unable to load project options.');
    }
};

const syncTaskMovePlacement = (form, options = {}) => {
    syncTaskMoveUI(form, options);
};

const prepareTaskMoveModal = (root, triggerButton) => {
    const modal = root?.querySelector('[data-project-task-move-modal]');
    const form = modal?.querySelector('[data-project-task-move-form]');

    if (!modal || !form || !triggerButton) {
        return null;
    }

    form.reset();
    clearTaskMoveFormErrors(form);
    syncTaskFormSelectState(form);
    form.setAttribute('action', triggerButton.dataset.projectTaskMoveUrl || '');

    const taskNameNode = modal.querySelector('[data-project-task-move-task-name]');
    const currentSprintNode = modal.querySelector('[data-project-task-move-current-sprint]');
    const toggleCheckbox = form.querySelector('[data-project-task-move-another-project-toggle]');

    if (taskNameNode) {
        taskNameNode.textContent = triggerButton.dataset.projectTaskName || 'this task';
    }

    if (currentSprintNode) {
        currentSprintNode.textContent = triggerButton.dataset.projectTaskCurrentSprint || '--';
    }

    if (toggleCheckbox) {
        toggleCheckbox.checked = false;
    }

    const projectSelect = form.querySelector('[data-project-task-move-project-select]');
    setSelectValue(projectSelect, '');
    setSelectValue(form.querySelector('[name="project_milestone_id"]'), '');

    syncTaskMoveUI(form, {
        selectedMilestoneId: '',
        selectedSprintId: '',
    });

    return modal;
};

const deleteProjectTask = async (root, triggerButton) => {
    const actionUrl = triggerButton?.dataset.projectTaskDeleteUrl || '';

    if (!root || !actionUrl) {
        Alert.errorModal('Unable to delete the task right now.');
        return;
    }

    const taskName = triggerButton.dataset.projectTaskName || 'this task';
    const confirmation = await Alert.confirm({
        title: 'Confirm Delete',
        text: `Delete ${taskName}?`,
        confirmText: 'Yes, delete it',
        cancelText: 'Cancel',
        requireText: 'DELETE',
    });

    if (!confirmation?.isConfirmed) {
        return;
    }

    const payload = new FormData();
    payload.append('_method', 'DELETE');

    const response = await fetch(actionUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: payload,
    });
    const result = await response.json();

    if (!response.ok || !result.status) {
        throw new Error(result.message || 'Unable to delete the task.');
    }

    const newRoot = replaceTasksRoot(root, result.html);
    Alert.success(result.message || 'Task deleted successfully.');

    if (newRoot) {
        initializeTasksRoot(newRoot);
    }
};

const setParentTaskOptions = (selectField, options = [], selectedValue = '', { placeholder = 'Select parent task', disabled = false } = {}) => {
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

        selectField.tomselect.clearCache();
        selectField.tomselect.refreshOptions(false);
        selectField.tomselect.enable();

        if (selectedValue) {
            selectField.tomselect.setValue(String(selectedValue), true);
            selectField.tomselect.refreshOptions(false);
        }

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
        optionElement.selected = String(selectedValue) === String(option.value);
        selectField.appendChild(optionElement);
    });

    selectField.disabled = disabled;
};

const loadParentTaskOptions = async (form, {
    sprintId: sprintIdOverride = null,
    selectedParentTaskId: selectedParentTaskIdOverride = null,
} = {}) => {
    if (!form) {
        return;
    }

    const parentTaskField = form.querySelector('[data-parent-task-select]');

    if (!parentTaskField) {
        return;
    }

    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const loadUrl = parentTaskField.dataset.parentTaskUrl || form.dataset.parentTaskUrl || '';
    const currentTaskId = form.dataset.currentTaskId || '';
    const selectedParentTaskId = selectedParentTaskIdOverride === null
        ? (parentTaskField.value || '')
        : String(selectedParentTaskIdOverride || '');
    const sprintId = sprintIdOverride === null
        ? (sprintField?.value || '')
        : String(sprintIdOverride || '');
    const isLinearFlow = !sprintField;

    if (!loadUrl) {
        return;
    }

    setParentTaskOptions(parentTaskField, [], selectedParentTaskId, {
        placeholder: 'Loading parent tasks...',
        disabled: true,
    });

    const requestUrl = new URL(loadUrl, window.location.origin);

    if (sprintId) {
        requestUrl.searchParams.set('project_sprint_id', sprintId);
    }

    if (currentTaskId) {
        requestUrl.searchParams.set('task_id', currentTaskId);
    }

    if (selectedParentTaskId) {
        requestUrl.searchParams.set('parent_task_id', selectedParentTaskId);
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

    setParentTaskOptions(parentTaskField, result.options || [], selectedParentTaskId, {
        placeholder: (result.options || []).length
            ? 'Select parent task'
            : (isLinearFlow || sprintId ? 'No parent tasks available' : 'No backlog parent tasks available'),
        disabled: false,
    });
};

const clearTaskDetailFormErrors = (form) => {
    form.querySelectorAll('[data-project-task-detail-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        getFieldErrorTarget(field)?.classList.remove('border-red-500');
    });
};

const applyTaskDetailFormErrors = (form, errors = {}) => {
    clearTaskDetailFormErrors(form);

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const field = form.querySelector(`[name="${fieldName}"], [name="${fieldName}[]"]`);
        const errorNode = form.querySelector(`[data-project-task-detail-error="${fieldName}"]`);

        getFieldErrorTarget(field)?.classList.add('border-red-500');

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
        <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">
            <div class="flex h-[82vh] items-center justify-center px-6 py-12 text-sm font-medium text-bgray-700 dark:text-bgray-300">
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
            syncProjectTaskPlacement(form);
            loadParentTaskOptions(form).catch(() => { });

            const editorElement = form.querySelector('#project_task_detail_description_editor');
            if (editorElement && !projectTaskDetailEditors.has(form)) {
                const descInput = form.querySelector('#project_task_detail_description_input');
                const initialValue = descInput ? descInput.value : '';

                const quill = new window.Quill(editorElement, {
                    theme: 'snow',
                    placeholder: 'Enter task description...',
                });

                if (initialValue) {
                    quill.clipboard.dangerouslyPasteHTML(initialValue);
                }

                projectTaskDetailEditors.set(form, quill);
            }
        }
    } catch (error) {
        closeTaskDetailModal(modal);
        Alert.errorModal(error.message || 'Unable to load task details.');
    }
};


const openTaskLogModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
};

const closeTaskLogModal = (modal) => {
    if (!modal) {
        return;
    }

    const content = modal.querySelector('[data-project-task-log-content]');

    modal.classList.add('hidden');

    if (content) {
        content.innerHTML = '';
    }
};

const showTaskLogLoading = (modal) => {
    const content = modal?.querySelector('[data-project-task-log-content]');

    if (!content) {
        return;
    }

    content.innerHTML = `
        <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">
            <div class="flex h-[82vh] items-center justify-center px-6 py-12 text-sm font-medium text-bgray-700 dark:text-bgray-300">
                Loading task logs...
            </div>
        </div>
    `;
};

// Load task log modal
const loadTaskLogModal = async (root, loadUrl) => {

    const modal = root.querySelector('[data-project-task-log-modal]');

    const content = modal?.querySelector('[data-project-task-log-content]');

    if (!modal || !content || !loadUrl) {
        return;
    }

    openTaskLogModal(modal);
    showTaskLogLoading(modal);

    try {
        const response = await fetch(loadUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load task log details.');
        }

        content.innerHTML = result.html;

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(content);
        }

    } catch (error) {
        closeTaskLogModal(modal);
        Alert.errorModal(
            error.message || 'Unable to load task log details.'
        );
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

const setProjectTaskSubtaskToggleState = (root, taskId, expanded) => {
    const toggle = root.querySelector(`[data-project-task-subtasks-parent="${taskId}"]`);
    const icon = toggle?.querySelector('[data-project-task-subtasks-icon]');

    toggle?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    icon?.classList.toggle('rotate-90', expanded);
};

const getProjectTaskDirectChildRows = (root, parentId) => root.querySelectorAll(`[data-project-task-parent-id="${parentId}"]`);

const collapseProjectTaskBranch = (root, parentId) => {
    if (!root || !parentId) {
        return;
    }

    getProjectTaskDirectChildRows(root, parentId).forEach((row) => {
        const childTaskId = row.dataset.projectTaskId || '';

        row.hidden = true;
        row.classList.add('hidden');
        setProjectTaskSubtaskToggleState(root, childTaskId, false);

        if (childTaskId) {
            collapseProjectTaskBranch(root, childTaskId);
        }
    });
};

const setProjectTaskSubtaskGroupState = (root, parentId, expanded) => {
    if (!root || !parentId) {
        return;
    }

    setProjectTaskSubtaskToggleState(root, parentId, expanded);

    if (!expanded) {
        collapseProjectTaskBranch(root, parentId);
        return;
    }

    getProjectTaskDirectChildRows(root, parentId).forEach((row) => {
        row.hidden = false;
        row.classList.remove('hidden');
    });
};

const handleTaskRootSuccess = (root, result, {
    successMessage = 'Task updated successfully.',
    closeModal = null,
} = {}) => {
    const responseMode = root?.dataset.projectTaskResponseMode || 'replace';

    closeModal?.();

    if (responseMode === 'reload') {
        window.location.reload();
        return null;
    }

    const newRoot = replaceTasksRoot(root, result.html);
    Alert.success(result.message || successMessage);

    if (newRoot) {
        initializeTasksRoot(newRoot);
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
    if (!root || root.dataset.projectTasksInitialized === 'true') {
        return;
    }

    root.dataset.projectTasksInitialized = 'true';
    initTomSelect(root);
    initializeTaskGroupPagination(root);

    const editorElement = root.querySelector('#project_task_description_editor');
    if (editorElement && !projectTaskEditors.has(root)) {
        const editor = new window.Quill(editorElement, {
            theme: 'snow',
            placeholder: 'Add task details...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'header': [1, 2, 3, false] }],
                    ['link']
                ]
            }
        });
        projectTaskEditors.set(root, editor);
    }

    if (!taskRowMenuDocumentListenerBound) {
        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-project-task-row-dropdown]')) {
                return;
            }

            closeAllTaskRowMenus();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const detailModal = document.querySelector('[data-project-task-detail-modal]:not(.hidden)');
                if (detailModal) {
                    closeTaskDetailModal(detailModal);
                }
            }
        });
        window.addEventListener('resize', () => {
            closeAllTaskRowMenus();
        });
        window.addEventListener('scroll', () => {
            closeAllTaskRowMenus();
        }, true);
        taskRowMenuDocumentListenerBound = true;
    }

    root.addEventListener('click', async (event) => {
        const rowMenuTrigger = event.target.closest('[data-project-task-row-menu-trigger]');

        if (rowMenuTrigger && root.contains(rowMenuTrigger)) {
            const dropdown = rowMenuTrigger.closest('[data-project-task-row-dropdown]');
            const menu = dropdown?.querySelector('[data-project-task-row-menu]');

            if (!dropdown || !menu) {
                return;
            }

            const shouldOpen = menu.classList.contains('hidden');
            closeAllTaskRowMenus(dropdown);

            if (shouldOpen) {
                menu.style.position = 'fixed';
                menu.style.top = '0px';
                menu.style.left = '0px';
                menu.style.right = 'auto';
                menu.style.bottom = 'auto';
                menu.style.visibility = 'hidden';
                menu.classList.remove('hidden');

                window.requestAnimationFrame(() => {
                    positionTaskRowMenu(rowMenuTrigger, menu);
                    menu.style.visibility = '';
                });
            } else {
                menu.classList.add('hidden');
            }

            rowMenuTrigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            return;
        }

        const moveOpenButton = event.target.closest('[data-project-task-move-open]');

        if (moveOpenButton && root.contains(moveOpenButton)) {
            closeAllTaskRowMenus();
            const modal = prepareTaskMoveModal(root, moveOpenButton);
            openTaskMoveModal(modal);
            return;
        }

        const deleteButton = event.target.closest('[data-project-task-delete]');

        if (deleteButton && root.contains(deleteButton)) {
            closeAllTaskRowMenus();

            try {
                await deleteProjectTask(root, deleteButton);
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to delete the task.');
            }

            return;
        }

        const subtaskToggle = event.target.closest('[data-project-task-subtasks-toggle]');

        if (subtaskToggle && root.contains(subtaskToggle)) {
            const parentId = subtaskToggle.dataset.projectTaskSubtasksParent || '';
            const isExpanded = subtaskToggle.getAttribute('aria-expanded') === 'true';

            setProjectTaskSubtaskGroupState(root, parentId, !isExpanded);
            return;
        }

        const moveCloseButton = event.target.closest('[data-project-task-move-close]');

        if (moveCloseButton && root.contains(moveCloseButton)) {
            closeTaskMoveModal(root.querySelector('[data-project-task-move-modal]'));
            return;
        }

        if (event.target.closest('[data-task-notes-modal-trigger]')) {
            return;
        }

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
            closeAllTaskRowMenus();

            await prepareTaskModal(root, {
                sprintId: openButton.dataset.projectTaskSprintId || '',
                milestoneId: openButton.dataset.projectTaskMilestoneId || '',
                parentTaskId: openButton.dataset.projectTaskParentTaskId || '',
            });
            openTaskModal(root.querySelector('[data-project-task-modal]'));
            return;
        }

        const logOpenButton = event.target.closest('[data-project-task-log-open]');

        if (logOpenButton && root.contains(logOpenButton)) {
            await loadTaskLogModal(
                root,
                logOpenButton.dataset.projectTaskLogUrl || ''
            );
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
        const detailModalTarget = event.target.closest('[data-project-task-detail-modal]');
        const detailContentTarget = event.target.closest('[data-project-task-detail-content]');

        if (detailCloseButton && root.contains(detailCloseButton)) {
            closeTaskDetailModal(root.querySelector('[data-project-task-detail-modal]'));
            return;
        }

        if (detailModalTarget && !detailContentTarget && root.contains(detailModalTarget)) {
            closeTaskDetailModal(detailModalTarget);
            return;
        }

        const taskLogCloseButton = event.target.closest('[data-project-task-log-close]');
        const taskLogTarget = event.target.closest('[data-project-task-log-modal]');
        const taskLogContentTarget = event.target.closest('[data-project-task-log-content]');

        if (taskLogCloseButton && root.contains(taskLogCloseButton)) {
            closeTaskLogModal(root.querySelector('[data-project-task-log-modal]'));
            return;
        }

        if (taskLogTarget && !taskLogContentTarget && root.contains(taskLogTarget)) {
            closeTaskDetailModal(taskLogTarget);
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
        const toggleCheckbox = event.target.closest('[data-project-task-move-another-project-toggle]');

        if (toggleCheckbox && root.contains(toggleCheckbox)) {
            const moveForm = toggleCheckbox.closest('[data-project-task-move-form]');
            if (moveForm) {
                clearTaskMoveFormErrors(moveForm);
                syncTaskMoveUI(moveForm, {
                    selectedMilestoneId: '',
                    selectedSprintId: '',
                });
                return;
            }
        }

        const projectSelect = event.target.closest('[data-project-task-move-project-select]');

        if (projectSelect && root.contains(projectSelect)) {
            const moveForm = projectSelect.closest('[data-project-task-move-form]');
            if (moveForm) {
                clearTaskMoveFormErrors(moveForm);
                syncTaskMoveUI(moveForm, {
                    selectedMilestoneId: '',
                    selectedSprintId: '',
                });
                return;
            }
        }

        const milestoneField = event.target.closest('[name="project_milestone_id"]');

        if (milestoneField && root.contains(milestoneField)) {
            const moveForm = milestoneField.closest('[data-project-task-move-form]');

            if (moveForm) {
                clearTaskMoveFormErrors(moveForm);
                syncTaskMoveUI(moveForm, {
                    selectedMilestoneId: milestoneField.value || '',
                    selectedSprintId: moveForm.querySelector('[name="project_sprint_id"]')?.value || '',
                });
                return;
            }

            const form = milestoneField.closest('[data-project-task-form], [data-project-task-detail-form]');

            if (!form) {
                return;
            }

            syncProjectTaskPlacement(form, {
                selectedMilestoneId: milestoneField.value || '',
                selectedSprintId: form.querySelector('[name="project_sprint_id"]')?.value || '',
            });

            loadParentTaskOptions(form).catch((error) => {
                Alert.errorModal(error.message || 'Unable to load parent tasks.');
            });
            return;
        }

        const sprintField = event.target.closest('[name="project_sprint_id"]');

        if (!sprintField || !root.contains(sprintField)) {
            return;
        }

        const moveForm = sprintField.closest('[data-project-task-move-form]');

        if (moveForm) {
            clearTaskMoveFormErrors(moveForm);
            syncTaskMoveUI(moveForm, {
                selectedMilestoneId: moveForm.querySelector('[name="project_milestone_id"]')?.value || '',
                selectedSprintId: sprintField.value || '',
                syncModuleFromSprint: true,
            });
            return;
        }

        const form = sprintField.closest('[data-project-task-form], [data-project-task-detail-form]');

        if (!form) {
            return;
        }

        syncProjectTaskPlacement(form, {
            selectedMilestoneId: form.querySelector('[name="project_milestone_id"]')?.value || '',
            selectedSprintId: sprintField.value || '',
            syncModuleFromSprint: true,
        });

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

            const editor = projectTaskEditors.get(root);
            const descInput = form.querySelector('#project_task_description_input');
            if (editor && descInput) {
                const content = editor.root.innerHTML.trim();
                descInput.value = (content === '<p><br></p>') ? '' : content;
            }

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

                handleTaskRootSuccess(root, result, {
                    successMessage: 'Task added successfully.',
                    closeModal: () => closeTaskModal(modal),
                });
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
        const moveForm = event.target.closest('[data-project-task-move-form]');

        if (moveForm && root.contains(moveForm)) {
            event.preventDefault();
            clearTaskMoveFormErrors(moveForm);

            const submitButton = moveForm.querySelector('[data-project-task-move-submit]');
            const modal = root.querySelector('[data-project-task-move-modal]');
            const actionUrl = moveForm.getAttribute('action');

            if (!actionUrl) {
                Alert.errorModal('Unable to move the task right now.');
                return;
            }

            const toggleCheckbox = moveForm.querySelector('[data-project-task-move-another-project-toggle]');
            const projectSelect = moveForm.querySelector('[data-project-task-move-project-select]');
            const isAnotherProject = Boolean(toggleCheckbox?.checked);
            const targetProjectId = String(projectSelect?.value || '');
            const milestoneId = String(moveForm.querySelector('[name="project_milestone_id"]')?.value || '');
            const sprintId = String(moveForm.querySelector('[name="project_sprint_id"]')?.value || '');

            // Frontend validation before submitting
            if (!isAnotherProject) {
                if (!sprintId) {
                    applyTaskMoveFormErrors(moveForm, {
                        project_sprint_id: ['Please choose a sprint to move this task to.'],
                    });
                    return;
                }
            } else {
                if (!targetProjectId) {
                    applyTaskMoveFormErrors(moveForm, {
                        target_project_id: ['Please select a project.'],
                    });
                    return;
                }

                const projectDeps = loadedMoveProjectDependencies[targetProjectId];
                const isLinear = projectDeps?.flow === 'linear';

                if (!isLinear && milestoneId && !sprintId) {
                    applyTaskMoveFormErrors(moveForm, {
                        project_sprint_id: ['Please choose a sprint for the selected milestone.'],
                        target_sprint_id: ['Please choose a sprint for the selected milestone.'],
                    });
                    return;
                }
            }

            submitButton?.setAttribute('disabled', 'disabled');

            if (submitButton) {
                submitButton.textContent = 'Moving...';
            }

            try {
                const payload = new FormData(moveForm);

                if (isAnotherProject) {
                    payload.set('move_to_another_project', '1');
                    payload.set('target_project_id', targetProjectId);
                    payload.set('target_milestone_id', milestoneId || '');
                    payload.set('target_sprint_id', sprintId || '');
                }

                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: payload,
                });
                const result = await response.json();

                if (response.status === 422 && result.errors) {
                    applyTaskMoveFormErrors(moveForm, result.errors);
                    throw new Error(result.message || 'Please correct the highlighted fields.');
                }

                if (!response.ok || !result.status) {
                    throw new Error(result.message || 'Unable to move the task.');
                }

                handleTaskRootSuccess(root, result, {
                    successMessage: 'Task moved successfully.',
                    closeModal: () => closeTaskMoveModal(modal),
                });
            } catch (error) {
                if (!(error.message || '').includes('highlighted fields')) {
                    Alert.errorModal(error.message || 'Unable to move the task.');
                }
            } finally {
                submitButton?.removeAttribute('disabled');

                if (submitButton) {
                    submitButton.textContent = 'Move';
                }
            }

            return;
        }

        const detailForm = event.target.closest('[data-project-task-detail-form]');

        if (!detailForm || !root.contains(detailForm)) {
            return;
        }

        event.preventDefault();
        clearTaskDetailFormErrors(detailForm);

        const editor = projectTaskDetailEditors.get(detailForm);
        if (editor) {
            const descInput = detailForm.querySelector('#project_task_detail_description_input');
            if (descInput) {
                const html = editor.root.innerHTML;
                descInput.value = (html === '<p><br></p>' || html === '<p></p>') ? '' : html;
            }
        }

        const submitButton = detailForm.querySelector('[data-project-task-detail-submit]');
        const modal = root.querySelector('[data-project-task-detail-modal]');
        const actionUrl = detailForm.getAttribute('action');
        const submitLabel = detailForm.dataset.submitLabel || 'Update Task';
        const submittingLabel = detailForm.dataset.submittingLabel || 'Updating...';

        if (!actionUrl) {
            Alert.errorModal('Unable to update the task right now.');
            return;
        }

        submitButton?.setAttribute('disabled', 'disabled');

        if (submitButton) {
            submitButton.textContent = submittingLabel;
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

            handleTaskRootSuccess(root, result, {
                successMessage: 'Task updated successfully.',
                closeModal: () => closeTaskDetailModal(modal),
            });
        } catch (error) {
            if (!(error.message || '').includes('highlighted fields')) {
                Alert.errorModal(error.message || 'Unable to update the task.');
            }
        } finally {
            submitButton?.removeAttribute('disabled');

            if (submitButton) {
                submitButton.textContent = submitLabel;
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
