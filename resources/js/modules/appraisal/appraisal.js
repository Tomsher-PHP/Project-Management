import { initTomSelect } from '../../components/tom-select';

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
    const modalPanel = root.querySelector('[data-appraisal-modal-panel]');
    const modalHeader = root.querySelector('[data-appraisal-modal-header]');
    const modalTitle = root.querySelector('[data-appraisal-modal-title]');
    const modalSubtitle = root.querySelector('[data-appraisal-modal-subtitle]');
    const modalSelectedCount = root.querySelector('[data-appraisal-modal-selected-count]');
    const modalSelectedUsers = root.querySelector('[data-appraisal-modal-selected-users]');
    const modalSelectedUsersSummary = root.querySelector('[data-appraisal-selected-users-summary]');
    const kpiSelect = root.querySelector('[data-appraisal-kpi-select]');
    const kpiDescription = root.querySelector('[data-appraisal-kpi-description]');
    const modalCategories = root.querySelector('[data-appraisal-modal-categories]');
    const addCategoryButton = root.querySelector('[data-appraisal-assignment-category-add]');
    const assignmentSteps = root.querySelectorAll('[data-appraisal-assignment-step]');
    const assignmentFooters = root.querySelectorAll('[data-appraisal-assignment-footer]');
    const assignmentContinueButton = root.querySelector('[data-appraisal-assignment-continue]');
    const reviewerAssignmentsContainer = root.querySelector('[data-appraisal-reviewer-assignments]');
    const reviewerNextButton = root.querySelector('[data-appraisal-reviewers-next]');
    const reviewerSubmitButtons = root.querySelectorAll('[data-appraisal-reviewers-submit]');
    const kpiAgreementModal = root.querySelector('[data-appraisal-kpi-agreement-modal]');
    const kpiAgreementTitle = root.querySelector('[data-appraisal-kpi-agreement-title]');
    const kpiAgreementDescription = root.querySelector('[data-appraisal-kpi-agreement-description]');
    const kpiAgreementCheckbox = root.querySelector('[data-appraisal-kpi-agreement-checkbox]');
    const kpiAgreementSubmit = root.querySelector('[data-appraisal-kpi-agreement-submit]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const initialDataNode = root.querySelector('[data-appraisal-initial-data]');
    const authUserId = Number(root.dataset.authUserId || 0);

    let assignmentData = { my_appraisals: [], users: [], kpis: [], categories: [] };
    let activeTab = 'my';
    let selectedUserIds = new Set();
    let draggedQuestionItem = null;
    let dragHandleItem = null;
    let kpiDescriptionEditor = null;
    let modalReadOnly = false;
    let activeAssignmentStep = 1;
    let reviewerAssignmentData = [];
    let kpiAgreementAppraisalId = null;

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const defaultQuestionType = () => assignmentData.default_question_type || Object.keys(assignmentData.question_types || {})[0] || '';
    const targetQuestionType = () => assignmentData.target_question_type || '';
    const questionTypeLabel = (value) => assignmentData.question_types?.[value] || value;
    const selectOptionsMarkup = (options, selectedValue, placeholder = '') => {
        const placeholderOption = placeholder
            ? `<option value="">${escapeHtml(placeholder)}</option>`
            : '';

        return placeholderOption + Object.entries(options || {})
            .map(([value, label]) => `<option value="${escapeHtml(value)}" ${String(value) === String(selectedValue) ? 'selected' : ''}>${escapeHtml(label)}</option>`)
            .join('');
    };
    const unitOptionsMarkup = (selectedValue = '') => {
        const selectedUnit = String(selectedValue || '');
        const units = [...(assignmentData.question_units || [])];

        if (selectedUnit && !units.some((unit) => String(unit) === selectedUnit)) {
            units.push(selectedUnit);
        }

        return '<option value="">Select unit</option>' + units
            .map((unit) => `<option value="${escapeHtml(unit)}" ${String(unit) === selectedUnit ? 'selected' : ''}>${escapeHtml(unit)}</option>`)
            .join('');
    };

    const alertSuccess = (message) => window.Alert?.success ? window.Alert.success(message) : window.alert(message);
    const alertError = (message) => window.Alert?.error ? window.Alert.error(message) : window.alert(message);
    const confirmAction = async (options) => {
        if (window.Alert?.confirm) {
            const result = await window.Alert.confirm(options);

            return result.isConfirmed;
        }

        return window.confirm(options.text || options.title || 'Are you sure?');
    };

    const showAssignmentStep = (step = 1) => {
        activeAssignmentStep = step;

        assignmentSteps.forEach((panel) => {
            panel.classList.toggle('hidden', Number(panel.dataset.appraisalAssignmentStep) !== step);
        });

        assignmentFooters.forEach((footer) => {
            const isActive = Number(footer.dataset.appraisalAssignmentFooter) === step;
            footer.classList.toggle('hidden', !isActive);
            footer.classList.toggle('flex', isActive);
        });
    };

    const destroyReviewerSelects = (rootElement = reviewerAssignmentsContainer) => {
        rootElement?.querySelectorAll('select.tom-select-no-search').forEach((select) => {
            select.tomselect?.destroy();
        });
    };

    const reviewerLevelMarkup = (assignment, levelIndex, selectedReviewerId = '', readOnly = modalReadOnly) => {
        const savedReviewer = assignment.reviewers?.find((item) => Number(item.level) === levelIndex + 1);
        const selectedId = Number(selectedReviewerId || savedReviewer?.reviewer_user_id || 0);

        if (readOnly) {
            return `
                <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-reviewer-level data-level="${levelIndex + 1}">
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-600 dark:text-bgray-300">Reviewer Level ${levelIndex + 1}</p>
                    <p class="mt-2 text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(savedReviewer?.name || 'Not Assigned')}</p>
                    <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">${escapeHtml(savedReviewer?.email || '')}</p>
                </div>
            `;
        }

        return `
            <div class="rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-reviewer-level data-level="${levelIndex + 1}">
                <div class="flex flex-wrap items-end gap-3">
                    <label class="min-w-0 flex-1">
                        <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Reviewer Level ${levelIndex + 1} <span class="text-red-500">*</span></span>
                        <select class="tom-select-no-search w-full" data-appraisal-reviewer-select>
                            <option value="">Select reviewer</option>
                            ${(assignment.available_reviewers || []).map((reviewer) => `
                                <option value="${escapeHtml(reviewer.id)}" ${Number(reviewer.id) === selectedId ? 'selected' : ''}>
                                    ${escapeHtml(reviewer.name)}${reviewer.email ? ` (${escapeHtml(reviewer.email)})` : ''}
                                </option>
                            `).join('')}
                        </select>
                    </label>
                    ${levelIndex > 0 ? '<button type="button" class="rounded-lg border border-red-200 bg-error-50 px-3 py-2.5 text-xs font-semibold text-error-300 transition hover:text-red-500 dark:border-darkblack-400" data-appraisal-reviewer-level-remove>Remove Level</button>' : ''}
                </div>
            </div>
        `;
    };

    const syncReviewerSelectOptions = (card) => {
        if (!card || modalReadOnly) {
            return;
        }

        const assignment = reviewerAssignmentData.find(
            (item) => Number(item.user?.id) === Number(card.dataset.userId)
        );
        const selects = Array.from(card.querySelectorAll('[data-appraisal-reviewer-select]'));
        const selectedIds = selects
            .map((select) => Number(select.tomselect?.getValue() ?? select.value ?? 0))
            .filter((id) => id > 0);

        selects.forEach((select) => {
            const instance = select.tomselect;

            if (!instance) {
                return;
            }

            const currentValue = Number(instance.getValue() || 0);
            const unavailableIds = new Set(selectedIds.filter((id) => id !== currentValue));

            instance.clearOptions();
            instance.addOption(
                (assignment?.available_reviewers || [])
                    .filter((reviewer) => !unavailableIds.has(Number(reviewer.id)))
                    .map((reviewer) => ({
                        value: String(reviewer.id),
                        text: reviewer.email
                            ? `${reviewer.name} (${reviewer.email})`
                            : reviewer.name,
                    }))
            );

            if (currentValue) {
                instance.setValue(String(currentValue), true);
            }

            instance.refreshOptions(false);
        });
    };

    const updateReviewerCardControls = (card) => {
        if (!card || modalReadOnly) {
            return;
        }

        const assignment = reviewerAssignmentData.find(
            (item) => Number(item.user?.id) === Number(card.dataset.userId)
        );
        const levels = Array.from(card.querySelectorAll('[data-appraisal-reviewer-level]'));
        const addButton = card.querySelector('[data-appraisal-reviewer-level-add]');
        const lastSelect = levels.at(-1)?.querySelector('[data-appraisal-reviewer-select]');
        const lastValue = lastSelect?.tomselect?.getValue() ?? lastSelect?.value ?? '';
        const allReviewersUsed = levels.length >= (assignment?.available_reviewers?.length || 0);

        if (addButton) {
            addButton.classList.toggle('hidden', allReviewersUsed);
            addButton.disabled = allReviewersUsed || String(lastValue).trim() === '';
        }

        levels.forEach((level, index) => {
            level.querySelector('[data-appraisal-reviewer-level-remove]')?.classList.toggle(
                'hidden',
                index !== levels.length - 1
            );
        });
    };

    const renderReviewerAssignments = (readOnly = modalReadOnly) => {
        if (!reviewerAssignmentsContainer) {
            return;
        }

        destroyReviewerSelects();

        if (!reviewerAssignmentData.length) {
            reviewerAssignmentsContainer.innerHTML = '<div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">No reviewer assignments are available.</div>';
            return;
        }

        reviewerAssignmentsContainer.innerHTML = reviewerAssignmentData.map((assignment) => {
            const savedReviewers = [...(assignment.reviewers || [])].sort((a, b) => Number(a.level) - Number(b.level));
            const levelCount = readOnly
                ? savedReviewers.length
                : Math.max(1, savedReviewers.length);
            const levels = Array.from({ length: levelCount }, (_, index) => reviewerLevelMarkup(
                assignment,
                index,
                savedReviewers[index]?.reviewer_user_id,
                readOnly
            )).join('');
            const noChain = !readOnly && !(assignment.available_reviewers || []).length;
            const reviewerLevels = readOnly && !savedReviewers.length
                ? '<p class="rounded-lg border border-dashed border-bgray-200 px-4 py-6 text-sm text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">No reviewers assigned.</p>'
                : `<div class="space-y-3" data-appraisal-reviewer-levels>${levels}</div>`;

            return `
                <article class="rounded-xl border border-bgray-200 bg-bgray-50 p-5 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-reviewer-card data-user-id="${escapeHtml(assignment.user?.id || '')}">
                    <div class="mb-4">
                        <h5 class="text-base font-bold text-bgray-900 dark:text-white">${escapeHtml(assignment.user?.name || 'Employee')}</h5>
                        <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">${escapeHtml(assignment.user?.email || '')}</p>
                    </div>
                    ${noChain
                    ? '<p class="rounded-lg border border-dashed border-bgray-200 px-4 py-6 text-sm text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300">No reporting hierarchy is available for this employee.</p>'
                    : reviewerLevels
                }
                    ${!readOnly && !noChain
                    ? '<button type="button" class="mt-3 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-600 dark:text-success-300" data-appraisal-reviewer-level-add>Add Level</button>'
                    : ''
                }
                </article>
            `;
        }).join('');

        if (!readOnly) {
            reviewerAssignmentsContainer.querySelectorAll('[data-appraisal-reviewer-card]').forEach((card) => {
                initTomSelect(card);
                syncReviewerSelectOptions(card);
                updateReviewerCardControls(card);
            });
        }
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
        if (user.avatar_html) {
            return user.avatar_html;
        }

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
            draft: 'bg-success-100 text-success-400 dark:bg-success-900/30 dark:text-success-300',
            published: 'bg-warning-100 text-warning-600 dark:bg-warning-900/30 dark:text-warning-300',
            completed: 'bg-primary-new text-bgray-900 dark:bg-primary-new dark:text-bgray-900',
            closed: 'bg-bgray-100 text-bgray-900 dark:bg-darkblack-500 dark:text-bgray-300',
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
            return '<span class="text-sm font-medium text-bgray-600 dark:text-bgray-300">--</span>';
        }

        return categories.map((category) => `
            <span class="inline-flex rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">${escapeHtml(category)}</span>
        `).join('');
    };

    const actionButton = (user) => {
        const action = !user.is_assigned ? 'assign' : (user.status === 'draft' ? 'edit' : 'view');

        if (action === 'edit') {
            return `
                <div class="flex items-center gap-1.5 flex-wrap md:flex-nowrap whitespace-nowrap">
                    <button type="button" class="rounded-lg bg-success-300 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-success-400" data-appraisal-row-action="edit" data-user-id="${escapeHtml(user.id)}" data-appraisal-id="${escapeHtml(user.appraisal_id || '')}">Edit</button>
                    <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-2.5 py-1.5 text-xs font-semibold text-success-400 transition hover:border-success-300 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-row-action="publish" data-user-id="${escapeHtml(user.id)}">Publish</button>
                </div>
            `;
        }

        if (user.status === 'published') {
            return `
                <div class="flex items-center gap-1.5 flex-wrap md:flex-nowrap whitespace-nowrap">
                    <button type="button" class="rounded-lg border border-bgray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-row-action="view" data-appraisal-id="${escapeHtml(user.appraisal_id)}">View</button>
                    <button type="button" class="rounded-lg border border-warning-200 bg-warning-50 px-2.5 py-1.5 text-xs font-semibold text-warning-600 transition hover:border-warning-300 dark:border-warning-900/40 dark:bg-darkblack-500 dark:text-warning-300" data-appraisal-row-action="unpublish" data-appraisal-id="${escapeHtml(user.appraisal_id)}">Unpublish</button>
                </div>
            `;
        }

        const label = action.charAt(0).toUpperCase() + action.slice(1);
        const classes = action === 'view'
            ? 'border border-bgray-200 bg-white text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50'
            : 'bg-success-300 text-white hover:bg-success-400';

        return `
            <div class="flex items-center gap-1.5 flex-wrap md:flex-nowrap whitespace-nowrap">
                <button type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition ${classes}" data-appraisal-row-action="${action}" data-user-id="${escapeHtml(user.id)}" data-appraisal-id="${escapeHtml(user.appraisal_id || '')}">${label}</button>
            </div>
        `;
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
            const reviewSummary = (prefix) => {
                const submittedAt = row[`${prefix}_submitted_at`];
                const rating = row[`${prefix}_average_rating`];
                const submittedById = Number(row[`${prefix}_submitted_by_id`] || 0);
                const submittedByName = row[`${prefix}_submitted_by_name`];

                if (!submittedAt) {
                    return `
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-bgray-900 dark:text-white">--</p>
                            <p class="text-xs font-medium text-bgray-600 dark:text-bgray-300">--</p>
                            <p class="text-xs font-medium text-bgray-600 dark:text-bgray-300">--</p>
                        </div>
                    `;
                }

                const reviewerName = submittedById === authUserId ? 'You' : (submittedByName || '--');
                const averageRating = rating !== null && rating !== undefined && rating !== ''
                    ? Number(rating).toFixed(2)
                    : '--';

                return `
                    <div class="space-y-1">
                        <p class="text-sm font-bold text-bgray-900 dark:text-white">${escapeHtml(averageRating)}</p>
                        <p class="text-xs font-medium text-bgray-700 dark:text-bgray-200">${escapeHtml(reviewerName)}</p>
                        <p class="text-xs font-medium text-bgray-600 dark:text-bgray-300">${escapeHtml(submittedAt)}</p>
                    </div>
                `;
            };
            const status = String(row.status || '').toLowerCase();
            const isAssignee = row.is_assignee || Number(user.id) === authUserId;
            const canAgree = row.can_agree || (row.appraisal_id && isAssignee && status === 'published' && !row.kpi_agreed);
            const canAnswer = row.can_answer || (row.appraisal_id && isAssignee && row.kpi_agreed);
            let action = '<span class="text-sm font-medium text-bgray-600 dark:text-bgray-300">--</span>';

            if (canAgree) {
                action = `<button type="button" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400" data-appraisal-kpi-agree data-appraisal-id="${escapeHtml(row.appraisal_id)}">Agree</button>`;
            } else if (canAnswer) {
                const actionLabel = row.can_edit_answer ? 'Answer' : 'View Answer';
                action = `<button type="button" class="rounded-lg bg-success-300 px-3 py-2 text-xs font-semibold text-white transition hover:bg-success-400" data-appraisal-answer-link data-appraisal-id="${escapeHtml(row.appraisal_id)}">${actionLabel}</button>`;
            }

            return `
                <tr class="border-b border-bgray-300 hover:bg-bgray-100 dark:border-darkblack-400 dark:hover:bg-darkblack-500">
                    <td class="px-4 py-4 xl:px-0">
                        <div class="flex items-center gap-3">
                            ${userAvatar(user)}
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(user.name || 'User')}</p>
                                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">${escapeHtml(meta)}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 xl:px-0">${reviewSummary('assignee')}</td>
                    <td class="px-4 py-4 xl:px-0">${reviewSummary('reporter')}</td>
                    <td class="px-4 py-4 xl:px-0">${reviewSummary('manager')}</td>
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
                <tr class="border-b border-bgray-300 hover:bg-bgray-100 dark:border-darkblack-400 dark:hover:bg-darkblack-500 ${disabledClasses}" data-appraisal-user-row data-user-id="${escapeHtml(user.id)}">
                    <td class="w-12 px-4 py-4">
                        <input type="checkbox" value="${escapeHtml(user.id)}" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-user-checkbox ${disabled} ${checked}>
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
                    <td class="px-4 py-4 xl:px-0 text-center">
                        <span class="text-sm font-medium text-bgray-700 dark:text-bgray-50">${user.is_assigned ? escapeHtml(user.questions_count) : '--'}</span>
                    </td>
                    <td class="px-4 py-4 xl:px-0">${statusBadge(user)}</td>
                    <td class="px-4 py-4 xl:px-0">${actionButton(user)}</td>
                </tr>
            `;
        }).join('');

        updateSelectedCount();
    };

    const syncKpiTomSelect = () => {
        if (!kpiSelect) return;

        if (kpiSelect.tomselect) {
            kpiSelect.tomselect.destroy();
        }

        if (modalReadOnly) {
            kpiSelect.disabled = true;
            kpiSelect.classList.add('opacity-70');
            return;
        }

        kpiSelect.disabled = false;
        kpiSelect.classList.remove('opacity-70');

        if (window.TomSelect) {
            const tsInstance = new window.TomSelect(kpiSelect, {
                create: false,
                persist: false,
                hideDropdownArrow: false,
                plugins: ['dropdown_input', 'remove_button'],
                searchField: ['text'],
                dropdownParent: 'body',
            });

            tsInstance.on('change', (value) => {
                const selectedKpi = (assignmentData.kpis || []).find((kpi) => Number(kpi.id) === Number(value));
                setKpiDescription(selectedKpi?.description || '');
            });
        }
    };

    const renderKpis = () => {
        if (!kpiSelect) {
            return;
        }

        if (kpiSelect.tomselect) {
            kpiSelect.tomselect.destroy();
        }

        kpiSelect.innerHTML = '<option value="">Select KPI</option>' + (assignmentData.kpis || [])
            .map((kpi) => `<option value="${escapeHtml(kpi.id)}">${escapeHtml(kpi.name)}</option>`)
            .join('');
    };

    const restoreKpiSelection = (appraisal) => {
        if (!kpiSelect) {
            return;
        }

        const matchedKpiId = Number(appraisal.kpi_id || 0);
        const selectedKpi = (assignmentData.kpis || [])
            .find((kpi) => Number(kpi.id) === matchedKpiId);

        if (selectedKpi) {
            const selectedValue = String(selectedKpi.id);

            kpiSelect.tomselect?.setValue(selectedValue, true);
            kpiSelect.value = selectedValue;
            kpiSelect.tomselect?.refreshItems();
            setKpiDescription(selectedKpi.description || appraisal.kpi_description || '');

            return;
        }

        if (appraisal.kpi_name) {
            const unavailableValue = '__missing';
            const unavailableLabel = `${appraisal.kpi_name} (not available)`;

            if (kpiSelect.tomselect) {
                kpiSelect.tomselect.addOption({
                    value: unavailableValue,
                    text: unavailableLabel,
                    disabled: true,
                });
                kpiSelect.tomselect.setValue(unavailableValue, true);
            } else {
                const unavailableOption = document.createElement('option');
                unavailableOption.value = unavailableValue;
                unavailableOption.textContent = unavailableLabel;
                kpiSelect.appendChild(unavailableOption);
            }

            kpiSelect.value = unavailableValue;
        }

        setKpiDescription(appraisal.kpi_description || '');
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

    const questionRowMarkup = (questionData = '', readOnly = modalReadOnly) => {
        const question = typeof questionData === 'object' ? (questionData.question || '') : questionData;
        const qType = typeof questionData === 'object'
            ? (questionData.question_type || defaultQuestionType())
            : defaultQuestionType();
        const measurementType = typeof questionData === 'object' ? (questionData.measurement_type || '') : '';
        const targetValue = typeof questionData === 'object' ? (questionData.target_value ?? '') : '';
        const unit = typeof questionData === 'object' ? (questionData.unit ?? questionData.unit_name ?? '') : '';
        const isTarget = qType === targetQuestionType();

        const questionControl = readOnly
            ? `
                <div class="flex-1">
                    <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">${escapeHtml(question)}</p>
                    <span class="mt-1 inline-flex items-center rounded-md bg-bgray-100 px-2 py-0.5 text-xs font-medium text-bgray-600 dark:bg-darkblack-400 dark:text-bgray-300">
                        ${escapeHtml(questionTypeLabel(qType))}
                    </span>
                    ${isTarget ? `
                        <div class="mt-3 grid grid-cols-3 divide-x divide-bgray-200 overflow-hidden rounded-lg border border-bgray-200 bg-bgray-50 dark:divide-darkblack-400 dark:border-darkblack-400 dark:bg-darkblack-600">
                            <div class="min-w-0 px-3 py-2">
                                <span class="block truncate text-xs font-semibold text-bgray-600 dark:text-bgray-300">Measurement Type</span>
                                <span class="mt-1 block truncate text-sm font-medium text-bgray-700 dark:text-bgray-300">${escapeHtml(assignmentData.measurement_types?.[measurementType] || measurementType)}</span>
                            </div>
                            <div class="min-w-0 px-3 py-2">
                                <span class="block truncate text-xs font-semibold text-bgray-600 dark:text-bgray-300">Target Value</span>
                                <span class="mt-1 block truncate text-sm font-medium text-bgray-700 dark:text-bgray-300">${escapeHtml(targetValue)}</span>
                            </div>
                            <div class="min-w-0 px-3 py-2">
                                <span class="block truncate text-xs font-semibold text-bgray-600 dark:text-bgray-300">Unit</span>
                                <span class="mt-1 block truncate text-sm font-medium text-bgray-700 dark:text-bgray-300">${escapeHtml(unit)}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
              `
            : `
                <div class="grid min-w-0 flex-1 gap-3 lg:grid-cols-12">
                    <label class="block lg:col-span-9">
                        <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Question</span>
                        <input type="text" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" value="${escapeHtml(question)}" placeholder="Enter an appraisal question" data-appraisal-assignment-question-input>
                    </label>
                    <label class="block lg:col-span-3">
                        <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Question Type</span>
                        <select class="tom-select-no-search w-full" data-appraisal-assignment-question-type-select>
                            ${selectOptionsMarkup(assignmentData.question_types, qType)}
                        </select>
                    </label>
                    <div class="${isTarget ? 'flex' : 'hidden'} flex-col gap-3 md:flex-row lg:col-span-12" data-appraisal-assignment-target-fields>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Measurement Type <span class="text-red-500">*</span></span>
                            <select class="tom-select-no-search w-full" data-appraisal-assignment-measurement-type>
                                ${selectOptionsMarkup(assignmentData.measurement_types, measurementType, 'Select measurement type')}
                            </select>
                        </label>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Target Value <span class="text-red-500">*</span></span>
                            <input type="number" step="any" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" value="${escapeHtml(targetValue)}" placeholder="e.g. 92.5" data-appraisal-assignment-target-value>
                        </label>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-xs font-semibold text-bgray-600 dark:text-bgray-300">Unit <span class="text-red-500">*</span></span>
                            <select class="tom-select w-full" data-appraisal-assignment-unit>
                                ${unitOptionsMarkup(unit)}
                            </select>
                        </label>
                    </div>
                </div>
            `;

        return `
            <div class="rounded-xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-assignment-question data-question-type="${escapeHtml(qType)}">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-start">
                    <div class="flex items-center gap-2 xl:pt-6">
                        ${readOnly ? '' : `
                            <button type="button" class="inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-lg border border-bgray-200 bg-bgray-50 text-bgray-600 transition duration-200 hover:border-success-200 hover:text-success-400 active:cursor-grabbing dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300" data-appraisal-assignment-question-handle aria-label="Drag question">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </button>
                        `}
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300" data-appraisal-assignment-question-number></span>
                    </div>
                    ${questionControl}
                    ${readOnly ? '' : '<button type="button" class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-bgray-200 bg-error-50 text-error-300 transition duration-200 hover:bg-bgray-100 hover:text-red-500 dark:border-darkblack-400 xl:mt-6" data-appraisal-assignment-question-remove aria-label="Remove question">×</button>'}
                </div>
            </div>
        `;
    };

    const setAssignmentTargetFieldsVisibility = (row) => {
        const typeSelect = row?.querySelector('[data-appraisal-assignment-question-type-select]');
        const targetFields = row?.querySelector('[data-appraisal-assignment-target-fields]');
        const isTarget = typeSelect?.value === targetQuestionType();

        if (!targetFields) {
            return;
        }

        row.dataset.questionType = typeSelect?.value || defaultQuestionType();
        targetFields.classList.toggle('hidden', !isTarget);
        targetFields.classList.toggle('flex', isTarget);
    };

    const initializeQuestionRowSelects = (row) => {
        if (!row) {
            return;
        }

        initTomSelect(row);
        setAssignmentTargetFieldsVisibility(row);
    };

    const destroyAssignmentQuestionSelects = (rootElement = modalCategories) => {
        rootElement?.querySelectorAll('select.tom-select, select.tom-select-no-search').forEach((select) => {
            select.tomselect?.destroy();
        });
    };

    const categoryMarkup = (category = {}, readOnly = modalReadOnly) => {
        const questions = category.questions || [];
        const categoryName = category.name || '';
        const categoryTitle = escapeHtml(categoryName || 'New Category');
        const categoryNameControl = readOnly
            ? `<span class="text-base font-semibold text-bgray-900 dark:text-white">${categoryTitle}</span>`
            : `<input type="text" class="min-w-[220px] flex-1 rounded-lg border border-gray-300 p-2.5 text-sm font-semibold text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white" value="${escapeHtml(categoryName)}" placeholder="Category name" data-appraisal-assignment-category-name>`;
        const questionCount = readOnly
            ? `<span class="text-xs font-semibold text-bgray-600 dark:text-bgray-300">(${questions.length} ${questions.length === 1 ? 'Question' : 'Questions'})</span>`
            : '';
        const actionButtons = readOnly
            ? ''
            : `
                <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-xs font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-600 dark:text-success-300" data-appraisal-assignment-question-add>Add Question</button>
                <button type="button" class="rounded-lg border border-red-200 bg-error-50 px-3 py-2 text-xs font-semibold text-error-300 transition disabled:cursor-not-allowed disabled:opacity-50 hover:bg-bgray-100 hover:text-red-500 dark:border-darkblack-400" data-appraisal-assignment-category-remove>Remove</button>
            `;

        return `
            <article class="rounded-xl border border-bgray-200 bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-assignment-category data-appraisal-template-source="${escapeHtml(categoryName)}">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 px-4 py-3 dark:border-darkblack-400">
                    ${categoryNameControl}
                    <div class="flex items-center gap-2">
                        ${actionButtons}
                        ${questionCount}
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-3 py-2 text-xs font-semibold text-bgray-700 transition hover:border-bgray-300 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50" data-appraisal-assignment-category-toggle aria-expanded="true">Collapse</button>
                    </div>
                </div>
                <div class="space-y-3 px-4 py-4" data-appraisal-assignment-category-body>
                    <div class="space-y-3" data-appraisal-assignment-question-list>
                        ${questions.map((question) => questionRowMarkup(question, readOnly)).join('') || questionRowMarkup({ question: '', question_type: defaultQuestionType() }, readOnly)}
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

        syncKpiTomSelect();

        assignmentContinueButton?.classList.toggle('hidden', readOnly);
        reviewerNextButton?.classList.toggle('hidden', !readOnly);
        reviewerSubmitButtons.forEach((button) => button.classList.toggle('hidden', readOnly));

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
            templatesContainer.innerHTML = '<p class="text-xs font-medium text-bgray-600 dark:text-bgray-300">No category templates available.</p>';
            return;
        }

        templatesContainer.innerHTML = templates.map((category) => `
            <div class="rounded-lg border border-bgray-200 bg-white p-3 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-template-item data-appraisal-template-name="${escapeHtml(category.name)}">
                <div class="flex items-center justify-between gap-2">
                    <button type="button" class="flex flex-1 items-center gap-2 text-left" data-appraisal-template-toggle aria-expanded="false">
                        <svg class="h-4 w-4 transform transition-transform duration-200 text-bgray-600 dark:text-bgray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <div>
                            <h5 class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(category.name)}</h5>
                            <span class="text-xs text-bgray-600 dark:text-bgray-300">${category.questions.length} ${category.questions.length === 1 ? 'question' : 'questions'}</span>
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
                        ${category.questions.map(q => {
            const suffix = q.question_type && q.question_type !== defaultQuestionType()
                ? ` (${questionTypeLabel(q.question_type)})`
                : '';
            return `<li>${escapeHtml(q.question)}${suffix}</li>`;
        }).join('')}
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

        destroyAssignmentQuestionSelects();

        if (!categories.length) {
            modalCategories.innerHTML = '<div class="rounded-lg border border-dashed border-bgray-200 px-4 py-8 text-center text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-300" data-appraisal-modal-empty>No active appraisal categories found.</div>';
            setModalReadOnly(readOnly);
            updateTemplateAddButtons();
            return;
        }

        modalCategories.innerHTML = categories.map((category) => categoryMarkup(category, readOnly)).join('');
        modalCategories.querySelectorAll('[data-appraisal-assignment-question]').forEach(initializeQuestionRowSelects);

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

    const setViewModalLayout = (isView = false) => {
        modalPanel?.classList.toggle('h-[92vh]', isView);
        modalPanel?.classList.toggle('flex', isView);
        modalPanel?.classList.toggle('flex-col', isView);

        assignmentSteps.forEach((step) => {
            step.classList.toggle('min-h-0', isView);
            step.classList.toggle('flex-1', isView);
        });

        modalSelectedUsersSummary?.classList.toggle('hidden', isView);
        modalSubtitle?.classList.toggle('hidden', !isView);
    };

    const setAssignEditModalLayout = (isFixed = false) => {
        modalPanel?.classList.toggle('h-[92vh]', isFixed);
        modalPanel?.classList.toggle('flex', isFixed);
        modalPanel?.classList.toggle('flex-col', isFixed);
        modalHeader?.classList.toggle('shrink-0', isFixed);

        assignmentSteps.forEach((step) => {
            step.classList.toggle('min-h-0', isFixed);
            step.classList.toggle('flex-1', isFixed);
        });

        assignmentFooters.forEach((footer) => {
            footer.classList.toggle('shrink-0', isFixed);
        });
    };

    const resetModal = () => {
        showAssignmentStep(1);
        setAssignEditModalLayout(false);
        setViewModalLayout(false);
        reviewerAssignmentData = [];
        destroyReviewerSelects();

        if (reviewerAssignmentsContainer) {
            reviewerAssignmentsContainer.innerHTML = '';
        }

        if (modalTitle) {
            modalTitle.textContent = 'Assign Appraisal';
        }

        if (modalSubtitle) {
            modalSubtitle.textContent = '';
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
        setAssignEditModalLayout(true);
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
            setAssignEditModalLayout(true);

            if (modalTitle) {
                modalTitle.textContent = 'Edit Appraisal';
            }

            renderModalCategories(appraisal.categories || [], false);
            renderTemplatesList();
            restoreKpiSelection(appraisal);
            reviewerAssignmentData = appraisal.reviewer_assignment
                ? [appraisal.reviewer_assignment]
                : [];
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        } catch (error) {
            alertError(error.message || 'Unable to load appraisal.');
        }
    };

    const openViewModal = async (appraisalId) => {
        try {
            const appraisal = await loadStoredAppraisal(appraisalId);
            const monthLabel = monthSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
            const yearLabel = yearSelect?.selectedOptions?.[0]?.textContent?.trim() || '';

            if (modalTitle) {
                modalTitle.textContent = `View Appraisal • ${[monthLabel, yearLabel].filter(Boolean).join(' ')}`;
            }

            if (modalSubtitle) {
                modalSubtitle.textContent = `Assignee • ${appraisal.user?.name || 'Unknown User'}`;
            }

            renderKpis();
            setViewModalLayout(true);
            renderModalCategories(appraisal.categories || [], true);
            restoreKpiSelection(appraisal);
            renderTemplatesList();
            reviewerAssignmentData = appraisal.reviewer_assignment
                ? [appraisal.reviewer_assignment]
                : [];
            renderReviewerAssignments(true);
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
        .map((categoryCard) => ({
            name: (categoryCard.querySelector('[data-appraisal-assignment-category-name]')?.value || '').trim(),
            questions: Array.from(categoryCard.querySelectorAll('[data-appraisal-assignment-question]'))
                .map((row) => {
                    const input = row.querySelector('[data-appraisal-assignment-question-input]');
                    const questionText = input ? input.value.trim() : (row.querySelector('p')?.textContent || '').trim();
                    const select = row.querySelector('[data-appraisal-assignment-question-type-select]');
                    const questionType = select ? select.value : (row.dataset.questionType || defaultQuestionType());
                    const isTarget = questionType === targetQuestionType();
                    const measurementType = row.querySelector('[data-appraisal-assignment-measurement-type]');
                    const targetValue = row.querySelector('[data-appraisal-assignment-target-value]');
                    const unit = row.querySelector('[data-appraisal-assignment-unit]');

                    return {
                        question: questionText,
                        question_type: questionType,
                        measurement_type: isTarget ? (measurementType?.value || '') : null,
                        target_value: isTarget ? (targetValue?.value || '') : null,
                        unit: isTarget ? (unit?.value || '') : null,
                    };
                })
                .filter((q) => q.question !== ''),
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

        for (const category of categories) {
            for (const question of category.questions) {
                if (question.question_type !== targetQuestionType()) {
                    continue;
                }

                if (!question.measurement_type) {
                    alertError(`Select a measurement type for "${question.question}".`);
                    return false;
                }

                if (String(question.target_value).trim() === '' || !Number.isFinite(Number(question.target_value))) {
                    alertError(`Enter a valid target value for "${question.question}".`);
                    return false;
                }

                if (!question.unit) {
                    alertError(`Select a unit for "${question.question}".`);
                    return false;
                }
            }
        }

        return true;
    };

    const submitAssignments = async (status, { keepModalOpen = false } = {}) => {
        const period = currentPeriod();
        const userIds = Array.from(selectedUserIds);
        const kpiId = Number(kpiSelect?.value || 0);
        const categories = serializeCategories();

        if (!userIds.length) {
            alertError('Select at least one user.');
            return false;
        }

        if (!kpiId) {
            alertError('Select a KPI.');
            kpiSelect?.focus();
            return false;
        }

        if (!validateCategories(categories)) {
            return false;
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
            reviewerAssignmentData = payload.data?.reviewer_assignments || reviewerAssignmentData;

            if (!keepModalOpen) {
                selectedUserIds.clear();
                closeAssignModal();
            }

            renderMyAppraisals();
            renderUsers();

            return true;
        } catch (error) {
            alertError(error.message || 'Unable to assign appraisals.');

            return false;
        }
    };

    const saveDraftAndContinue = async () => {
        if (!assignmentContinueButton || activeAssignmentStep !== 1) {
            return;
        }

        const originalText = assignmentContinueButton.textContent;
        assignmentContinueButton.disabled = true;
        assignmentContinueButton.textContent = 'Saving...';

        try {
            const saved = await submitAssignments('draft', { keepModalOpen: true });

            if (saved) {
                renderReviewerAssignments(false);
                showAssignmentStep(2);
            }
        } finally {
            assignmentContinueButton.disabled = false;
            assignmentContinueButton.textContent = originalText;
        }
    };

    const serializeReviewerAssignments = () => Array.from(
        reviewerAssignmentsContainer?.querySelectorAll('[data-appraisal-reviewer-card]') || []
    ).map((card) => ({
        user_id: Number(card.dataset.userId),
        reviewer_user_ids: Array.from(card.querySelectorAll('[data-appraisal-reviewer-select]'))
            .map((select) => Number(select.tomselect?.getValue() ?? select.value ?? 0))
            .filter((id) => id > 0),
    }));

    const validateReviewerAssignments = (assignments) => {
        if (!assignments.length) {
            alertError('Reviewer assignments are not available.');
            return false;
        }

        for (const assignment of assignments) {
            const reviewerData = reviewerAssignmentData.find(
                (item) => Number(item.user?.id) === Number(assignment.user_id)
            );
            const chainIds = (reviewerData?.available_reviewers || []).map((reviewer) => Number(reviewer.id));
            const employeeName = reviewerData?.user?.name || 'the employee';

            if (!chainIds.length) {
                alertError(`No reporting hierarchy is available for ${employeeName}.`);
                return false;
            }

            if (!assignment.reviewer_user_ids.length) {
                alertError(`Select at least one reviewer for ${employeeName}.`);
                return false;
            }

            if (
                assignment.reviewer_user_ids.length !== new Set(assignment.reviewer_user_ids).size
                || assignment.reviewer_user_ids.some((id) => !chainIds.includes(id))
            ) {
                alertError(`Reviewers for ${employeeName} must be unique users from the available reporter chain.`);
                return false;
            }
        }

        return true;
    };

    const saveReviewerAssignments = async (publishAfterSave = false) => {
        const assignments = serializeReviewerAssignments();

        if (!validateReviewerAssignments(assignments)) {
            return;
        }

        const userIds = assignments.map((assignment) => assignment.user_id);
        const submitButton = root.querySelector(
            `[data-appraisal-reviewers-submit="${publishAfterSave ? 'published' : 'draft'}"]`
        );
        const originalText = submitButton?.textContent;

        reviewerSubmitButtons.forEach((button) => {
            button.disabled = true;
        });

        if (submitButton) {
            submitButton.textContent = 'Saving...';
        }

        try {
            const period = currentPeriod();
            const response = await fetch(root.dataset.reviewerSubmitUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    month: period.month,
                    year: period.year,
                    assignments,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to assign appraisal reviewers.');
            }

            assignmentData = {
                ...assignmentData,
                ...(payload.data || {}),
            };
            reviewerAssignmentData = payload.data?.reviewer_assignments || reviewerAssignmentData;

            if (publishAfterSave) {
                const published = await publishAppraisals(userIds);

                if (published) {
                    closeAssignModal();
                }

                return;
            }

            alertSuccess(payload.message || 'Appraisal reviewers assigned successfully.');
            selectedUserIds.clear();
            closeAssignModal();
            renderMyAppraisals();
            renderUsers();
        } catch (error) {
            alertError(error.message || 'Unable to assign appraisal reviewers.');
        } finally {
            reviewerSubmitButtons.forEach((button) => {
                button.disabled = false;
            });

            if (submitButton) {
                submitButton.textContent = originalText;
            }
        }
    };

    const publishAppraisals = async (userIds) => {
        const publishableUserIds = userIds
            .map((userId) => Number(userId))
            .filter((userId) => (assignmentData.users || []).some((user) => Number(user.id) === userId && user.status === 'draft'));

        if (!publishableUserIds.length) {
            alertError('Select at least one draft appraisal to publish.');
            return false;
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
            return false;
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

            return true;
        } catch (error) {
            alertError(error.message || 'Unable to publish appraisals.');

            return false;
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

        const appraisalId = kpiAgreementAppraisalId;

        try {
            const response = await fetch(urlFromTemplate(root.dataset.agreeKpiUrlTemplate, appraisalId), {
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

            await alertSuccess(payload.message || 'KPI agreed successfully.');
            closeKpiAgreementModal();
            window.location.href = urlFromTemplate(root.dataset.answerPageUrlTemplate, appraisalId);
        } catch (error) {
            alertError(error.message || 'Unable to agree to KPI.');
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

        document.querySelectorAll('[data-filter-tab]').forEach((el) => {
            el.classList.toggle('hidden', el.dataset.filterTab !== tab);
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
            const period = currentPeriod();
            const url = new URL(window.location.href);
            url.searchParams.set('month', period.month);
            url.searchParams.set('year', period.year);
            url.searchParams.delete('my_page');
            url.searchParams.delete('assign_page');
            window.location.href = url.toString();
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

        if (event.target.matches('[data-appraisal-assignment-question-type-select]')) {
            setAssignmentTargetFieldsVisibility(
                event.target.closest('[data-appraisal-assignment-question]')
            );
            return;
        }

        if (event.target.matches('[data-appraisal-reviewer-select]')) {
            const card = event.target.closest('[data-appraisal-reviewer-card]');

            syncReviewerSelectOptions(card);
            updateReviewerCardControls(card);
            return;
        }

        if (event.target.matches('[data-appraisal-kpi-agreement-checkbox]')) {
            if (kpiAgreementSubmit) {
                kpiAgreementSubmit.disabled = !event.target.checked;
            }
            return;
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
            const nextTab = tabButton.dataset.tab;
            if (nextTab !== activeTab) {
                localStorage.setItem('appraisal_active_tab', nextTab);

                const period = currentPeriod();
                const url = new URL(window.location.href);
                const perPage = url.searchParams.get('per_page');
                url.search = '';
                url.searchParams.set('month', period.month);
                url.searchParams.set('year', period.year);
                if (perPage) {
                    url.searchParams.set('per_page', perPage);
                }

                window.location.href = url.toString();
            }
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

        const answerButton = event.target.closest('[data-appraisal-answer-link]');

        if (answerButton) {
            window.location.href = urlFromTemplate(root.dataset.answerPageUrlTemplate, answerButton.dataset.appraisalId);
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

        if (event.target.closest('[data-appraisal-assignment-continue]')) {
            saveDraftAndContinue();
            return;
        }

        if (event.target.closest('[data-appraisal-assignment-back]')) {
            showAssignmentStep(1);
            return;
        }

        if (event.target.closest('[data-appraisal-reviewers-next]')) {
            renderReviewerAssignments(true);
            showAssignmentStep(2);
            return;
        }

        const reviewerSubmitButton = event.target.closest('[data-appraisal-reviewers-submit]');

        if (reviewerSubmitButton) {
            saveReviewerAssignments(
                reviewerSubmitButton.dataset.appraisalReviewersSubmit === 'published'
            );
            return;
        }

        const addReviewerLevel = event.target.closest('[data-appraisal-reviewer-level-add]');

        if (addReviewerLevel) {
            const card = addReviewerLevel.closest('[data-appraisal-reviewer-card]');
            const assignment = reviewerAssignmentData.find(
                (item) => Number(item.user?.id) === Number(card?.dataset.userId)
            );
            const levelsContainer = card?.querySelector('[data-appraisal-reviewer-levels]');
            const currentLevels = card?.querySelectorAll('[data-appraisal-reviewer-level]').length || 0;
            const previousSelect = card?.querySelector('[data-appraisal-reviewer-level]:last-child [data-appraisal-reviewer-select]');
            const previousValue = previousSelect?.tomselect?.getValue() ?? previousSelect?.value ?? '';

            if (
                !card
                || !levelsContainer
                || !assignment
                || String(previousValue).trim() === ''
                || currentLevels >= (assignment.available_reviewers || []).length
            ) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = reviewerLevelMarkup(assignment, currentLevels, '', false);
            const level = wrapper.firstElementChild;

            if (level) {
                levelsContainer.appendChild(level);
                initTomSelect(level);
                syncReviewerSelectOptions(card);
                updateReviewerCardControls(card);
            }
            return;
        }

        const removeReviewerLevel = event.target.closest('[data-appraisal-reviewer-level-remove]');

        if (removeReviewerLevel) {
            const card = removeReviewerLevel.closest('[data-appraisal-reviewer-card]');
            const level = removeReviewerLevel.closest('[data-appraisal-reviewer-level]');
            const levels = card?.querySelectorAll('[data-appraisal-reviewer-level]') || [];

            if (!card || !level || levels.length <= 1 || level !== levels[levels.length - 1]) {
                return;
            }

            level.querySelector('[data-appraisal-reviewer-select]')?.tomselect?.destroy();
            level.remove();
            syncReviewerSelectOptions(card);
            updateReviewerCardControls(card);
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
                categoryCard.querySelectorAll('[data-appraisal-assignment-question]').forEach(initializeQuestionRowSelects);
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

            const categoryCard = removeCategory.closest('[data-appraisal-assignment-category]');
            destroyAssignmentQuestionSelects(categoryCard);
            categoryCard?.remove();
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
                    categoryCard.querySelectorAll('[data-appraisal-assignment-question]').forEach(initializeQuestionRowSelects);
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
                initializeQuestionRowSelects(row);
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

            const questionRow = removeQuestion.closest('[data-appraisal-assignment-question]');
            destroyAssignmentQuestionSelects(questionRow);
            questionRow?.remove();
            refreshQuestionNumbers(card);
            return;
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
