const setTaskToggleState = (root, taskId, expanded) => {
    const toggle = root.querySelector(`[data-task-subtasks-parent="${taskId}"]`);
    const icon = toggle?.querySelector('[data-task-subtasks-icon]');

    toggle?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    icon?.classList.toggle('rotate-90', expanded);
};

const getDirectChildRows = (root, parentId) => root.querySelectorAll(`[data-task-parent-id="${parentId}"]`);

const collapseTaskBranch = (root, parentId) => {
    if (!root || !parentId) {
        return;
    }

    getDirectChildRows(root, parentId).forEach((row) => {
        const childTaskId = row.dataset.taskId || '';

        row.hidden = true;
        row.classList.add('hidden');
        setTaskToggleState(root, childTaskId, false);

        if (childTaskId) {
            collapseTaskBranch(root, childTaskId);
        }
    });
};

const setSubtaskGroupState = (root, parentId, expanded) => {
    if (!root || !parentId) {
        return;
    }

    setTaskToggleState(root, parentId, expanded);

    if (!expanded) {
        collapseTaskBranch(root, parentId);
        return;
    }

    getDirectChildRows(root, parentId).forEach((row) => {
        row.hidden = false;
        row.classList.remove('hidden');
    });
};

const initializeTaskSubtaskRoot = (root) => {
    if (!root || root.dataset.taskSubtasksInitialized === 'true') {
        return;
    }

    root.dataset.taskSubtasksInitialized = 'true';

    root.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-task-subtasks-toggle]');

        if (!toggle || !root.contains(toggle)) {
            return;
        }

        const parentId = toggle.dataset.taskSubtasksParent || '';
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

        setSubtaskGroupState(root, parentId, !isExpanded);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-task-subtasks-root]').forEach((root) => {
        initializeTaskSubtaskRoot(root);
    });
});
