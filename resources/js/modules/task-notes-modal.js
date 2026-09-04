document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('task-notes-modal');

    if (!modal) {
        return;
    }

    const content = document.getElementById('task-notes-modal-content');
    const loadingTemplate = document.getElementById('task-notes-loading-template');
    const errorTemplate = document.getElementById('task-notes-error-template');
    let activeUrl = null;

    const hasVisibleSiblingModal = () => {
        const insightsModal = document.getElementById('task-insights-modal');
        const activityModal = document.getElementById('activity-log-details-modal');

        return (insightsModal && !insightsModal.classList.contains('hidden')) ||
               (activityModal && !activityModal.classList.contains('hidden'));
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        if (!hasVisibleSiblingModal()) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    const setLoadingState = () => {
        if (loadingTemplate) {
            content.replaceChildren(loadingTemplate.content.cloneNode(true));
        }
    };

    const setErrorState = (errorMessage) => {
        if (errorTemplate) {
            const clone = errorTemplate.content.cloneNode(true);
            const msgNode = clone.querySelector('[data-task-notes-error-message]');
            if (msgNode && errorMessage) {
                msgNode.textContent = errorMessage;
            }
            content.replaceChildren(clone);
        }
    };

    const loadContent = async (url) => {
        if (!url || activeUrl === url) {
            return;
        }

        activeUrl = url;
        openModal();
        setLoadingState();

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'Unable to load notes & files.');
            }

            content.innerHTML = data.html;
            document.dispatchEvent(new CustomEvent('task-notes:loaded', {
                detail: { url, content },
            }));
        } catch (error) {
            setErrorState(error.message || 'Unable to load notes & files.');
        } finally {
            activeUrl = null;
        }
    };

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-task-notes-modal-trigger]');

        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            loadContent(trigger.dataset.taskNotesModalUrl);
            return;
        }

        if (event.target.closest('[data-task-notes-modal-close]') || event.target.closest('[data-task-notes-modal-overlay]')) {
            event.preventDefault();
            closeModal();
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden') && !hasVisibleSiblingModal()) {
            closeModal();
        }
    });
});
