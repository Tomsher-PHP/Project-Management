
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('task-insights-modal');

    if (!modal) return;

    let activeTrigger = null;
    let commentsLoadedTaskId = null;
    let commentsLoading = false;

    const commentsContent = document.getElementById(
        'task-insights-modal-content'
    );

    const hasVisibleSiblingModal = () => {
        const siblingModal = document.getElementById(
            'activity-log-details-modal'
        );

        return siblingModal && !siblingModal.classList.contains('hidden');
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        if (modal.classList.contains('hidden')) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        if (!hasVisibleSiblingModal()) {
            document.body.classList.remove('overflow-hidden');
        }

        document.dispatchEvent(
            new CustomEvent('task-insights:closed', {
                detail: {
                    taskId: String(activeTrigger?.dataset.taskId || ''),
                },
            })
        );
    };

    function activateTab(selectedTab) {
        modal.querySelectorAll('[data-task-insights-tab]').forEach((button) => {
            const isActive =
                button.dataset.taskInsightsTab === selectedTab;

            button.classList.toggle('border-success-300', isActive);
            button.classList.toggle('text-success-400', isActive);
            button.classList.toggle('border-transparent', !isActive);
            button.classList.toggle('text-bgray-500', !isActive);
        });

        modal.querySelectorAll('[data-task-insights-panel]').forEach((panel) => {
            const isActive =
                panel.dataset.taskInsightsPanel === selectedTab;

            panel.classList.toggle('hidden', !isActive);

            if (isActive && selectedTab === 'developer-notes') {
                panel.classList.add('flex');
            } else {
                panel.classList.remove('flex');
            }
        });
    }

    function showCommentsLoading() {
        if (!commentsContent) return;

        commentsContent.innerHTML = `
            <div class="flex min-h-[300px] flex-1 items-center justify-center px-6 py-10">
                <div class="text-center">
                    <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-bgray-200 border-t-success-300 dark:border-darkblack-400 dark:border-t-success-300"></div>
                    <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                        Loading comments...
                    </p>
                </div>
            </div>
        `;
    }

    async function loadComments() {
        if (!activeTrigger || commentsLoading || !commentsContent) return;

        const taskId = String(activeTrigger.dataset.taskId || '');
        const url = activeTrigger.dataset.taskInsightsUrl;

        if (!url) {
            Alert.error('Comments URL is missing.');
            return;
        }

        if (commentsLoadedTaskId === taskId) return;

        commentsLoading = true;
        showCommentsLoading();

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok || !data.success || !data.html) {
                throw new Error(
                    data.message || 'Unable to load task comments.'
                );
            }

            commentsContent.innerHTML = data.html;
            commentsLoadedTaskId = taskId;

            const commentsRoot = commentsContent.querySelector(
                '[data-task-comments-root]'
            );

            const count = commentsRoot?.dataset.totalComments;

            const countElement = modal.querySelector(
                '[data-task-insights-comments-count]'
            );

            if (countElement && count !== undefined) {
                countElement.textContent = count;
            }

            document.dispatchEvent(
                new CustomEvent('task-insights:loaded', {
                    detail: {
                        url,
                        content: commentsContent,
                    },
                })
            );
        } catch (error) {
            commentsContent.innerHTML = `
                <div class="flex flex-1 items-center justify-center p-8">
                    <p class="text-sm text-error-300">
                        ${error.message || 'Unable to load task comments.'}
                    </p>
                </div>
            `;

            Alert.error(
                error.message || 'Unable to load task comments.'
            );
        } finally {
            commentsLoading = false;
        }
    }

    function openActivityModal(trigger) {
        activeTrigger = trigger;
        commentsLoadedTaskId = null;

        const title = modal.querySelector('[data-task-insights-title]');

        if (title) {
            title.textContent =
                trigger.dataset.taskName || 'Task Activity';
        }

        openModal();

        const initialTab =
            trigger.dataset.taskInsightsInitialTab ||
            trigger.dataset.initialTab ||
            'developer-notes';

        const selectedTab =
            initialTab === 'comments' ? 'comments' : 'developer-notes';

        activateTab(selectedTab);

        if (selectedTab === 'comments') {
            loadComments();
        } else {
            document.dispatchEvent(
                new CustomEvent('task-insights:developer-notes:open', {
                    detail: {
                        trigger: activeTrigger,
                    },
                })
            );
        }
    }

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) return;

        const trigger = event.target.closest(
            '[data-task-activity-trigger], ' +
            '[data-task-insights-trigger], ' +
            '[data-developer-notes-trigger]'
        );

        if (trigger) {
            event.preventDefault();
            openActivityModal(trigger);
            return;
        }

        const tab = event.target.closest('[data-task-insights-tab]');

        if (tab) {
            const selectedTab = tab.dataset.taskInsightsTab;

            activateTab(selectedTab);

            if (selectedTab === 'comments') {
                loadComments();
            } else if (selectedTab === 'developer-notes') {
                document.dispatchEvent(
                    new CustomEvent(
                        'task-insights:developer-notes:open',
                        {
                            detail: {
                                trigger: activeTrigger,
                            },
                        }
                    )
                );
            }

            return;
        }

        if (
            event.target.closest('[data-task-insights-close]') ||
            event.target.closest('[data-task-insights-overlay]')
        ) {
            closeModal();
        }
    });

    document.addEventListener('task-insights:close', closeModal);

    document.addEventListener('keydown', function (event) {
        if (
            event.key === 'Escape' &&
            !modal.classList.contains('hidden') &&
            !hasVisibleSiblingModal()
        ) {
            closeModal();
        }
    });
});
