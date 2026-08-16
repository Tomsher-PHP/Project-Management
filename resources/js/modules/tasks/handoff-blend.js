import Alert from '../../alert';

function getProjectFlowIcon(flow) {
    if (!flow) return '';
    const isAgile = flow.toLowerCase() === 'agile';
    if (isAgile) {
        return `<span class="inline-flex shrink-0 items-center justify-center border border-bgray-200 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 h-5 w-5 rounded bg-success-50" title="Project Flow: Agile"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-success-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h4m0 0v4m0-4l-6 6"></path><path stroke-linecap="round" stroke-linejoin="round" d="M7 17h4m-4 0v-4m0 4l10-10" opacity=".45"></path></svg></span>`;
    } else {
        return `<span class="inline-flex shrink-0 items-center justify-center border border-bgray-200 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 h-5 w-5 rounded bg-bgray-100" title="Project Flow: Linear"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 8l4 4-4 4"></path></svg></span>`;
    }
}

function renderHandoffDescription(element, html) {
    const template = document.createElement('template');
    template.innerHTML = html || '';

    const allowedTags = new Set(['A', 'B', 'BLOCKQUOTE', 'BR', 'EM', 'H1', 'H2', 'H3', 'I', 'LI', 'OL', 'P', 'STRONG', 'U', 'UL']);

    template.content.querySelectorAll('*').forEach((node) => {
        if (!allowedTags.has(node.tagName)) {
            node.replaceWith(document.createTextNode(node.textContent || ''));
            return;
        }

        [...node.attributes].forEach((attribute) => {
            if (node.tagName !== 'A' || attribute.name !== 'href') {
                node.removeAttribute(attribute.name);
            }
        });

        if (node.tagName === 'A') {
            const href = node.getAttribute('href') || '';
            if (!/^(https?:|mailto:|\/)/i.test(href)) {
                node.removeAttribute('href');
            }
        }
    });

    element.replaceChildren(template.content);

    if (!element.textContent.trim()) {
        element.textContent = '--';
    }
}

window.openHandoffViewModal = function (data) {
    document.getElementById('viewModalDate').textContent = data.date;
    document.getElementById('viewModalRequestedBy').textContent = data.requestedBy;
    document.getElementById('viewModalTargetUser').textContent = data.targetUser;

    const projectEl = document.getElementById('viewModalProject');
    projectEl.innerHTML = getProjectFlowIcon(data.projectFlow) + '<span>' + data.project + '</span>';

    document.getElementById('viewModalMilestone').textContent = data.milestone;
    document.getElementById('viewModalSprint').textContent = data.sprint;
    document.getElementById('viewModalSourceTask').textContent = data.sourceTask;
    document.getElementById('viewModalCreatedTask').textContent = data.createdTask;
    document.getElementById('viewModalPurpose').textContent = data.purpose;
    document.getElementById('viewModalStatus').textContent = data.status;
    renderHandoffDescription(document.getElementById('viewModalDescription'), data.description);

    const modal = document.getElementById('handoffViewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

window.closeHandoffViewModal = function () {
    const modal = document.getElementById('handoffViewModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

window.confirmHandoffNote = function (button) {
    const form = button.closest('form');
    if (!form) return;

    Alert.confirm({
        title: 'Mark as Noted?',
        text: 'Are you sure you want to mark this request as noted?',
        confirmText: 'Yes, mark as noted'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

document.addEventListener('click', (e) => {
    const assignBtn = e.target.closest('[data-handoff-assign-btn]');
    if (!assignBtn) return;

    // Wait for the modal script (task-list-create.js) to finish setup and reset
    setTimeout(() => {
        const root = document.querySelector('[data-task-create-root]');
        if (!root) return;

        const form = root.querySelector('[data-task-create-form]');
        if (!form) return;

        const handoffId = assignBtn.dataset.handoffRequestId;
        const handoffInput = form.querySelector('[name="handoff_request_id"]');
        if (handoffInput) handoffInput.value = handoffId || '';

        const descInput = form.querySelector('[name="description"]');
        if (descInput) {
            let descText = '';
            if (assignBtn.dataset.purpose) descText += `Purpose: ${assignBtn.dataset.purpose}\n\n`;
            if (assignBtn.dataset.description) descText += assignBtn.dataset.description;
            descInput.value = descText;
        }

        const projectId = assignBtn.dataset.projectId;
        const targetUserId = assignBtn.dataset.targetUserId;
        const projectField = form.querySelector('[name="project_id"]');
        if (projectId && projectField) {
            if (projectField.tomselect) {
                projectField.tomselect.setValue(projectId);
            } else {
                projectField.value = projectId;
                projectField.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Wait for project fields (milestones) to update
            setTimeout(() => {
                const assigneeField = form.querySelector('[name="current_assignee_id"]');
                if (targetUserId && assigneeField) {
                    if (assigneeField.tomselect) {
                        assigneeField.tomselect.setValue(targetUserId);
                    } else {
                        assigneeField.value = targetUserId;
                        assigneeField.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                const milestoneId = assignBtn.dataset.projectMilestoneId;
                const milestoneField = form.querySelector('[name="project_milestone_id"]');
                if (milestoneId && milestoneField) {
                    if (milestoneField.tomselect) {
                        milestoneField.tomselect.setValue(milestoneId);
                    } else {
                        milestoneField.value = milestoneId;
                        milestoneField.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Wait for sprints to load based on milestone
                    setTimeout(() => {
                        const sprintId = assignBtn.dataset.projectSprintId;
                        const sprintField = form.querySelector('[name="project_sprint_id"]');
                        if (sprintId && sprintField) {
                            if (sprintField.tomselect) {
                                sprintField.tomselect.setValue(sprintId);
                            } else {
                                sprintField.value = sprintId;
                                sprintField.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    }, 200);
                }
            }, 200);
        }
    }, 100);
});
