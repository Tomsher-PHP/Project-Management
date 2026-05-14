document.addEventListener('DOMContentLoaded', () => {
    const summarySection = document.querySelector('[data-workspace-summary-section]');
    if (!summarySection) return;

    const summaryUrl = summarySection.dataset.workspaceSummaryUrl;
    if (!summaryUrl) return;

    /**
     * Animate counter from start value to target value.
     */
    const animateCounter = (element, target, duration = 700) => {
        const start = parseInt(element.innerText.replace(/,/g, '')) || 0;
        const targetValue = parseInt(target) || 0;
        
        if (start === targetValue) return;

        const change = targetValue - start;
        const startTime = performance.now();

        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Ease out quad: f(t) = t(2-t)
            const easedProgress = progress * (2 - progress);
            
            const currentValue = Math.floor(start + (change * easedProgress));
            element.innerText = currentValue.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.innerText = targetValue.toLocaleString();
            }
        };

        requestAnimationFrame(updateCounter);
    };

    /**
     * Resolve the selected user ID from the workspace user selector.
     */
    const getSelectedWorkspaceUserId = () => {
        // Based on the selector used in workspace-user-selector.js
        const select = document.querySelector('[data-workspace-user-select]');
        return select ? select.value : '';
    };

    /**
     * Load summary data via AJAX and update tiles.
     */
    const loadWorkspaceSummary = async () => {
        const userId = getSelectedWorkspaceUserId();
        const url = new URL(summaryUrl, window.location.origin);
        
        if (userId) {
            url.searchParams.set('user_id', userId);
        }

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Summary fetch failed');

            const result = await response.json();

            if (result.success && result.data) {
                Object.entries(result.data).forEach(([key, value]) => {
                    const element = summarySection.querySelector(`[data-workspace-summary-count="${key}"]`);
                    if (element) {
                        animateCounter(element, value);
                    }
                });

                document.dispatchEvent(new CustomEvent('workspace:summary-loaded', { detail: result.data }));
            }
        } catch (error) {
            console.warn('Workspace Summary Error:', error);
        }
    };

    // Initial load
    loadWorkspaceSummary();

    // Event listeners for re-loading summary
    document.addEventListener('workspace:user-changed', loadWorkspaceSummary);
    document.addEventListener('workspace:kanban-refreshed', loadWorkspaceSummary);
});
