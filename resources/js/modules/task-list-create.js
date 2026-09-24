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
    'tag_ids',
    'is_billable',
]);

const taskCreateEditors = new WeakMap();
const taskCreateNoteEditors = new WeakMap();
const taskCreateFilesMap = new WeakMap();

const renderTaskCreateFiles = (root) => {
    const container = root.querySelector('#task_create_selected_files');
    if (!container) return;

    const files = taskCreateFilesMap.get(root) || [];
    if (!files.length) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = files.map((file, index) => `
        <div class="flex items-center justify-between rounded-lg border border-bgray-200 bg-bgray-50 px-3 py-2 text-xs dark:border-darkblack-400 dark:bg-darkblack-500">
            <div class="flex items-center gap-2 min-w-0 pr-2">
                <svg class="h-4 w-4 text-bgray-600 dark:text-bgray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="truncate font-medium text-bgray-900 dark:text-white">${file.name}</span>
                <span class="text-bgray-700 dark:text-bgray-300 flex-shrink-0">(${(file.size / 1024).toFixed(1)} KB)</span>
            </div>
            <button type="button" class="text-red-500 hover:text-red-700 flex-shrink-0 font-bold px-1" data-task-create-remove-file="${index}">✕</button>
        </div>
    `).join('');
};

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
    const panel = root?.querySelector('[data-task-create-modal-panel]');
    if (panel) {
        panel.classList.remove('max-w-lg');
        panel.classList.add('max-w-[95vw]', '2xl:max-w-[1400px]');
    }
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

