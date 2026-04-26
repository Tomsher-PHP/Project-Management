import Alert from '../alert';
import { initTomSelect } from '../components/tom-select';
import { initDatepicker } from '../components/datepicker';
import { initTimepicker } from '../components/timepicker';
import { initWeekPicker } from '../components/weekpicker';
import { initializeEstimatedTimeInputs } from '../components/estimated-time-input';
import './task-insights-modal';
import './task-comments';
import './task-files';
import './tasks/task-status-dropdown';
import './tasks/time-log-change-request';
import './projects/project-tasks';

const TASK_DETAIL_LOADING_HTML = (tab) => `
    <div class="flex items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 py-12 text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
        Loading ${tab.charAt(0).toUpperCase() + tab.slice(1)}...
    </div>
`;

const getFieldErrorTarget = (field) => field?.tomselect?.control || field;

const clearTaskSettingsErrors = (form) => {
    form.querySelectorAll('[data-task-settings-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        getFieldErrorTarget(field)?.classList.remove('border-red-500');
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

    field.disabled = disabled;
    field.value = normalizedValue;
};

const setTaskSettingsPlacementHint = (form, { selectedMilestoneId = '', selectedSprintId = '' } = {}) => {
    const hintNode = form?.querySelector('[data-task-settings-placement-hint]');

    if (!hintNode) {
        return;
    }

    let message = 'Leave both milestone and sprint empty to move this task into the project backlog.';

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

const syncTaskSettingsPlacement = (
    form,
    { selectedMilestoneId = null, selectedSprintId = null, syncModuleFromSprint = false } = {}
) => {
    const milestoneField = form?.querySelector('[data-task-settings-milestone-select]');
    const sprintField = form?.querySelector('[data-task-settings-sprint-select]');

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

    setTaskSettingsPlacementHint(form, {
        selectedMilestoneId: milestoneId,
        selectedSprintId: resolvedSprintId,
    });
};

const applyTaskSettingsErrors = (form, errors = {}) => {
    clearTaskSettingsErrors(form);

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const normalizedFieldName = fieldName.split('.')[0];
        const field = form.querySelector(`[name="${normalizedFieldName}"], [name="${normalizedFieldName}[]"]`);
        const errorNode = form.querySelector(`[data-task-settings-error="${normalizedFieldName}"]`);

        getFieldErrorTarget(field)?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
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

        selectField.tomselect.refreshOptions(false);

        if (selectedValue) {
            selectField.tomselect.setValue(String(selectedValue), true);
        }

        if (disabled) {
            selectField.tomselect.disable();
        } else {
            selectField.tomselect.enable();
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

const loadParentTaskOptions = async (form) => {
    if (!form) {
        return;
    }

    const parentTaskField = form.querySelector('[data-task-settings-parent-task-select]');

    if (!parentTaskField) {
        return;
    }

    const sprintField = form.querySelector('[data-task-settings-sprint-select]');
    const loadUrl = form.dataset.parentTaskUrl || '';
    const selectedParentTaskId = parentTaskField.value || '';
    const sprintId = sprintField?.value || '';

    if (!loadUrl) {
        return;
    }

    if (sprintField && !sprintId) {
        setParentTaskOptions(parentTaskField, [], selectedParentTaskId, {
            placeholder: 'Choose sprint to enable parent tasks',
            disabled: true,
        });
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
        placeholder: (result.options || []).length ? 'Select parent task' : 'No parent tasks available',
        disabled: false,
    });
};

const initializeTaskSettings = (root = document) => {
    const form = root.querySelector ? root.querySelector('[data-task-settings-form]') : document.querySelector('[data-task-settings-form]');

    if (!form || form.dataset.initialized === 'true') {
        return;
    }

    const canEdit = form.dataset.canEdit === 'true';
    const submitButton = form.querySelector('[data-task-settings-submit]');

    if (!canEdit) {
        form.querySelectorAll('select').forEach((field) => {
            field.disabled = true;
            field.tomselect?.disable();
        });
        form.querySelectorAll('input, textarea, button[type="submit"]').forEach((field) => {
            field.disabled = true;
        });
        form.dataset.initialized = 'true';
        return;
    }

    let dirty = false;

    const setDirty = () => {
        dirty = true;

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Update Task';
        }
    };

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.addEventListener('input', setDirty);
        field.addEventListener('change', setDirty);
    });

    const sprintField = form.querySelector('[data-task-settings-sprint-select]');
    const milestoneField = form.querySelector('[data-task-settings-milestone-select]');

    if (milestoneField && sprintField) {
        syncTaskSettingsPlacement(form);

        milestoneField.addEventListener('change', () => {
            syncTaskSettingsPlacement(form, {
                selectedMilestoneId: milestoneField.value || '',
                selectedSprintId: sprintField.value || '',
            });

            loadParentTaskOptions(form).catch((error) => {
                Alert.error(error.message || 'Unable to load parent tasks.');
            });
        });
    }

    if (sprintField) {
        sprintField.addEventListener('change', () => {
            syncTaskSettingsPlacement(form, {
                selectedMilestoneId: milestoneField?.value || '',
                selectedSprintId: sprintField.value || '',
                syncModuleFromSprint: true,
            });

            loadParentTaskOptions(form).catch((error) => {
                Alert.error(error.message || 'Unable to load parent tasks.');
            });
        });
    }

    loadParentTaskOptions(form).catch(() => {});

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearTaskSettingsErrors(form);

        const actionUrl = form.getAttribute('action');

        if (!actionUrl) {
            Alert.error('Unable to update the task right now.');
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
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
                body: new FormData(form),
            });
            const result = await response.json();

            if (response.status === 422 && result.errors) {
                applyTaskSettingsErrors(form, result.errors);
                throw new Error(result.message || 'Please correct the highlighted fields.');
            }

            if (!response.ok || !result.status) {
                throw new Error(result.message || 'Unable to update the task.');
            }

            const header = document.getElementById('task-detail-header');
            const tabsRoot = document.querySelector('[data-task-tabs]');
            const overviewPanel = tabsRoot?.querySelector('[data-task-tab-panel="overview"]');
            const settingsPanel = tabsRoot?.querySelector('[data-task-tab-panel="settings"]');
            const historyPanel = tabsRoot?.querySelector('[data-task-tab-panel="history"]');

            if (header && result.header_html) {
                header.innerHTML = result.header_html;
            }

            if (overviewPanel && result.overview_html) {
                overviewPanel.innerHTML = result.overview_html;
                overviewPanel.dataset.loaded = 'true';
            }

            if (settingsPanel && result.settings_html) {
                settingsPanel.innerHTML = result.settings_html;
                settingsPanel.dataset.loaded = 'true';
                initializeInjectedContent(settingsPanel, 'settings');
            }

            if (historyPanel) {
                historyPanel.dataset.loaded = 'false';
                historyPanel.innerHTML = '';
            }

            dirty = false;

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Updated';
            }

            Alert.success(result.message || 'Task updated successfully.');
        } catch (error) {
            if (!(error.message || '').includes('highlighted fields')) {
                Alert.error(error.message || 'Unable to update the task.');
            }

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Update Task';
            }
        }
    });

    form.dataset.initialized = 'true';
};

