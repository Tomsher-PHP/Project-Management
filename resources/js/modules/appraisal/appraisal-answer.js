document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-appraisal-answer-page]');
    const pageDataElement = root?.querySelector('[data-appraisal-answer-page-data]');

    if (!root || !pageDataElement) {
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
    const reviewerCommentTextarea = root.querySelector('[data-appraisal-reviewer-comment-textarea]');
    const acknowledgementButton = root.querySelector('[data-appraisal-acknowledge-review]');
    const acknowledgementRemark = root.querySelector('[data-appraisal-acknowledgement-remark]');
    let activeAnswerCategoryId = answerFormData.categories?.[0]?.id || null;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const alertSuccess = (message) => window.Alert?.success ? window.Alert.success(message) : window.alert(message);
    const alertError = (message) => window.Alert?.error ? window.Alert.error(message) : window.alert(message);
    const confirmAction = async (options) => {
        if (window.Alert?.confirm) {
            return (await window.Alert.confirm(options)).isConfirmed;
        }

        return window.confirm(options.text || options.title || 'Are you sure?');
    };
    const allQuestions = () => (answerFormData.categories || []).flatMap((category) => category.questions || []);
    const currentReview = (question) => (question.reviews || []).find((review) => review.is_current) || null;
    const hasValue = (value) => value !== undefined && value !== null && String(value).trim() !== '';
    const isRatingCompleted = (rating, remark) => {
        const numericRating = Number(rating);

        return hasValue(rating)
            && !Number.isNaN(numericRating)
            && numericRating >= 0.1
            && numericRating <= 5
            && Number(numericRating.toFixed(1)) === numericRating
            && hasValue(remark);
    };

    const isQuestionCompleted = (question) => {
        const role = answerFormData.role;

        if (question.question_type === 'answer') {
            return role !== 'assignee' || hasValue(question.answer?.answer);
        }

        if (question.question_type === 'target') {
            return role === 'assignee'
                ? hasValue(question.answer?.achieved_value) && !Number.isNaN(Number(question.answer.achieved_value))
                : role !== 'reviewer' || hasValue(currentReview(question)?.remark);
        }

        const response = role === 'reviewer' ? currentReview(question) : question.answer;

        return role === 'viewer' || isRatingCompleted(response?.rating, response?.remark);
    };

    const persistVisibleAnswerValues = () => {
        answerQuestions?.querySelectorAll('[data-appraisal-answer-input]:not([readonly]):not(:disabled)').forEach((input) => {
            const question = allQuestions().find((item) => Number(item.id) === Number(input.dataset.questionId));
            const field = input.dataset.answerField;

            if (!question || !field) {
                return;
            }

            if (input.dataset.answerScope === 'review') {
                const review = (question.reviews || []).find(
                    (item) => Number(item.appraisal_reviewer_id) === Number(input.dataset.reviewerId)
                );

                if (review) {
                    review[field] = input.value;
                }
            } else {
                question.answer = { ...(question.answer || {}), [field]: input.value };
            }
        });
    };

    const activeCategoryClasses = ['border-success-300', 'bg-success-50', 'text-success-500', 'dark:border-success-900/50', 'dark:bg-darkblack-600', 'dark:text-success-300'];
    const inactiveCategoryClasses = ['border-bgray-200', 'bg-white', 'text-bgray-700', 'hover:border-success-200', 'hover:text-success-400', 'dark:border-darkblack-400', 'dark:bg-darkblack-600', 'dark:text-bgray-50'];

    const updateAnswerCategories = () => {
        answerCategories?.querySelectorAll('[data-appraisal-answer-category-id]').forEach((button) => {
            const category = (answerFormData.categories || []).find(
                (item) => Number(item.id) === Number(button.dataset.appraisalAnswerCategoryId)
            );

            if (!category) {
                return;
            }

            const answeredCount = (category.questions || []).filter(isQuestionCompleted).length;
            const totalQuestions = (category.questions || []).length;
            const isCompleted = answeredCount === totalQuestions;
            const isActive = Number(category.id) === Number(activeAnswerCategoryId);
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
        const questions = allQuestions();
        const completedQuestions = questions.filter(isQuestionCompleted).length;
        const percentage = questions.length ? Math.round((completedQuestions / questions.length) * 100) : 0;
        const allCompleted = questions.length > 0 && completedQuestions === questions.length;

        updateAnswerCategories();
        if (overallCount) overallCount.textContent = `${completedQuestions} / ${questions.length} Questions`;
        if (overallPercentage) overallPercentage.textContent = `${percentage}%`;
        if (overallBar) overallBar.style.width = `${percentage}%`;

        if (answerFormData.is_submitted) {
            answerSaveDraft?.classList.add('hidden');
            answerSubmit?.classList.add('hidden');
            answerHelperMessage?.classList.add('hidden');
            return;
        }

        answerSaveDraft?.classList.remove('hidden');
        answerSubmit?.classList.remove('hidden');
        if (answerSubmit) {
            answerSubmit.disabled = !allCompleted;
            answerSubmit.classList.toggle('opacity-50', !allCompleted);
            answerSubmit.classList.toggle('cursor-not-allowed', !allCompleted);
        }
        answerHelperMessage?.classList.toggle('hidden', allCompleted);
    };

    const buildAnswersPayload = () => allQuestions().map((question) => {
        const base = { question_id: question.id };

        if (answerFormData.role === 'assignee') {
            if (question.question_type === 'answer') {
                return { ...base, assignee_answer: hasValue(question.answer?.answer) ? String(question.answer.answer).trim() : null };
            }
            if (question.question_type === 'target') {
                return { ...base, achieved_value: hasValue(question.answer?.achieved_value) ? Number(question.answer.achieved_value) : null };
            }

            return {
                ...base,
                rating: hasValue(question.answer?.rating) ? Number(question.answer.rating) : null,
                remark: hasValue(question.answer?.remark) ? String(question.answer.remark).trim() : null,
            };
        }

        const review = currentReview(question);

        if (question.question_type === 'rating') {
            return {
                ...base,
                rating: hasValue(review?.rating) ? Number(review.rating) : null,
                remark: hasValue(review?.remark) ? String(review.remark).trim() : null,
            };
        }
        if (question.question_type === 'target') {
            return { ...base, remark: hasValue(review?.remark) ? String(review.remark).trim() : null };
        }

        return base;
    });

    const sendAnswers = async (url, submitting) => {
        const button = submitting ? answerSubmit : answerSaveDraft;

        if (!button || answerFormData.is_submitted) {
            return;
        }

        persistVisibleAnswerValues();
        if (submitting && !allQuestions().every(isQuestionCompleted)) {
            alertError('Please complete all questions before submitting.');
            return;
        }

        const overallComment = answerFormData.role === 'reviewer' ? reviewerCommentTextarea?.value ?? '' : null;
        if (submitting && answerFormData.role === 'reviewer' && !String(overallComment).trim()) {
            const confirmed = await confirmAction({
                title: 'Submit Without Overall Comment?',
                text: 'You have not entered an overall comment. Are you sure you want to submit?',
                confirmText: 'Submit Anyway',
                cancelText: 'Go Back',
                icon: 'warning',
            });
            if (!confirmed) return;
        }

        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = submitting ? 'Submitting...' : 'Saving...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ answers: buildAnswersPayload(), overall_comment: overallComment }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to save appraisal answers.');
            }

            await alertSuccess(payload.message || (submitting ? 'Appraisal answers submitted successfully.' : 'Draft saved successfully.'));
            if (submitting) window.location.href = root.dataset.indexUrl;
        } catch (error) {
            alertError(error.message || 'Unable to save appraisal answers.');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
            updateAnswerProgress();
        }
    };

    const acknowledgeReview = async () => {
        if (!acknowledgementButton) {
            return;
        }

        const originalText = acknowledgementButton.textContent;
        acknowledgementButton.disabled = true;
        acknowledgementButton.textContent = 'Acknowledging...';

        try {
            const response = await fetch(root.dataset.acknowledgeReviewUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    appraisal_reviewer_id: Number(acknowledgementButton.dataset.appraisalReviewerId),
                    acknowledgement_remark: acknowledgementRemark?.value.trim() || null,
                }),
            });
            const payload = await response.json();

            if (!response.ok || !payload.status) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                throw new Error(errors[0] || payload.message || 'Unable to acknowledge this reviewer submission.');
            }

            await alertSuccess(payload.message || 'Reviewer submission acknowledged successfully.');
            window.location.reload();
        } catch (error) {
            alertError(error.message || 'Unable to acknowledge this reviewer submission.');
            acknowledgementButton.disabled = false;
            acknowledgementButton.textContent = originalText;
        }
    };

    const syncAnswerInput = (input) => {
        persistVisibleAnswerValues();

        if (input.dataset.answerField === 'achieved_value') {
            const target = Number(input.dataset.targetValue);
            const achieved = Number(input.value);
            const output = input.closest('[data-appraisal-answer-question-card]')?.querySelector('[data-appraisal-target-achievement]');

            if (output) {
                output.textContent = input.value !== '' && target > 0 && !Number.isNaN(achieved)
                    ? `${((achieved / target) * 100).toFixed(2)}%`
                    : '—';
            }
        }

        updateAnswerProgress();
    };

    root.addEventListener('input', (event) => {
        if (event.target.matches('[data-appraisal-answer-input]')) syncAnswerInput(event.target);
    });
    root.addEventListener('change', (event) => {
        if (event.target.matches('[data-appraisal-answer-input]')) syncAnswerInput(event.target);
    });
    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-appraisal-acknowledge-review]')) {
            acknowledgeReview();
            return;
        }

        if (event.target.closest('[data-appraisal-answer-save-draft]')) {
            sendAnswers(root.dataset.saveDraftUrl, false);
            return;
        }
        if (event.target.closest('[data-appraisal-answer-submit]')) {
            sendAnswers(root.dataset.submitAnswersUrl, true);
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
        const card = questionHeader?.closest('[data-appraisal-answer-question-card]');
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
