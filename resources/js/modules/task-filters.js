const TASK_FILTER_SCRIPT_ID = 'task-filter-dependencies';

const normalizeValues = (value) => {
    if (Array.isArray(value)) {
        return value.map((item) => String(item)).filter(Boolean);
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    return [String(value)];
};

const getSelectedValues = (select) => {
    if (!select) {
        return [];
    }

    if (select.tomselect) {
        return normalizeValues(select.tomselect.getValue());
    }

    return Array.from(select.selectedOptions).map((option) => String(option.value)).filter(Boolean);
};

const rebuildSelectOptions = (select, options, selectedValues = []) => {
    if (!select) {
        return [];
    }

    const normalizedOptions = options.map((option) => ({
        value: String(option.id),
        text: option.name,
    }));
    const allowedValues = new Set(normalizedOptions.map((option) => option.value));
    const nextSelectedValues = selectedValues.filter((value) => allowedValues.has(String(value)));

    if (select.tomselect) {
        select.tomselect.clear(true);
        select.tomselect.clearOptions();
        select.tomselect.addOption(normalizedOptions);
        select.tomselect.refreshOptions(false);

        if (nextSelectedValues.length) {
            select.tomselect.setValue(nextSelectedValues, true);
        }

        if (normalizedOptions.length) {
            select.tomselect.enable();
        } else {
            select.tomselect.disable();
        }
    }

    select.innerHTML = '';

    normalizedOptions.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        optionElement.selected = nextSelectedValues.includes(option.value);
        select.appendChild(optionElement);
    });

    select.disabled = normalizedOptions.length === 0;

    return nextSelectedValues;
};

const initializeTaskFilters = () => {
    const dataNode = document.getElementById(TASK_FILTER_SCRIPT_ID);

    if (!dataNode || dataNode.dataset.initialized === 'true') {
        return;
    }

    const projectSelect = document.querySelector('select[name="project_id[]"]');
    const milestoneSelect = document.querySelector('select[name="project_milestone_id[]"]');
    const sprintSelect = document.querySelector('select[name="project_sprint_id[]"]');

    if (!projectSelect || !milestoneSelect || !sprintSelect) {
        return;
    }

    let dependencies = { milestones: [], sprints: [] };

    try {
        dependencies = JSON.parse(dataNode.textContent || '{}');
    } catch (error) {
        return;
    }

    const allModules = Array.isArray(dependencies.milestones) ? dependencies.milestones : [];
    const allSprints = Array.isArray(dependencies.sprints) ? dependencies.sprints : [];

    const syncDependentFilters = () => {
        const selectedProjectIds = getSelectedValues(projectSelect);
        const currentMilestoneIds = getSelectedValues(milestoneSelect);
        const currentSprintIds = getSelectedValues(sprintSelect);

        const filteredModules = selectedProjectIds.length
            ? allModules.filter((milestone) => selectedProjectIds.includes(String(milestone.project_id)))
            : allModules;

        const nextMilestoneIds = rebuildSelectOptions(milestoneSelect, filteredModules, currentMilestoneIds);

        const filteredSprints = nextMilestoneIds.length
            ? allSprints.filter((sprint) => nextMilestoneIds.includes(String(sprint.project_milestone_id)))
            : selectedProjectIds.length
                ? allSprints.filter((sprint) => selectedProjectIds.includes(String(sprint.project_id)))
                : allSprints;

        rebuildSelectOptions(sprintSelect, filteredSprints, currentSprintIds);
    };

    projectSelect.addEventListener('change', syncDependentFilters);
    milestoneSelect.addEventListener('change', syncDependentFilters);

    dataNode.dataset.initialized = 'true';
    syncDependentFilters();
};

document.addEventListener('tomselect:ready', initializeTaskFilters);
document.addEventListener('DOMContentLoaded', initializeTaskFilters);
