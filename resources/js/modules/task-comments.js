const scrollTaskCommentsToBottom = (root = document) => {
    const scroller = root.querySelector ? root.querySelector('[data-task-comments-scroll]') : document.querySelector('[data-task-comments-scroll]');

    if (!scroller) {
        return;
    }

    scroller.scrollTop = scroller.scrollHeight;
};


/**
 * Focus the comment textarea.
 * Uses two animation frames to ensure the Comments panel is visible.
 */
const focusTaskCommentInput = () => {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            const modal = document.getElementById('task-insights-modal');

            if (!modal || modal.classList.contains('hidden')) {
                return;
            }

            const commentsPanel = modal.querySelector(
                '[data-task-insights-panel="comments"]'
            );

            // Do not focus if the Comments tab is not active.
            if (!commentsPanel || commentsPanel.classList.contains('hidden')) {
                return;
            }

            const input = commentsPanel.querySelector(
                '[data-task-comment-input]'
            );

            if (!input) {
                return;
            }

            input.focus();

            // Place cursor at the end of existing content.
            const length = input.value.length;
            input.setSelectionRange(length, length);
        });
    });
};


/**
 * Initialize comments functionality.
 */
const initializeTaskComments = (root = document) => {
    const commentsRoot = root.querySelector ? root.querySelector('[data-task-comments-root]') : document.querySelector('[data-task-comments-root]');

    if (!commentsRoot) {
        return;
    }

    scrollTaskCommentsToBottom(commentsRoot);

    if (commentsRoot.dataset.initialized === 'true') {
        return;
    }

    const form = commentsRoot.querySelector('[data-task-comment-form]');
    const input = commentsRoot.querySelector('[data-task-comment-input]');
    const submitButton = commentsRoot.querySelector('[data-task-comment-submit]');
    const errorNode = commentsRoot.querySelector('[data-task-comment-error]');

    if (!form || !input || !submitButton) {
        commentsRoot.dataset.initialized = 'true';
        return;
    }

    const clearError = () => {
        if (!errorNode) {
            return;
        }

        errorNode.textContent = '';
        errorNode.classList.add('hidden');
    };

    const showError = (message) => {
        if (!errorNode) {
            return;
        }

        errorNode.textContent = message;
        errorNode.classList.remove('hidden');
    };

    // Ctrl + Enter submits the comment.
    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !event.ctrlKey) {
            return;
        }

        event.preventDefault();

        if (submitButton.disabled) {
            return;
        }

        form.requestSubmit();
    });

    // Submit comment.
    form.addEventListener('submit', async (event) => {
        const taskId = String(commentsRoot.dataset.taskId || '');

        event.preventDefault();
        clearError();

        const actionUrl = form.getAttribute('action');

        if (!actionUrl) {
            Alert.error('Unable to add the comment right now.');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';

        try {
            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: new FormData(form),
            });

            const result = await response.json();

            if (response.status === 422 && result.errors) {
                showError(
                    result.errors.comment?.[0] ||
                    result.message ||
                    'Please enter a comment.'
                );

                throw new Error('');
            }

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message || 'Unable to add the comment.'
                );
            }

            // Refresh comments HTML.
            const modalContent = document.getElementById(
                'task-insights-modal-content'
            );

            if (modalContent && result.html) {
                modalContent.innerHTML = result.html;

                initializeTaskComments(modalContent);

                // Keep the list scrolled to the latest comment.
                scrollTaskCommentsToBottom(modalContent);
            }

            // Update comments count in the modal.
            const countBadge = document.querySelector(
                '[data-task-insights-comments-count]'
            );

            if (countBadge && typeof result.count !== 'undefined') {
                countBadge.textContent = String(result.count);
            }

            // Update comments count and total Activity count on Kanban cards.
            if (taskId && typeof result.count !== 'undefined') {
                document.querySelectorAll(
                    '[data-task-activity-trigger]'
                ).forEach((button) => {
                    if (String(button.dataset.taskId) !== taskId) {
                        return;
                    }

                    const commentsCount = Number(result.count);

                    const developerNotesCount = Number(
                        button.dataset.developerNotesCount || 0
                    );

                    button.dataset.commentsCount = String(commentsCount);

                    const totalCount =
                        commentsCount + developerNotesCount;

                    const countElement = button.querySelector(
                        '[data-task-activity-count]'
                    );

                    if (countElement) {
                        countElement.textContent = String(totalCount);
                    }

                    button.title =
                        `Completion Notes: ${developerNotesCount} | Comments: ${commentsCount}`;

                    button.setAttribute(
                        'aria-label',
                        `Task Activity: ${developerNotesCount} developer notes and ${commentsCount} comments`
                    );
                });
            }

            Alert.success(
                result.message || 'Comment added successfully.'
            );

            document.dispatchEvent(
                new CustomEvent('task-insights:close')
            );

        } catch (error) {
            if (error.message) {
                Alert.error(error.message);
            }

        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Send';
        }
    });

    commentsRoot.dataset.initialized = 'true';
};


/**
 * Initialize comments when the page loads.
 */
document.addEventListener('DOMContentLoaded', () => {
    initializeTaskComments();
});


/**
 * Initialize comments after dynamically loading their HTML.
 * Focus only if the Comments panel is currently visible.
 */
document.addEventListener('task-insights:loaded', (event) => {
    const content = event.detail?.content || document;

    initializeTaskComments(content);

    const modal = document.getElementById('task-insights-modal');
    const commentsPanel = modal?.querySelector(
        '[data-task-insights-panel="comments"]'
    );

    if (
        commentsPanel &&
        !commentsPanel.classList.contains('hidden')
    ) {
        focusTaskCommentInput();
    }
});


/**
 * Focus the textarea every time the Comments tab is clicked.
 * This also works when comments have already been initialized.
 */
document.addEventListener('click', (event) => {
    const commentsTab = event.target.closest(
        '[data-task-insights-tab="comments"]'
    );

    if (!commentsTab) {
        return;
    }

    // Wait until the existing tab-switching handler makes the panel visible.
    focusTaskCommentInput();
});
