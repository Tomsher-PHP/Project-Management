import Alert from '../../alert';

const state = {
    activeMemberId: null,
    activeButton: null,
    library: [],
    checklists: [],
    saveUrl: '',
    member: null,
    loading: false,
    saving: false,
    librarySearch: '',
    errors: {},
};

const getModal = () => document.querySelector('[data-project-checklist-modal]');
const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const escapeHtml = (value = '') => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const createClientKey = (prefix = 'checklist') => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

const normalizeQuestion = (question = {}) => ({
    clientKey: createClientKey('question'),
    id: Number(question.id || 0) || null,
    question: String(question.question || ''),
    status: Number(question.status || 0) || 0,
});

const normalizeChecklist = (checklist = {}) => ({
    clientKey: createClientKey('checklist'),
    id: Number(checklist.id || 0) || null,
    checklist_template_id: Number(checklist.checklist_template_id || 0) || null,
    template_name: checklist.template_name ? String(checklist.template_name) : '',
    title: String(checklist.title || ''),
    questions: Array.isArray(checklist.questions) && checklist.questions.length
        ? checklist.questions.map((question) => normalizeQuestion(question))
        : [normalizeQuestion({ question: '' })],
});

const createBlankChecklist = () => normalizeChecklist({
    title: '',
    questions: [{ question: '' }],
});

const getSelectedTemplateIds = () => state.checklists
    .map((checklist) => Number(checklist.checklist_template_id || 0))
    .filter(Boolean);

const getError = (path) => {
    const value = state.errors?.[path];

    if (!value) {
        return '';
    }

    return Array.isArray(value) ? String(value[0] || '') : String(value);
};

const getFirstErrorMessage = (errors = {}) => {
    const firstValue = Object.values(errors)[0];

    if (Array.isArray(firstValue)) {
        return firstValue[0] || 'Please review the checklist form.';
    }

    return firstValue || 'Please review the checklist form.';
};

const setModalVisibility = (visible) => {
    const modal = getModal();

    if (!modal) {
        return;
    }

    modal.classList.toggle('hidden', !visible);
    document.body.style.overflow = visible ? 'hidden' : '';

    if (!visible) {
        state.errors = {};
        state.activeButton?.focus?.();
    }
};

const renderWorkspaceEmptyState = () => `
    <div class="rounded-2xl border border-dashed border-bgray-200 bg-white/80 px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600/80" data-project-checklist-dropzone>
        <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-bgray-900 dark:text-white">Drop checklist templates here</p>
                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">You can also add a blank checklist and build the questions manually.</p>
            </div>
        </div>
    </div>
`;

