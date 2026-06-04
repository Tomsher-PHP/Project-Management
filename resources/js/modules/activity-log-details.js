document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('activity-log-details-modal');

    if (!modal) {
        return;
    }

    const content = document.getElementById('activity-log-modal-content');
    const hasVisibleSiblingModal = () => {
        const siblingModal = document.getElementById('project-insights-modal');

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

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-activity-log-view]');

        if (trigger) {
            openModal();
            content.innerHTML = `
                <div class="px-6 py-10 text-center">
                    <p class="text-sm font-medium text-bgray-700 dark:text-bgray-300">Loading activity details...</p>
                </div>
            `;

            fetch(trigger.dataset.activityLogUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async (response) => {
                    const data = await response.json();

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Unable to load activity details.');
                    }

                    content.innerHTML = data.html;
                })
                .catch((error) => {
                    closeModal();
                    console.error('Failed to load activity log details.', error);
                    Alert.error(error.message || 'Unable to load activity details.');
                });

            return;
        }

        if (event.target.closest('[data-activity-log-close]') || event.target.closest('[data-activity-log-overlay]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
