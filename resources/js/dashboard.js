document.addEventListener('DOMContentLoaded', () => {
    const summarySection = document.querySelector('[data-dashboard-summary-section]');
    if (!summarySection) return;

    const summaryUrl = summarySection.getAttribute('data-dashboard-summary-url');
    if (!summaryUrl) return;

    const animateCounter = (element, target, duration = 700) => {
        // Clear skeleton loader if present
        const skeleton = element.querySelector('.animate-pulse');
        if (skeleton) {
            element.innerText = '0';
        }

        const start = parseInt(element.innerText.replace(/,/g, '')) || 0;
        const targetValue = parseInt(target) || 0;

        if (start === targetValue) {
            element.innerText = targetValue.toLocaleString();
            return;
        }

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

    const loadDashboardSummary = async () => {
        try {
            const response = await fetch(summaryUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Dashboard summary fetch failed');

            const result = await response.json();

            if (result.success && result.data) {
                Object.entries(result.data).forEach(([key, value]) => {
                    const elements = summarySection.querySelectorAll(`[data-dashboard-count="${key}"]`);
                    elements.forEach(element => {
                        animateCounter(element, value);
                    });
                });
            }
        } catch (error) {
            console.warn('Dashboard Summary Error:', error);
            // Fallback: clear skeletons if it fails
            const countElements = summarySection.querySelectorAll('[data-dashboard-count]');
            countElements.forEach(element => {
                const skeleton = element.querySelector('.animate-pulse');
                if (skeleton) {
                    element.innerText = '0';
                }
            });
        }
    };

    loadDashboardSummary();
});
