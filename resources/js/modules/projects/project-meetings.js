import Alert from '../../alert';

const meetingListPaginationObservers = new WeakMap();

const initializeMeetingListPagination = (group) => {
    const scrollContainer = group.closest('[data-project-meetings-scroll]');
    const sentinel = group.querySelector('[data-project-meeting-group-sentinel]');
    const loadUrl = group.dataset.loadUrl || '';
    const existingObserver = meetingListPaginationObservers.get(group);

    if (existingObserver) {
        existingObserver.disconnect();
        meetingListPaginationObservers.delete(group);
    }

    if (!group || !scrollContainer || !sentinel || !loadUrl || group.dataset.hasMorePages !== 'true') {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        const hasVisibleEntry = entries.some((entry) => entry.isIntersecting);

        if (!hasVisibleEntry || group.dataset.loading === 'true') {
            return;
        }

        const nextPage = Number(group.dataset.nextPage || 0);

        if (!nextPage) {
            return;
        }

        loadMoreGroupMeetings(group, nextPage).catch((error) => {
            console.error('Error loading more meetings:', error);
            Alert.error(error.message || 'Unable to load more meetings.');
        });
    }, {
        root: scrollContainer,
        threshold: 0,
        rootMargin: '220px 0px',
    });

    observer.observe(sentinel);
    meetingListPaginationObservers.set(group, observer);
};

const loadMoreGroupMeetings = async (group, page) => {
    const loadUrl = group.dataset.loadUrl;

    if (!group || !loadUrl || group.dataset.loading === 'true') {
        return;
    }

    group.dataset.loading = 'true';
    group.querySelector('[data-project-meeting-group-loading]')?.removeAttribute('hidden');

    try {
        const requestUrl = new URL(loadUrl, window.location.origin);
        requestUrl.searchParams.set('page', String(page));

        const response = await fetch(requestUrl.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const result = await response.json();

        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Unable to load more meetings.');
        }

        if (result.items_html) {
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = result.items_html;
            const newChildren = Array.from(wrapper.children);

            const sentinelRow = group.querySelector('[data-project-meeting-group-sentinel-row]');

            newChildren.forEach((child) => {
                if (sentinelRow) {
                    group.insertBefore(child, sentinelRow);
                } else {
                    group.appendChild(child);
                }
            });
        }

        group.dataset.currentPage = String(result.pagination?.page || page);
        group.dataset.nextPage = result.pagination?.next_page ? String(result.pagination.next_page) : '';
        group.dataset.hasMorePages = result.pagination?.has_more_pages ? 'true' : 'false';

        if (group.dataset.hasMorePages !== 'true') {
            group.querySelector('[data-project-meeting-group-sentinel-row]')?.remove();
        }

        initializeMeetingListPagination(group);
    } finally {
        delete group.dataset.loading;

        if (group.dataset.hasMorePages === 'true') {
            group.querySelector('[data-project-meeting-group-loading]')?.setAttribute('hidden', 'hidden');
        }
    }
};

const setupProjectMeetings = (root = document) => {
    const groups = root.querySelectorAll('[data-project-meeting-group]');
    groups.forEach((group) => {
        initializeMeetingListPagination(group);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupProjectMeetings();

    document.addEventListener('project-tab:loaded', (e) => {
        if (e.detail?.tab === 'meetings' && e.detail?.panel) {
            setupProjectMeetings(e.detail.panel);
        }
    });

    // Delegated click handler for preview meeting buttons / rows inside project meetings tab
    document.addEventListener('click', (e) => {
        if (e.target.closest('.edit-meeting-btn')) {
            return;
        }
        const btn = e.target.closest('[data-project-tab-panel="meetings"] .preview-meeting-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const meetingId = btn.dataset.id;
            if (meetingId && typeof window.openMeetingPreview === 'function') {
                window.openMeetingPreview(meetingId);
            }
        }
    });
});

export { setupProjectMeetings };
