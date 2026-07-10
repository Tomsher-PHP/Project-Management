document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-appraisal-root]');

    if (!root) {
        return;
    }

    const monthSelect = root.querySelector('[data-appraisal-month]');
    const yearSelect = root.querySelector('[data-appraisal-year]');
    const usersContainer = root.querySelector('[data-appraisal-users]');
    const userSearch = root.querySelector('[data-appraisal-user-search]');
    const selectedCount = root.querySelector('[data-appraisal-selected-count]');
    const selectAllCheckbox = root.querySelector('[data-appraisal-users-select-all]');
    const canAssign = root.dataset.canAssign === 'true';
    const initialDataNode = root.querySelector('[data-appraisal-initial-data]');

    let assignmentData = { users: [] };
    let activeTab = 'my';
    let selectedUserIds = new Set();

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const alertError = (message) => window.Alert?.error ? window.Alert.error(message) : window.alert(message);

    const parseInitialData = () => {
        if (!initialDataNode) {
            return;
        }

        try {
            assignmentData = JSON.parse(initialDataNode.textContent || '{}');
        } catch (error) {
            assignmentData = { users: [] };
        }
    };

    const currentPeriod = () => ({
        month: Number(monthSelect?.value || new Date().getMonth() + 1),
        year: Number(yearSelect?.value || new Date().getFullYear()),
    });

    const userInitials = (name = 'User') => String(name)
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'U';

    const userAvatar = (user) => {
        if (user.profile_image_url) {
            return `<span class="inline-flex h-10 w-10 shrink-0 overflow-hidden rounded-full bg-success-50"><img src="${escapeHtml(user.profile_image_url)}" alt="${escapeHtml(user.name)}" class="h-full w-full rounded-full object-cover"></span>`;
        }

        return `<span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-50 text-sm font-bold text-success-400 dark:bg-darkblack-500 dark:text-success-300">${escapeHtml(userInitials(user.name))}</span>`;
    };

    const statusBadge = (user) => {
        if (!user.is_assigned) {
            return '<span class="inline-flex rounded-full bg-bgray-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">Not Assigned</span>';
        }

        const label = user.status_label || String(user.status || '').replace(/^\w/, (letter) => letter.toUpperCase());
        const classes = {
            draft: 'bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-300',
            published: 'bg-warning-100 text-warning-600 dark:bg-warning-900/30 dark:text-warning-300',
            completed: 'bg-info-50 text-info-500 dark:bg-darkblack-500 dark:text-info-500',
            closed: 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300',
        }[user.status] || 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300';

        return `<span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] ${classes}">${label}</span>`;
    };

    const categoryBadges = (user) => {
        const categories = user.categories || [];

        if (!categories.length) {
            return '<span class="text-sm font-medium text-bgray-500 dark:text-bgray-300">--</span>';
        }

        return categories.map((category) => `
            <span class="inline-flex rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">${escapeHtml(category)}</span>
        `).join('');
    };

    const actionButton = (user) => {
        const action = !user.is_assigned ? 'assign' : (user.status === 'draft' ? 'edit' : 'view');
        const label = action.charAt(0).toUpperCase() + action.slice(1);
        const classes = action === 'view'
            ? 'border border-bgray-200 bg-white text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50'
            : 'bg-success-300 text-white';

        return `<button type="button" class="rounded-lg px-3 py-2 text-xs font-semibold transition ${classes}">${label}</button>`;
    };

    const updateSelectedCount = () => {
        if (!selectedCount || !usersContainer) {
            return;
        }

        const checkboxes = Array.from(usersContainer.querySelectorAll('[data-appraisal-user-checkbox]'));
        const enabledCheckboxes = checkboxes.filter((input) => !input.disabled);
        const checkedCheckboxes = checkboxes.filter((input) => input.checked);

        selectedCount.textContent = String(selectedUserIds.size);

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = enabledCheckboxes.length > 0 && enabledCheckboxes.every((input) => input.checked);
            selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && !selectAllCheckbox.checked;
            selectAllCheckbox.disabled = enabledCheckboxes.length === 0;
        }
    };

    const renderUsers = () => {
        if (!usersContainer) {
            return;
        }

        const term = String(userSearch?.value || '').trim().toLowerCase();
        const users = (assignmentData.users || []).filter((user) => {
            if (!term) {
                return true;
            }

            return [
                user.name,
                user.email,
                user.department,
                user.designation,
            ].some((value) => String(value || '').toLowerCase().includes(term));
        });

        if (!users.length) {
            usersContainer.innerHTML = '<tr><td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-bgray-600 dark:text-bgray-300">No users found.</td></tr>';
            updateSelectedCount();
            return;
        }

        usersContainer.innerHTML = users.map((user) => {
            const disabled = user.is_editable === false ? 'disabled' : '';
            const disabledClasses = user.is_editable === false ? 'opacity-60' : '';
            const checked = selectedUserIds.has(Number(user.id)) && user.is_editable !== false ? 'checked' : '';
            const meta = [user.department, user.designation].filter(Boolean).join(' · ') || 'No department / designation';

            return `
                <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-50 dark:hover:bg-darkblack-500 ${disabledClasses}" data-appraisal-user-row data-user-id="${escapeHtml(user.id)}">
                    <td class="px-4 py-4 xl:px-0">
                        <input type="checkbox" value="${escapeHtml(user.id)}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-user-checkbox ${disabled} ${checked}>
                    </td>
                    <td class="px-4 py-4 xl:px-0">
                        <div class="flex items-center gap-3">
                            ${userAvatar(user)}
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(user.name)}</p>
                                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">${escapeHtml(meta)}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 xl:px-0">
                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">${escapeHtml(user.kpi_name || 'Not Assigned')}</span>
                    </td>
                    <td class="px-4 py-4 xl:px-0">
                        <div class="flex flex-wrap gap-1.5">${categoryBadges(user)}</div>
                    </td>
                    <td class="px-4 py-4 xl:px-0">${statusBadge(user)}</td>
                    <td class="px-4 py-4 xl:px-0">${actionButton(user)}</td>
                </tr>
            `;
        }).join('');

        updateSelectedCount();
    };

    const setActiveTab = (tab) => {
        activeTab = tab;

        root.querySelectorAll('[data-appraisal-tab-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.appraisalTabPanel !== tab);
        });

        root.querySelectorAll('[data-appraisal-tab-button]').forEach((button) => {
            const isActive = button.dataset.tab === tab;
            button.classList.toggle('bg-success-300', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border', !isActive);
            button.classList.toggle('border-bgray-200', !isActive);
            button.classList.toggle('bg-bgray-50', !isActive);
            button.classList.toggle('text-bgray-700', !isActive);
        });

        if (tab === 'assign') {
            renderUsers();
        }
    };

    const loadAssignmentData = async () => {
        if (!canAssign || !root.dataset.assignmentUrl) {
            return;
        }

        const period = currentPeriod();
        const url = new URL(root.dataset.assignmentUrl, window.location.origin);
        url.searchParams.set('month', period.month);
        url.searchParams.set('year', period.year);

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                throw new Error(payload.message || 'Unable to load appraisal assignments.');
            }

            assignmentData = payload.data || { users: [] };
            selectedUserIds.clear();

            if (activeTab === 'assign') {
                renderUsers();
            }
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal assignments.');
        }
    };

    root.addEventListener('change', (event) => {
        if (event.target.matches('[data-appraisal-month], [data-appraisal-year]')) {
            loadAssignmentData();
            return;
        }

        if (event.target.matches('[data-appraisal-users-select-all]')) {
            usersContainer?.querySelectorAll('[data-appraisal-user-checkbox]:not(:disabled)').forEach((input) => {
                input.checked = event.target.checked;

                if (event.target.checked) {
                    selectedUserIds.add(Number(input.value));
                    return;
                }

                selectedUserIds.delete(Number(input.value));
            });
            updateSelectedCount();
            return;
        }

        if (event.target.matches('[data-appraisal-user-checkbox]')) {
            if (event.target.checked) {
                selectedUserIds.add(Number(event.target.value));
            } else {
                selectedUserIds.delete(Number(event.target.value));
            }
            updateSelectedCount();
        }
    });

    root.addEventListener('input', (event) => {
        if (event.target.matches('[data-appraisal-user-search]')) {
            renderUsers();
        }
    });

    root.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-appraisal-tab-button]');

        if (tabButton) {
            setActiveTab(tabButton.dataset.tab);
        }
    });

    parseInitialData();
    renderUsers();
    setActiveTab('my');
});
