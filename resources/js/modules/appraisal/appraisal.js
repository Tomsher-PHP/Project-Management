document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-appraisal-root]');

    if (!root) {
        return;
    }

    const monthSelect = root.querySelector('[data-appraisal-month]');
    const yearSelect = root.querySelector('[data-appraisal-year]');
    const myAppraisalsContainer = root.querySelector('[data-appraisal-my-list]');
    const usersContainer = root.querySelector('[data-appraisal-users]');
    const userSearch = root.querySelector('[data-appraisal-user-search]');
    const selectedCount = root.querySelector('[data-appraisal-selected-count]');
    const selectAllCheckbox = root.querySelector('[data-appraisal-users-select-all]');
    const openAssignButton = root.querySelector('[data-appraisal-open-assign]');
    const publishSelectedButton = root.querySelector('[data-appraisal-publish-selected]');
    const modal = root.querySelector('[data-appraisal-assign-modal]');
    const modalTitle = root.querySelector('[data-appraisal-modal-title]');
    const modalSelectedCount = root.querySelector('[data-appraisal-modal-selected-count]');
    const modalSelectedUsers = root.querySelector('[data-appraisal-modal-selected-users]');
    const kpiSelect = root.querySelector('[data-appraisal-kpi-select]');
    const kpiDescription = root.querySelector('[data-appraisal-kpi-description]');
    const modalCategories = root.querySelector('[data-appraisal-modal-categories]');
    const addCategoryButton = root.querySelector('[data-appraisal-assignment-category-add]');
    const kpiAgreementModal = root.querySelector('[data-appraisal-kpi-agreement-modal]');
    const kpiAgreementTitle = root.querySelector('[data-appraisal-kpi-agreement-title]');
    const kpiAgreementDescription = root.querySelector('[data-appraisal-kpi-agreement-description]');
    const kpiAgreementCheckbox = root.querySelector('[data-appraisal-kpi-agreement-checkbox]');
    const kpiAgreementSubmit = root.querySelector('[data-appraisal-kpi-agreement-submit]');
    const answerModal = root.querySelector('[data-appraisal-answer-modal]');
    const answerModalTitle = root.querySelector('[data-appraisal-answer-modal-title]');
    const answerMeta = root.querySelector('[data-appraisal-answer-meta]');
    const answerKpiTitle = root.querySelector('[data-appraisal-answer-kpi-title]');
    const answerKpiDescription = root.querySelector('[data-appraisal-answer-kpi-description]');
    const answerCategoryTitle = root.querySelector('[data-appraisal-answer-category-title]');
    const answerQuestions = root.querySelector('[data-appraisal-answer-questions]');
    const answerCategories = root.querySelector('[data-appraisal-answer-categories]');
    const answerSubmit = root.querySelector('[data-appraisal-answer-submit]');
    const answerSaveDraft = root.querySelector('[data-appraisal-answer-save-draft]');
    const answerHelperMessage = root.querySelector('[data-appraisal-answer-helper-message]');
    const overallCount = root.querySelector('[data-appraisal-answer-overall-count]');
    const overallPercentage = root.querySelector('[data-appraisal-answer-overall-percentage]');
    const overallBar = root.querySelector('[data-appraisal-answer-overall-bar]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const initialDataNode = root.querySelector('[data-appraisal-initial-data]');
    const submitButtons = root.querySelectorAll('[data-appraisal-submit]');
    const authUserId = Number(root.dataset.authUserId || 0);

    let assignmentData = { my_appraisals: [], users: [], kpis: [], categories: [] };
    let activeTab = 'my';
    let selectedUserIds = new Set();
    let draggedQuestionItem = null;
    let dragHandleItem = null;
    let kpiDescriptionEditor = null;
    let modalReadOnly = false;
    let kpiAgreementAppraisalId = null;
    let answerFormData = null;
    let activeAnswerCategoryId = null;

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const alertSuccess = (message) => window.Alert?.success ? window.Alert.success(message) : window.alert(message);
    const alertError = (message) => window.Alert?.error ? window.Alert.error(message) : window.alert(message);
    const confirmAction = async (options) => {
        if (window.Alert?.confirm) {
            const result = await window.Alert.confirm(options);

            return result.isConfirmed;
        }

        return window.confirm(options.text || options.title || 'Are you sure?');
    };

    const parseInitialData = () => {
        if (!initialDataNode) {
            return;
        }

        try {
            assignmentData = JSON.parse(initialDataNode.textContent || '{}');
        } catch (error) {
            assignmentData = { my_appraisals: [], users: [], kpis: [], categories: [] };
        }
    };

    const currentPeriod = () => ({
        month: Number(monthSelect?.value || new Date().getMonth() + 1),
        year: Number(yearSelect?.value || new Date().getFullYear()),
    });

    const cloneCategories = (categories = []) => JSON.parse(JSON.stringify(categories || []));

    const selectedUsers = () => (assignmentData.users || [])
        .filter((user) => selectedUserIds.has(Number(user.id)) && user.is_editable !== false);

    const selectedDraftUserIds = () => (assignmentData.users || [])
        .filter((user) => selectedUserIds.has(Number(user.id)) && user.status === 'draft')
        .map((user) => Number(user.id));

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

    const statusBadge = (record) => {
        if (!record.is_assigned && !record.status) {
            return '<span class="inline-flex rounded-full bg-bgray-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">Not Assigned</span>';
        }

        const label = record.status_label || String(record.status || '').replace(/^\w/, (letter) => letter.toUpperCase());
        const classes = {
            draft: 'bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-300',
            published: 'bg-warning-100 text-warning-600 dark:bg-warning-900/30 dark:text-warning-300',
            completed: 'bg-info-50 text-info-500 dark:bg-darkblack-500 dark:text-info-500',
            closed: 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300',
        }[record.status] || 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300';

        return `<span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.08em] ${classes}">${label}</span>`;
    };

    const kpiAgreementBadge = (isAgreed = false) => {
        const label = isAgreed ? 'Agreed' : 'Not Agreed';
        const classes = isAgreed
            ? 'bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-300'
            : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300';

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

        if (action === 'edit') {
            return `
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400" data-appraisal-row-action="edit" data-user-id="${escapeHtml(user.id)}" data-appraisal-id="${escapeHtml(user.appraisal_id || '')}">Edit</button>
                    <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-xs font-semibold text-success-400 transition hover:border-success-300 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-row-action="publish" data-user-id="${escapeHtml(user.id)}">Publish</button>
                </div>
            `;
        }

        if (user.status === 'published') {
            return `
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-lg border border-bgray-200 bg-white px-3 py-2 text-xs font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-row-action="view" data-appraisal-id="${escapeHtml(user.appraisal_id)}">View</button>
                    <button type="button" class="rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-xs font-semibold text-warning-600 transition hover:border-warning-300 dark:border-warning-900/40 dark:bg-darkblack-500 dark:text-warning-300" data-appraisal-row-action="unpublish" data-appraisal-id="${escapeHtml(user.appraisal_id)}">Unpublish</button>
                </div>
            `;
        }

        const label = action.charAt(0).toUpperCase() + action.slice(1);
        const classes = action === 'view'
            ? 'border border-bgray-200 bg-white text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50'
            : 'bg-success-300 text-white hover:bg-success-400';

        return `<button type="button" class="rounded-lg px-3 py-2 text-xs font-semibold transition ${classes}" data-appraisal-row-action="${action}" data-user-id="${escapeHtml(user.id)}" data-appraisal-id="${escapeHtml(user.appraisal_id || '')}">${label}</button>`;
    };

    const updateSelectedCount = () => {
        if (!selectedCount || !usersContainer) {
            return;
        }

        const checkboxes = Array.from(usersContainer.querySelectorAll('[data-appraisal-user-checkbox]'));
        const enabledCheckboxes = checkboxes.filter((input) => !input.disabled);
        const checkedEnabledCheckboxes = enabledCheckboxes.filter((input) => input.checked);

        selectedCount.textContent = String(selectedUserIds.size);

        if (openAssignButton) {
            openAssignButton.disabled = selectedUserIds.size === 0;
        }

        if (publishSelectedButton) {
            publishSelectedButton.disabled = selectedDraftUserIds().length === 0;
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = enabledCheckboxes.length > 0 && enabledCheckboxes.every((input) => input.checked);
            selectAllCheckbox.indeterminate = checkedEnabledCheckboxes.length > 0 && !selectAllCheckbox.checked;
            selectAllCheckbox.disabled = enabledCheckboxes.length === 0;
        }
    };

    const renderMyAppraisals = () => {
        if (!myAppraisalsContainer) {
            return;
        }

        const appraisals = assignmentData.my_appraisals || [];

        if (!appraisals.length) {
            myAppraisalsContainer.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-sm font-medium text-bgray-600 dark:text-bgray-300">No users found.</td></tr>';
            return;
        }

        myAppraisalsContainer.innerHTML = appraisals.map((row) => {
            const user = row.user || {};
            const meta = [user.department, user.designation].filter(Boolean).join(' · ') || 'No department / designation';
            const cellText = (value) => `<span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">${escapeHtml(value || '--')}</span>`;
            const status = String(row.status || '').toLowerCase();
            const isAssignee = row.is_assignee || Number(user.id) === authUserId;
            const canAgree = row.can_agree || (row.appraisal_id && isAssignee && status === 'published' && !row.kpi_agreed);
            const canAnswer = row.can_answer || (row.appraisal_id && isAssignee && row.kpi_agreed);
            let action = '<span class="text-sm font-medium text-bgray-500 dark:text-bgray-300">--</span>';

            if (canAgree) {
                action = `<button type="button" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400" data-appraisal-kpi-agree data-appraisal-id="${escapeHtml(row.appraisal_id)}">Agree</button>`;
            } else if (canAnswer) {
                action = `<button type="button" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400" data-appraisal-answer-placeholder data-appraisal-id="${escapeHtml(row.appraisal_id)}">Answer</button>`;
            }

            return `
                <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-50 dark:hover:bg-darkblack-500">
                    <td class="px-4 py-4 xl:px-0">
                        <div class="flex items-center gap-3">
                            ${userAvatar(user)}
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(user.name || 'User')}</p>
                                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">${escapeHtml(meta)}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 xl:px-0">${cellText(row.assignee_submitted_at)}</td>
                    <td class="px-4 py-4 xl:px-0">${cellText(row.reporter_submitted_at)}</td>
                    <td class="px-4 py-4 xl:px-0">${cellText(row.manager_submitted_at)}</td>
                    <td class="px-4 py-4 xl:px-0">${cellText(row.kpi_agreed_at)}</td>
                    <td class="px-4 py-4 xl:px-0">${kpiAgreementBadge(row.kpi_agreed)}</td>
                    <td class="px-4 py-4 xl:px-0">${action}</td>
                </tr>
            `;
        }).join('');
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

    const renderKpis = () => {
        if (!kpiSelect) {
            return;
        }

        kpiSelect.innerHTML = '<option value="">Select KPI</option>' + (assignmentData.kpis || [])
            .map((kpi) => `<option value="${escapeHtml(kpi.id)}">${escapeHtml(kpi.name)}</option>`)
            .join('');
    };

    const normalizeHtml = (value) => {
        const plainText = String(value || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/&nbsp;/g, ' ')
            .trim();

        return plainText === '' ? '' : String(value);
    };

    const initializeKpiDescriptionEditor = () => {
        if (!kpiDescription || !window.Quill || kpiDescriptionEditor) {
            return;
        }

        kpiDescriptionEditor = new window.Quill(kpiDescription, {
            theme: 'snow',
            readOnly: true,
            modules: {
                toolbar: false,
            },
        });

        kpiDescription.dataset.quillInitialized = 'true';
    };

    const setKpiDescription = (description = '') => {
        const value = normalizeHtml(description);

        if (kpiDescriptionEditor) {
            kpiDescriptionEditor.setContents([]);

            if (value) {
                kpiDescriptionEditor.clipboard.dangerouslyPasteHTML(value);
            }

            return;
        }

        if (kpiDescription) {
            kpiDescription.innerHTML = value;
        }
    };

    const questionRowMarkup = (question = '', readOnly = modalReadOnly) => {
        const questionControl = readOnly
            ? `<p class="flex-1 pt-1 text-sm font-medium text-bgray-700 dark:text-bgray-100">${escapeHtml(question)}</p>`
            : `
                <button type="button" class="mt-0.5 inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-500 transition duration-200 hover:border-success-200 hover:text-success-400 active:cursor-grabbing dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" data-appraisal-assignment-question-handle aria-label="Drag question">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                </button>
                <div class="flex-1">
                    <input type="text" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" value="${escapeHtml(question)}" placeholder="Enter an appraisal question" data-appraisal-assignment-question-input>
                </div>
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" data-appraisal-assignment-question-remove aria-label="Remove question">×</button>
            `;

        return `
            <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-assignment-question>
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-appraisal-assignment-question-number></span>
                    ${questionControl}
                </div>
            </div>
        `;
    };

    const categoryMarkup = (category = {}, readOnly = modalReadOnly) => {
        const questions = category.questions || [];
        const categoryName = category.name || '';
        const categoryTitle = escapeHtml(categoryName || 'New Category');
        const categoryNameControl = readOnly
            ? `<span class="text-base font-semibold text-bgray-900 dark:text-white">${categoryTitle}</span>`
            : `<input type="text" class="min-w-[220px] flex-1 rounded-lg border border-gray-300 p-2.5 text-sm font-semibold text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" value="${escapeHtml(categoryName)}" placeholder="Category name" data-appraisal-assignment-category-name>`;
        const categorySelector = readOnly
            ? categoryNameControl
            : `
                <label class="flex min-w-0 flex-1 items-center gap-3">
                    <input type="checkbox" class="h-4 w-4 shrink-0 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" checked data-appraisal-assignment-category-checkbox>
                    ${categoryNameControl}
                </label>
            `;
        const actionButtons = readOnly
            ? ''
            : `
                <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-xs font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-600 dark:text-success-300" data-appraisal-assignment-question-add>Add Question</button>
                <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-500 transition hover:border-red-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/40 dark:bg-darkblack-600 dark:text-red-300" data-appraisal-assignment-category-remove>Remove</button>
            `;

        return `
            <article class="rounded-xl border border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-assignment-category data-appraisal-template-source="${escapeHtml(categoryName)}">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 px-4 py-3 dark:border-darkblack-400">
                    ${categorySelector}
                    <div class="flex items-center gap-2">
                        ${actionButtons}
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-3 py-2 text-xs font-semibold text-bgray-700 transition hover:border-bgray-300 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50" data-appraisal-assignment-category-toggle aria-expanded="true">Collapse</button>
                    </div>
                </div>
                <div class="space-y-3 px-4 py-4" data-appraisal-assignment-category-body>
                    <div class="space-y-3" data-appraisal-assignment-question-list>
                        ${questions.map((question) => questionRowMarkup(question.question, readOnly)).join('') || questionRowMarkup('', readOnly)}
                    </div>
                </div>
            </article>
        `;
    };

    const refreshQuestionNumbers = (categoryCard = null) => {
        const scope = categoryCard || modalCategories;
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

    const refreshCategoryControls = () => {
        const categories = Array.from(modalCategories?.querySelectorAll('[data-appraisal-assignment-category]') || []);

        categories.forEach((categoryCard) => {
            const removeButton = categoryCard.querySelector('[data-appraisal-assignment-category-remove]');

            if (removeButton) {
                removeButton.disabled = categories.length === 1;
                removeButton.classList.toggle('opacity-50', categories.length === 1);
                removeButton.classList.toggle('cursor-not-allowed', categories.length === 1);
            }
        });
    };

    const setModalReadOnly = (readOnly = false) => {
        modalReadOnly = readOnly;

        if (kpiSelect) {
            kpiSelect.disabled = readOnly;
            kpiSelect.classList.toggle('opacity-70', readOnly);
        }

        submitButtons.forEach((button) => {
            button.classList.toggle('hidden', readOnly);
        });

        if (addCategoryButton) {
            addCategoryButton.classList.toggle('hidden', readOnly);
            addCategoryButton.disabled = readOnly;
        }

        modalCategories?.querySelectorAll('input, button[data-appraisal-assignment-question-add], button[data-appraisal-assignment-question-remove], button[data-appraisal-assignment-category-remove], [data-appraisal-assignment-question-handle]').forEach((element) => {
            element.disabled = readOnly;
            element.classList.toggle('opacity-50', readOnly && element.tagName === 'BUTTON');
            element.classList.toggle('cursor-not-allowed', readOnly && element.tagName === 'BUTTON');
        });
    };

    const updateTemplateAddButtons = () => {
        const templatesList = root.querySelector('[data-appraisal-assign-templates-list]');
        if (!templatesList) return;

        const addedTemplates = Array.from(modalCategories?.querySelectorAll('[data-appraisal-template-source]') || [])
            .map(el => el.dataset.appraisalTemplateSource)
            .filter(Boolean);

        const isReadOnly = modalReadOnly;

        templatesList.querySelectorAll('[data-appraisal-template-item]').forEach(item => {
            const name = item.dataset.appraisalTemplateName;
            const addButton = item.querySelector('[data-appraisal-template-add]');
            if (addButton) {
                const alreadyAdded = addedTemplates.includes(name);
                const disabled = alreadyAdded || isReadOnly;
                addButton.disabled = disabled;
                addButton.classList.toggle('opacity-30', disabled);
            }
        });
    };

    const renderTemplatesList = () => {
        const templatesContainer = root.querySelector('[data-appraisal-assign-templates-list]');
        if (!templatesContainer) return;

        const templates = assignmentData.categories || [];
        if (!templates.length) {
            templatesContainer.innerHTML = '<p class="text-xs font-medium text-bgray-500 dark:text-bgray-300">No category templates available.</p>';
            return;
        }

        templatesContainer.innerHTML = templates.map((category) => `
            <div class="rounded-lg border border-bgray-200 bg-white p-3 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-template-item data-appraisal-template-name="${escapeHtml(category.name)}">
                <div class="flex items-center justify-between gap-2">
                    <button type="button" class="flex flex-1 items-center gap-2 text-left" data-appraisal-template-toggle aria-expanded="false">
                        <svg class="h-4 w-4 transform transition-transform duration-200 text-bgray-500 dark:text-bgray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <div>
                            <h5 class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(category.name)}</h5>
                            <span class="text-xs text-bgray-500 dark:text-bgray-300">${category.questions.length} ${category.questions.length === 1 ? 'question' : 'questions'}</span>
                        </div>
                    </button>
                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-success-50 text-success-300 hover:bg-success-100 disabled:opacity-30 disabled:cursor-not-allowed dark:bg-darkblack-500 dark:text-success-300 dark:hover:bg-darkblack-400" data-appraisal-template-add aria-label="Add category to appraisal">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
                <div class="mt-3 hidden border-t border-bgray-100 pt-2 dark:border-darkblack-400" data-appraisal-template-questions-body>
                    <ul class="list-disc pl-5 text-xs text-bgray-600 dark:text-bgray-300 space-y-1">
                        ${category.questions.map(q => `<li>${escapeHtml(q.question)}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `).join('');

        updateTemplateAddButtons();
    };

    const renderModalCategories = (categoriesToRender = null, readOnly = modalReadOnly) => {
        if (!modalCategories) {
            return;
        }

        const categories = cloneCategories(categoriesToRender || assignmentData.categories || []);

        if (!categories.length) {
            modalCategories.innerHTML = '<div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300" data-appraisal-modal-empty>No active appraisal categories found.</div>';
            setModalReadOnly(readOnly);
            updateTemplateAddButtons();
            return;
        }

        modalCategories.innerHTML = categories.map((category) => categoryMarkup(category, readOnly)).join('');

        refreshQuestionNumbers();
        refreshCategoryControls();
        setModalReadOnly(readOnly);
        updateTemplateAddButtons();
    };

    const renderSelectedUsers = (users = selectedUsers()) => {

        if (modalSelectedCount) {
            modalSelectedCount.textContent = `${users.length} ${users.length === 1 ? 'User' : 'Users'} Selected`;
        }

        if (modalSelectedUsers) {
            modalSelectedUsers.innerHTML = users
                .map((user) => `<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-50">${escapeHtml(user.name)}</span>`)
                .join('');
        }
    };

    const resetModal = () => {
        if (modalTitle) {
            modalTitle.textContent = 'Assign Appraisal';
        }

        if (kpiSelect) {
            kpiSelect.disabled = false;
            kpiSelect.value = '';
        }

        setKpiDescription('');

        const defaultCategories = (assignmentData.categories || []).filter(cat => cat.is_default);
        renderModalCategories(defaultCategories, false);
        resetDraggedItemState();

        const templatesContainer = root.querySelector('[data-appraisal-assign-templates-list]');
        if (templatesContainer) {
            templatesContainer.innerHTML = '';
        }
    };

    const openAssignModal = () => {
        if (!selectedUserIds.size) {
            alertError('Select at least one user.');
            return;
        }

        renderSelectedUsers();
        renderKpis();
        resetModal();
        renderTemplatesList();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };

    const urlFromTemplate = (template, id) => String(template || '').replace('__ID__', id);

    const loadStoredAppraisal = async (appraisalId) => {
        if (!appraisalId) {
            throw new Error('Appraisal not found.');
        }

        const response = await fetch(urlFromTemplate(root.dataset.showUrlTemplate, appraisalId), {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (!response.ok || !payload.status) {
            const errors = payload.errors ? Object.values(payload.errors).flat() : [];
            throw new Error(errors[0] || payload.message || 'Unable to load appraisal.');
        }

        return payload.data;
    };

    const openEditModal = async (appraisalId) => {
        try {
            const appraisal = await loadStoredAppraisal(appraisalId);

            if (appraisal.status !== 'draft' || appraisal.is_editable === false) {
                throw new Error('Only draft appraisals can be updated.');
            }

            selectedUserIds = new Set([Number(appraisal.user?.id)]);
            renderUsers();
            renderSelectedUsers([appraisal.user]);
            renderKpis();
            resetModal();

            if (modalTitle) {
                modalTitle.textContent = 'Edit Appraisal';
            }

            if (kpiSelect) {
                const matchedKpiId = Number(appraisal.kpi_id || 0);
                const hasMatchedKpi = (assignmentData.kpis || []).some((kpi) => Number(kpi.id) === matchedKpiId);

                if (matchedKpiId && hasMatchedKpi) {
                    kpiSelect.value = String(matchedKpiId);
                } else if (appraisal.kpi_name) {
                    kpiSelect.insertAdjacentHTML('beforeend', `<option value="__missing" selected disabled>${escapeHtml(appraisal.kpi_name)} (not available)</option>`);
                    kpiSelect.value = '__missing';
                }
            }

            setKpiDescription(appraisal.kpi_description || '');
            renderModalCategories(appraisal.categories || [], false);
            renderTemplatesList();
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal.');
        }
    };

    const openViewModal = async (appraisalId) => {
        try {
            const appraisal = await loadStoredAppraisal(appraisalId);

            if (modalTitle) {
                modalTitle.textContent = 'View Appraisal';
            }

            renderSelectedUsers([appraisal.user]);

            if (kpiSelect) {
                kpiSelect.innerHTML = `<option value="${escapeHtml(appraisal.id)}">${escapeHtml(appraisal.kpi_name || 'Untitled KPI')}</option>`;
                kpiSelect.value = String(appraisal.id);
            }

            setKpiDescription(appraisal.kpi_description || '');
            renderModalCategories(appraisal.categories || [], true);
            renderTemplatesList();
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal.');
        }
    };

    const closeAssignModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        resetModal();
    };

    const serializeCategories = () => Array.from(modalCategories?.querySelectorAll('[data-appraisal-assignment-category]') || [])
        .filter((categoryCard) => categoryCard.querySelector('[data-appraisal-assignment-category-checkbox]')?.checked !== false)
        .map((categoryCard) => ({
            name: (categoryCard.querySelector('[data-appraisal-assignment-category-name]')?.value || '').trim(),
            questions: Array.from(categoryCard.querySelectorAll('[data-appraisal-assignment-question-input]'))
                .map((input) => ({ question: input.value.trim() }))
                .filter((question) => question.question !== ''),
        }));

    const validateCategories = (categories) => {
        if (!categories.length) {
            alertError('Select at least one category with questions.');
            return false;
        }

        const missingName = categories.find((category) => !category.name);

        if (missingName) {
            alertError('Category name is required.');
            return false;
        }

        const missingQuestions = categories.find((category) => !category.questions.length);

        if (missingQuestions) {
            alertError(`Add at least one question for ${missingQuestions.name}.`);
            return false;
        }

        const normalizedCategoryNames = categories.map((category) => category.name.trim().toLowerCase());

        if (normalizedCategoryNames.length !== new Set(normalizedCategoryNames).size) {
            alertError('Duplicate category names are not allowed.');
            return false;
        }

        const duplicateCategory = categories.find((category) => {
            const normalizedQuestions = category.questions.map((question) => question.question.trim().toLowerCase());

            return normalizedQuestions.length !== new Set(normalizedQuestions).size;
        });

        if (duplicateCategory) {
            alertError(`Duplicate questions are not allowed within ${duplicateCategory.name}.`);
            return false;
        }

        return true;
    };

    const submitAssignments = async (status) => {
        const period = currentPeriod();
        const userIds = Array.from(selectedUserIds);
        const kpiId = Number(kpiSelect?.value || 0);
        const categories = serializeCategories();

        if (!userIds.length) {
            alertError('Select at least one user.');
            return;
        }

        if (!kpiId) {
            alertError('Select a KPI.');
            kpiSelect?.focus();
            return;
        }

        if (!validateCategories(categories)) {
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
                    kpi_id: kpiId,
                    user_ids: userIds,
                    categories,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to assign appraisals.');
            }

            alertSuccess(payload.message || 'Appraisals assigned successfully.');
            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            selectedUserIds.clear();
            closeAssignModal();
            renderMyAppraisals();
            renderUsers();
        } catch (error) {
            alertError(error.message || 'Unable to assign appraisals.');
        }
    };

    const publishAppraisals = async (userIds) => {
        const publishableUserIds = userIds
            .map((userId) => Number(userId))
            .filter((userId) => (assignmentData.users || []).some((user) => Number(user.id) === userId && user.status === 'draft'));

        if (!publishableUserIds.length) {
            alertError('Select at least one draft appraisal to publish.');
            return;
        }

        const confirmed = await confirmAction({
            title: 'Publish appraisal?',
            text: publishableUserIds.length === 1
                ? 'This appraisal will become visible to the assignee.'
                : `${publishableUserIds.length} draft appraisals will become visible to their assignees.`,
            confirmText: 'Publish',
            icon: 'warning',
        });

        if (!confirmed) {
            return;
        }

        const period = currentPeriod();

        try {
            const response = await fetch(root.dataset.publishUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    month: period.month,
                    year: period.year,
                    user_ids: publishableUserIds,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to publish appraisals.');
            }

            alertSuccess(payload.message || 'Appraisals published successfully.');
            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            selectedUserIds.clear();
            renderMyAppraisals();
            renderUsers();
        } catch (error) {
            alertError(error.message || 'Unable to publish appraisals.');
        }
    };

    const unpublishAppraisal = async (appraisalId) => {
        if (!appraisalId) {
            alertError('Appraisal not found.');
            return;
        }

        const confirmed = await confirmAction({
            title: 'Unpublish appraisal?',
            text: 'This will return the appraisal to Draft and allow it to be edited again.',
            confirmText: 'Unpublish',
            icon: 'warning',
        });

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(urlFromTemplate(root.dataset.unpublishUrlTemplate, appraisalId), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to unpublish appraisal.');
            }

            alertSuccess(payload.message || 'Appraisal unpublished successfully.');
            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            selectedUserIds.clear();
            renderMyAppraisals();
            renderUsers();
        } catch (error) {
            alertError(error.message || 'Unable to unpublish appraisal.');
        }
    };

    const findMyAppraisalRow = (appraisalId) => (assignmentData.my_appraisals || [])
        .find((row) => Number(row.appraisal_id) === Number(appraisalId));

    const canAgreeToKpi = (row = {}) => {
        const user = row.user || {};
        const status = String(row.status || '').toLowerCase();
        const isAssignee = row.is_assignee || Number(user.id) === authUserId;

        return row.can_agree || (row.appraisal_id && isAssignee && status === 'published' && !row.kpi_agreed);
    };

    const resetKpiAgreementModal = () => {
        kpiAgreementAppraisalId = null;

        if (kpiAgreementTitle) {
            kpiAgreementTitle.textContent = '';
        }

        if (kpiAgreementDescription) {
            kpiAgreementDescription.innerHTML = '';
        }

        if (kpiAgreementCheckbox) {
            kpiAgreementCheckbox.checked = false;
        }

        if (kpiAgreementSubmit) {
            kpiAgreementSubmit.disabled = true;
        }
    };

    const openKpiAgreementModal = (appraisalId) => {
        const row = findMyAppraisalRow(appraisalId);

        if (!row || !canAgreeToKpi(row)) {
            alertError('This KPI cannot be agreed.');
            return;
        }

        kpiAgreementAppraisalId = row.appraisal_id;

        if (kpiAgreementTitle) {
            kpiAgreementTitle.textContent = row.kpi_name || 'Untitled KPI';
        }

        if (kpiAgreementDescription) {
            kpiAgreementDescription.innerHTML = normalizeHtml(row.kpi_description || '') || '<p>--</p>';
        }

        if (kpiAgreementCheckbox) {
            kpiAgreementCheckbox.checked = false;
        }

        if (kpiAgreementSubmit) {
            kpiAgreementSubmit.disabled = true;
        }

        kpiAgreementModal?.classList.remove('hidden');
        kpiAgreementModal?.classList.add('flex');
    };

    const closeKpiAgreementModal = () => {
        kpiAgreementModal?.classList.add('hidden');
        kpiAgreementModal?.classList.remove('flex');
        resetKpiAgreementModal();
    };

    const submitKpiAgreement = async () => {
        if (!kpiAgreementAppraisalId || !kpiAgreementCheckbox?.checked) {
            return;
        }

        try {
            const response = await fetch(urlFromTemplate(root.dataset.agreeKpiUrlTemplate, kpiAgreementAppraisalId), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to agree to KPI.');
            }

            alertSuccess(payload.message || 'KPI agreed successfully.');
            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            closeKpiAgreementModal();
            renderMyAppraisals();
        } catch (error) {
            alertError(error.message || 'Unable to agree to KPI.');
        }
    };
    const saveAppraisalDraft = async () => {
        if (!answerFormData || !answerSaveDraft) {
            return;
        }

        if (answerFormData.is_submitted) {
            alertError('Your appraisal has already been submitted and can no longer be edited.');
            return;
        }

        persistVisibleAnswerValues();

        const role = answerFormData.role;
        const answersList = [];
        (answerFormData.categories || []).forEach((category) => {
            (category.questions || []).forEach((question) => {
                const rating = question.answer?.[`${role}_rating`];
                const remark = question.answer?.[`${role}_remark`];

                let ratingVal = null;
                if (rating !== undefined && rating !== null && rating !== '') {
                    ratingVal = Number(rating);
                    if (isNaN(ratingVal)) {
                        ratingVal = rating;
                    }
                }

                answersList.push({
                    question_id: question.id,
                    rating: ratingVal,
                    remark: remark !== undefined && remark !== null ? String(remark).trim() : null,
                });
            });
        });

        const originalText = answerSaveDraft.textContent;
        answerSaveDraft.disabled = true;
        answerSaveDraft.textContent = 'Saving...';

        try {
            const response = await fetch(urlFromTemplate(root.dataset.saveDraftUrlTemplate, answerFormData.id), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ answers: answersList }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to save draft.');
            }

            alertSuccess(payload.message || 'Draft saved successfully.');
            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            renderMyAppraisals();
        } catch (error) {
            alertError(error.message || 'Unable to save draft.');
        } finally {
            if (answerSaveDraft) {
                answerSaveDraft.disabled = false;
                answerSaveDraft.textContent = originalText;
            }
        }
    };
    const submitAppraisalAnswers = async () => {
        if (!answerFormData || !answerSubmit) {
            return;
        }

        if (answerFormData.is_submitted) {
            alertError('Your appraisal has already been submitted and can no longer be edited.');
            return;
        }

        persistVisibleAnswerValues();

        const role = answerFormData.role;
        const answersList = [];

        let allCompleted = true;
        (answerFormData.categories || []).forEach((category) => {
            (category.questions || []).forEach((question) => {
                const rating = question.answer?.[`${role}_rating`];
                const remark = question.answer?.[`${role}_remark`];

                if (!isQuestionCompleted(question, role)) {
                    allCompleted = false;
                }

                answersList.push({
                    question_id: question.id,
                    rating: rating === '' ? null : Number(rating),
                    remark: String(remark || '').trim(),
                });
            });
        });

        if (!allCompleted) {
            alertError('Please complete all questions before submitting.');
            return;
        }

        const originalText = answerSubmit.textContent;
        answerSubmit.disabled = true;
        answerSubmit.textContent = 'Submitting...';

        try {
            const response = await fetch(urlFromTemplate(root.dataset.submitAnswersUrlTemplate, answerFormData.id), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ answers: answersList }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to submit answers.');
            }

            alertSuccess(payload.message || 'Appraisal answers submitted successfully.');
            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            closeAnswerModal();
            renderMyAppraisals();
        } catch (error) {
            alertError(error.message || 'Unable to submit answers.');
        } finally {
            if (answerSubmit) {
                answerSubmit.disabled = false;
                answerSubmit.textContent = originalText;
            }
        }
    };

    const isQuestionCompleted = (question, role) => {
        const rating = question.answer?.[`${role}_rating`];
        const remark = question.answer?.[`${role}_remark`];

        if (rating === undefined || rating === null || rating === '') {
            return false;
        }
        const numericRating = Number(rating);
        if (isNaN(numericRating) || numericRating < 0.1 || numericRating > 5.0) {
            return false;
        }

        if (Number(numericRating.toFixed(1)) !== numericRating) {
            return false;
        }

        if (remark === undefined || remark === null || String(remark).trim() === '') {
            return false;
        }

        return true;
    };

    const updateAnswerProgress = () => {
        if (!answerFormData) {
            return;
        }

        renderAnswerCategories();

        const role = answerFormData.role;
        let allCompleted = true;
        let totalQuestions = 0;
        let completedQuestions = 0;

        (answerFormData.categories || []).forEach((category) => {
            (category.questions || []).forEach((question) => {
                totalQuestions++;
                if (isQuestionCompleted(question, role)) {
                    completedQuestions++;
                } else {
                    allCompleted = false;
                }
            });
        });

        const percentage = totalQuestions > 0 ? Math.round((completedQuestions / totalQuestions) * 100) : 0;

        if (overallCount) {
            overallCount.textContent = `${completedQuestions} / ${totalQuestions} Questions`;
        }
        if (overallPercentage) {
            overallPercentage.textContent = `${percentage}%`;
        }
        if (overallBar) {
            overallBar.style.width = `${percentage}%`;
        }

        if (answerFormData.is_submitted) {
            if (answerSaveDraft) {
                answerSaveDraft.classList.add('hidden');
            }
            if (answerSubmit) {
                answerSubmit.classList.add('hidden');
            }
            if (answerHelperMessage) {
                answerHelperMessage.classList.add('hidden');
            }
        } else {
            if (answerSaveDraft) {
                answerSaveDraft.classList.remove('hidden');
            }
            if (answerSubmit) {
                answerSubmit.classList.remove('hidden');
                if (allCompleted && totalQuestions > 0) {
                    answerSubmit.disabled = false;
                    answerSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    answerSubmit.disabled = true;
                    answerSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }

            if (answerHelperMessage) {
                if (allCompleted && totalQuestions > 0) {
                    answerHelperMessage.classList.add('hidden');
                } else {
                    answerHelperMessage.classList.remove('hidden');
                    answerHelperMessage.textContent = 'All questions must be answered before submitting. You can save your progress as a draft anytime.';
                }
            }
        }
    };

    const answerValue = (question, field) => question.answer?.[field] ?? '';

    const answerSectionMarkup = (label, ratingField, remarkField, question, editable = false) => {
        const readonlyAttr = editable ? '' : 'readonly';
        const readonlyClasses = editable ? '' : 'bg-bgray-100 dark:bg-darkblack-400 cursor-default';

        return `
            <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 dark:border-darkblack-400 dark:bg-darkblack-600">
                <p class="text-xs font-bold text-bgray-900 dark:text-white uppercase tracking-[0.08em]">${label}</p>
                <div class="mt-1.5 grid gap-2 md:grid-cols-[120px_1fr]">
                    <input type="number" min="0" max="5" step="0.5" placeholder="Rating" value="${escapeHtml(answerValue(question, ratingField))}" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 disabled:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-400 ${readonlyClasses}" data-appraisal-answer-input data-question-id="${escapeHtml(question.id)}" data-answer-field="${escapeHtml(ratingField)}" ${readonlyAttr}>
                    <textarea rows="1" placeholder="Remark" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 disabled:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-400 ${readonlyClasses}" data-appraisal-answer-input data-question-id="${escapeHtml(question.id)}" data-answer-field="${escapeHtml(remarkField)}" ${readonlyAttr}>${escapeHtml(answerValue(question, remarkField))}</textarea>
                </div>
            </div>
        `;
    };

    const persistVisibleAnswerValues = () => {
        if (!answerFormData || !answerQuestions) {
            return;
        }

        answerQuestions.querySelectorAll('[data-appraisal-answer-input]:not([readonly]):not(:disabled)').forEach((input) => {
            const questionId = Number(input.dataset.questionId);
            const field = input.dataset.answerField;
            const question = answerFormData.categories
                ?.flatMap((category) => category.questions || [])
                .find((item) => Number(item.id) === questionId);

            if (question && field) {
                question.answer = {
                    ...(question.answer || {}),
                    [field]: input.value,
                };
            }
        });
    };

    const renderAnswerCategories = () => {
        if (!answerCategories || !answerFormData) {
            return;
        }

        answerCategories.innerHTML = (answerFormData.categories || []).map((category) => {
            const isActive = Number(category.id) === Number(activeAnswerCategoryId);
            
            const totalQuestions = (category.questions || []).length;
            const answeredCount = (category.questions || []).filter(q => isQuestionCompleted(q, answerFormData.role)).length;
            const isCompleted = answeredCount === totalQuestions;

            const classes = isActive
                ? 'border-success-300 bg-success-50 text-success-500 dark:border-success-900/50 dark:bg-darkblack-600 dark:text-success-300'
                : 'border-bgray-200 bg-white text-bgray-700 hover:border-success-200 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50';

            const progressText = isCompleted
                ? `✓ ${answeredCount} / ${totalQuestions} Completed`
                : `${answeredCount} / ${totalQuestions} Questions`;

            const textClasses = isCompleted ? 'text-success-500 dark:text-success-300 font-bold' : 'opacity-80';

            return `
                <button type="button" class="w-full rounded-lg border px-3 py-3 text-left transition ${classes}" data-appraisal-answer-category-id="${escapeHtml(category.id)}">
                    <span class="block text-sm font-bold">${escapeHtml(category.name)}</span>
                    <span class="mt-1 block text-xs font-medium ${textClasses}">${progressText}</span>
                </button>
            `;
        }).join('');
    };

    const renderAnswerQuestions = () => {
        if (!answerFormData || !answerQuestions) {
            return;
        }

        const category = (answerFormData.categories || []).find((item) => Number(item.id) === Number(activeAnswerCategoryId));

        if (!category) {
            if (answerCategoryTitle) {
                answerCategoryTitle.textContent = '';
            }

            answerQuestions.innerHTML = '<div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-300">No questions found.</div>';
            return;
        }

        if (answerCategoryTitle) {
            answerCategoryTitle.textContent = category.name;
        }

        answerQuestions.innerHTML = (category.questions || []).map((question, index) => {
            const sections = [];

            const isEditable = !answerFormData.is_submitted;
            const isAssigneeRole = answerFormData.role === 'assignee' && isEditable;
            const isReporterRole = answerFormData.role === 'reporter' && isEditable;
            const isManagerRole = answerFormData.role === 'manager' && isEditable;

            sections.push(answerSectionMarkup(
                isAssigneeRole ? 'Self' : 'Assignee',
                'assignee_rating',
                'assignee_remark',
                question,
                isAssigneeRole
            ));

            sections.push(answerSectionMarkup(
                'Reporter',
                'reporter_rating',
                'reporter_remark',
                question,
                isReporterRole
            ));

            sections.push(answerSectionMarkup(
                'Manager',
                'manager_rating',
                'manager_remark',
                question,
                isManagerRole
            ));

            return `
                <article class="rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 overflow-hidden" data-appraisal-answer-question-card>
                    <header class="flex items-center justify-between gap-3 p-4 cursor-pointer hover:bg-bgray-50 dark:hover:bg-darkblack-600 transition animate-fade-in" data-appraisal-answer-question-header>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300">${index + 1}</span>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(question.question)}</p>
                        </div>
                        <button type="button" class="text-bgray-500 hover:text-bgray-800 dark:text-bgray-400 dark:hover:text-white focus:outline-none transition-transform duration-200" aria-label="Toggle answer body" data-appraisal-answer-question-toggle>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform duration-200 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </header>
                    <div class="border-t border-bgray-100 dark:border-darkblack-400 p-4 space-y-3 transition-all duration-200" data-appraisal-answer-question-body>
                        ${sections.join('')}
                    </div>
                </article>
            `;
        }).join('');
    };

    const renderAnswerModal = () => {
        if (!answerFormData) {
            return;
        }

        if (answerModalTitle) {
            answerModalTitle.textContent = answerFormData.period ? `Answer Appraisal • ${answerFormData.period}` : 'Answer Appraisal';
        }

        if (answerMeta) {
            answerMeta.textContent = `${answerFormData.role_label || 'Reviewer'} • ${answerFormData.assignee?.name || 'Assignee'}`;
        }

        if (answerKpiTitle) {
            answerKpiTitle.textContent = answerFormData.kpi_name || 'Untitled KPI';
        }

        if (answerKpiDescription) {
            answerKpiDescription.innerHTML = normalizeHtml(answerFormData.kpi_description || '') || '<p>--</p>';
        }

        activeAnswerCategoryId = answerFormData.categories?.[0]?.id || null;
        renderAnswerCategories();
        renderAnswerQuestions();
        updateAnswerProgress();
    };

    const openAnswerModal = async (appraisalId) => {
        if (!appraisalId) {
            alertError('Appraisal not found.');
            return;
        }

        try {
            const response = await fetch(urlFromTemplate(root.dataset.answerFormUrlTemplate, appraisalId), {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to load appraisal.');
            }

            answerFormData = payload.data;
            renderAnswerModal();
            answerModal?.classList.remove('hidden');
            answerModal?.classList.add('flex');
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal.');
        }
    };

    const closeAnswerModal = () => {
        answerModal?.classList.add('hidden');
        answerModal?.classList.remove('flex');
        answerFormData = null;
        activeAnswerCategoryId = null;

        if (answerModalTitle) {
            answerModalTitle.textContent = 'Answer Appraisal';
        }

        if (answerMeta) {
            answerMeta.textContent = '';
        }

        if (answerKpiTitle) {
            answerKpiTitle.textContent = '';
        }

        if (answerKpiDescription) {
            answerKpiDescription.innerHTML = '';
        }

        if (answerCategoryTitle) {
            answerCategoryTitle.textContent = '';
        }

        if (answerQuestions) {
            answerQuestions.innerHTML = '';
        }

        if (answerCategories) {
            answerCategories.innerHTML = '';
        }
    };

    const setActiveTab = (tab) => {
        activeTab = tab;
        localStorage.setItem('appraisal_active_tab', tab);

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
        if (!root.dataset.assignmentUrl) {
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

            assignmentData = payload.data || { my_appraisals: [], users: [], kpis: [], categories: [] };
            selectedUserIds.clear();
            renderMyAppraisals();

            if (activeTab === 'assign') {
                renderUsers();
            }
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal assignments.');
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
            return;
        }

        if (event.target.matches('[data-appraisal-kpi-select]')) {
            const selectedKpi = (assignmentData.kpis || []).find((kpi) => Number(kpi.id) === Number(event.target.value));

            setKpiDescription(selectedKpi?.description || '');
            return;
        }

        if (event.target.matches('[data-appraisal-kpi-agreement-checkbox]')) {
            if (kpiAgreementSubmit) {
                kpiAgreementSubmit.disabled = !event.target.checked;
            }
            return;
        }

        if (event.target.matches('[data-appraisal-answer-input]')) {
            const input = event.target;
            const questionId = Number(input.dataset.questionId);
            const field = input.dataset.answerField;
            const question = answerFormData?.categories
                ?.flatMap((category) => category.questions || [])
                .find((item) => Number(item.id) === questionId);

            if (question && field) {
                question.answer = {
                    ...(question.answer || {}),
                    [field]: input.value,
                };
                updateAnswerProgress();
            }
        }
    });

    root.addEventListener('input', (event) => {
        if (event.target.matches('[data-appraisal-user-search]')) {
            renderUsers();
            return;
        }

        if (event.target.matches('[data-appraisal-answer-input]')) {
            const input = event.target;
            const questionId = Number(input.dataset.questionId);
            const field = input.dataset.answerField;
            const question = answerFormData?.categories
                ?.flatMap((category) => category.questions || [])
                .find((item) => Number(item.id) === questionId);

            if (question && field) {
                question.answer = {
                    ...(question.answer || {}),
                    [field]: input.value,
                };
                updateAnswerProgress();
            }
        }
    });

    root.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-appraisal-tab-button]');

        if (tabButton) {
            setActiveTab(tabButton.dataset.tab);
            return;
        }

        if (event.target.closest('[data-appraisal-open-assign]')) {
            openAssignModal();
            return;
        }

        if (event.target.closest('[data-appraisal-publish-selected]')) {
            publishAppraisals(selectedDraftUserIds());
            return;
        }

        const agreeKpiButton = event.target.closest('[data-appraisal-kpi-agree]');

        if (agreeKpiButton) {
            openKpiAgreementModal(Number(agreeKpiButton.dataset.appraisalId));
            return;
        }

        const answerButton = event.target.closest('[data-appraisal-answer-placeholder]');

        if (answerButton) {
            openAnswerModal(Number(answerButton.dataset.appraisalId));
            return;
        }

        if (event.target.closest('[data-appraisal-kpi-agreement-close]')) {
            closeKpiAgreementModal();
            return;
        }

        if (event.target.closest('[data-appraisal-kpi-agreement-submit]')) {
            submitKpiAgreement();
            return;
        }

        if (event.target.closest('[data-appraisal-answer-close]')) {
            closeAnswerModal();
            return;
        }

        if (event.target.closest('[data-appraisal-answer-submit]')) {
            submitAppraisalAnswers();
            return;
        }

        if (event.target.closest('[data-appraisal-answer-save-draft]')) {
            saveAppraisalDraft();
            return;
        }

        const answerCategoryButton = event.target.closest('[data-appraisal-answer-category-id]');

        if (answerCategoryButton) {
            persistVisibleAnswerValues();
            activeAnswerCategoryId = Number(answerCategoryButton.dataset.appraisalAnswerCategoryId);
            renderAnswerCategories();
            renderAnswerQuestions();
            return;
        }

        const questionHeader = event.target.closest('[data-appraisal-answer-question-header]');

        if (questionHeader) {
            const card = questionHeader.closest('[data-appraisal-answer-question-card]');
            const body = card?.querySelector('[data-appraisal-answer-question-body]');
            const svg = card?.querySelector('[data-appraisal-answer-question-toggle] svg');

            if (body && svg) {
                const isHidden = body.classList.toggle('hidden');
                svg.classList.toggle('rotate-180', !isHidden);
            }
            return;
        }

        const rowAction = event.target.closest('[data-appraisal-row-action]');

        if (rowAction) {
            const userId = Number(rowAction.dataset.userId);
            const appraisalId = Number(rowAction.dataset.appraisalId);
            const user = (assignmentData.users || []).find((item) => Number(item.id) === userId);

            if (rowAction.dataset.appraisalRowAction === 'publish') {
                publishAppraisals([userId]);
                return;
            }

            if (rowAction.dataset.appraisalRowAction === 'view') {
                openViewModal(appraisalId);
                return;
            }

            if (rowAction.dataset.appraisalRowAction === 'unpublish') {
                unpublishAppraisal(appraisalId);
                return;
            }

            if (rowAction.dataset.appraisalRowAction === 'edit') {
                openEditModal(appraisalId || user?.appraisal_id);
                return;
            }

            if (user?.is_editable === false) {
                alertError('Only draft appraisals can be updated.');
                return;
            }

            selectedUserIds = new Set([userId]);
            renderUsers();
            openAssignModal();
            return;
        }

        if (event.target.closest('[data-appraisal-modal-close]')) {
            closeAssignModal();
            return;
        }

        if (event.target.closest('[data-appraisal-assignment-category-add]')) {
            if (modalReadOnly) {
                return;
            }

            if (modalCategories?.querySelector('[data-appraisal-modal-empty]')) {
                modalCategories.innerHTML = '';
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = categoryMarkup({ name: '', questions: [{ question: '' }] }, false);
            const categoryCard = wrapper.firstElementChild;

            if (categoryCard && modalCategories) {
                modalCategories.appendChild(categoryCard);
                refreshQuestionNumbers(categoryCard);
                refreshCategoryControls();
                categoryCard.querySelector('[data-appraisal-assignment-category-name]')?.focus();
            }

            return;
        }

        const removeCategory = event.target.closest('[data-appraisal-assignment-category-remove]');

        if (removeCategory) {
            if (modalReadOnly) {
                return;
            }

            const categories = modalCategories?.querySelectorAll('[data-appraisal-assignment-category]') || [];

            if (categories.length <= 1) {
                alertError('At least one category is required.');
                return;
            }

            removeCategory.closest('[data-appraisal-assignment-category]')?.remove();
            refreshCategoryControls();
            updateTemplateAddButtons();
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

        const templateToggle = event.target.closest('[data-appraisal-template-toggle]');
        if (templateToggle) {
            const item = templateToggle.closest('[data-appraisal-template-item]');
            const body = item?.querySelector('[data-appraisal-template-questions-body]');
            const svg = templateToggle.querySelector('svg');
            if (body) {
                const isHidden = body.classList.toggle('hidden');
                svg?.classList.toggle('rotate-90', !isHidden);
                templateToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
            }
            return;
        }

        const templateAdd = event.target.closest('[data-appraisal-template-add]');
        if (templateAdd) {
            if (modalReadOnly) {
                return;
            }

            const item = templateAdd.closest('[data-appraisal-template-item]');
            const name = item?.dataset.appraisalTemplateName;
            if (!name) return;

            const template = (assignmentData.categories || []).find(cat => cat.name === name);
            if (!template) return;

            const alreadyAdded = modalCategories?.querySelector(`[data-appraisal-template-source="${escapeHtml(name)}"]`);
            if (alreadyAdded) {
                alertError('This category template has already been added.');
                return;
            }

            if (modalCategories) {
                const emptyMessage = modalCategories.querySelector('[data-appraisal-modal-empty]');
                if (emptyMessage) {
                    modalCategories.innerHTML = '';
                }

                const wrapper = document.createElement('div');
                wrapper.innerHTML = categoryMarkup(template, false);
                const categoryCard = wrapper.firstElementChild;

                if (categoryCard) {
                    modalCategories.appendChild(categoryCard);
                    refreshQuestionNumbers(categoryCard);
                    refreshCategoryControls();
                    updateTemplateAddButtons();
                }
            }
            return;
        }

        const addQuestion = event.target.closest('[data-appraisal-assignment-question-add]');

        if (addQuestion) {
            if (modalReadOnly) {
                return;
            }

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
            if (modalReadOnly) {
                return;
            }

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
            if (modalReadOnly) {
                return;
            }

            submitAssignments(submitButton.dataset.appraisalSubmit);
        }
    });

    root.addEventListener('mousedown', (event) => {
        if (modalReadOnly) {
            return;
        }

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
    renderMyAppraisals();
    renderUsers();
    renderKpis();
    initializeKpiDescriptionEditor();

    const savedTab = localStorage.getItem('appraisal_active_tab');
    const initialTab = (savedTab && root.querySelector(`[data-appraisal-tab-button][data-tab="${savedTab}"]`)) ? savedTab : 'my';
    setActiveTab(initialTab);
});
