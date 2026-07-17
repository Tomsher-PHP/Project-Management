document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-appraisal-answer-page]');

    if (!root) {
        return;
    }

    const pageDataElement = root.querySelector('[data-appraisal-answer-page-data]');

    if (!pageDataElement) {
        return;
    }

    const answerFormData = JSON.parse(pageDataElement.textContent || '{}');
    const answerCategoryTitle = root.querySelector('[data-appraisal-answer-category-title]');
    const answerQuestions = root.querySelector('[data-appraisal-answer-questions]');
    const answerCategories = root.querySelector('[data-appraisal-answer-categories]');
    const answerSubmit = root.querySelector('[data-appraisal-answer-submit]');
    const answerSaveDraft = root.querySelector('[data-appraisal-answer-save-draft]');
    const answerHelperMessage = root.querySelector('[data-appraisal-answer-helper-message]');
    const overallCount = root.querySelector('[data-appraisal-answer-overall-count]');
    const overallPercentage = root.querySelector('[data-appraisal-answer-overall-percentage]');
    const overallBar = root.querySelector('[data-appraisal-answer-overall-bar]');
    const overallCommentsSection = root.querySelector('[data-appraisal-overall-comments-section]');
    const reporterCommentMeta = root.querySelector('[data-appraisal-reporter-comment-meta]');
    const reporterCommentTextarea = root.querySelector('[data-appraisal-reporter-comment-textarea]');
    const managerCommentMeta = root.querySelector('[data-appraisal-manager-comment-meta]');
    const managerCommentTextarea = root.querySelector('[data-appraisal-manager-comment-textarea]');

    let activeAnswerCategoryId = answerFormData.categories?.[0]?.id || null;

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const alertSuccess = (message) => window.Alert?.success ? window.Alert.success(message) : window.alert(message);
    const alertError = (message) => window.Alert?.error ? window.Alert.error(message) : window.alert(message);
    const confirmAction = async (options) => {
        if (window.Alert?.confirm) {
            const result = await window.Alert.confirm(options);

            return result.isConfirmed;
        }

        return window.confirm(options.text || options.title || 'Are you sure?');
    };

    const isQuestionCompleted = (question, role) => {
        if (question.question_type === 'answer') {
            if (role === 'assignee') {
                const answerText = question.answer?.assignee_answer;

                return answerText !== undefined && answerText !== null && String(answerText).trim() !== '';
            }

            return true;
        }

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

        return remark !== undefined && remark !== null && String(remark).trim() !== '';
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
        if (!answerQuestions) {
            return;
        }

        answerQuestions.querySelectorAll('[data-appraisal-answer-input]:not([readonly]):not(:disabled)').forEach((input) => {
            const questionId = Number(input.dataset.questionId);
            const field = input.dataset.answerField;
            const question = (answerFormData.categories || [])
                .flatMap((category) => category.questions || [])
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
        if (!answerCategories) {
            return;
        }

        answerCategories.innerHTML = (answerFormData.categories || []).map((category) => {
            const isActive = Number(category.id) === Number(activeAnswerCategoryId);
            const totalQuestions = (category.questions || []).length;
            const answeredCount = (category.questions || [])
                .filter((question) => isQuestionCompleted(question, answerFormData.role))
                .length;
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
        if (!answerQuestions) {
            return;
        }

        const category = (answerFormData.categories || [])
            .find((item) => Number(item.id) === Number(activeAnswerCategoryId));

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
            const isEditable = !answerFormData.is_submitted;
            const isAssigneeRole = answerFormData.role === 'assignee' && isEditable;
            const isReporterRole = answerFormData.role === 'reporter' && isEditable;
            const isManagerRole = answerFormData.role === 'manager' && isEditable;

            if (question.question_type === 'answer') {
                const editable = answerFormData.role === 'assignee' && isEditable;
                const readonlyAttr = editable ? '' : 'readonly';
                const readonlyClasses = editable ? '' : 'bg-bgray-100 dark:bg-darkblack-400 cursor-default';
                const answerText = question.answer?.assignee_answer ?? '';

                return `
                    <article class="rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 overflow-hidden" data-appraisal-answer-question-card>
                        <header class="flex items-center justify-between gap-3 p-4 cursor-pointer hover:bg-bgray-50 dark:hover:bg-darkblack-600 transition animate-fade-in" data-appraisal-answer-question-header>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300">${index + 1}</span>
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(question.question)}</p>
                            </div>
                            <button type="button" class="text-bgray-600 hover:text-bgray-800 dark:text-bgray-400 dark:hover:text-white focus:outline-none transition-transform duration-200" aria-label="Toggle answer body" data-appraisal-answer-question-toggle>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform duration-200 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </header>
                        <div class="border-t border-bgray-100 dark:border-darkblack-400 p-4 space-y-3 transition-all duration-200" data-appraisal-answer-question-body>
                            <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-2.5 dark:border-darkblack-400 dark:bg-darkblack-600">
                                <p class="text-xs font-bold text-bgray-900 dark:text-white uppercase tracking-[0.08em]">Assignee Answer Only</p>
                                <div class="mt-1.5">
                                    <textarea rows="4" placeholder="Enter your answer" class="w-full rounded-lg border border-gray-300 p-2 text-sm focus:border-success-300 focus:ring-0 disabled:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white dark:disabled:bg-darkblack-400 ${readonlyClasses}" data-appraisal-answer-input data-question-id="${escapeHtml(question.id)}" data-answer-field="assignee_answer" ${readonlyAttr}>${escapeHtml(answerText)}</textarea>
                                </div>
                            </div>
                        </div>
                    </article>
                `;
            }

            const sections = [
                answerSectionMarkup(
                    isAssigneeRole ? 'Self' : 'Assignee',
                    'assignee_rating',
                    'assignee_remark',
                    question,
                    isAssigneeRole,
                ),
                answerSectionMarkup('Reporter', 'reporter_rating', 'reporter_remark', question, isReporterRole),
                answerSectionMarkup('Manager', 'manager_rating', 'manager_remark', question, isManagerRole),
            ];

            return `
                <article class="rounded-xl border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-500 overflow-hidden" data-appraisal-answer-question-card>
                    <header class="flex items-center justify-between gap-3 p-4 cursor-pointer hover:bg-bgray-50 dark:hover:bg-darkblack-600 transition animate-fade-in" data-appraisal-answer-question-header>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-success-50 text-sm font-semibold text-success-400 dark:bg-darkblack-400 dark:text-success-300">${index + 1}</span>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(question.question)}</p>
                        </div>
                        <button type="button" class="text-bgray-600 hover:text-bgray-800 dark:text-bgray-400 dark:hover:text-white focus:outline-none transition-transform duration-200" aria-label="Toggle answer body" data-appraisal-answer-question-toggle>
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

    const renderOverallComments = () => {
        if (!overallCommentsSection) {
            return;
        }

        overallCommentsSection.classList.remove('hidden');

        const comments = answerFormData.comments || [];
        const reporterComment = comments.find((comment) => comment.role === 'reporter');
        const managerComment = comments.find((comment) => comment.role === 'manager');

        if (reporterCommentTextarea) {
            reporterCommentTextarea.value = reporterComment?.comment || '';
        }
        if (reporterCommentMeta) {
            reporterCommentMeta.textContent = reporterComment
                ? `By ${reporterComment.commentator_name} • ${reporterComment.created_at}`
                : '';
        }
        if (managerCommentTextarea) {
            managerCommentTextarea.value = managerComment?.comment || '';
        }
        if (managerCommentMeta) {
            managerCommentMeta.textContent = managerComment
                ? `By ${managerComment.commentator_name} • ${managerComment.created_at}`
                : '';
        }

        const hasSubmitted = answerFormData.is_submitted === true;
        reporterCommentTextarea?.toggleAttribute('disabled', answerFormData.role !== 'reporter' || hasSubmitted);
        managerCommentTextarea?.toggleAttribute('disabled', answerFormData.role !== 'manager' || hasSubmitted);
    };

    const updateAnswerProgress = () => {
        let allCompleted = true;
        let totalQuestions = 0;
        let completedQuestions = 0;

        renderAnswerCategories();

        (answerFormData.categories || []).forEach((category) => {
            (category.questions || []).forEach((question) => {
                totalQuestions++;

                if (isQuestionCompleted(question, answerFormData.role)) {
                    completedQuestions++;
                } else {
                    allCompleted = false;
                }
            });
        });

        const percentage = totalQuestions > 0
            ? Math.round((completedQuestions / totalQuestions) * 100)
            : 0;

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
            answerSaveDraft?.classList.add('hidden');
            answerSubmit?.classList.add('hidden');
            answerHelperMessage?.classList.add('hidden');
            return;
        }

        answerSaveDraft?.classList.remove('hidden');
        answerSubmit?.classList.remove('hidden');

        if (answerSubmit) {
            answerSubmit.disabled = !allCompleted || totalQuestions === 0;
            answerSubmit.classList.toggle('opacity-50', answerSubmit.disabled);
            answerSubmit.classList.toggle('cursor-not-allowed', answerSubmit.disabled);
        }

        if (answerHelperMessage) {
            answerHelperMessage.classList.toggle('hidden', allCompleted && totalQuestions > 0);
        }
    };

    const saveAppraisalDraft = async () => {
        if (!answerSaveDraft) {
            return;
        }

        if (answerFormData.is_submitted) {
            alertError('Your appraisal has already been submitted and can no longer be edited.');
            return;
        }

        persistVisibleAnswerValues();

        const role = answerFormData.role;
        const overallComment = role === 'reporter'
            ? reporterCommentTextarea?.value ?? ''
            : role === 'manager'
                ? managerCommentTextarea?.value ?? ''
                : null;
        const answersList = [];

        (answerFormData.categories || []).forEach((category) => {
            (category.questions || []).forEach((question) => {
                if (question.question_type === 'answer') {
                    answersList.push({
                        question_id: question.id,
                        assignee_answer: question.answer?.assignee_answer !== undefined && question.answer?.assignee_answer !== null
                            ? String(question.answer.assignee_answer).trim()
                            : null,
                    });
                } else {
                    const rating = question.answer?.[`${role}_rating`];
                    const remark = question.answer?.[`${role}_remark`];
                    let ratingValue = null;

                    if (rating !== undefined && rating !== null && rating !== '') {
                        ratingValue = Number(rating);
                        if (isNaN(ratingValue)) {
                            ratingValue = rating;
                        }
                    }

                    answersList.push({
                        question_id: question.id,
                        rating: ratingValue,
                        remark: remark !== undefined && remark !== null ? String(remark).trim() : null,
                    });
                }
            });
        });

        const originalText = answerSaveDraft.textContent;
        answerSaveDraft.disabled = true;
        answerSaveDraft.textContent = 'Saving...';

        try {
            const response = await fetch(root.dataset.saveDraftUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    answers: answersList,
                    overall_comment: overallComment,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to save draft.');
            }

            alertSuccess(payload.message || 'Draft saved successfully.');
        } catch (error) {
            alertError(error.message || 'Unable to save draft.');
        } finally {
            answerSaveDraft.disabled = false;
            answerSaveDraft.textContent = originalText;
        }
    };

    const submitAppraisalAnswers = async () => {
        if (!answerSubmit) {
            return;
        }

        if (answerFormData.is_submitted) {
            alertError('Your appraisal has already been submitted and can no longer be edited.');
            return;
        }

        persistVisibleAnswerValues();

        const role = answerFormData.role;
        const overallComment = role === 'reporter'
            ? reporterCommentTextarea?.value ?? ''
            : role === 'manager'
                ? managerCommentTextarea?.value ?? ''
                : null;
        const answersList = [];
        let allCompleted = true;

        (answerFormData.categories || []).forEach((category) => {
            (category.questions || []).forEach((question) => {
                if (!isQuestionCompleted(question, role)) {
                    allCompleted = false;
                }

                if (question.question_type === 'answer') {
                    answersList.push({
                        question_id: question.id,
                        assignee_answer: String(question.answer?.assignee_answer || '').trim(),
                    });
                } else {
                    const rating = question.answer?.[`${role}_rating`];
                    const remark = question.answer?.[`${role}_remark`];

                    answersList.push({
                        question_id: question.id,
                        rating: rating === '' ? null : Number(rating),
                        remark: String(remark || '').trim(),
                    });
                }
            });
        });

        if (!allCompleted) {
            alertError('Please complete all questions before submitting.');
            return;
        }

        if (['reporter', 'manager'].includes(role) && !String(overallComment || '').trim()) {
            const confirmed = await confirmAction({
                title: 'Submit Without Overall Comment?',
                text: 'You have not entered an overall comment for this appraisal. Are you sure you want to submit without adding one?',
                confirmText: 'Submit Anyway',
                cancelText: 'Go Back',
                icon: 'warning',
            });

            if (!confirmed) {
                return;
            }
        }

        const originalText = answerSubmit.textContent;
        answerSubmit.disabled = true;
        answerSubmit.textContent = 'Submitting...';

        try {
            const response = await fetch(root.dataset.submitAnswersUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    answers: answersList,
                    overall_comment: overallComment,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to submit answers.');
            }

            await alertSuccess(payload.message || 'Appraisal answers submitted successfully.');
            window.location.href = root.dataset.indexUrl;
        } catch (error) {
            alertError(error.message || 'Unable to submit answers.');
        } finally {
            answerSubmit.disabled = false;
            answerSubmit.textContent = originalText;
        }
    };

    const syncAnswerInput = (input) => {
        const questionId = Number(input.dataset.questionId);
        const field = input.dataset.answerField;
        const question = (answerFormData.categories || [])
            .flatMap((category) => category.questions || [])
            .find((item) => Number(item.id) === questionId);

        if (question && field) {
            question.answer = {
                ...(question.answer || {}),
                [field]: input.value,
            };
            updateAnswerProgress();
        }
    };

    root.addEventListener('input', (event) => {
        if (event.target.matches('[data-appraisal-answer-input]')) {
            syncAnswerInput(event.target);
        }
    });

    root.addEventListener('change', (event) => {
        if (event.target.matches('[data-appraisal-answer-input]')) {
            syncAnswerInput(event.target);
        }
    });

    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-appraisal-answer-save-draft]')) {
            saveAppraisalDraft();
            return;
        }

        if (event.target.closest('[data-appraisal-answer-submit]')) {
            submitAppraisalAnswers();
            return;
        }

        const categoryButton = event.target.closest('[data-appraisal-answer-category-id]');

        if (categoryButton) {
            persistVisibleAnswerValues();
            activeAnswerCategoryId = Number(categoryButton.dataset.appraisalAnswerCategoryId);
            renderAnswerCategories();
            renderAnswerQuestions();
            return;
        }

        const questionHeader = event.target.closest('[data-appraisal-answer-question-header]');

        if (!questionHeader) {
            return;
        }

        const card = questionHeader.closest('[data-appraisal-answer-question-card]');
        const body = card?.querySelector('[data-appraisal-answer-question-body]');
        const icon = card?.querySelector('[data-appraisal-answer-question-toggle] svg');

        if (body && icon) {
            const isHidden = body.classList.toggle('hidden');
            icon.classList.toggle('rotate-180', !isHidden);
        }
    });

    renderAnswerQuestions();
    updateAnswerProgress();
    renderOverallComments();

    console.log('Appraisal answer page data:', answerFormData);
});
