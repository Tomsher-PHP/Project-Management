import Alert from '../../alert';
import { initTomSelect } from '../../components/tom-select';

const getFieldErrorTarget = (field) => field?.tomselect?.control || field;

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

const parseTaskMovePlacementOptions = (form) => {
    if (!form?.dataset.taskMovePlacement) {
        return {
            timelines: [],
            milestones: [],
            sprints: [],
        };
    }

    try {
        return JSON.parse(form.dataset.taskMovePlacement);
    } catch (error) {
        return {
            timelines: [],
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
    {
        selectedTimelineId = null,
        selectedMilestoneId = null,
        selectedSprintId = null,
        syncModuleFromSprint = false,
    } = {}
) => {
    if (!form) return;

    const toggleCheckbox = form.querySelector('[data-project-task-move-another-project-toggle]');
    const projectContainer = form.querySelector('[data-project-task-move-project-container]');
    const projectSelect = form.querySelector('[data-project-task-move-project-select]');
    const timelineField = form.querySelector('[name="project_timeline_id"]');
    const milestoneField = form.querySelector('[name="project_milestone_id"]');
    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const subtitleNode = form.querySelector('[data-project-task-move-subtitle-text]');
    const sprintStarNode = form.querySelector('[data-project-task-move-sprint-star]');
    const milestoneHintNode = form.querySelector('[data-project-task-move-milestone-hint]');
    const sprintHintNode = form.querySelector('[data-project-task-move-sprint-hint]');
    const timelineHintNode = form.querySelector('[data-project-task-move-timeline-hint]');
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
        if (subtitleNode) subtitleNode.textContent = form.dataset.sourceProjectFlow === 'linear'
            ? 'to another timeline'
            : 'to another sprint or timeline';

        if (projectSelect) {
            setSelectValue(projectSelect, '');
            if (projectSelect.tomselect) {
                projectSelect.tomselect.disable();
            } else {
                projectSelect.disabled = true;
            }
        }

        const placement = parseTaskMovePlacementOptions(form);
        const sourceProjectFlow = String(form.dataset.sourceProjectFlow || 'agile').toLowerCase();
        const isLinear = sourceProjectFlow === 'linear';
        const currentTimelineId = String(form.dataset.currentTimelineId || '');
        const timelineOptions = (placement.timelines || []).map(timeline => ({
            value: String(timeline.id || timeline.value),
            text: timeline.name || timeline.text,
            subtype: timeline.subtype || '',
        }));
        const requestedTimelineId = selectedTimelineId === null
            ? String(timelineField?.value || '')
            : String(selectedTimelineId || currentTimelineId);
        const resolvedTimelineId = timelineOptions.some(option => option.value === requestedTimelineId)
            ? requestedTimelineId
            : '';

        setPlacementSelectOptions(timelineField, timelineOptions, {
            placeholder: timelineOptions.length ? 'Select project timeline' : 'No eligible timelines available',
            disabled: !timelineOptions.length,
            value: resolvedTimelineId,
        });

        if (timelineHintNode) {
            timelineHintNode.textContent = timelineOptions.length
                ? ''
                : 'This project has no active or completed timelines available.';
            timelineHintNode.classList.toggle('hidden', timelineOptions.length > 0);
        }

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
                return syncTaskMoveUI(form, {
                    selectedTimelineId: resolvedTimelineId,
                    selectedMilestoneId: matchedMilestoneId,
                    selectedSprintId: sprintId,
                });
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

        if (sprintStarNode) sprintStarNode.classList.add('hidden');
        if (milestoneHintNode) {
            milestoneHintNode.textContent = 'Optional. Choose a milestone to narrow the sprint list.';
            milestoneHintNode.classList.remove('hidden');
        }
        if (sprintHintNode) {
            sprintHintNode.textContent = 'Choose another sprint, or leave it empty when moving only to another timeline.';
            sprintHintNode.classList.remove('hidden');
        }
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
        setPlacementSelectOptions(timelineField, [], {
            placeholder: 'Select project to load timelines',
            disabled: true,
            value: '',
        });

        if (sprintStarNode) sprintStarNode.classList.add('hidden');
        if (milestoneHintNode) {
            milestoneHintNode.textContent = 'Select a project to view available milestones and sprints.';
            milestoneHintNode.classList.remove('hidden');
        }
        if (sprintHintNode) sprintHintNode.classList.add('hidden');
        if (timelineHintNode) {
            timelineHintNode.textContent = 'Select a project to view available timelines.';
            timelineHintNode.classList.remove('hidden');
        }
        return;
    }

    try {
        const projectDeps = await fetchMoveProjectDependencies(form, targetProjectId);
        if (!projectDeps) return;
        if (!toggleCheckbox?.checked || String(projectSelect?.value || '') !== targetProjectId) return;

        const timelines = (projectDeps.timelines || []).map(timeline => ({
            value: String(timeline.value || timeline.id),
            text: timeline.text || timeline.name,
            subtype: timeline.subtype || '',
        }));
        const requestedTimelineId = selectedTimelineId === null
            ? String(timelineField?.value || '')
            : String(selectedTimelineId || '');
        const defaultTimelineId = String(projectDeps.default_timeline_id || '');
        const resolvedTimelineId = timelines.some(timeline => timeline.value === requestedTimelineId)
            ? requestedTimelineId
            : (timelines.some(timeline => timeline.value === defaultTimelineId) ? defaultTimelineId : '');

        setPlacementSelectOptions(timelineField, timelines, {
            placeholder: timelines.length ? 'Select project timeline' : 'No eligible timelines available',
            disabled: !timelines.length,
            value: resolvedTimelineId,
        });

        if (timelineHintNode) {
            timelineHintNode.textContent = timelines.length
                ? ''
                : 'The selected project has no active or completed timelines available.';
            timelineHintNode.classList.toggle('hidden', timelines.length > 0);
        }

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
    const taskCodeNode = modal.querySelector('[data-project-task-move-task-code]');
    const currentSprintNode = modal.querySelector('[data-project-task-move-current-sprint]');
    const currentTimelineNode = modal.querySelector('[data-project-task-move-current-timeline]');
    const toggleCheckbox = form.querySelector('[data-project-task-move-another-project-toggle]');

    if (taskNameNode) {
        taskNameNode.textContent = triggerButton.dataset.projectTaskName || '--';
    }

    if (taskCodeNode) {
        taskCodeNode.textContent = triggerButton.dataset.projectTaskCode || '--';
    }

    if (currentSprintNode) {
        currentSprintNode.textContent = triggerButton.dataset.projectTaskCurrentSprint || '--';
    }

    if (currentTimelineNode) {
        currentTimelineNode.textContent = triggerButton.dataset.projectTaskCurrentTimeline || '--';
    }

    if (toggleCheckbox) {
        toggleCheckbox.checked = false;
    }

    const projectSelect = form.querySelector('[data-project-task-move-project-select]');
    form.dataset.currentTimelineId = triggerButton.dataset.projectTaskCurrentTimelineId || '';
    form.dataset.currentSprintId = triggerButton.dataset.projectTaskCurrentSprintId || '';
    setSelectValue(projectSelect, '');
    setSelectValue(form.querySelector('[name="project_milestone_id"]'), '');

    syncTaskMoveUI(form, {
        selectedTimelineId: form.dataset.currentTimelineId,
        selectedMilestoneId: '',
        selectedSprintId: '',
    });

    return modal;
};

export const initializeProjectTaskMove = (root, {
    onBeforeOpen = null,
    onMoved = null,
} = {}) => {
    if (!root || root.dataset.projectTaskMoveInitialized === 'true') {
        return;
    }

    const modal = root.querySelector('[data-project-task-move-modal]');

    if (!modal) {
        return;
    }

    root.dataset.projectTaskMoveInitialized = 'true';
    initTomSelect(modal);

    root.addEventListener('click', (event) => {
        const moveOpenButton = event.target.closest('[data-project-task-move-open]');

        if (moveOpenButton && root.contains(moveOpenButton)) {
            onBeforeOpen?.();
            openTaskMoveModal(prepareTaskMoveModal(root, moveOpenButton));
            return;
        }

        const moveCloseButton = event.target.closest('[data-project-task-move-close]');

        if (moveCloseButton && root.contains(moveCloseButton)) {
            closeTaskMoveModal(modal);
            return;
        }

        const panel = modal.querySelector('[data-project-task-move-panel]');
        const isClickInsideModal = modal.contains(event.target);
        const isClickInsidePanel = panel?.contains(event.target);
        const isClickOnDropdown = event.target.closest('.ts-dropdown');

        if (isClickInsideModal && !isClickInsidePanel && !isClickOnDropdown) {
            closeTaskMoveModal(modal);
        }
    });

    root.addEventListener('change', (event) => {
        const toggleCheckbox = event.target.closest('[data-project-task-move-another-project-toggle]');

        if (toggleCheckbox && root.contains(toggleCheckbox)) {
            const moveForm = toggleCheckbox.closest('[data-project-task-move-form]');

            if (moveForm) {
                clearTaskMoveFormErrors(moveForm);
                syncTaskMoveUI(moveForm, {
                    selectedTimelineId: toggleCheckbox.checked
                        ? ''
                        : moveForm.dataset.currentTimelineId || '',
                    selectedMilestoneId: '',
                    selectedSprintId: '',
                });
            }

            return;
        }

        const projectSelect = event.target.closest('[data-project-task-move-project-select]');

        if (projectSelect && root.contains(projectSelect)) {
            const moveForm = projectSelect.closest('[data-project-task-move-form]');

            if (moveForm) {
                clearTaskMoveFormErrors(moveForm);
                syncTaskMoveUI(moveForm, {
                    selectedTimelineId: '',
                    selectedMilestoneId: '',
                    selectedSprintId: '',
                });
            }

            return;
        }

        const timelineField = event.target.closest('[name="project_timeline_id"]');

        if (timelineField?.closest('[data-project-task-move-form]') && root.contains(timelineField)) {
            clearTaskMoveFormErrors(timelineField.closest('[data-project-task-move-form]'));
            return;
        }

        const milestoneField = event.target.closest('[name="project_milestone_id"]');
        const milestoneMoveForm = milestoneField?.closest('[data-project-task-move-form]');

        if (milestoneMoveForm && root.contains(milestoneField)) {
            clearTaskMoveFormErrors(milestoneMoveForm);
            syncTaskMoveUI(milestoneMoveForm, {
                selectedTimelineId: milestoneMoveForm.querySelector('[name="project_timeline_id"]')?.value || '',
                selectedMilestoneId: milestoneField.value || '',
                selectedSprintId: milestoneMoveForm.querySelector('[name="project_sprint_id"]')?.value || '',
            });
            return;
        }

        const sprintField = event.target.closest('[name="project_sprint_id"]');
        const sprintMoveForm = sprintField?.closest('[data-project-task-move-form]');

        if (sprintMoveForm && root.contains(sprintField)) {
            clearTaskMoveFormErrors(sprintMoveForm);
            syncTaskMoveUI(sprintMoveForm, {
                selectedTimelineId: sprintMoveForm.querySelector('[name="project_timeline_id"]')?.value || '',
                selectedMilestoneId: sprintMoveForm.querySelector('[name="project_milestone_id"]')?.value || '',
                selectedSprintId: sprintField.value || '',
                syncModuleFromSprint: true,
            });
        }
    });

    root.addEventListener('submit', async (event) => {
        const moveForm = event.target.closest('[data-project-task-move-form]');

        if (!moveForm || !root.contains(moveForm)) {
            return;
        }

        event.preventDefault();
        clearTaskMoveFormErrors(moveForm);

        const submitButton = moveForm.querySelector('[data-project-task-move-submit]');
        const actionUrl = moveForm.getAttribute('action');

        if (!actionUrl) {
            Alert.errorModal('Unable to move the task right now.');
            return;
        }

        const toggleCheckbox = moveForm.querySelector('[data-project-task-move-another-project-toggle]');
        const projectSelect = moveForm.querySelector('[data-project-task-move-project-select]');
        const isAnotherProject = Boolean(toggleCheckbox?.checked);
        const targetProjectId = String(projectSelect?.value || '');
        const timelineId = String(moveForm.querySelector('[name="project_timeline_id"]')?.value || '');
        const milestoneId = String(moveForm.querySelector('[name="project_milestone_id"]')?.value || '');
        const sprintId = String(moveForm.querySelector('[name="project_sprint_id"]')?.value || '');
        const currentTimelineId = String(moveForm.dataset.currentTimelineId || '');
        const timelineChanged = timelineId !== currentTimelineId;

        if (!isAnotherProject) {
            if (!timelineId) {
                applyTaskMoveFormErrors(moveForm, {
                    project_timeline_id: ['Please select a project timeline.'],
                });
                return;
            }

            if (!timelineChanged && !sprintId) {
                applyTaskMoveFormErrors(moveForm, {
                    project_timeline_id: ['Please select another timeline or choose a different sprint.'],
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

            if (!timelineId) {
                applyTaskMoveFormErrors(moveForm, {
                    project_timeline_id: ['Please select a project timeline.'],
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

            closeTaskMoveModal(modal);

            if (typeof onMoved === 'function') {
                onMoved(result);
            } else {
                Alert.success(result.message || 'Task moved successfully.');
                window.location.reload();
            }
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
    });
};

