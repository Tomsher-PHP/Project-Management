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
    const reporterCommentTextarea = root.querySelector('[data-appraisal-reporter-comment-textarea]');
    const managerCommentTextarea = root.querySelector('[data-appraisal-manager-comment-textarea]');

    let activeAnswerCategoryId = answerFormData.categories?.[0]?.id || null;

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

    const activeCategoryClasses = [
        'border-success-300',
        'bg-success-50',
        'text-success-500',
        'dark:border-success-900/50',
        'dark:bg-darkblack-600',
        'dark:text-success-300',
    ];
    const inactiveCategoryClasses = [
        'border-bgray-200',
        'bg-white',
        'text-bgray-700',
        'hover:border-success-200',
        'hover:text-success-400',
        'dark:border-darkblack-400',
        'dark:bg-darkblack-600',
        'dark:text-bgray-50',
    ];

    const updateAnswerCategories = () => {
        if (!answerCategories) {
            return;
        }

        answerCategories.querySelectorAll('[data-appraisal-answer-category-id]').forEach((button) => {
            const category = (answerFormData.categories || [])
                .find((item) => Number(item.id) === Number(button.dataset.appraisalAnswerCategoryId));

            if (!category) {
                return;
            }

            const isActive = Number(category.id) === Number(activeAnswerCategoryId);
            const totalQuestions = (category.questions || []).length;
            const answeredCount = (category.questions || [])
                .filter((question) => isQuestionCompleted(question, answerFormData.role))
                .length;
            const isCompleted = answeredCount === totalQuestions;
            const progressElement = button.querySelector('[data-appraisal-answer-category-progress]');

            button.classList.remove(...activeCategoryClasses, ...inactiveCategoryClasses);
            button.classList.add(...(isActive ? activeCategoryClasses : inactiveCategoryClasses));

            if (progressElement) {
                progressElement.textContent = isCompleted
                    ? `✓ ${answeredCount} / ${totalQuestions} Completed`
                    : `${answeredCount} / ${totalQuestions} Questions`;
                progressElement.classList.toggle('text-success-500', isCompleted);
                progressElement.classList.toggle('dark:text-success-300', isCompleted);
                progressElement.classList.toggle('font-bold', isCompleted);
                progressElement.classList.toggle('opacity-80', !isCompleted);
            }
        });
    };

    const showAnswerCategory = () => {
        let activeCategoryName = '';

        answerQuestions?.querySelectorAll('[data-appraisal-answer-category-panel]').forEach((panel) => {
            const isActive = Number(panel.dataset.categoryId) === Number(activeAnswerCategoryId);

            panel.classList.toggle('hidden', !isActive);

            if (isActive) {
                activeCategoryName = panel.dataset.categoryName || '';
            }
        });

        if (answerCategoryTitle) {
            answerCategoryTitle.textContent = activeCategoryName;
        }

        updateAnswerCategories();
    };

    const updateAnswerProgress = () => {
        let allCompleted = true;
        let totalQuestions = 0;
        let completedQuestions = 0;

        updateAnswerCategories();

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
            showAnswerCategory();
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

    showAnswerCategory();
    updateAnswerProgress();
});
