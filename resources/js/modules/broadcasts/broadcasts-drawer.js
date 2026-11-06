/**
 * Global Broadcasts Drawer
 * Module: Broadcast
 * Scope: Read-only drawer for authenticated users to view eligible broadcasts.
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('broadcast-drawer-toggle-btn');
    const drawer = document.getElementById('broadcast-drawer');
    const backdrop = document.getElementById('broadcast-drawer-backdrop');
    const panel = document.getElementById('broadcast-drawer-panel');
    const drawerBody = document.getElementById('broadcast-drawer-body');
    const loadingState = document.getElementById('broadcast-drawer-loading');
    const errorState = document.getElementById('broadcast-drawer-error');
    const contentArea = document.getElementById('broadcast-drawer-content');
    const closeBtn = document.getElementById('close-broadcast-drawer-btn');
    const retryBtn = drawer?.querySelector('[data-broadcast-drawer-retry]');
    const countNumberEl = document.querySelector('[data-broadcast-count-number]');
    const countLabelEl = document.querySelector('[data-broadcast-count-label]');

    if (!toggleBtn || !drawer) {
        return;
    }

    let isDrawerOpen = false;
    let isFetchingCount = false;
    let isFetchingList = false;
    let isFetchingDetails = false;
    let isFetchingMore = false;
    let activeAbortController = null;

    const requestHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    };

    /**
     * Show Project Alert / Toast
     */
    function showToast(message, type = 'error') {
        if (window.Alert && typeof window.Alert[type] === 'function') {
            window.Alert[type](message);
        } else if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }
    }

    /**
     * Update Navbar Count display with correct singular/plural label.
     */
    function updateNavbarCount(count) {
        const numericCount = Math.max(0, parseInt(count, 10) || 0);
        if (countNumberEl) {
            countNumberEl.textContent = String(numericCount);
        }
        if (countLabelEl) {
            countLabelEl.textContent = numericCount === 1 ? 'Broadcast' : 'Broadcasts';
        }
    }

    /**
     * Fetch lightweight eligible broadcast count for navbar.
     */
    async function fetchBroadcastCount() {
        if (isFetchingCount) return;
        isFetchingCount = true;

        try {
            const response = await fetch('/broadcasts/drawer/count', {
                headers: requestHeaders,
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (data.status && typeof data.count !== 'undefined') {
                updateNavbarCount(data.count);
            }
        } catch (error) {
            // Silently fail for count without interrupting user
        } finally {
            isFetchingCount = false;
        }
    }

    /**
     * Open Drawer with smooth transition animations.
     */
    function openDrawer() {
        if (isDrawerOpen) return;
        isDrawerOpen = true;

        drawer.classList.remove('hidden');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                if (backdrop) {
                    backdrop.classList.remove('opacity-0');
                    backdrop.classList.add('opacity-100');
                }
                if (panel) {
                    panel.classList.remove('translate-x-full');
                    panel.classList.add('translate-x-0');
                }
            });
        });

        // Always fetch fresh broadcasts list when opening
        loadDrawerList();
    }

    /**
     * Close Drawer with smooth slide-out.
     */
    function closeDrawer() {
        if (!isDrawerOpen) return;
        isDrawerOpen = false;

        activeAbortController?.abort();
        activeAbortController = null;

        if (backdrop) {
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
        }
        if (panel) {
            panel.classList.remove('translate-x-0');
            panel.classList.add('translate-x-full');
        }

        setTimeout(() => {
            drawer.classList.add('hidden');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }

    /**
     * Set View State inside drawer: 'loading', 'error', 'content'
     */
    function setDrawerState(state) {
        if (loadingState) loadingState.classList.toggle('hidden', state !== 'loading');
        if (errorState) errorState.classList.toggle('hidden', state !== 'error');
        if (contentArea) contentArea.classList.toggle('hidden', state !== 'content');
    }

    /**
     * Load initial list of broadcasts.
     */
    async function loadDrawerList() {
        if (isFetchingList) return;
        isFetchingList = true;

        activeAbortController?.abort();
        activeAbortController = new AbortController();

        setDrawerState('loading');

        try {
            const response = await fetch('/broadcasts/drawer/list?page=1', {
                headers: requestHeaders,
                signal: activeAbortController.signal,
            });

            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }

            const data = await response.json();
            if (!data.status || typeof data.html !== 'string') {
                throw new Error('Invalid response structure');
            }

            if (contentArea) {
                contentArea.innerHTML = `
                    <div id="broadcast-drawer-list-container" data-broadcast-list-container>
                        ${data.html}
                    </div>
                    <div id="broadcast-drawer-detail-container" class="hidden" data-broadcast-detail-container></div>
                `;
            }

            setDrawerState('content');

            if (typeof data.total !== 'undefined') {
                updateNavbarCount(data.total);
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            setDrawerState('error');
        } finally {
            isFetchingList = false;
        }
    }

    /**
     * Load next page via Load More.
     */
    async function loadMoreBroadcasts(page, button) {
        if (isFetchingMore) return;
        isFetchingMore = true;

        const label = button.querySelector('[data-load-more-label]');
        const spinner = button.querySelector('[data-load-more-spinner]');

        button.disabled = true;
        label?.classList.add('opacity-50');
        spinner?.classList.remove('hidden');

        try {
            const response = await fetch(`/broadcasts/drawer/list?page=${page}`, {
                headers: requestHeaders,
            });

            if (!response.ok) {
                throw new Error('Failed to load more');
            }

            const data = await response.json();
            if (!data.status || typeof data.html !== 'string') {
                throw new Error('Invalid response');
            }

            const moreContainer = contentArea.querySelector('[data-broadcast-more-items]');
            if (moreContainer) {
                moreContainer.classList.remove('hidden');
                moreContainer.insertAdjacentHTML('beforeend', data.html);
            }

            const loadMoreWrapper = contentArea.querySelector('[data-broadcast-load-more-container]');
            if (data.has_more && data.next_page) {
                button.dataset.nextPage = String(data.next_page);
                button.disabled = false;
                label?.classList.remove('opacity-50');
                spinner?.classList.add('hidden');
            } else if (loadMoreWrapper) {
                loadMoreWrapper.remove();
            }
        } catch (error) {
            showToast('Unable to load more broadcasts. Please try again.', 'error');
            button.disabled = false;
            label?.classList.remove('opacity-50');
            spinner?.classList.add('hidden');
        } finally {
            isFetchingMore = false;
        }
    }

    /**
     * Load single broadcast details and display inside drawer.
     */
    async function loadBroadcastDetails(broadcastId) {
        if (isFetchingDetails) return;
        isFetchingDetails = true;

        const listContainer = contentArea.querySelector('[data-broadcast-list-container]');
        const detailContainer = contentArea.querySelector('[data-broadcast-detail-container]');

        if (!detailContainer) {
            isFetchingDetails = false;
            return;
        }

        detailContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="h-8 w-8 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="mt-3 text-xs font-medium text-bgray-600 dark:text-bgray-300">Loading details...</span>
            </div>
        `;

        listContainer?.classList.add('hidden');
        detailContainer.classList.remove('hidden');

        // Scroll to top of drawer
        if (drawerBody) {
            drawerBody.scrollTop = 0;
        }

        try {
            const response = await fetch(`/broadcasts/drawer/${broadcastId}`, {
                headers: requestHeaders,
            });

            if (!response.ok) {
                throw new Error('Unable to view details');
            }

            const data = await response.json();
            if (!data.status || typeof data.html !== 'string') {
                throw new Error('Invalid details response');
            }

            detailContainer.innerHTML = data.html;
        } catch (error) {
            showToast('Unable to load broadcast details.', 'error');
            detailContainer.classList.add('hidden');
            detailContainer.innerHTML = '';
            listContainer?.classList.remove('hidden');
        } finally {
            isFetchingDetails = false;
        }
    }

    /**
     * Return from Detail View back to the List View.
     */
    function backToList() {
        const listContainer = contentArea.querySelector('[data-broadcast-list-container]');
        const detailContainer = contentArea.querySelector('[data-broadcast-detail-container]');

        if (detailContainer) {
            detailContainer.classList.add('hidden');
            detailContainer.innerHTML = '';
        }

        if (listContainer) {
            listContainer.classList.remove('hidden');
        }
    }

    // Toggle Button Click
    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        openDrawer();
    });

    // Close Button Click
    closeBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        closeDrawer();
    });

    // Backdrop Click closes drawer
    backdrop?.addEventListener('click', (e) => {
        e.preventDefault();
        closeDrawer();
    });

    // Retry Button Click
    retryBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        loadDrawerList();
    });

    // Escape Key closes drawer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isDrawerOpen) {
            closeDrawer();
        }
    });

    // Delegated clicks inside Drawer Content
    contentArea?.addEventListener('click', (e) => {
        // View Details click
        const viewBtn = e.target.closest('[data-broadcast-view-details]');
        if (viewBtn) {
            e.preventDefault();
            const broadcastId = viewBtn.getAttribute('data-broadcast-view-details');
            if (broadcastId) {
                loadBroadcastDetails(broadcastId);
            }
            return;
        }

        // Back to List click
        const backBtn = e.target.closest('[data-broadcast-back-to-list]');
        if (backBtn) {
            e.preventDefault();
            backToList();
            return;
        }

        // Load More click
        const loadMoreBtn = e.target.closest('[data-broadcast-load-more-btn]');
        if (loadMoreBtn && !loadMoreBtn.disabled) {
            e.preventDefault();
            const nextPage = parseInt(loadMoreBtn.dataset.nextPage, 10);
            if (nextPage) {
                loadMoreBroadcasts(nextPage, loadMoreBtn);
            }
        }
    });

    // Initial load: Fetch lightweight broadcast count for navbar
    fetchBroadcastCount();
});