const setTaskCreatePlacementHint = (form, projectMeta, { selectedMilestoneId = '', selectedSprintId = '' } = {}) => {
    const hintNode = form?.querySelector('[data-task-create-placement-hint]');

    if (!hintNode) {
        return;
    }

    if (!projectMeta || projectMeta.flow !== 'agile') {
        hintNode.textContent = '';
        hintNode.classList.add('hidden');
        return;
    }

    let message = 'Leave both milestone and sprint empty to place this task in the project backlog.';

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
            const multiValues = Array.isArray(normalizedValue)
                ? normalizedValue
                : (normalizedValue !== '' ? [normalizedValue] : []);
            field.tomselect.setValue(multiValues, true);
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
        if (option.subtype) {
            optionElement.dataset.subtype = option.subtype;
        }
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

const getTaskCreateMode = (root) => root?.dataset.taskCreateMode === 'request' ? 'request' : 'create';

const setTaskCreateMode = (root, mode = 'create') => {
    if (!root) {
        return;
    }

    const normalizedMode = mode === 'request' ? 'request' : 'create';
    const form = root.querySelector('[data-task-create-form]');
    const titleNode = root.querySelector('[data-task-create-title]');
    const assigneeFieldWrapper = root.querySelector('[data-task-create-assignee-field]');
    const requestTypeField = form?.querySelector('[data-task-create-request-type]');
    const submitButton = form?.querySelector('[data-task-create-submit]');

    root.dataset.taskCreateMode = normalizedMode;

    if (form) {
        form.dataset.storeUrl = normalizedMode === 'request'
            ? (form.dataset.requestStoreUrl || form.dataset.defaultStoreUrl || form.dataset.storeUrl || '')
            : (form.dataset.defaultStoreUrl || form.dataset.storeUrl || '');
    }

    if (titleNode) {
        titleNode.textContent = normalizedMode === 'request'
            ? (titleNode.dataset.requestTitle || 'Request Task')
            : (titleNode.dataset.defaultTitle || 'Add Task');
    }

    if (assigneeFieldWrapper) {
        assigneeFieldWrapper.hidden = normalizedMode === 'request';
    }

    const assigneeTimeDueRow = root.querySelector('[data-task-create-row-assignee-time-due]');
    if (assigneeTimeDueRow) {
        if (normalizedMode === 'request') {
            assigneeTimeDueRow.classList.remove('grid-cols-3', 'md:grid-cols-3');
            assigneeTimeDueRow.classList.add('grid-cols-2');
        } else {
            assigneeTimeDueRow.classList.remove('grid-cols-2', 'md:grid-cols-2');
            assigneeTimeDueRow.classList.add('grid-cols-3');
        }
    }

    if (requestTypeField) {
        requestTypeField.value = normalizedMode === 'request' ? 'self' : 'assigned';
    }

    if (submitButton) {
        submitButton.textContent = normalizedMode === 'request' ? 'Request Task' : 'Save Task';
    }
};

const syncSelfAssignee = (form, options = []) => {
    if (!form || getTaskCreateMode(form.closest('[data-task-create-root]')) !== 'request') {
        return;
    }

    const assigneeField = form.querySelector('[name="current_assignee_ids[]"], [name="current_assignee_id"]');
    const selfAssigneeId = String(form.dataset.selfAssigneeId || '');

    if (!assigneeField || !selfAssigneeId) {
        return;
    }

    const hasSelfOption = options.some((option) => String(option.value) === selfAssigneeId)
        || Array.from(assigneeField.options).some((option) => String(option.value) === selfAssigneeId);

    if (!hasSelfOption) {
        return;
    }

    if (assigneeField.tomselect) {
        assigneeField.tomselect.setValue(assigneeField.multiple ? [selfAssigneeId] : selfAssigneeId, true);
        return;
    }

    if (assigneeField.multiple) {
        Array.from(assigneeField.options).forEach((opt) => {
            opt.selected = String(opt.value) === selfAssigneeId;
        });
    } else {
        assigneeField.value = selfAssigneeId;
    }
};

const openTaskCreateModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

const closeTaskCreateModal = (modal) => {
    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

const updateSprintOptions = (form, dependencies, { selectedSprintId = '', selectedMilestoneId = '' } = {}) => {
    const sprintField = form.querySelector('[name="project_sprint_id"]');
    const projectField = form.querySelector('[name="project_id"]');
    const projectMeta = getProjectMeta(dependencies, projectField?.value || '');
    const normalizedMilestoneId = String(selectedMilestoneId || form.querySelector('[name="project_milestone_id"]')?.value || '');
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
        if (!normalizedMilestoneId) {
            return true;
        }

        return String(sprint.project_milestone_id || '') === normalizedMilestoneId;
    });
    const resolvedSprintId = availableSprints.some((option) => option.value === normalizedSprintId)
        ? normalizedSprintId
        : '';

    setSelectOptions(sprintField, availableSprints, {
        placeholder: normalizedMilestoneId
            ? (availableSprints.length ? 'Select sprint' : 'No sprints in selected milestone')
            : (availableSprints.length ? 'Select sprint or leave empty for backlog' : 'No sprints available'),
        disabled: false,
        value: resolvedSprintId,
    });

    setTaskCreateRequiredIndicators(form, true, Boolean(normalizedMilestoneId));
    setTaskCreatePlacementHint(form, projectMeta, {
        selectedMilestoneId: normalizedMilestoneId,
        selectedSprintId: resolvedSprintId,
    });
};

const getDefaultTaskDueDateTime = () => {
    const now = new Date();
    const target = new Date(now);
    if (now.getHours() >= 18) {
        target.setDate(target.getDate() + 1);
    }
    target.setHours(19, 0, 0, 0);

    const year = target.getFullYear();
    const month = String(target.getMonth() + 1).padStart(2, '0');
    const day = String(target.getDate()).padStart(2, '0');
    return `${year}-${month}-${day} 19:00`;
};

