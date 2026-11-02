import Alert from '../../alert';

const openTaskLogModal = (modal) => {
    modal?.classList.remove('hidden');
};

const closeTaskLogModal = (modal) => {
    if (!modal) {
        return;
    }

    const content = modal.querySelector('[data-task-log-content]');

    modal.classList.add('hidden');

    if (content) {
        content.innerHTML = '';
    }
};

const showTaskLogLoading = (modal) => {
    const content = modal?.querySelector('[data-task-log-content]');

    if (!content) {
        return;
    }

    content.innerHTML = `
        <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">
            <div class="flex h-[82vh] items-center justify-center px-6 py-12 text-sm font-medium text-bgray-700 dark:text-bgray-300">
                Loading task logs...
            </div>
        </div>
    `;
};

const loadTaskLogModal = async (modal, loadUrl) => {
    const content = modal?.querySelector('[data-task-log-content]');

    if (!modal || !content || !loadUrl) {
        return;
    }

    openTaskLogModal(modal);
    showTaskLogLoading(modal);

    try {
        const response = await fetch(loadUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load task log details.');
        }

        content.innerHTML = result.html;

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(content);
        }
    } catch (error) {
        closeTaskLogModal(modal);
        Alert.errorModal(error.message || 'Unable to load task log details.');
    }
};

export const initializeTaskLogViewer = (root) => {
    if (!root || root.dataset.taskLogViewerInitialized === 'true') {
        return;
    }

    const modal = root.querySelector('[data-task-log-modal]');

    if (!modal) {
        return;
    }

    root.dataset.taskLogViewerInitialized = 'true';

    root.addEventListener('click', async (event) => {
        const openButton = event.target.closest('[data-task-log-open]');

        if (openButton && root.contains(openButton)) {
            await loadTaskLogModal(modal, openButton.dataset.taskLogUrl || '');
            return;
        }

        const closeButton = event.target.closest('[data-task-log-close]');

        if (closeButton && root.contains(closeButton)) {
            closeTaskLogModal(modal);
            return;
        }

        const modalTarget = event.target.closest('[data-task-log-modal]');
        const contentTarget = event.target.closest('[data-task-log-content]');

        if (modalTarget === modal && !contentTarget) {
            closeTaskLogModal(modal);
        }
    });
};
