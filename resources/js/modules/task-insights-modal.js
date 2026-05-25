document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('task-insights-modal');

    if (!modal) {
        return;
    }

    const content = document.getElementById('task-insights-modal-content');
    let activeUrl = null;
    const hasVisibleSiblingModal = () => {
        const siblingModal = document.getElementById('activity-log-details-modal');

        return siblingModal && !siblingModal.classList.contains('hidden');
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
        content.innerHTML = `
            <div class="flex min-h-[420px] flex-1 items-center justify-center px-6 py-10">
                <div class="text-center">
                    <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-bgray-200 border-t-success-300 dark:border-darkblack-400 dark:border-t-success-300"></div>
                    <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">Loading task details...</p>
                </div>
            </div>
        `;
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
                throw new Error(data.message || 'Unable to load the task details.');
            }

            content.innerHTML = data.html;
            document.dispatchEvent(new CustomEvent('task-insights:loaded', {
                detail: { url, content },
            }));
        } catch (error) {
            closeModal();
            Alert.error(error.message || 'Unable to load the task details.');
        } finally {
            activeUrl = null;
        }
    };

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-task-insights-trigger]');

        if (trigger) {
            loadContent(trigger.dataset.taskInsightsUrl);
            return;
        }

        if (event.target.closest('[data-task-insights-close]') || event.target.closest('[data-task-insights-overlay]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden') && !hasVisibleSiblingModal()) {
            closeModal();
        }
    });
});