const renderWorkspaceChecklist = (checklist, checklistIndex) => {
    const titleError = getError(`checklists.${checklistIndex}.title`);
    const questionsError = getError(`checklists.${checklistIndex}.questions`);

    const questionsMarkup = checklist.questions.map((question, questionIndex) => {
        const questionError = getError(`checklists.${checklistIndex}.questions.${questionIndex}.question`);

        return `
            <div class="rounded-xl border border-bgray-200 bg-bgray-50/70 p-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
                <div class="flex items-start gap-3">
                    <span class="mt-2 inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-white text-xs font-semibold text-success-500 shadow-sm dark:bg-darkblack-600 dark:text-success-300">
                        ${questionIndex + 1}
                    </span>

                    <div class="min-w-0 flex-1">
                        <input
                            type="text"
                            value="${escapeHtml(question.question)}"
                            class="w-full rounded-lg border ${questionError ? 'border-red-500' : 'border-bgray-200'} bg-white px-3 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white"
                            placeholder="Enter checklist question"
                            data-project-checklist-question-input
                            data-checklist-index="${checklistIndex}"
                            data-question-index="${questionIndex}"
                        >
                        ${questionError ? `<p class="mt-1 text-xs text-red-500">${escapeHtml(questionError)}</p>` : ''}
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-bgray-200 bg-white text-bgray-600 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300"
                        data-project-checklist-remove-question
                        data-checklist-index="${checklistIndex}"
                        data-question-index="${questionIndex}"
                        aria-label="Remove question"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    return `
        <article class="rounded-2xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-checklist-card data-client-key="${escapeHtml(checklist.clientKey)}">
            <div class="flex flex-col gap-3 border-b border-bgray-200 pb-4 dark:border-darkblack-400 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        ${checklist.template_name
            ? `<span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-success-500 dark:bg-darkblack-500 dark:text-success-300">Library Template</span>`
            : `<span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">Custom</span>`}
                        ${checklist.template_name ? `<span class="text-xs text-bgray-500 dark:text-bgray-300">${escapeHtml(checklist.template_name)}</span>` : ''}
                    </div>

                    <input
                        type="text"
                        value="${escapeHtml(checklist.title)}"
                        class="w-full rounded-xl border ${titleError ? 'border-red-500' : 'border-bgray-200'} bg-white px-4 py-3 text-sm font-semibold text-bgray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                        placeholder="Checklist title"
                        data-project-checklist-title-input
                        data-checklist-index="${checklistIndex}"
                    >
                    ${titleError ? `<p class="mt-1 text-xs text-red-500">${escapeHtml(titleError)}</p>` : ''}
                </div>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300"
                    data-project-checklist-remove
                    data-checklist-index="${checklistIndex}"
                    aria-label="Remove checklist"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-3">
                ${questionsMarkup}
                ${questionsError ? `<p class="text-xs text-red-500">${escapeHtml(questionsError)}</p>` : ''}
            </div>

            <div class="mt-4">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm font-medium text-success-500 transition duration-200 hover:border-success-300 hover:bg-success-100 dark:border-success-900/30 dark:bg-darkblack-500 dark:text-success-300"
                    data-project-checklist-add-question
                    data-checklist-index="${checklistIndex}"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Question</span>
                </button>
            </div>
        </article>
    `;
};

const renderWorkspace = () => {
    const modal = getModal();
    const workspace = modal?.querySelector('[data-project-checklist-workspace]');

    if (!workspace) {
        return;
    }

    if (state.loading) {
        workspace.innerHTML = `
            <div class="flex h-full min-h-[320px] items-center justify-center rounded-2xl border border-dashed border-bgray-200 bg-white text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                Loading checklist assignments...
            </div>
        `;
        return;
    }

    const cards = state.checklists.map((checklist, index) => renderWorkspaceChecklist(checklist, index)).join('');

    workspace.innerHTML = state.checklists.length
        ? `${cards}<div class="h-24 rounded-2xl border border-dashed border-bgray-200 bg-bgray-50/50 dark:border-darkblack-400 dark:bg-darkblack-500/20" data-project-checklist-dropzone></div>`
        : renderWorkspaceEmptyState();
};

const renderLibrary = () => {
    const modal = getModal();
    const library = modal?.querySelector('[data-project-checklist-library]');

    if (!library) {
        return;
    }

    if (state.loading) {
        library.innerHTML = `
            <div class="flex h-full min-h-[240px] items-center justify-center rounded-2xl border border-dashed border-bgray-200 bg-white px-4 text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                Loading checklist library...
            </div>
        `;
        return;
    }

    const selectedTemplateIds = getSelectedTemplateIds();
    const keyword = state.librarySearch.trim().toLowerCase();
    const filteredTemplates = state.library.filter((template) => {
        if (!keyword) {
            return true;
        }

        const haystack = [template.name, ...(template.questions || []).map((question) => question.question)]
            .join(' ')
            .toLowerCase();

        return haystack.includes(keyword);
    });

    if (!filteredTemplates.length) {
        library.innerHTML = `
            <div class="rounded-2xl border border-dashed border-bgray-200 bg-white px-5 py-10 text-center text-sm text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-300">
                No checklist templates match your search.
            </div>
        `;
        return;
    }

    library.innerHTML = `
        <div class="space-y-3">
            ${filteredTemplates.map((template) => {
        const isSelected = selectedTemplateIds.includes(Number(template.id));
        const preview = (template.questions || []).slice(0, 3);

        return `
                    <article
                        class="rounded-2xl border ${isSelected ? 'border-success-300 bg-success-50/60 dark:border-success-900/40 dark:bg-darkblack-600' : 'border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600'} p-4 shadow-sm transition duration-200"
                        ${isSelected ? '' : 'draggable="true"'}
                        data-project-checklist-library-item
                        data-template-id="${template.id}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h5 class="truncate text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(template.name)}</h5>
                                    ${isSelected ? '<span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-success-600 dark:bg-darkblack-500 dark:text-success-300">Selected</span>' : ''}
                                </div>
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">${template.questions.length} ${template.questions.length === 1 ? 'question' : 'questions'}</p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl ${isSelected ? 'bg-success-100 text-success-500 dark:bg-darkblack-500 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200'} transition duration-200 hover:bg-success-50 hover:text-success-500"
                                data-project-checklist-library-add
                                data-template-id="${template.id}"
                                ${isSelected ? 'disabled' : ''}
                                aria-label="Add checklist template"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-3 space-y-2">
                            ${preview.map((question, index) => `
                                <div class="rounded-xl bg-bgray-50 px-3 py-2 text-xs text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-200">
                                    ${index + 1}. ${escapeHtml(question.question)}
                                </div>
                            `).join('')}
                            ${template.questions.length > preview.length ? `<p class="text-xs text-bgray-400 dark:text-bgray-300">+${template.questions.length - preview.length} more questions</p>` : ''}
                        </div>
                    </article>
                `;
    }).join('')}
        </div>
    `;
};

const renderMemberSummary = () => {
    const modal = getModal();

    if (!modal) {
        return;
    }

    const avatar = modal.querySelector('[data-project-checklist-member-avatar]');
    const name = modal.querySelector('[data-project-checklist-member-name]');
    const meta = modal.querySelector('[data-project-checklist-member-meta]');
    const count = modal.querySelector('[data-project-checklist-count]');
    const saveButton = modal.querySelector('[data-project-checklist-save]');

    if (avatar) {
        avatar.src = state.member?.avatar || avatar.getAttribute('src') || '';
    }

    if (name) {
        name.textContent = state.member?.name || 'Choose a team member';
    }

    if (meta) {
        const metaParts = [state.member?.email, state.member?.designation].filter(Boolean);
        meta.textContent = metaParts.length
            ? metaParts.join(' • ')
            : 'Drag templates into the workspace and tailor the questions before saving.';
    }

    if (count) {
        count.textContent = String(state.checklists.length);
    }

    if (saveButton) {
        saveButton.disabled = state.loading || state.saving;
        saveButton.textContent = state.saving ? 'Saving...' : 'Save Checklists';
    }
};

const renderChecklistModal = () => {
    renderMemberSummary();
    renderWorkspace();
    renderLibrary();
};

const scrollChecklistIntoView = (clientKey) => {
    const modal = getModal();
    const card = modal?.querySelector(`[data-project-checklist-card][data-client-key="${clientKey}"]`);

    if (!card) {
        return;
    }

    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    card.classList.add('ring-2', 'ring-success-300');

    window.setTimeout(() => {
        card.classList.remove('ring-2', 'ring-success-300');
    }, 1600);
};

const addChecklistFromTemplate = (templateId) => {
    const normalizedTemplateId = Number(templateId || 0);

    if (!normalizedTemplateId) {
        return;
    }

    const existingChecklist = state.checklists.find((checklist) => Number(checklist.checklist_template_id || 0) === normalizedTemplateId);

    if (existingChecklist) {
        renderChecklistModal();
        scrollChecklistIntoView(existingChecklist.clientKey);
        Alert.info('This checklist template is already assigned to the member.');
        return;
    }

    const template = state.library.find((item) => Number(item.id) === normalizedTemplateId);

    if (!template) {
        return;
    }

    const checklist = normalizeChecklist({
        checklist_template_id: template.id,
        template_name: template.name,
        title: template.name,
        questions: template.questions,
    });

    state.checklists.push(checklist);
    state.errors = {};
    renderChecklistModal();
    scrollChecklistIntoView(checklist.clientKey);
};

const openChecklistManager = async (button) => {
    const projectId = button.dataset.projectId || window.ProjectApp?.id;
    const memberId = button.dataset.id;

    if (!projectId || !memberId) {
        return;
    }

    state.activeButton = button;
    state.activeMemberId = Number(memberId);
    state.loading = true;
    state.saving = false;
    state.librarySearch = '';
    state.errors = {};
    state.member = null;
    state.library = [];
    state.checklists = [];
    state.saveUrl = '';

    setModalVisibility(true);
    renderChecklistModal();

    try {
        const response = await fetch(`/projects/${projectId}/members/${memberId}/checklists`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load member checklists.');
        }

        state.member = result.member || null;
        state.library = Array.isArray(result.library) ? result.library : [];
        state.checklists = Array.isArray(result.checklists)
            ? result.checklists.map((checklist) => normalizeChecklist(checklist))
            : [];
        state.saveUrl = result.save_url || '';
        state.librarySearch = '';
    } catch (error) {
        setModalVisibility(false);
        Alert.error(error.message || 'Unable to load member checklists.');
        return;
    } finally {
        state.loading = false;
    }

    renderChecklistModal();
};

const saveChecklists = async () => {
    if (state.loading || state.saving || !state.saveUrl) {
        return;
    }

    state.saving = true;
    state.errors = {};
    renderMemberSummary();

    try {
        const response = await fetch(state.saveUrl, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                checklists: state.checklists.map((checklist) => ({
                    id: checklist.id,
                    checklist_template_id: checklist.checklist_template_id,
                    title: checklist.title,
                    questions: checklist.questions.map((question) => ({
                        id: question.id,
                        question: question.question,
                    })),
                })),
            }),
        });
        const result = await response.json();

        if (response.status === 422) {
            state.errors = result.errors || {};
            renderChecklistModal();
            Alert.error(getFirstErrorMessage(state.errors));
            return;
        }

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to save project checklists.');
        }

        if (result.member_card && state.activeMemberId) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = result.member_card.trim();
            const newCard = wrapper.firstElementChild;
            const currentCard = document.querySelector(`.team-member-card[data-member-id="${state.activeMemberId}"]`);

            if (newCard && currentCard) {
                currentCard.replaceWith(newCard);
            }
        }

        setModalVisibility(false);
        Alert.success(result.message || 'Project checklists saved successfully.');
    } catch (error) {
        Alert.error(error.message || 'Unable to save project checklists.');
    } finally {
        state.saving = false;
        renderMemberSummary();
    }
};

const bindChecklistListeners = () => {
    if (document.body.dataset.projectChecklistBound === 'true') {
        return;
    }

    document.addEventListener('click', (event) => {
        const manageButton = event.target.closest('.manage-checklist');

        if (manageButton) {
            openChecklistManager(manageButton);
            return;
        }

        const closeButton = event.target.closest('[data-project-checklist-close]');

        if (closeButton) {
            setModalVisibility(false);
            return;
        }

        const addBlankButton = event.target.closest('[data-project-checklist-add-blank]');

        if (addBlankButton) {
            state.checklists.push(createBlankChecklist());
            state.errors = {};
            renderChecklistModal();
            scrollChecklistIntoView(state.checklists[state.checklists.length - 1].clientKey);
            return;
        }

        const addLibraryButton = event.target.closest('[data-project-checklist-library-add]');

        if (addLibraryButton) {
            addChecklistFromTemplate(addLibraryButton.dataset.templateId);
            return;
        }

        const removeChecklistButton = event.target.closest('[data-project-checklist-remove]');

        if (removeChecklistButton) {
            const checklistIndex = Number(removeChecklistButton.dataset.checklistIndex || -1);

            if (checklistIndex < 0) {
                return;
            }

            state.checklists.splice(checklistIndex, 1);
            state.errors = {};
            renderChecklistModal();
            return;
        }

        const addQuestionButton = event.target.closest('[data-project-checklist-add-question]');

        if (addQuestionButton) {
            const checklistIndex = Number(addQuestionButton.dataset.checklistIndex || -1);
            const checklist = state.checklists[checklistIndex];

            if (!checklist) {
                return;
            }

            checklist.questions.push(normalizeQuestion({ question: '' }));
            state.errors = {};
            renderChecklistModal();
            return;
        }

        const removeQuestionButton = event.target.closest('[data-project-checklist-remove-question]');

        if (removeQuestionButton) {
            const checklistIndex = Number(removeQuestionButton.dataset.checklistIndex || -1);
            const questionIndex = Number(removeQuestionButton.dataset.questionIndex || -1);
            const checklist = state.checklists[checklistIndex];

            if (!checklist || questionIndex < 0) {
                return;
            }

            if (checklist.questions.length === 1) {
                Alert.info('Each checklist needs at least one question.');
                return;
            }

            checklist.questions.splice(questionIndex, 1);
            state.errors = {};
            renderChecklistModal();
            return;
        }

        const saveButton = event.target.closest('[data-project-checklist-save]');

        if (saveButton) {
            saveChecklists();
        }
    });

    document.addEventListener('input', (event) => {
        const searchInput = event.target.closest('[data-project-checklist-library-search]');

        if (searchInput) {
            state.librarySearch = searchInput.value || '';
            renderLibrary();
            return;
        }

        const titleInput = event.target.closest('[data-project-checklist-title-input]');

        if (titleInput) {
            const checklistIndex = Number(titleInput.dataset.checklistIndex || -1);
            const checklist = state.checklists[checklistIndex];

            if (!checklist) {
                return;
            }

            checklist.title = titleInput.value;
            delete state.errors[`checklists.${checklistIndex}.title`];
            return;
        }

        const questionInput = event.target.closest('[data-project-checklist-question-input]');

        if (questionInput) {
            const checklistIndex = Number(questionInput.dataset.checklistIndex || -1);
            const questionIndex = Number(questionInput.dataset.questionIndex || -1);
            const checklist = state.checklists[checklistIndex];
            const question = checklist?.questions?.[questionIndex];

            if (!question) {
                return;
            }

            question.question = questionInput.value;
            delete state.errors[`checklists.${checklistIndex}.questions.${questionIndex}.question`];
        }
    });

    document.addEventListener('dragstart', (event) => {
        const libraryItem = event.target.closest('[data-project-checklist-library-item]');

        if (!libraryItem || !event.dataTransfer) {
            return;
        }

        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('text/project-checklist-template-id', libraryItem.dataset.templateId || '');
    });

    document.addEventListener('dragover', (event) => {
        const dropzone = event.target.closest('[data-project-checklist-dropzone]');

        if (!dropzone) {
            return;
        }

        event.preventDefault();
        dropzone.classList.add('border-success-300', 'bg-success-50');
    });

    document.addEventListener('dragleave', (event) => {
        const dropzone = event.target.closest('[data-project-checklist-dropzone]');

        if (!dropzone) {
            return;
        }

        dropzone.classList.remove('border-success-300', 'bg-success-50');
    });

    document.addEventListener('drop', (event) => {
        const dropzone = event.target.closest('[data-project-checklist-dropzone]');

        if (!dropzone) {
            return;
        }

        event.preventDefault();
        dropzone.classList.remove('border-success-300', 'bg-success-50');

        const templateId = event.dataTransfer?.getData('text/project-checklist-template-id');

        if (templateId) {
            addChecklistFromTemplate(templateId);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const modal = getModal();

        if (modal && !modal.classList.contains('hidden')) {
            setModalVisibility(false);
        }
    });

    document.body.dataset.projectChecklistBound = 'true';
};

document.addEventListener('DOMContentLoaded', bindChecklistListeners);
