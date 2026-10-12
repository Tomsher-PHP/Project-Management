/**
 * Project Analytics Dashboard Module
 * Coordinates AJAX loading for both Overall Dashboard and Single Project Dashboard modes.
 */

import { initDatepicker } from "../../components/datepicker";

const chartInstances = new Map();

/**
 * Safely escape HTML to prevent XSS.
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Destroy chart instance by canvas ID.
 */
export function destroyChart(canvasId) {
    if (chartInstances.has(canvasId)) {
        try {
            chartInstances.get(canvasId).destroy();
        } catch (e) {
            console.warn(`Error destroying chart ${canvasId}:`, e);
        }
        chartInstances.delete(canvasId);
    }
}

/**
 * Animate counter from start value to target value.
 */
export function animateCounter(element, target, duration = 600) {
    if (!element) return;
    const currentText = element.innerText || '';
    const start = parseInt(currentText.replace(/,/g, ''), 10) || 0;
    const targetValue = parseInt(target, 10) || 0;

    if (start === targetValue) {
        element.innerText = targetValue.toLocaleString();
        return;
    }

    const change = targetValue - start;
    const startTime = performance.now();

    const updateCounter = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easedProgress = progress * (2 - progress); // Ease out quad
        const currentValue = Math.floor(start + (change * easedProgress));

        element.innerText = currentValue.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            element.innerText = targetValue.toLocaleString();
        }
    };

    requestAnimationFrame(updateCounter);
}

/**
 * Retrieve current user and project filter values.
 */
export function getActiveFilters() {
    const userSelect = document.querySelector('[data-project-analytics-user-select]');
    const projectSelect = document.querySelector('[data-project-analytics-project-select]');

    const urlParams = new URLSearchParams(window.location.search);

    const userId = userSelect
        ? String(userSelect.value || '').trim()
        : String(urlParams.get('user_id') || '').trim();

    const projectId = projectSelect
        ? String(projectSelect.value || '').trim()
        : String(urlParams.get('project_id') || '').trim();

    return { userId, projectId };
}

/**
 * Helper to fetch JSON with query parameters and standard headers.
 */
async function fetchAnalyticsData(endpointUrl, params = {}) {
    const url = new URL(endpointUrl, window.location.origin);
    if (params.userId) url.searchParams.set('user_id', params.userId);
    if (params.projectId) url.searchParams.set('project_id', params.projectId);
    if (params.date) url.searchParams.set('date', params.date);

    const response = await fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error(`Analytics fetch failed with status ${response.status}`);
    }

    const result = await response.json();
    if (!result.success) {
        throw new Error(result.message || 'Analytics fetch unsuccessful');
    }

    return result.data;
}

/* ==========================================================================
   OVERALL DASHBOARD LOADERS
   ========================================================================== */

/**
 * Load Summary Counts via AJAX (Overall Mode).
 */
export async function loadSummaryCounts(filters = getActiveFilters()) {
    const section = document.querySelector('[data-project-analytics-summary-section]');
    if (!section) return;

    const summaryUrl = section.dataset.summaryUrl;
    if (!summaryUrl) return;

    try {
        const data = await fetchAnalyticsData(summaryUrl, filters);

        Object.entries(data).forEach(([key, value]) => {
            const countEl = section.querySelector(`[data-project-analytics-summary-count="${key}"]`);
            if (!countEl) return;

            if (typeof value === 'number') {
                animateCounter(countEl, value);
            } else {
                countEl.innerText = value ?? '—';
            }
        });

        document.dispatchEvent(new CustomEvent('project-analytics:summary-loaded', { detail: data }));
    } catch (err) {
        console.warn('Failed to load overall summary counts:', err);
        const countElements = section.querySelectorAll('[data-project-analytics-summary-count]');
        countElements.forEach(el => { el.innerText = '—'; });
    }
}

/**
 * Load Projects by Service Type (Horizontal Progress Bars).
 */