const setEmptyProjectState = (form, dependencies = null) => {
    setTaskCreateRequiredIndicators(form, false);
    setTaskCreatePlacementHint(form, null);

    setSelectOptions(form.querySelector('[name="project_milestone_id"]'), [], {
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
    setSelectOptions(form.querySelector('[name="current_assignee_ids[]"], [name="current_assignee_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="status_id"]'), [], {
        placeholder: 'Select project first',
        disabled: true,
    });
    setEstimatedTimeValue(form, 0);
    setCheckboxValue(form.querySelector('input[type="checkbox"][name="is_billable"]'), false);

    const dueDateField = form.querySelector('[name="due_date_time"]');
    const defaultDue = dependencies?.defaults?.due_date_time || getDefaultTaskDueDateTime();
    setFieldValue(dueDateField, defaultDue);
};

const setProjectLoadingState = (form) => {
    setTaskCreateRequiredIndicators(form, false);
    setTaskCreatePlacementHint(form, null);

    setSelectOptions(form.querySelector('[name="project_milestone_id"]'), [], {
        placeholder: 'Loading milestones...',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="project_sprint_id"]'), [], {
        placeholder: 'Loading sprints...',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="parent_task_id"]'), [], {
        placeholder: 'Loading parent tasks...',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="current_assignee_ids[]"], [name="current_assignee_id"]'), [], {
        placeholder: 'Loading assignees...',
        disabled: true,
    });
    setSelectOptions(form.querySelector('[name="status_id"]'), [], {
        placeholder: 'Loading statuses...',
        disabled: true,
    });
};