const initializeInjectedContent = (panel, tab) => {
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        window.Alpine.initTree(panel);
    }

    initTomSelect(panel);
    initDatepicker('.datepicker', {}, panel);
    initTimepicker('.timepicker', {}, panel);
    initWeekPicker('.weekPicker', undefined, panel);
    initializeEstimatedTimeInputs(panel);

    if (tab === 'settings') {
        initializeTaskSettings(panel);
    }

    document.dispatchEvent(new CustomEvent('task-tab:loaded', {
        detail: { tab, panel },
    }));
};

document.addEventListener('DOMContentLoaded', function () {
    const tabsRoot = document.querySelector('[data-task-tabs]');

    if (!tabsRoot) {
        return;
    }

    const taskId = tabsRoot.dataset.taskId;
    const defaultTab = tabsRoot.dataset.defaultTab || 'overview';
    const tabsUrlTemplate = tabsRoot.dataset.tabsUrlTemplate;
    const storageKey = `taskTab_${taskId}`;
    const triggers = Array.from(tabsRoot.querySelectorAll('[data-task-tab-trigger]'));
    const panels = Array.from(tabsRoot.querySelectorAll('[data-task-tab-panel]'));
    const availableTabs = triggers.map((trigger) => trigger.dataset.taskTabTrigger);
    let activeRequestTab = null;

    if (!taskId || !tabsUrlTemplate || !triggers.length || !panels.length) {
        return;
    }

    const getPanel = (tab) => tabsRoot.querySelector(`[data-task-tab-panel="${tab}"]`);
    const getCurrentParams = () => new URLSearchParams(window.location.search);
    const syncUrlForTab = (tab) => {
        const url = new URL(window.location.href);

        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    };

    const setActiveStyles = (activeTab) => {
        triggers.forEach((trigger) => {
            const isActive = trigger.dataset.taskTabTrigger === activeTab;
            trigger.classList.toggle('border-success-300', isActive);
            trigger.classList.toggle('text-success-300', isActive);
            trigger.classList.toggle('border-transparent', !isActive);
            trigger.classList.toggle('text-bgray-500', !isActive);
        });
    };

    const showTab = (tab) => {
        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.taskTabPanel !== tab);
        });

        setActiveStyles(tab);
        localStorage.setItem(storageKey, tab);
        syncUrlForTab(tab);
    };

    const loadTab = async (tab) => {
        const panel = getPanel(tab);

        if (!panel) {
            return;
        }

        if (panel.dataset.loaded === 'true') {
            showTab(tab);
            return;
        }

        if (activeRequestTab === tab) {
            return;
        }

        activeRequestTab = tab;
        panel.innerHTML = TASK_DETAIL_LOADING_HTML(tab);

        try {
            const requestUrl = new URL(tabsUrlTemplate.replace('__TAB__', tab), window.location.origin);
            const params = getCurrentParams();

            params.forEach((value, key) => {
                requestUrl.searchParams.append(key, value);
            });

            const response = await fetch(requestUrl.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });
            const result = await response.json();

            if (!response.ok || !result.status) {
                throw new Error(result.message || `Unable to load the ${tab} tab.`);
            }

            panel.innerHTML = result.html;
            panel.dataset.loaded = 'true';
            initializeInjectedContent(panel, tab);
            showTab(tab);
        } catch (error) {
            panel.innerHTML = '';
            Alert.error(error.message || `Unable to load the ${tab} tab.`);
        } finally {
            activeRequestTab = null;
        }
    };

    initializeInjectedContent(getPanel('overview'), 'overview');

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', function () {
            loadTab(this.dataset.taskTabTrigger);
        });
    });

    document.addEventListener('task-history:changed', () => {
        const historyPanel = getPanel('history');

        if (!historyPanel) {
            return;
        }

        historyPanel.dataset.loaded = 'false';

        if (historyPanel.classList.contains('hidden')) {
            historyPanel.innerHTML = '';

            return;
        }

        loadTab('history');
    });

    document.addEventListener('task-status:changed', (event) => {
        const result = event.detail?.response || {};
        const header = document.getElementById('task-detail-header');
        const overviewPanel = getPanel('overview');
        const historyPanel = getPanel('history');

        if (header && result.header_html) {
            header.innerHTML = result.header_html;
            document.dispatchEvent(new CustomEvent('task-timer:refresh'));
        }

        if (overviewPanel && result.overview_html) {
            overviewPanel.innerHTML = result.overview_html;
            overviewPanel.dataset.loaded = 'true';
            initializeInjectedContent(overviewPanel, 'overview');
        }

        if (historyPanel && result.history_html) {
            historyPanel.innerHTML = result.history_html;
            historyPanel.dataset.loaded = 'true';
            initializeInjectedContent(historyPanel, 'history');
        }
    });

    const savedTab = localStorage.getItem(storageKey);
    const requestedTab = getCurrentParams().get('tab');
    const initialTab = availableTabs.includes(requestedTab)
        ? requestedTab
        : (availableTabs.includes(savedTab) ? savedTab : defaultTab);

    loadTab(initialTab);
});