export async function loadProjectsByServiceType(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-section="service-type"]');
    if (!card) return;

    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content]');
    const listContainer = card.querySelector('[data-service-type-list]');
    const totalCountEl = card.querySelector('[data-service-type-total-count]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        const totalProjects = Number(data.total_projects) || 0;
        const items = data.items || [];
        const hasData = items.some(item => Number(item.count) > 0);

        if (totalCountEl) totalCountEl.innerText = totalProjects.toLocaleString();
        if (loadingState) loadingState.classList.add('hidden');

        if (!hasData || items.length === 0) {
            if (emptyState) emptyState.classList.remove('hidden');
            if (content) content.classList.add('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        const html = items.map(item => {
            const count = Number(item.count) || 0;
            const percentage = totalProjects > 0 ? Math.round((count / totalProjects) * 100) : 0;
            const barWidth = Math.min(100, percentage);
            const color = item.color || '#3B82F6';

            return `
                <div class="space-y-1.5" data-service-type-id="${item.id ?? 'none'}">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: ${escapeHtml(color)}"></span>
                            <span class="font-semibold text-bgray-900 dark:text-white truncate">${escapeHtml(item.name)}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-bold text-bgray-900 dark:text-white">${count.toLocaleString()}</span>
                            <span class="text-[11px] text-bgray-700 dark:text-bgray-400">(${percentage}%)</span>
                        </div>
                    </div>
                    <div class="h-2 w-full rounded-full bg-bgray-100 dark:bg-darkblack-500 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 ease-out" style="width: ${barWidth}%; background-color: ${escapeHtml(color)};"></div>
                    </div>
                </div>
            `;
        }).join('');

        if (listContainer) listContainer.innerHTML = html;
    } catch (err) {
        console.warn('Failed to load projects by service type:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
    }
}

/**
 * Generic Donut Chart Renderer.
 */
