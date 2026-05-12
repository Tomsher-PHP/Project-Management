document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('handoff_create_modal');
    const openBtns = document.querySelectorAll('[data-handoff-create-open]');
    const closeBtns = document.querySelectorAll('[data-handoff-create-close]');
    const form = document.querySelector('[data-handoff-create-form]');

    if (!modalEl || !form) return;

    // Elements
    const projectSelect = form.querySelector('[data-handoff-project-select]');
    const milestoneSelect = form.querySelector('[data-handoff-milestone-select]');
    const sprintSelect = form.querySelector('[data-handoff-sprint-select]');
    const taskSelect = form.querySelector('[data-handoff-task-select]');
    const purposeSelect = form.querySelector('[data-handoff-purpose-select]');
    const submitBtn = form.querySelector('[data-handoff-create-submit]');

    // TomSelect Instances
    let tsProject, tsMilestone, tsSprint, tsTask, tsPurpose;

    const initSelects = () => {
        tsProject = projectSelect.tomselect;
        tsMilestone = milestoneSelect.tomselect;
        tsSprint = sprintSelect.tomselect;
        tsTask = taskSelect.tomselect;
        tsPurpose = purposeSelect.tomselect;

        if (!tsProject) return;

        // Cascading updates
        tsProject.on('change', (projectId) => {
            tsMilestone.clear();
            tsMilestone.clearOptions();
            tsSprint.clear();
            tsSprint.clearOptions();
            tsTask.clear();
            tsTask.clearOptions();

            if (!projectId) return;

            // Filter milestones
            const projectMilestones = dependencies.milestones.filter(m => String(m.project_id) === String(projectId));
            projectMilestones.forEach(m => {
                tsMilestone.addOption({ value: m.id, text: m.name });
            });
            tsMilestone.refreshOptions(false);

            // Filter sprints
            const projectSprints = dependencies.sprints.filter(s => String(s.project_id) === String(projectId));
            projectSprints.forEach(s => {
                tsSprint.addOption({ value: s.id, text: s.name });
            });
            tsSprint.refreshOptions(false);

            fetchTasks();
        });

        tsMilestone.on('change', (milestoneId) => {
            if (!milestoneId) return;
            const projectId = tsProject.getValue();
            tsSprint.clear();
            tsSprint.clearOptions();

            // Re-filter sprints based on milestone
            const projectSprints = dependencies.sprints.filter(s => {
                const matchProject = String(s.project_id) === String(projectId);
                const matchMilestone = String(s.project_milestone_id) === String(milestoneId);
                return matchProject && matchMilestone;
            });
            projectSprints.forEach(s => {
                tsSprint.addOption({ value: s.id, text: s.name });
            });
            tsSprint.refreshOptions(false);
            fetchTasks();
        });

        tsSprint.on('change', () => {
            fetchTasks();
        });
    };

    // Load dependencies from JSON block
    const getDependencies = () => {
        const depBlock = document.getElementById('task-filter-dependencies');
        return depBlock ? JSON.parse(depBlock.textContent) : { milestones: [], sprints: [] };
    };

    const dependencies = getDependencies();

    if (projectSelect.tomselect) {
        initSelects();
    } else {
        document.addEventListener('tomselect:ready', initSelects);
    }

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

    const toggleModal = (show) => {
        if (show) {
            modalEl.classList.remove('hidden');
            modalEl.classList.add('flex');
        } else {
            modalEl.classList.add('hidden');
            modalEl.classList.remove('flex');
            form.reset();
            if (tsProject) tsProject.clear();
            if (tsPurpose) tsPurpose.clear();
            if (tsMilestone) tsMilestone.clear();
            if (tsSprint) tsSprint.clear();
            if (tsTask) tsTask.clear();

            form.querySelectorAll('[data-handoff-create-error]').forEach(el => {
                el.textContent = '';
                el.classList.add('hidden');
            });
        }
    };

    openBtns.forEach(btn => btn.addEventListener('click', () => toggleModal(true)));
    closeBtns.forEach(btn => btn.addEventListener('click', () => toggleModal(false)));

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Reset errors
        form.querySelectorAll('[data-handoff-create-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });

        const formData = new FormData(form);
        const submitUrl = form.getAttribute('data-store-url');

        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Saving...';
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
                if (window.Toast && window.Toast.success) {
                    window.Toast.success(data.message || 'Handoff created successfully.');
                } else if (window.toastr) {
                    window.toastr.success(data.message || 'Handoff created successfully.');
                } else {
                    Alert.success(data.message || 'Handoff created successfully.');
                }
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
                    Alert.error(data.message || 'An error occurred.');
                }
            }
        } catch (err) {
            console.error('Submit error:', err);
            Alert.error('A network error occurred.');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});
