import Alert from '../../alert';

function getProjectFlowIcon(flow) {
    if (!flow) return '';
    const isAgile = flow.toLowerCase() === 'agile';
    const title = `Project Flow: ${isAgile ? 'Agile' : 'Linear'}`;
    const colorClasses = isAgile ? 'text-purple-600 dark:text-purple-400' : 'text-blue-600 dark:text-blue-400';
    const svgContent = isAgile
        ? `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>`
        : `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>`;

    return `<span class="inline-flex shrink-0 items-center justify-center transition duration-150 h-5 w-5 rounded ${colorClasses}" title="${title}">${svgContent}</span>`;
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function convertPlainTextToHtml(text) {
    if (!text) return '';
    const lines = text.split(/\r?\n/);
    let html = '';
    let currentListType = null;

    lines.forEach((line) => {
        const trimmed = line.trim();
        if (!trimmed) {
            if (currentListType) {
                html += `</${currentListType}>`;
                currentListType = null;
            }
            return;
        }

        const headingMatch = trimmed.match(/^(#{1,6})\s+(.*)$/);
        const bulletMatch = trimmed.match(/^[-\*•–]\s+(.*)$/);
        const numberMatch = trimmed.match(/^\d+[\.\)]\s+(.*)$/);

        if (headingMatch) {
            if (currentListType) {
                html += `</${currentListType}>`;
                currentListType = null;
            }
            const level = headingMatch[1].length;
            html += `<h${level}>${escapeHtml(headingMatch[2])}</h${level}>`;
        } else if (bulletMatch) {
            if (currentListType !== 'ul') {
                if (currentListType) html += `</${currentListType}>`;
                html += '<ul>';
                currentListType = 'ul';
            }
            html += `<li>${escapeHtml(bulletMatch[1])}</li>`;
        } else if (numberMatch) {
            if (currentListType !== 'ol') {
                if (currentListType) html += `</${currentListType}>`;
                html += '<ol>';
                currentListType = 'ol';
            }
            html += `<li>${escapeHtml(numberMatch[1])}</li>`;
        } else {
            if (currentListType) {
                html += `</${currentListType}>`;
                currentListType = null;
            }
            html += `<p>${escapeHtml(trimmed)}</p>`;
        }
    });

    if (currentListType) {
        html += `</${currentListType}>`;
    }

    return html;
}

function renderHandoffDescription(element, html) {
    if (!element) return;

    if (!html || !html.trim()) {
        element.textContent = '--';
        return;
    }

    let processedHtml = html;

    if (/&lt;[a-z][\s\S]*&gt;/i.test(processedHtml) && !/<[a-z][\s\S]*>/i.test(processedHtml)) {
        const txt = document.createElement('textarea');
        txt.innerHTML = processedHtml;
        processedHtml = txt.value;
    }

    const isHtml = /<[a-z][\s\S]*>/i.test(processedHtml);
    if (!isHtml) {
        processedHtml = convertPlainTextToHtml(processedHtml);
    }

    const template = document.createElement('template');
    template.innerHTML = processedHtml || '';

    // Transform Quill ol with data-list="bullet" into ul
    template.content.querySelectorAll('ol').forEach((ol) => {
        const hasBulletLi = ol.querySelector('li[data-list="bullet"]');
        if (hasBulletLi) {
            const ul = document.createElement('ul');
            while (ol.firstChild) {
                ul.appendChild(ol.firstChild);
            }
            ol.replaceWith(ul);
        }
    });

    const allowedTags = new Set([
        'A', 'B', 'BLOCKQUOTE', 'BR', 'CODE', 'DIV', 'EM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'I',
        'LI', 'OL', 'P', 'PRE', 'SPAN', 'STRONG', 'U', 'UL'
    ]);

    template.content.querySelectorAll('*').forEach((node) => {
        if (!allowedTags.has(node.tagName)) {
            node.replaceWith(document.createTextNode(node.textContent || ''));
            return;
        }

        [...node.attributes].forEach((attribute) => {
            if (node.tagName === 'A' && attribute.name === 'href') {
                return;
            }
            if (node.tagName === 'LI' && attribute.name === 'data-list') {
                return;
            }
            if (attribute.name === 'class') {
                return;
            }
            node.removeAttribute(attribute.name);
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

function prepareDescriptionHtmlForQuill(html) {
    if (!html || !html.trim()) return '';

    let content = html.trim();

    if (/&lt;[a-z][\s\S]*&gt;/i.test(content) && !/<[a-z][\s\S]*>/i.test(content)) {
        const txt = document.createElement('textarea');
        txt.innerHTML = content;
        content = txt.value;
    }

    if (!/<[a-z][\s\S]*>/i.test(content)) {
        content = convertPlainTextToHtml(content);
    }

    content = content.replace(/<span class="ql-ui"[^>]*>.*?<\/span>/gi, '');
    content = content.replace(/\s*contenteditable="(false|true)"/gi, '');

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;

    tempDiv.querySelectorAll('ol').forEach((ol) => {
        const hasBulletLi = ol.querySelector('li[data-list="bullet"]');
        if (hasBulletLi) {
            const ul = document.createElement('ul');
            while (ol.firstChild) {
                ul.appendChild(ol.firstChild);
            }
            ol.replaceWith(ul);
        }
    });

    tempDiv.querySelectorAll('li[data-list]').forEach((li) => {
        li.removeAttribute('data-list');
    });

    return tempDiv.innerHTML.trim();
}

document.addEventListener('click', (e) => {
    const assignBtn = e.target.closest('[data-handoff-assign-btn]');
    if (!assignBtn) return;

    // Wait for the modal script (task-list-create.js) to finish setup and reset
    setTimeout(async () => {
        const root = document.querySelector('[data-task-create-root]');
        if (!root) return;

        const form = root.querySelector('[data-task-create-form]');
        if (!form) return;

        const handoffId = assignBtn.dataset.handoffRequestId;
        const handoffInput = form.querySelector('[name="handoff_request_id"]');
        if (handoffInput) handoffInput.value = handoffId || '';

        const handoffDesc = assignBtn.dataset.description ? assignBtn.dataset.description.trim() : '';
        const handoffPurpose = assignBtn.dataset.purpose ? assignBtn.dataset.purpose.trim() : '';

        let rawDescription = '';
        if (handoffDesc) {
            rawDescription = handoffDesc;
        } else if (handoffPurpose) {
            rawDescription = `<p>Purpose: ${escapeHtml(handoffPurpose)}</p>`;
        }

        const cleanHtml = prepareDescriptionHtmlForQuill(rawDescription);

        const descInput = form.querySelector('#task_create_description_input') || form.querySelector('[name="description"]');
        if (descInput) {
            descInput.value = cleanHtml;
        }

        const editorElement = root.querySelector('#task_create_description_editor');
        if (editorElement) {
            const quill = editorElement.__quill || (window.Quill?.find ? window.Quill.find(editorElement) : null);
            if (quill) {
                quill.setContents([]);
                if (cleanHtml) {
                    quill.clipboard.dangerouslyPasteHTML(0, cleanHtml);
                }
            } else {
                const qlEditor = editorElement.querySelector('.ql-editor');
                if (qlEditor) {
                    qlEditor.innerHTML = cleanHtml;
                }
            }
        }

        const projectId = assignBtn.dataset.projectId;
        const projectName = assignBtn.dataset.projectName;
        const projectCode = assignBtn.dataset.projectCode;
        const targetUserId = assignBtn.dataset.targetUserId;
        const milestoneId = assignBtn.dataset.projectMilestoneId;
        const sprintId = assignBtn.dataset.projectSprintId;

        const projectField = form.querySelector('[name="project_id"]');
        if (projectId && projectField) {
            if (projectField.tomselect) {
                if (!projectField.tomselect.options[projectId]) {
                    const label = projectName
                        ? (projectCode ? `${projectName} (${projectCode})` : projectName)
                        : `Project #${projectId}`;
                    projectField.tomselect.addOption({
                        value: String(projectId),
                        text: label,
                        subtype: projectCode || '',
                    });
                }
                projectField.tomselect.setValue(projectId);
            } else {
                projectField.value = projectId;
                projectField.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Poll for dependencies to load into task-create-dependencies script tag or dependencies state
            const waitForDependencies = () => {
                return new Promise((resolve) => {
                    let attempts = 0;
                    const maxAttempts = 30; // 3 seconds timeout max
                    const checkInterval = setInterval(() => {
                        attempts++;
                        const scriptDepsNode = document.getElementById('task-create-dependencies');
                        let dependencies = {};
                        if (scriptDepsNode) {
                            try {
                                dependencies = JSON.parse(scriptDepsNode.textContent || '{}');
                            } catch (_) {}
                        }
                        if ((dependencies.projects && dependencies.projects[String(projectId)]) || attempts >= maxAttempts) {
                            clearInterval(checkInterval);
                            resolve();
                        }
                    }, 100);
                });
            };

            await waitForDependencies();

            // Set Assignee
            const assigneeField = form.querySelector('[name="current_assignee_id"]');
            if (targetUserId && assigneeField) {
                if (assigneeField.tomselect) {
                    assigneeField.tomselect.setValue(targetUserId);
                } else {
                    assigneeField.value = targetUserId;
                    assigneeField.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Set Milestone
            const milestoneField = form.querySelector('[name="project_milestone_id"]');
            if (milestoneId && milestoneField) {
                if (milestoneField.tomselect) {
                    milestoneField.tomselect.setValue(milestoneId);
                } else {
                    milestoneField.value = milestoneId;
                    milestoneField.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Small pause for sprint field options update after milestone change
                setTimeout(() => {
                    const sprintField = form.querySelector('[name="project_sprint_id"]');
                    if (sprintId && sprintField) {
                        if (sprintField.tomselect) {
                            sprintField.tomselect.setValue(sprintId);
                        } else {
                            sprintField.value = sprintId;
                            sprintField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                }, 150);
            }
        }
    }, 150);
});