const fetchProjectDependencies = async (dependencies, projectId) => {
    if (!projectId) {
        return null;
    }

    const key = String(projectId);

    if (dependencies.projects && dependencies.projects[key]) {
        return dependencies.projects[key];
    }

    if (!dependencies.projects) {
        dependencies.projects = {};
    }

    const urlTemplate = dependencies.dependencies_url_template || '/projects/:id/task-create-dependencies';
    const requestUrl = urlTemplate.replace(':id', encodeURIComponent(key));

    const response = await fetch(requestUrl, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const result = await response.json();

    if (!response.ok || !result.status || !result.data) {
        throw new Error(result.message || 'Unable to load project task dependencies.');
    }

    dependencies.projects[key] = result.data;
    return result.data;
};

const applyProjectDefaults = async (form, dependencies) => {
    const projectField = form.querySelector('[name="project_id"]');
    const milestoneField = form.querySelector('[name="project_milestone_id"]');
    const assigneeField = form.querySelector('[name="current_assignee_ids[]"], [name="current_assignee_id"]');
    const statusField = form.querySelector('[name="status_id"]');
    const priorityField = form.querySelector('[name="priority"]');
    const dueDateField = form.querySelector('[name="due_date_time"]');
    const billableField = form.querySelector('input[type="checkbox"][name="is_billable"]');

    const projectId = projectField?.value || '';

    if (!projectId) {
        setEmptyProjectState(form, dependencies);
        return;
    }

    setProjectLoadingState(form);

    let projectMeta = null;
    try {
        projectMeta = await fetchProjectDependencies(dependencies, projectId);
    } catch (error) {
        setEmptyProjectState(form, dependencies);
        throw error;
    }

    if (!projectMeta) {
        setEmptyProjectState(form, dependencies);
        return;
    }

    const isAgile = projectMeta.flow === 'agile';

    setSelectOptions(milestoneField, projectMeta.milestones || [], {
        placeholder: isAgile ? 'Select milestone or leave empty for backlog' : 'Not used for linear projects',
        disabled: !isAgile,
    });

    updateSprintOptions(form, dependencies);

    setSelectOptions(assigneeField, projectMeta.assignees || [], {
        placeholder: 'Select assignee',
        disabled: false,
    });
    syncSelfAssignee(form, projectMeta.assignees || []);

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

    const defaultDue = dependencies.defaults?.due_date_time || getDefaultTaskDueDateTime();
    setFieldValue(dueDateField, defaultDue);
    setEstimatedTimeValue(form, projectMeta.default_task_estimate_minutes ?? 0);
    setCheckboxValue(billableField, projectMeta.default_billable);

    await loadParentTaskOptions(form, dependencies);
};

const loadParentTaskOptions = async (form, dependencies) => {
    const parentField = form.querySelector('[data-task-create-parent-select]');
    const projectField = form.querySelector('[name="project_id"]');
    const milestoneField = form.querySelector('[name="project_milestone_id"]');
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

    if (projectMeta.flow === 'agile' && milestoneField?.value && !sprintField?.value) {
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

    if (milestoneField?.value) {
        requestUrl.searchParams.set('project_milestone_id', String(milestoneField.value));
    }

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
    const editor = taskCreateEditors.get(root);
    if (editor) {
        editor.setContents([]);
    }
    const descInput = form.querySelector('#task_create_description_input');
    if (descInput) {
        descInput.value = '';
    }

    const noteEditor = taskCreateNoteEditors.get(root);
    if (noteEditor) {
        noteEditor.setContents([]);
    }
    const noteInput = form.querySelector('#task_create_note_input');
    if (noteInput) {
        noteInput.value = '';
    }

    taskCreateFilesMap.set(root, []);
    const attachmentsInput = form.querySelector('#task_create_attachments_input');
    if (attachmentsInput) {
        attachmentsInput.value = '';
    }
    renderTaskCreateFiles(root);

    clearTaskCreateErrors(form);
    setTaskCreateAdvancedState(root, false);
    setTaskCreateMode(root, getTaskCreateMode(root));
    syncTaskCreateSelectState(form);
    setEmptyProjectState(form, dependencies);

    const projectField = form.querySelector('[name="project_id"]');

    if (projectField?.value) {
        await applyProjectDefaults(form, dependencies);
    }
};

const applyTaskCreateErrors = (root, form, errors = {}) => {
    clearTaskCreateErrors(form);

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

    const editorElement = root.querySelector('#task_create_description_editor');
    if (editorElement && !taskCreateEditors.has(root)) {
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
        taskCreateEditors.set(root, editor);
        editorElement.__quill = editor;
    }

    const noteEditorElement = root.querySelector('#task_create_note_editor');
    if (noteEditorElement && !taskCreateNoteEditors.has(root)) {
        const noteEditor = new window.Quill(noteEditorElement, {
            theme: 'snow',
            placeholder: 'Write an initial task note...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'header': [1, 2, 3, false] }],
                    ['link']
                ]
            }
        });
        taskCreateNoteEditors.set(root, noteEditor);
        noteEditorElement.__quill = noteEditor;
    }

    const attachmentsInput = root.querySelector('#task_create_attachments_input');
    if (attachmentsInput && attachmentsInput.dataset.bound !== 'true') {
        attachmentsInput.dataset.bound = 'true';
        attachmentsInput.addEventListener('change', () => {
            const newFiles = Array.from(attachmentsInput.files || []);
            let currentFiles = taskCreateFilesMap.get(root) || [];
            const combined = [...currentFiles];
            newFiles.forEach((f) => {
                if (!combined.some((item) => item.name === f.name && item.size === f.size)) {
                    combined.push(f);
                }
            });
            taskCreateFilesMap.set(root, combined);
            renderTaskCreateFiles(root);
        });
    }

    root.addEventListener('click', async (event) => {
        const removeFileBtn = event.target.closest('[data-task-create-remove-file]');
        if (removeFileBtn && root.contains(removeFileBtn)) {
            const index = Number(removeFileBtn.dataset.taskCreateRemoveFile);
            let currentFiles = taskCreateFilesMap.get(root) || [];
            currentFiles.splice(index, 1);
            taskCreateFilesMap.set(root, currentFiles);
            renderTaskCreateFiles(root);
            return;
        }

        const openButton = event.target.closest('[data-task-create-open]');

        if (openButton) {
            setTaskCreateMode(root, openButton.dataset.taskCreateRequestType === 'self' ? 'request' : 'create');
            openTaskCreateModal(root.querySelector('[data-task-create-modal]'));

            try {
                await prepareTaskCreateModal(root, dependencies);
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to load project task options.');
            }
            return;
        }

        const taskCreateModal = root.querySelector('[data-task-create-modal]');
        if (taskCreateModal && !taskCreateModal.classList.contains('hidden')) {
            const closeButton = event.target.closest('[data-task-create-close]');
            const panel = taskCreateModal.querySelector('[data-task-create-modal-panel]');
            const isClickInsidePanel = panel && panel.contains(event.target);
            const isClickInsideModal = taskCreateModal.contains(event.target);
            const isClickOnDropdownPopup = event.target.closest('.ts-dropdown, .flatpickr-calendar, .ql-toolbar, .ql-container');

            if (closeButton && root.contains(closeButton)) {
                closeTaskCreateModal(taskCreateModal);
                return;
            }

            if (isClickInsideModal && !isClickInsidePanel && !isClickOnDropdownPopup) {
                closeTaskCreateModal(taskCreateModal);
                return;
            }
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

        const milestoneField = event.target.closest('[name="project_milestone_id"]');

        if (milestoneField && root.contains(milestoneField)) {
            updateSprintOptions(form, dependencies, {
                selectedMilestoneId: milestoneField.value || '',
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
            const milestoneFieldInForm = form.querySelector('[name="project_milestone_id"]');

            if (selectedSprint && !milestoneFieldInForm?.value) {
                if (milestoneFieldInForm?.tomselect) {
                    milestoneFieldInForm.tomselect.setValue(String(selectedSprint.project_milestone_id || ''), true);
                } else if (milestoneFieldInForm) {
                    milestoneFieldInForm.value = String(selectedSprint.project_milestone_id || '');
                }
            }

            updateSprintOptions(form, dependencies, {
                selectedMilestoneId: milestoneFieldInForm?.value || '',
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


    let isTaskCreateSubmitting = false;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isTaskCreateSubmitting) {
            return;
        }

        clearTaskCreateErrors(form);

        const editor = taskCreateEditors.get(root);
        const descInput = form.querySelector('#task_create_description_input');

        if (editor && descInput) {
            const content = editor.root.innerHTML.trim();
            descInput.value = content === '<p><br></p>' ? '' : content;
        }

        const noteEditor = taskCreateNoteEditors.get(root);
        const noteInput = form.querySelector('#task_create_note_input');

        if (noteEditor && noteInput) {
            const noteContent = noteEditor.root.innerHTML.trim();
            noteInput.value = noteContent === '<p><br></p>' ? '' : noteContent;
        }

        const submitButton = form.querySelector('[data-task-create-submit]');
        const storeUrl = form.dataset.storeUrl;

        if (!storeUrl) {
            Alert.errorModal('Unable to save the task right now.');
            return;
        }

        isTaskCreateSubmitting = true;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        try {
            const formData = new FormData(form);
            const selectedFiles = taskCreateFilesMap.get(root) || [];
            if (selectedFiles.length > 0) {
                formData.delete('attachments[]');
                selectedFiles.forEach((file) => {
                    formData.append('attachments[]', file);
                });
            }

            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: formData,
            });

            const result = await response.json();

            if (response.status === 422 && result.errors) {
                applyTaskCreateErrors(root, form, result.errors);

                throw new Error(
                    result.message || 'Please correct the highlighted fields.'
                );
            }

            if (!response.ok || !result.status) {
                throw new Error(
                    result.message || 'Unable to save the task.'
                );
            }

            window.location.reload();

        } catch (error) {
            isTaskCreateSubmitting = false;

            if (!(error.message || '').includes('highlighted fields')) {
                Alert.errorModal(
                    error.message || 'Unable to save the task.'
                );
            }

            if (submitButton) {
                submitButton.disabled = false;

                submitButton.textContent =
                    getTaskCreateMode(root) === 'request'
                        ? 'Request Task'
                        : 'Save Task';
            }
        }
    });
};

const initTaskCreate = () => {
    const dependencies = parseDependencies();

    document.querySelectorAll('[data-task-create-root]').forEach((root) => {
        initializeTaskCreateRoot(root, dependencies);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTaskCreate);
} else {
    initTaskCreate();
}
