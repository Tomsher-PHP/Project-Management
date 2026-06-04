const scrollTaskCommentsToBottom = (root = document) => {
    const scroller = root.querySelector ? root.querySelector('[data-task-comments-scroll]') : document.querySelector('[data-task-comments-scroll]');

    if (!scroller) {
        return;
    }

    scroller.scrollTop = scroller.scrollHeight;
};

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

    form.addEventListener('submit', async (event) => {
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: new FormData(form),
            });
            const result = await response.json();

            if (response.status === 422 && result.errors) {
                showError(result.errors.comment?.[0] || result.message || 'Please enter a comment.');
                throw new Error('');
            }

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Unable to add the comment.');
            }

            const modalContent = document.getElementById('task-insights-modal-content');

            if (modalContent && result.html) {
                modalContent.innerHTML = result.html;
                initializeTaskComments(modalContent);
            }

            const countBadge = document.querySelector('[data-task-comments-count]');

            if (countBadge && typeof result.count !== 'undefined') {
                countBadge.textContent = String(result.count);
            }

            Alert.success(result.message || 'Comment added successfully.');
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

document.addEventListener('DOMContentLoaded', function () {
    initializeTaskComments();
});

document.addEventListener('task-insights:loaded', function (event) {
    initializeTaskComments(event.detail?.content);
});