function renderDonutChart(card, canvasId, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof window.Chart === 'undefined') return;

    const values = data.values || [];
    const labels = data.labels || [];
    const colors = data.colors || [];
    const hasData = values.some(v => Number(v) > 0);
    const totalValue = values.reduce((acc, val) => acc + (Number(val) || 0), 0);

    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (!hasData) {
        destroyChart(canvasId);
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.remove('hidden');

    destroyChart(canvasId);

    const totalNode = card.querySelector('[data-chart-total]');
    if (totalNode) totalNode.innerText = totalValue.toLocaleString();

    const legendContainer = card.querySelector('[data-chart-legend]');
    if (legendContainer) {
        legendContainer.innerHTML = labels.map((label, idx) => {
            const val = Number(values[idx]) || 0;
            const pct = totalValue > 0 ? Math.round((val / totalValue) * 100) : 0;
            const color = colors[idx] || '#64748B';

            return `
                <div class="flex items-center justify-between gap-4 rounded-xl border border-bgray-200 px-4 py-2 dark:border-darkblack-400">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background-color: ${escapeHtml(color)};"></span>
                        <span class="truncate text-xs font-semibold text-bgray-700 dark:text-bgray-300">${escapeHtml(label)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-bgray-900 dark:text-white">${val.toLocaleString()}</span>
                        <span class="rounded-full bg-bgray-50 px-2 py-0.5 text-[10px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                            ${pct}%
                        </span>
                    </div>
                </div>
            `;
        }).join('');
    }

    const ctx = canvas.getContext('2d');
    const chart = new window.Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const val = context.raw || 0;
                            const pct = totalValue > 0 ? Math.round((val / totalValue) * 100) : 0;
                            return `${context.label}: ${val.toLocaleString()} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    chartInstances.set(canvasId, chart);
}

/**
 * Load Projects by Flow (Agile vs Linear).
 */
export async function loadProjectsByFlow(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-chart="flow"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        if (loadingState) loadingState.classList.add('hidden');
        renderDonutChart(card, 'projectFlowChart', data);
    } catch (err) {
        console.warn('Failed to load projects by flow:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        destroyChart('projectFlowChart');
    }
}

/**
 * Load Projects by Status.
 */
export async function loadProjectsByStatus(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-chart="status"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        if (loadingState) loadingState.classList.add('hidden');
        renderDonutChart(card, 'projectStatusChart', data);
    } catch (err) {
        console.warn('Failed to load projects by status:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        destroyChart('projectStatusChart');
    }
}

/**
 * Load Projects by Stage.
 */
export async function loadProjectsByStage(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-chart="stage"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        if (loadingState) loadingState.classList.add('hidden');
        renderDonutChart(card, 'projectStageChart', data);
    } catch (err) {
        console.warn('Failed to load projects by stage:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        destroyChart('projectStageChart');
    }
}

/**
 * Load Upcoming Timeline Expirations Table.
 */
export async function loadUpcomingTimelines(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-section="upcoming-timelines"], [data-project-analytics-section="upcoming-contracts"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content]');
    const tbody = card.querySelector('[data-timelines-table-body], [data-contracts-table-body]');
    const totalCountEl = card.querySelector('[data-timelines-total-count], [data-contracts-total-count]');
    const emptyTitle = card.querySelector('[data-empty-title]');
    const emptySubtitle = card.querySelector('[data-empty-subtitle]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        const canView = data.can_view !== false;
        const timelines = data.timelines || data.contracts || [];

        if (loadingState) loadingState.classList.add('hidden');

        if (!canView) {
            if (emptyTitle) emptyTitle.innerText = 'Access Restricted';
            if (emptySubtitle) emptySubtitle.innerText = 'You do not have permission to view timeline expiration dates.';
            if (emptyState) emptyState.classList.remove('hidden');
            if (content) content.classList.add('hidden');
            if (totalCountEl) totalCountEl.innerText = '0';
            return;
        }

        if (totalCountEl) totalCountEl.innerText = timelines.length.toLocaleString();

        if (timelines.length === 0) {
            if (emptyTitle) emptyTitle.innerText = 'No upcoming timeline expirations';
            if (emptySubtitle) emptySubtitle.innerText = 'There are no active timelines with upcoming expiration dates.';
            if (emptyState) emptyState.classList.remove('hidden');
            if (content) content.classList.add('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        const rowsHtml = timelines.map(item => {
            const daysLeft = Number(item.days_remaining) || 0;
            const isSoon = Boolean(item.is_expiring_soon);

            const daysBadge = isSoon
                ? `<span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-[11px] font-bold text-error-400 dark:bg-error-500/10">${daysLeft} days left</span>`
                : `<span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">${daysLeft} days left</span>`;

            const statusColor = item.status === 'active'
                ? 'bg-success-50 text-success-500 dark:bg-success-500/10'
                : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300';

            const serviceTypes = item.service_types || [];
            let serviceTypeHtml = '<span class="text-bgray-400">—</span>';
            if (serviceTypes.length > 0) {
                serviceTypeHtml = serviceTypes.map(st => `
                    <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-medium border" style="border-color: ${escapeHtml(st.color || '#3B82F6')}40; color: ${escapeHtml(st.color || '#3B82F6')}; background-color: ${escapeHtml(st.color || '#3B82F6')}10;">
                        ${escapeHtml(st.name)}
                    </span>
                `).join(' ');
            } else if (item.service_type_names && item.service_type_names !== '--') {
                serviceTypeHtml = `<span class="text-xs text-bgray-700 dark:text-bgray-300">${escapeHtml(item.service_type_names)}</span>`;
            }

            return `
                <tr class="transition-colors hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
                    <td class="py-3 px-4">
                        <div class="font-semibold text-bgray-900 dark:text-white">${escapeHtml(item.project_name)}</div>
                        <div class="text-[11px] text-bgray-700 dark:text-bgray-400">${escapeHtml(item.project_code || item.customer_name || '')}</div>
                    </td>
                    <td class="py-3 px-4">
                        ${serviceTypeHtml}
                    </td>
                    <td class="py-3 px-4 font-medium text-bgray-700 dark:text-bgray-300">
                        ${escapeHtml(item.formatted_start_date || item.start_date || '—')}
                    </td>
                    <td class="py-3 px-4 font-semibold text-bgray-900 dark:text-white">
                        ${escapeHtml(item.formatted_end_date || item.end_date || item.formatted_customer_end_date || item.customer_end_date || '—')}
                    </td>
                    <td class="py-3 px-4">
                        ${daysBadge}
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ${statusColor}">
                            ${escapeHtml(item.status_label || 'Active')}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        if (tbody) tbody.innerHTML = rowsHtml;
    } catch (err) {
        console.warn('Failed to load upcoming timeline expirations:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
    }
}

export const loadUpcomingContracts = loadUpcomingTimelines;

/**
 * Load all Overall Dashboard sections.
 */
export function loadAllOverallDashboardSections(filters = getActiveFilters()) {
    loadSummaryCounts(filters);
    loadProjectsByServiceType(filters);
    loadProjectsByFlow(filters);
    loadProjectsByStatus(filters);
    loadProjectsByStage(filters);
    loadUpcomingTimelines(filters);
}

/* ==========================================================================
   SINGLE PROJECT DASHBOARD LOADERS
   ========================================================================== */

/**
 * Load Single Project Details & Task Summary.
 */
export async function loadSingleProjectDetails(filters = getActiveFilters()) {
    const section = document.querySelector('[data-single-project-summary-section]');
    if (!section || !filters.projectId) return;

    const url = section.dataset.detailsUrl;
    if (!url) return;

    const loadingState = section.querySelector('[data-loading-state]');
    const content = section.querySelector('[data-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);

        if (loadingState) loadingState.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        // Project metadata
        const nameEl = section.querySelector('[data-project-name]');
        const codeEl = section.querySelector('[data-project-code]');
        const customerEl = section.querySelector('[data-project-customer]');
        const statusBadge = section.querySelector('[data-project-status-badge]');
        const stageBadge = section.querySelector('[data-project-stage-badge]');
        const flowBadge = section.querySelector('[data-project-flow-badge]');
        const serviceTypesContainer = section.querySelector('[data-project-service-types]');
        const startDateEl = section.querySelector('[data-project-start-date]');
        const endDateEl = section.querySelector('[data-project-end-date]');
        const contractDateEl = section.querySelector('[data-project-timeline-date], [data-project-contract-date]');

        if (nameEl) nameEl.innerText = data.name || '--';
        if (codeEl) codeEl.innerText = data.project_code || '--';
        if (customerEl) customerEl.innerText = data.customer_name || '--';

        if (statusBadge) {
            statusBadge.innerText = data.status?.name || '--';
            const color = data.status?.color || '#6B7280';
            statusBadge.style.backgroundColor = `${color}15`;
            statusBadge.style.color = color;
            statusBadge.style.borderColor = `${color}40`;
        }

        if (stageBadge) {
            stageBadge.innerText = data.stage?.name || '--';
            const color = data.stage?.color || '#64748B';
            stageBadge.style.backgroundColor = `${color}15`;
            stageBadge.style.color = color;
            stageBadge.style.borderColor = `${color}40`;
        }

        if (flowBadge) {
            flowBadge.innerText = data.project_flow_label || 'Linear';
        }

        if (serviceTypesContainer) {
            const types = data.service_types || [];
            if (types.length === 0) {
                serviceTypesContainer.innerHTML = '<span class="text-bgray-400">Unassigned</span>';
            } else {
                serviceTypesContainer.innerHTML = types.map(st => `
                    <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold border" style="background-color: ${escapeHtml(st.color || '#3B82F6')}15; color: ${escapeHtml(st.color || '#3B82F6')}; border-color: ${escapeHtml(st.color || '#3B82F6')}40;">
                        ${escapeHtml(st.name)}
                    </span>
                `).join('');
            }
        }

        if (startDateEl) startDateEl.innerText = data.dates?.formatted_start_date || '--';
        if (endDateEl) endDateEl.innerText = data.dates?.formatted_end_date || '--';
        if (contractDateEl) contractDateEl.innerText = data.dates?.formatted_timeline_end_date || data.dates?.formatted_end_date || data.dates?.formatted_contract_end_date || '--';

        // Task summary count metrics
        const taskSummary = data.task_summary || {};
        const countElements = section.querySelectorAll('[data-single-task-count]');
        countElements.forEach(el => {
            const key = el.dataset.singleTaskCount;
            const val = taskSummary[key];
            if (typeof val === 'number') {
                animateCounter(el, val);
            } else {
                el.innerText = val ?? '—';
            }
        });

        document.dispatchEvent(new CustomEvent('project-analytics:single-details-loaded', { detail: data }));
    } catch (err) {
        console.warn('Failed to load single project details:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (content) content.classList.remove('hidden');
    }
}

/**
 * Load Single Project Task Status Breakdown Chart.
 */
export async function loadSingleProjectTaskStatusChart(filters = getActiveFilters()) {
    const card = document.querySelector('[data-single-project-charts-section] [data-project-chart="task-status"]');
    if (!card || !filters.projectId) return;

    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        if (loadingState) loadingState.classList.add('hidden');
        renderDonutChart(card, 'singleProjectTaskStatusChart', data);
    } catch (err) {
        console.warn('Failed to load single project task status chart:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        destroyChart('singleProjectTaskStatusChart');
    }
}

/**
 * Load Single Project Task Priority Breakdown Chart.
 */
export async function loadSingleProjectTaskPriorityChart(filters = getActiveFilters()) {
    const card = document.querySelector('[data-single-project-charts-section] [data-project-chart="task-priority"]');
    if (!card || !filters.projectId) return;

    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        if (loadingState) loadingState.classList.add('hidden');
        renderDonutChart(card, 'singleProjectTaskPriorityChart', data);
    } catch (err) {
        console.warn('Failed to load single project task priority chart:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        destroyChart('singleProjectTaskPriorityChart');
    }
}

/**
 * Render Single Project Time Comparison Chart.
 */
function renderSingleProjectTimeComparisonChart(card, canvasId, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof window.Chart === 'undefined') return;

    const values = data.values || [];
    const formattedValues = data.formatted_values || [];
    const labels = data.labels || [];
    const colors = data.colors || [];
    const hasData = values.some(v => Number(v) > 0);

    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (!hasData) {
        destroyChart(canvasId);
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.remove('hidden');

    destroyChart(canvasId);

    // Center display: Worked time
    const centerValSpan = card.querySelector('[data-chart-total-val]');
    if (centerValSpan) {
        const shiftSec = values[0] || 0;
        const workedSec = values[1] || 0;
        const isTargetMet = shiftSec > 0 ? (workedSec >= shiftSec) : true;
        const colorClass = isTargetMet ? 'text-success-500 dark:text-success-400' : 'text-error-400 dark:text-error-400';

        centerValSpan.innerText = formattedValues[1] || '00h 00m';
        centerValSpan.className = `text-xs font-bold ${colorClass}`;
    }

    // Legend generation
    const legendContainer = card.querySelector('[data-chart-legend]');
    if (legendContainer) {
        const shiftVal = values[0] || 0;

        legendContainer.innerHTML = labels.map((label, index) => {
            const val = values[index];
            const formatted = formattedValues[index] || '00h 00m';
            const color = colors[index] || '#3B82F6';

            let percentageHtml = '';
            let valColor = 'text-bgray-900 dark:text-white';

            if (index === 0) {
                percentageHtml = '';
            } else if (index === 1) {
                const pct = shiftVal > 0 ? Math.round((val / shiftVal) * 100) : 0;
                const isTargetMet = val >= shiftVal;
                const badgeClass = isTargetMet
                    ? 'text-success-500 bg-success-50 dark:bg-success-500/10'
                    : 'text-error-400 bg-error-50 dark:bg-error-500/10';
                valColor = isTargetMet ? 'text-success-500 dark:text-success-400' : 'text-error-400 dark:text-error-400';
                percentageHtml = `<span class="rounded-full px-2 py-0.5 text-[10px] font-bold ${badgeClass}">${pct}%</span>`;
            } else if (index === 2) {
                const pct = shiftVal > 0 ? Math.round((val / shiftVal) * 100) : 0;
                percentageHtml = `<span class="rounded-full bg-bgray-50 px-2 py-0.5 text-[10px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">${pct}%</span>`;
            }

            return `
                <div class="flex items-center justify-between gap-4 rounded-xl border border-bgray-200 px-4 py-2 dark:border-darkblack-400">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background-color: ${escapeHtml(color)};"></span>
                        <span class="truncate text-xs font-semibold text-bgray-700 dark:text-bgray-300">${escapeHtml(label)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold ${valColor}">${formatted}</span>
                        ${percentageHtml}
                    </div>
                </div>
            `;
        }).join('');
    }

    const ctx = canvas.getContext('2d');
    const chart = new window.Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const index = context.dataIndex;
                            const formatted = formattedValues[index] || '00h 00m';
                            return `${context.label}: ${formatted}`;
                        }
                    }
                }
            }
        }
    });

    chartInstances.set(canvasId, chart);
}

/**
 * Load Single Project Time Comparison Chart.
 */
export async function loadSingleProjectTimeComparisonChart(filters = getActiveFilters()) {
    const card = document.querySelector('[data-single-time-comparison-wrapper]');
    if (!card || !filters.projectId || !filters.userId) return;

    const url = card.dataset.url;
    if (!url) return;

    const dateInput = card.querySelector('[data-project-time-chart-date]');
    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-chart-empty-state]');
    const content = card.querySelector('[data-chart-content]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    const params = {
        ...filters,
        date: dateInput ? dateInput.value : undefined,
    };

    try {
        const data = await fetchAnalyticsData(url, params);
        if (loadingState) loadingState.classList.add('hidden');
        renderSingleProjectTimeComparisonChart(card, 'singleProjectTimeComparisonChart', data);
    } catch (err) {
        console.warn('Failed to load single project time comparison chart:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        destroyChart('singleProjectTimeComparisonChart');
    }
}

/**
 * Initialize Single Project Time Comparison Filters (Today / Yesterday / Custom).
 */
function initTimeComparisonFilterButtons() {
    const card = document.querySelector('[data-single-time-comparison-wrapper]');
    if (!card) return;

    const filterGroup = card.querySelector('[data-time-filter-group]');
    const dateInput = card.querySelector('[data-project-time-chart-date]');
    if (!filterGroup || !dateInput) return;

    const buttons = filterGroup.querySelectorAll('[data-time-filter]');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.timeFilter;

            buttons.forEach(b => {
                b.setAttribute('aria-pressed', 'false');
                b.classList.remove('active');
            });
            btn.setAttribute('aria-pressed', 'true');
            btn.classList.add('active');

            if (type === 'today') {
                const today = new Date().toLocaleDateString('en-CA');
                dateInput.value = today;
                if (dateInput._flatpickr) dateInput._flatpickr.setDate(today);
                loadSingleProjectTimeComparisonChart();
            } else if (type === 'yesterday') {
                const yest = new Date();
                yest.setDate(yest.getDate() - 1);
                const yestStr = yest.toLocaleDateString('en-CA');
                dateInput.value = yestStr;
                if (dateInput._flatpickr) dateInput._flatpickr.setDate(yestStr);
                loadSingleProjectTimeComparisonChart();
            } else if (type === 'custom') {
                if (dateInput._flatpickr) {
                    dateInput._flatpickr.open();
                }
            }
        });
    });

    if (typeof initDatepicker === 'function' && !dateInput._flatpickr) {
        initDatepicker('[data-project-time-chart-date]', {
            maxDate: 'today',
            onChange: (selectedDates, dateStr) => {
                buttons.forEach(b => {
                    const isActive = b.dataset.timeFilter === 'custom';
                    b.setAttribute('aria-pressed', isActive);
                    b.classList.toggle('active', isActive);
                });
                loadSingleProjectTimeComparisonChart();
            }
        }, card);
    }
}

/* ==========================================================================
   DASHBOARD MODE SWITCHER & COORDINATION
   ========================================================================== */

/**
 * Switch between Overall Dashboard and Single Project Dashboard modes.
 */
export function switchDashboardMode(filters = getActiveFilters()) {
    const overallView = document.querySelector('[data-overall-dashboard-view]');
    const singleProjectView = document.querySelector('[data-single-project-dashboard-view]');

    if (!overallView || !singleProjectView) return;

    const isSingleProject = Boolean(filters.projectId);

    if (isSingleProject) {
        // --- Single Project Mode ---
        overallView.classList.add('hidden');
        singleProjectView.classList.remove('hidden');

        // Destroy overall charts to prevent leaks
        destroyChart('projectFlowChart');
        destroyChart('projectStatusChart');
        destroyChart('projectStageChart');

        // Load Single Project Data
        loadSingleProjectDetails(filters);
        loadSingleProjectTaskStatusChart(filters);
        loadSingleProjectTaskPriorityChart(filters);

        // Time Comparison: shown ONLY when a specific user is selected
        const timeWrapper = singleProjectView.querySelector('[data-single-time-comparison-wrapper]');
        const chartsSection = singleProjectView.querySelector('[data-single-project-charts-section]');

        if (filters.userId) {
            if (timeWrapper) timeWrapper.classList.remove('hidden');
            if (chartsSection) {
                chartsSection.classList.remove('xl:grid-cols-2');
                chartsSection.classList.add('xl:grid-cols-3');
            }
            loadSingleProjectTimeComparisonChart(filters);
        } else {
            if (timeWrapper) timeWrapper.classList.add('hidden');
            if (chartsSection) {
                chartsSection.classList.remove('xl:grid-cols-3');
                chartsSection.classList.add('xl:grid-cols-2');
            }
            destroyChart('singleProjectTimeComparisonChart');
        }
    } else {
        // --- Overall Dashboard Mode ---
        singleProjectView.classList.add('hidden');
        overallView.classList.remove('hidden');

        // Destroy single project charts
        destroyChart('singleProjectTaskStatusChart');
        destroyChart('singleProjectTaskPriorityChart');
        destroyChart('singleProjectTimeComparisonChart');

        // Load Overall Dashboard Sections
        loadAllOverallDashboardSections(filters);
    }
}

/**
 * Initialize dashboard listeners and lifecycle.
 */
export function initProjectAnalyticsDashboard() {
    const dashboardRoot = document.querySelector('[data-project-analytics-root]');
    if (!dashboardRoot) return;

    // Listen to filter change events from project-analytics-filters.js
    document.addEventListener('project-analytics:filter-changed', (event) => {
        const filters = event.detail || getActiveFilters();
        switchDashboardMode(filters);
    });

    // Initialize time comparison buttons
    initTimeComparisonFilterButtons();

    // Initial load
    switchDashboardMode();
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectAnalyticsDashboard);
} else {
    initProjectAnalyticsDashboard();
}
