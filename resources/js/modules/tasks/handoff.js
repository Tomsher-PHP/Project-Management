import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('handoff_create_modal');
    const openBtns = document.querySelectorAll('[data-handoff-create-open]');
    const closeBtns = document.querySelectorAll('[data-handoff-create-close]');
    const form = document.querySelector('[data-handoff-create-form]');

    if (!modalEl || !form) return;

    // Elements
    const modalTitleEl = modalEl.querySelector('[data-handoff-modal-title]');
    const projectSelect = form.querySelector('[data-handoff-project-select]');
    const milestoneSelect = form.querySelector('[data-handoff-milestone-select]');
    const sprintSelect = form.querySelector('[data-handoff-sprint-select]');
    const taskSelect = form.querySelector('[data-handoff-task-select]');
    const targetUserSelect = form.querySelector('[data-handoff-target-user-select]');
    const purposeSelect = form.querySelector('[data-handoff-purpose-select]');
    const submitBtn = form.querySelector('[data-handoff-create-submit]');
    const descriptionInput = form.querySelector('#handoff_description_input');
    const descriptionEditorElement = form.querySelector('#handoff_description_editor');

    // State
    let currentEditId = null;

    // TomSelect Instances
    let tsProject, tsMilestone, tsSprint, tsTask, tsTargetUser, tsPurpose, descriptionEditor;

    const initDescriptionEditor = () => {
        if (!descriptionEditorElement || !window.Quill || descriptionEditor) return;

        descriptionEditor = new window.Quill(descriptionEditorElement, {
            theme: 'snow',
            placeholder: 'Provide handoff details',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ header: [1, 2, 3, false] }],
                    ['link'],
                ],
            },
        });
    };

    const initSelects = () => {
        tsProject = projectSelect.tomselect;
        tsMilestone = milestoneSelect.tomselect;
        tsSprint = sprintSelect.tomselect;
        tsTask = taskSelect.tomselect;
        tsTargetUser = targetUserSelect?.tomselect;
        tsPurpose = purposeSelect?.tomselect;

        if (tsPurpose) {
            tsPurpose.on('item_add', () => {
                tsPurpose.close();
                tsPurpose.blur();
            });
            tsPurpose.on('change', () => {
                tsPurpose.close();
            });
        }

        if (!tsProject) return;

        const loadedHandoffProjectDependencies = {};

        const fetchHandoffProjectDependencies = async (projectId) => {
            if (!projectId) return null;
            const key = String(projectId);
            if (loadedHandoffProjectDependencies[key]) {
                return loadedHandoffProjectDependencies[key];
            }

            const requestUrl = `/projects/${encodeURIComponent(key)}/task-create-dependencies`;
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

            loadedHandoffProjectDependencies[key] = result.data;
            return result.data;
        };

        // Cascading updates
        tsProject.on('change', async (projectId) => {
            tsMilestone.clear();
            tsMilestone.clearOptions();
            tsSprint.clear();
            tsSprint.clearOptions();
            tsTask.clear();
            tsTask.clearOptions();
            tsTargetUser?.clear();
            tsTargetUser?.clearOptions();

            if (!projectId) return;

            try {
                const projectDeps = await fetchHandoffProjectDependencies(projectId);
                if (!projectDeps) return;

                const currentUserId = String(form.dataset.currentUserId || '');
                const targetUsers = (projectDeps.assignees || []).filter(u => String(u.value) !== currentUserId);

                targetUsers.forEach((user) => {
                    tsTargetUser?.addOption(user);
                });
                tsTargetUser?.refreshOptions(false);

                (projectDeps.milestones || []).forEach(m => {
                    tsMilestone.addOption({ value: m.value || m.id, text: m.text || m.name });
                });
                tsMilestone.refreshOptions(false);

                (projectDeps.sprints || []).forEach(s => {
                    tsSprint.addOption({ value: s.value || s.id, text: s.text || s.name });
                });
                tsSprint.refreshOptions(false);

                fetchTasks();
            } catch (error) {
                Alert.errorModal(error.message || 'Unable to load project handoff options.');
            }
        });

        tsMilestone.on('change', (milestoneId) => {
            if (!milestoneId) return;
            const projectId = tsProject.getValue();
            tsSprint.clear();
            tsSprint.clearOptions();

            const projectDeps = loadedHandoffProjectDependencies[String(projectId)];
            const availableSprints = (projectDeps?.sprints || []).filter(s => {
                return String(s.project_milestone_id || '') === String(milestoneId);
            });

            availableSprints.forEach(s => {
                tsSprint.addOption({ value: s.value || s.id, text: s.text || s.name });
            });
            tsSprint.refreshOptions(false);
            fetchTasks();
        });

        tsSprint.on('change', () => {
            fetchTasks();
        });
    };

    initDescriptionEditor();

    if (projectSelect.tomselect) {
        initSelects();
    } else {
        document.addEventListener('tomselect:ready', initSelects);
    }

    purposeSelect?.addEventListener('change', () => {
        if (purposeSelect.tomselect) {
            purposeSelect.tomselect.close();
            purposeSelect.tomselect.blur();
        }
    });

    const fetchTasks = async () => {
        const projectId = projectSelect?.value || '';
        const milestoneId = milestoneSelect?.value || '';
        const sprintId = sprintSelect?.value || '';

        if (!projectId) {
            tsTask.clearOptions();
            tsTask.clear();
            return;
        }

        const params = new URLSearchParams({
            project_id: projectId,
        });

        if (milestoneId) {
            params.append('project_milestone_id', milestoneId);
        }

        if (sprintId) {
            params.append('project_sprint_id', sprintId);
        }

        try {
            const res = await fetch(`/tasks/dropdown-options?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) return;

            const data = await res.json();
            const tasks = data.options || data.data || [];

            tsTask.clearOptions();
            tsTask.clear();

            tasks.forEach(task => {
                tsTask.addOption({
                    value: task.id,
                    text: task.name || task.text,
                });
            });

            tsTask.refreshOptions(false);
        } catch (err) {
            console.error('Could not fetch handoff tasks', err);
        }
    };

    const resetHandoffForm = () => {
        currentEditId = null;
        if (modalTitleEl) modalTitleEl.textContent = 'Create Handoff Request';
        if (submitBtn) submitBtn.textContent = 'Create Handoff';
        form.reset();
        if (descriptionEditor) descriptionEditor.setContents([]);
        if (descriptionInput) descriptionInput.value = '';
        if (tsProject) tsProject.clear();
        if (tsPurpose) tsPurpose.clear();
        if (tsMilestone) tsMilestone.clear();
        if (tsSprint) tsSprint.clear();
        if (tsTask) tsTask.clear();
        if (tsTargetUser) tsTargetUser.clear();

        form.querySelectorAll('[data-handoff-create-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    };

    const toggleModal = (show) => {
        if (show) {
            resetHandoffForm();
            modalEl.classList.remove('hidden');
            modalEl.classList.add('flex');
        } else {
            modalEl.classList.add('hidden');
            modalEl.classList.remove('flex');
            resetHandoffForm();
        }
    };

    const populateEditForm = async (btn) => {
        resetHandoffForm();

        const handoffId = btn.dataset.handoffRequestId;
        const projectId = btn.dataset.projectId;
        const milestoneId = btn.dataset.projectMilestoneId;
        const sprintId = btn.dataset.projectSprintId;
        const sourceTaskId = btn.dataset.sourceTaskId;
        const targetUserId = btn.dataset.targetUserId;
        const purpose = btn.dataset.purpose;
        const description = btn.dataset.description;

        currentEditId = handoffId;

        if (modalTitleEl) modalTitleEl.textContent = 'Edit Handoff Request';
        if (submitBtn) submitBtn.textContent = 'Update Handoff';

        if (projectId && tsProject) {
            tsProject.setValue(projectId);

            if (targetUserId && tsTargetUser) {
                tsTargetUser.setValue(targetUserId);
            }

            if (milestoneId && tsMilestone) {
                tsMilestone.setValue(milestoneId);
            }

            if (sprintId && tsSprint) {
                tsSprint.setValue(sprintId);
            }

            await fetchTasks();

            if (sourceTaskId && tsTask) {
                tsTask.setValue(sourceTaskId);
            }
        }

        if (purpose && tsPurpose) {
            tsPurpose.setValue(purpose);
        }

        if (description) {
            let cleanHtml = description.trim();
            if (/&lt;[a-z][\s\S]*&gt;/i.test(cleanHtml) && !/<[a-z][\s\S]*>/i.test(cleanHtml)) {
                const txt = document.createElement('textarea');
                txt.innerHTML = cleanHtml;
                cleanHtml = txt.value;
            }
            if (descriptionEditor) {
                descriptionEditor.setContents([]);
                descriptionEditor.clipboard.dangerouslyPasteHTML(0, cleanHtml);
            } else if (descriptionEditorElement) {
                const qlEditor = descriptionEditorElement.querySelector('.ql-editor');
                if (qlEditor) qlEditor.innerHTML = cleanHtml;
            }
            if (descriptionInput) descriptionInput.value = cleanHtml;
        }

        modalEl.classList.remove('hidden');
        modalEl.classList.add('flex');
    };

    openBtns.forEach(btn => btn.addEventListener('click', () => toggleModal(true)));
    closeBtns.forEach(btn => btn.addEventListener('click', () => toggleModal(false)));

    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('[data-handoff-edit-btn]');
        if (editBtn) {
            populateEditForm(editBtn);
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Reset errors
        form.querySelectorAll('[data-handoff-create-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });

        if (descriptionEditor && descriptionInput) {
            const content = descriptionEditor.root.innerHTML.trim();
            descriptionInput.value = content === '<p><br></p>' ? '' : content;
        }

        const formData = new FormData(form);
        let submitUrl = form.getAttribute('data-store-url');
        if (currentEditId) {
            const template = form.getAttribute('data-update-url-template') || '/handoff-requests/:id';
            submitUrl = template.replace(':id', currentEditId).replace('__ID__', currentEditId);
            formData.append('_method', 'PUT');
        }

        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = currentEditId ? 'Updating...' : 'Saving...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.status) {
                toggleModal(false);
                const msg = data.message || (currentEditId ? 'Handoff updated successfully.' : 'Handoff created successfully.');
                if (window.Toast && window.Toast.success) {
                    window.Toast.success(msg);
                } else if (window.toastr) {
                    window.toastr.success(msg);
                } else if (Alert && Alert.success) {
                    Alert.success(msg);
                } else {
                    alert(msg);
                }
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } else {
                if (data.errors) {
                    for (const [key, messages] of Object.entries(data.errors)) {
                        const errorEl = form.querySelector(`[data-handoff-create-error="${key}"]`);
                        if (errorEl) {
                            errorEl.textContent = messages[0];
                            errorEl.classList.remove('hidden');
                        }
                    }
                } else {
                    if (Alert && Alert.error) {
                        Alert.error(data.message || 'An error occurred.');
                    } else {
                        alert(data.message || 'An error occurred.');
                    }
                }
            }
        } catch (err) {
            console.error('Submit error:', err);
            if (Alert && Alert.error) {
                Alert.error('A network error occurred.');
            } else {
                alert('A network error occurred.');
            }
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});

