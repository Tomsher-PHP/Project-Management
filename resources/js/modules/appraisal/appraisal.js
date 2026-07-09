document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-appraisal-root]');

    if (!root) {
        return;
    }

    const monthSelect = root.querySelector('[data-appraisal-month]');
    const yearSelect = root.querySelector('[data-appraisal-year]');
    const usersContainer = root.querySelector('[data-appraisal-users]');
    const categoriesContainer = root.querySelector('[data-appraisal-categories]');
    const userSearch = root.querySelector('[data-appraisal-user-search]');
    const selectedCount = root.querySelector('[data-appraisal-selected-count]');
    const canAssign = root.dataset.canAssign === 'true';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const initialDataNode = root.querySelector('[data-appraisal-initial-data]');

    let assignmentData = { users: [], categories: [] };
    let activeTab = canAssign ? 'my' : 'my';
    let draggedQuestionItem = null;
    let dragHandleItem = null;

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const alertSuccess = (message) => window.Alert?.success ? window.Alert.success(message) : window.alert(message);
    const alertError = (message) => window.Alert?.error ? window.Alert.error(message) : window.alert(message);

    const parseInitialData = () => {
        if (!initialDataNode) {
            return;
        }

        try {
            assignmentData = JSON.parse(initialDataNode.textContent || '{}');
        } catch (error) {
            assignmentData = { users: [], categories: [] };
        }
    };

    const currentPeriod = () => ({
        month: Number(monthSelect?.value || new Date().getMonth() + 1),
        year: Number(yearSelect?.value || new Date().getFullYear()),
    });

    const statusBadge = (user) => {
        if (!user.is_assigned) {
            return '<span class="inline-flex rounded-full bg-bgray-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">Not Assigned</span>';
        }

        const label = user.status === 'published' ? 'Assigned: Published' : 'Assigned';
        const classes = user.status === 'published'
            ? 'bg-warning-100 text-warning-600 dark:bg-warning-900/30 dark:text-warning-300'
            : 'bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-300';

        return `<span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] ${classes}">${label}</span>`;
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
            usersContainer.innerHTML = '<div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">No users found.</div>';
            updateSelectedCount();
            return;
        }

        usersContainer.innerHTML = users.map((user) => {
            const disabled = user.is_editable === false ? 'disabled' : '';
            const disabledClasses = user.is_editable === false ? 'opacity-60' : '';

            return `
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bgray-200 bg-bgray-50 px-3 py-3 transition hover:border-success-200 dark:border-darkblack-400 dark:bg-darkblack-500 ${disabledClasses}" data-appraisal-user-row>
                    <input type="checkbox" value="${escapeHtml(user.id)}" class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-user-checkbox ${disabled}>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(user.name)}</span>
                            ${statusBadge(user)}
                        </span>
                        <span class="mt-1 block text-xs text-bgray-600 dark:text-bgray-300">
                            ${escapeHtml(user.department || 'No department')} · ${escapeHtml(user.designation || 'No designation')}
                        </span>
                    </span>
                </label>
            `;
        }).join('');

        updateSelectedCount();
    };

    const questionRowMarkup = (question = '') => `
        <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-assignment-question>
            <div class="flex items-start gap-3">
                <button type="button" class="mt-0.5 inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 hover:border-success-200 hover:text-success-400 active:cursor-grabbing dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" data-appraisal-assignment-question-handle aria-label="Drag question">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                </button>
                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-appraisal-assignment-question-number></span>
                <div class="flex-1">
                    <input type="text" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" value="${escapeHtml(question)}" placeholder="Enter an appraisal question" data-appraisal-assignment-question-input>
                </div>
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" data-appraisal-assignment-question-remove aria-label="Remove question">✕</button>
            </div>
        </div>
    `;

    const refreshQuestionNumbers = (categoryCard = null) => {
        const scope = categoryCard || categoriesContainer;
        scope?.querySelectorAll('[data-appraisal-assignment-question-list]').forEach((list) => {
            const rows = Array.from(list.querySelectorAll('[data-appraisal-assignment-question]'));

            rows.forEach((row, index) => {
                const number = row.querySelector('[data-appraisal-assignment-question-number]');
                const removeButton = row.querySelector('[data-appraisal-assignment-question-remove]');

                if (number) {
                    number.textContent = String(index + 1);
                }

                if (removeButton) {
                    removeButton.disabled = rows.length === 1;
                    removeButton.classList.toggle('opacity-50', rows.length === 1);
                    removeButton.classList.toggle('cursor-not-allowed', rows.length === 1);
                }
            });
        });
    };

    const renderCategories = () => {
        if (!categoriesContainer) {
            return;
        }

        const categories = assignmentData.categories || [];

        if (!categories.length) {
            categoriesContainer.innerHTML = '<div class="rounded-lg border border-dashed border-bgray-200 px-4 py-10 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">No active appraisal categories found.</div>';
            return;
        }

        categoriesContainer.innerHTML = categories.map((category, categoryIndex) => `
            <article class="rounded-xl border border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-assignment-category>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 px-4 py-3 dark:border-darkblack-400">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" checked data-appraisal-assignment-category-checkbox>
                        <input type="hidden" value="${escapeHtml(category.name)}" data-appraisal-assignment-category-name>
                        <span class="text-base font-semibold text-bgray-900 dark:text-white">${escapeHtml(category.name)}</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-xs font-semibold text-success-400 transition hover:border-success-300 dark:border-success-900/40 dark:bg-darkblack-600 dark:text-success-300" data-appraisal-assignment-question-add>Add Question</button>
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-3 py-2 text-xs font-semibold text-bgray-700 transition hover:border-bgray-300 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50" data-appraisal-assignment-category-toggle aria-expanded="true">Collapse</button>
                    </div>
                </div>
                <div class="space-y-3 px-4 py-4" data-appraisal-assignment-category-body>
                    <div class="space-y-3" data-appraisal-assignment-question-list>
                        ${(category.questions || []).map((question) => questionRowMarkup(question.question)).join('') || questionRowMarkup('')}
                    </div>
                </div>
            </article>
        `).join('');

        refreshQuestionNumbers();
    };

    const updateSelectedCount = () => {
        if (!selectedCount || !usersContainer) {
            return;
        }

        selectedCount.textContent = String(usersContainer.querySelectorAll('[data-appraisal-user-checkbox]:checked').length);
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
            renderCategories();
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

            assignmentData = payload.data || { users: [], categories: [] };

            if (activeTab === 'assign') {
                renderUsers();
                renderCategories();
            }
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal assignments.');
        }
    };

    const serializeCategories = () => Array.from(categoriesContainer?.querySelectorAll('[data-appraisal-assignment-category]') || [])
        .filter((categoryCard) => categoryCard.querySelector('[data-appraisal-assignment-category-checkbox]')?.checked)
        .map((categoryCard) => ({
            name: categoryCard.querySelector('[data-appraisal-assignment-category-name]')?.value || '',
            questions: Array.from(categoryCard.querySelectorAll('[data-appraisal-assignment-question-input]'))
                .map((input) => ({ question: input.value.trim() }))
                .filter((question) => question.question !== ''),
        }))
        .filter((category) => category.name && category.questions.length);

    const submitAssignments = async (status) => {
        const period = currentPeriod();
        const userIds = Array.from(usersContainer?.querySelectorAll('[data-appraisal-user-checkbox]:checked') || []).map((input) => Number(input.value));
        const categories = serializeCategories();

        if (!userIds.length) {
            alertError('Select at least one user.');
            return;
        }

        if (!categories.length) {
            alertError('Select at least one category with questions.');
            return;
        }

        try {
            const response = await fetch(root.dataset.submitUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    month: period.month,
                    year: period.year,
                    status,
                    user_ids: userIds,
                    categories,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to save appraisal assignments.');
            }

            alertSuccess(payload.message || 'Appraisal assignments saved.');
            assignmentData.users = payload.data?.users || assignmentData.users;
            renderUsers();
        } catch (error) {
            alertError(error.message || 'Unable to save appraisal assignments.');
        }
    };

    const resetDraggedItemState = () => {
        if (draggedQuestionItem) {
            draggedQuestionItem.classList.remove('opacity-60', 'scale-[0.99]');
            draggedQuestionItem.setAttribute('draggable', 'false');
            draggedQuestionItem.style.boxShadow = '';
        }

        if (dragHandleItem) {
            dragHandleItem.setAttribute('draggable', 'false');
        }

        draggedQuestionItem = null;
        dragHandleItem = null;
    };

    root.addEventListener('change', (event) => {
        if (event.target.matches('[data-appraisal-month], [data-appraisal-year]')) {
            loadAssignmentData();
            return;
        }

        if (event.target.matches('[data-appraisal-user-checkbox]')) {
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
            return;
        }

        if (event.target.closest('[data-appraisal-users-select-all]')) {
            usersContainer?.querySelectorAll('[data-appraisal-user-checkbox]:not(:disabled)').forEach((input) => {
                input.checked = true;
            });
            updateSelectedCount();
            return;
        }

        if (event.target.closest('[data-appraisal-users-clear]')) {
            usersContainer?.querySelectorAll('[data-appraisal-user-checkbox]').forEach((input) => {
                input.checked = false;
            });
            updateSelectedCount();
            return;
        }

        const categoryToggle = event.target.closest('[data-appraisal-assignment-category-toggle]');

        if (categoryToggle) {
            const card = categoryToggle.closest('[data-appraisal-assignment-category]');
            const body = card?.querySelector('[data-appraisal-assignment-category-body]');
            const isHidden = body?.classList.toggle('hidden');
            categoryToggle.textContent = isHidden ? 'Expand' : 'Collapse';
            categoryToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
            return;
        }

        const addQuestion = event.target.closest('[data-appraisal-assignment-question-add]');

        if (addQuestion) {
            const card = addQuestion.closest('[data-appraisal-assignment-category]');
            const list = card?.querySelector('[data-appraisal-assignment-question-list]');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = questionRowMarkup('');
            const row = wrapper.firstElementChild;

            if (row && list) {
                list.appendChild(row);
                refreshQuestionNumbers(card);
                row.querySelector('[data-appraisal-assignment-question-input]')?.focus();
            }

            return;
        }

        const removeQuestion = event.target.closest('[data-appraisal-assignment-question-remove]');

        if (removeQuestion) {
            const card = removeQuestion.closest('[data-appraisal-assignment-category]');
            const list = card?.querySelector('[data-appraisal-assignment-question-list]');
            const rows = list?.querySelectorAll('[data-appraisal-assignment-question]') || [];

            if (rows.length <= 1) {
                const input = list?.querySelector('[data-appraisal-assignment-question-input]');
                if (input) {
                    input.value = '';
                    input.focus();
                }
                return;
            }

            removeQuestion.closest('[data-appraisal-assignment-question]')?.remove();
            refreshQuestionNumbers(card);
            return;
        }

        const submitButton = event.target.closest('[data-appraisal-submit]');

        if (submitButton) {
            submitAssignments(submitButton.dataset.appraisalSubmit);
        }
    });

    root.addEventListener('mousedown', (event) => {
        const handle = event.target.closest('[data-appraisal-assignment-question-handle]');

        if (!handle) {
            return;
        }

        const item = handle.closest('[data-appraisal-assignment-question]');

        if (!item) {
            return;
        }

        dragHandleItem = item;
        item.setAttribute('draggable', 'true');
    });

    root.addEventListener('mouseup', () => {
        if (!draggedQuestionItem && dragHandleItem) {
            dragHandleItem.setAttribute('draggable', 'false');
            dragHandleItem = null;
        }
    });

    root.addEventListener('dragstart', (event) => {
        const item = event.target.closest('[data-appraisal-assignment-question]');

        if (!item || item !== dragHandleItem) {
            event.preventDefault();
            return;
        }

        draggedQuestionItem = item;
        item.classList.add('opacity-60', 'scale-[0.99]');
        item.style.boxShadow = '0 18px 35px -18px rgba(15, 23, 42, 0.35)';

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', '');
        }
    });

    root.addEventListener('dragover', (event) => {
        if (draggedQuestionItem) {
            event.preventDefault();
        }

        const targetItem = event.target.closest('[data-appraisal-assignment-question]');
        const sourceList = draggedQuestionItem?.closest('[data-appraisal-assignment-question-list]');

        if (!draggedQuestionItem || !targetItem || targetItem === draggedQuestionItem || targetItem.closest('[data-appraisal-assignment-question-list]') !== sourceList) {
            return;
        }

        const bounds = targetItem.getBoundingClientRect();
        const insertAfter = event.clientY > bounds.top + (bounds.height / 2);

        sourceList.insertBefore(draggedQuestionItem, insertAfter ? targetItem.nextElementSibling : targetItem);
    });

    root.addEventListener('drop', (event) => {
        if (!draggedQuestionItem) {
            return;
        }

        event.preventDefault();
        refreshQuestionNumbers(draggedQuestionItem.closest('[data-appraisal-assignment-category]'));
        resetDraggedItemState();
    });

    root.addEventListener('dragend', () => {
        if (draggedQuestionItem) {
            refreshQuestionNumbers(draggedQuestionItem.closest('[data-appraisal-assignment-category]'));
        }

        resetDraggedItemState();
    });

    parseInitialData();
    renderUsers();
    renderCategories();
    setActiveTab('my');
});
