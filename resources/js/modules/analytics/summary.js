import flatpickr from "flatpickr";
import { initDatepicker } from "../../components/datepicker";

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
     * Store chart instances to destroy them before re-rendering.
     */
    const workspaceCharts = new Map();

    const destroyChart = (chartId) => {
        if (workspaceCharts.has(chartId)) {
            workspaceCharts.get(chartId).destroy();
            workspaceCharts.delete(chartId);
        }
    };

    /**
     * Toggle chart empty state based on data availability.
     */
    const toggleChartEmptyState = (card, hasData) => {
        if (!card) return;
        const emptyState = card.querySelector('[data-chart-empty-state]');
        const content = card.querySelector('[data-chart-content]');

        if (emptyState && content) {
            emptyState.classList.toggle('hidden', hasData);
            content.classList.toggle('hidden', !hasData);
        }
    };

    /**
     * Render a donut/pie chart.
     */
    const renderWorkspaceDonutChart = (canvasId, data) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof window.Chart === 'undefined') return;

        const card = canvas.closest('[data-workspace-chart]');
        const hasData = data.values && data.values.some(v => Number(v) > 0);

        // Toggle empty state
        toggleChartEmptyState(card, hasData);

        if (!hasData) {
            destroyChart(canvasId);
            return;
        }

        destroyChart(canvasId);

        const ctx = canvas.getContext('2d');
        const totalValue = data.values.reduce((a, b) => a + b, 0);

        // Update total display if exists
        const totalNode = card ? card.querySelector('[data-chart-total]') : null;
        if (totalNode) {
            if (card.dataset.workspaceChart === 'time-comparison' && data.formatted_values?.length > 1) {
                // Show the second value (Task Worked Time) in the center for this specific chart
                const valueSpan = totalNode.querySelector('span:last-child');
                const isTargetMet = data.values[1] >= data.values[0];
                const colorClass = isTargetMet ? 'text-success-400 dark:text-success-400' : 'text-error-300 dark:text-error-400';

                if (valueSpan) {
                    valueSpan.innerText = data.formatted_values[1];
                    valueSpan.className = `text-xs font-bold ${colorClass}`;
                } else {
                    totalNode.innerText = data.formatted_values[1];
                }
            } else {
                totalNode.innerText = totalValue.toLocaleString();
            }
        }

        // Generate Legend
        const legendContainer = card ? card.querySelector('[data-chart-legend]') : null;
        if (legendContainer) {
            const isTimeComparison = card.dataset.workspaceChart === 'time-comparison';
            const shiftValue = isTimeComparison ? (data.values[0] || 0) : 0;

            legendContainer.innerHTML = data.labels.map((label, index) => {
                const value = data.values[index];
                const formattedValue = data.formatted_values ? data.formatted_values[index] : value.toLocaleString();
                const color = data.colors[index];

                let percentageHtml = '';
                let valueColorClass = 'text-bgray-900 dark:text-white';

                if (isTimeComparison) {
                    if (index === 0) {
                        // Shift Work Time: No percentage
                        percentageHtml = '';
                    } else if (index === 1) {
                        // Task Worked Time: Compare to Shift
                        const percentage = shiftValue > 0 ? Math.round((value / shiftValue) * 100) : 0;
                        const isTargetMet = value >= shiftValue;
                        const statusClass = isTargetMet
                            ? 'text-success-400 bg-success-50 dark:bg-success-500/10'
                            : 'text-error-300 bg-error-50 dark:bg-error-500/10';

                        valueColorClass = isTargetMet ? 'text-success-400 dark:text-success-400' : 'text-error-300 dark:text-error-400';

                        percentageHtml = `
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ${statusClass}">
                                ${percentage}%
                            </span>
                        `;
                    } else if (index === 2) {
                        // Total Break Time: Compare to Shift (Neutral)
                        const percentage = shiftValue > 0 ? Math.round((value / shiftValue) * 100) : 0;
                        percentageHtml = `
                            <span class="rounded-full bg-bgray-50 px-2 py-0.5 text-[10px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                                ${percentage}%
                            </span>
                        `;
                    }
                } else {
                    // Standard Donut logic: % of total
                    const percentage = totalValue > 0 ? Math.round((value / totalValue) * 100) : 0;
                    percentageHtml = `
                        <span class="rounded-full bg-bgray-50 px-2 py-0.5 text-[10px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                            ${percentage}%
                        </span>
                    `;
                }

                return `
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-bgray-200 px-4 py-2 dark:border-darkblack-400">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="h-2 w-2 flex-shrink-0 rounded-full" style="background-color: ${color};"></span>
                            <span class="truncate text-xs font-semibold text-bgray-700 dark:text-bgray-300">${label}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold ${valueColorClass}">${formattedValue}</span>
                            ${percentageHtml}
                        </div>
                    </div>
                `;
            }).join('');
        }

        const chart = new window.Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.colors,
                    borderWidth: 0,
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const index = context.dataIndex;
                                const value = context.raw;
                                const formattedValue = data.formatted_values ? data.formatted_values[index] : value.toLocaleString();
                                const percentage = totalValue > 0 ? Math.round((value / totalValue) * 100) : 0;
                                return `${context.label}: ${formattedValue} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        workspaceCharts.set(canvasId, chart);
    };

    /**
     * Resolve the selected project ID from the workspace project filter.
     */
    const getSelectedProjectId = () => {
        const select = document.querySelector('[data-workspace-project-filter], [data-filter-project]');
        return select ? select.value : null;
    };

    /**
     * AJAX Chart Loaders
     */
    const loadTaskStatusChart = async () => {
        const card = document.querySelector('[data-workspace-chart="task-status"]');
        if (!card) return;

        const userId = getSelectedWorkspaceUserId();
        const projectId = getSelectedProjectId();
        const url = new URL(card.dataset.taskStatusChartUrl, window.location.origin);

        if (userId) url.searchParams.set('user_id', userId);
        if (projectId) url.searchParams.set('project_id', projectId);

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.success) renderWorkspaceDonutChart('workspaceTaskStatusChart', result.data);
        } catch (e) { console.warn('Status Chart Error:', e); }
    };

    const loadTaskPriorityChart = async () => {
        const card = document.querySelector('[data-workspace-chart="task-priority"]');
        if (!card) return;

        const userId = getSelectedWorkspaceUserId();
        const url = new URL(card.dataset.taskPriorityChartUrl, window.location.origin);
        if (userId) url.searchParams.set('user_id', userId);

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.success) renderWorkspaceDonutChart('workspaceTaskPriorityChart', result.data);
        } catch (e) { console.warn('Priority Chart Error:', e); }
    };

    const loadTimeComparisonChart = async () => {
        const card = document.querySelector('[data-workspace-chart="time-comparison"]');
        if (!card) return;

        const userId = getSelectedWorkspaceUserId();
        const dateInput = card.querySelector('[data-workspace-time-chart-date]');
        const url = new URL(card.dataset.timeComparisonChartUrl, window.location.origin);

        if (userId) url.searchParams.set('user_id', userId);
        if (dateInput && dateInput.value) url.searchParams.set('date', dateInput.value);

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const result = await response.json();
            if (result.success) renderWorkspaceDonutChart('workspaceTimeComparisonChart', result.data);
        } catch (e) { console.warn('Time Chart Error:', e); }
    };

    const loadAllWorkspaceCharts = () => {
        loadTaskStatusChart();
        loadTaskPriorityChart();
        loadTimeComparisonChart();
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
    loadAllWorkspaceCharts();

    // Event listeners for re-loading
    document.addEventListener('workspace:user-changed', () => {
        loadWorkspaceSummary();
        loadAllWorkspaceCharts();
    });

    document.addEventListener('workspace:kanban-refreshed', () => {
        loadWorkspaceSummary();
        loadAllWorkspaceCharts();
    });

    // Time Comparison Chart Filter Logic
    const timeChartCard = document.querySelector('[data-workspace-chart="time-comparison"]');
    if (timeChartCard) {
        const filterGroup = timeChartCard.querySelector('[data-time-filter-group]');
        const dateInput = timeChartCard.querySelector('[data-workspace-time-chart-date]');

        if (filterGroup && dateInput) {
            const buttons = filterGroup.querySelectorAll('[data-time-filter]');

            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const type = btn.dataset.timeFilter;

                    // Reset active state
                    buttons.forEach(b => {
                        b.setAttribute('aria-pressed', 'false');
                        b.classList.remove('active');
                    });
                    btn.setAttribute('aria-pressed', 'true');
                    btn.classList.add('active');

                    if (type === 'today') {
                        const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD
                        dateInput.value = today;
                        if (dateInput._flatpickr) dateInput._flatpickr.setDate(today);
                        loadTimeComparisonChart();
                    } else if (type === 'yesterday') {
                        const yesterday = new Date();
                        yesterday.setDate(yesterday.getDate() - 1);
                        const yesterdayStr = yesterday.toLocaleDateString('en-CA');
                        dateInput.value = yesterdayStr;
                        if (dateInput._flatpickr) dateInput._flatpickr.setDate(yesterdayStr);
                        loadTimeComparisonChart();
                    } else if (type === 'custom') {
                        if (dateInput._flatpickr) {
                            dateInput._flatpickr.open();
                        }
                    }
                });
            });

            // Initialize datepicker
            if (typeof initDatepicker === 'function' && !dateInput._flatpickr) {
                initDatepicker('[data-workspace-time-chart-date]', {
                    maxDate: "today",
                    onChange: (selectedDates, dateStr) => {
                        // When custom date is picked, ensure "Custom" button is active
                        buttons.forEach(b => {
                            const isActive = b.dataset.timeFilter === 'custom';
                            b.setAttribute('aria-pressed', isActive);
                            b.classList.toggle('active', isActive);
                        });
                        loadTimeComparisonChart();
                    }
                }, timeChartCard);
            }
        }
    }
});


