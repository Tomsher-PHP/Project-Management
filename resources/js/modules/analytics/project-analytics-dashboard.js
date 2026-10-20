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
    const skeleton = element.querySelector('.animate-pulse');
    if (skeleton) {
        element.innerText = '0';
    }

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

    return result.data !== undefined ? result.data : result;
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

    const summaryUrl = section.dataset.summaryUrl || section.dataset.dashboardSummaryUrl;
    if (!summaryUrl) return;

    // Visibility toggle per requirement:
    // - No project chosen (neither, or user only) -> show project and task summary counts
    // - Project chosen (project only, or user + project) -> show only task summary counts
    const projectCountsWrapper = section.querySelector('[data-project-counts-wrapper]');
    const taskCountsWrapper = section.querySelector('[data-task-counts-wrapper]');
    const hasProject = Boolean(filters.projectId);

    if (projectCountsWrapper && taskCountsWrapper) {
        if (hasProject) {
            projectCountsWrapper.classList.add('hidden');
            taskCountsWrapper.classList.add('md:col-span-2');
        } else {
            projectCountsWrapper.classList.remove('hidden');
            taskCountsWrapper.classList.remove('md:col-span-2');
        }
    }

    try {
        const data = await fetchAnalyticsData(summaryUrl, filters);

        Object.entries(data).forEach(([key, value]) => {
            const countEls = section.querySelectorAll(
                `[data-dashboard-count="${key}"], [data-project-analytics-summary-count="${key}"]`
            );
            countEls.forEach(countEl => {
                if (typeof value === 'number') {
                    animateCounter(countEl, value);
                } else {
                    const skeleton = countEl.querySelector('.animate-pulse');
                    if (skeleton) skeleton.remove();
                    countEl.innerText = value ?? '0';
                }
            });
        });

        document.dispatchEvent(new CustomEvent('project-analytics:summary-loaded', { detail: data }));
    } catch (err) {
        console.warn('Failed to load summary counts:', err);
        const countElements = section.querySelectorAll('[data-dashboard-count], [data-project-analytics-summary-count]');
        countElements.forEach(el => {
            const skeleton = el.querySelector('.animate-pulse');
            if (skeleton) skeleton.remove();
            el.innerText = '0';
        });
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

        if (listContainer) {
            listContainer.innerHTML = data.html || '';
        }
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
    const card = document.querySelector('[data-project-analytics-section="upcoming-timelines"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content]');
    const tbody = card.querySelector('[data-timelines-table-body]');
    const totalCountEl = card.querySelector('[data-timelines-total-count]');
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

        if (tbody) {
            tbody.innerHTML = data.html || '';
        }
    } catch (err) {
        console.warn('Failed to load upcoming timeline expirations:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
    }
}

export const loadUpcomingContracts = loadUpcomingTimelines;

/**
 * Load Parent Projects Overview table data.
 */
export async function loadParentProjectsOverview(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-section="parent-projects-overview"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content]');
    const tbody = card.querySelector('[data-parent-projects-table-body]');
    const totalCountEl = card.querySelector('[data-parent-projects-total-count]');
    const emptyTitle = card.querySelector('[data-empty-title]');
    const emptySubtitle = card.querySelector('[data-empty-subtitle]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        const canView = data.can_view !== false;
        const projects = data.projects || [];

        if (loadingState) loadingState.classList.add('hidden');

        if (!canView) {
            if (emptyTitle) emptyTitle.innerText = 'Access Restricted';
            if (emptySubtitle) emptySubtitle.innerText = 'You do not have permission to view parent projects.';
            if (emptyState) emptyState.classList.remove('hidden');
            if (content) content.classList.add('hidden');
            if (totalCountEl) totalCountEl.innerText = '0';
            return;
        }

        if (totalCountEl) totalCountEl.innerText = projects.length.toLocaleString();

        if (projects.length === 0) {
            if (emptyTitle) emptyTitle.innerText = 'No parent projects found';
            if (emptySubtitle) emptySubtitle.innerText = 'There are no parent projects with child projects matching the current filter.';
            if (emptyState) emptyState.classList.remove('hidden');
            if (content) content.classList.add('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        if (tbody) {
            tbody.innerHTML = data.html || '';
        }
    } catch (err) {
        console.warn('Failed to load parent projects overview:', err);
        if (loadingState) loadingState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
    }
}

/**
 * Helper to recursively hide or show descendant child rows.
 */
function hideDescendantRows(rowElement) {
    const rowId = rowElement.getAttribute('data-parent-row-id') || rowElement.getAttribute('data-child-row-id');
    if (!rowId) return;

    const directChildren = document.querySelectorAll(`.child-project-row[data-parent-id="${rowId}"]`);
    directChildren.forEach(child => {
        child.classList.add('hidden');
        // If this child was also expanded, collapse its button state and hide its descendants too
        if (child.getAttribute('data-expanded') === 'true') {
            child.setAttribute('data-expanded', 'false');
            const subToggleBtns = child.querySelectorAll(`[data-child-project-toggle="${child.getAttribute('data-child-row-id')}"]`);
            subToggleBtns.forEach(btn => btn.setAttribute('aria-expanded', 'false'));
            const chevron = child.querySelector('[data-toggle-chevron]');
            if (chevron) chevron.classList.remove('rotate-90');
            hideDescendantRows(child);
        }
    });
}

/**
 * Handle toggle and AJAX load of child projects under a parent or sub-child project.
 */
export async function toggleChildProjects(targetId, explicitDepth = null) {
    // Look for parent row first, then child row
    const targetRow = document.querySelector(`[data-parent-row-id="${targetId}"]`)
        || document.querySelector(`[data-child-row-id="${targetId}"]`);
    if (!targetRow) return;

    const toggleBtns = document.querySelectorAll(`[data-parent-project-toggle="${targetId}"], [data-child-project-toggle="${targetId}"]`);
    const chevronIcon = targetRow.querySelector('[data-toggle-chevron]');
    const existingChildRows = document.querySelectorAll(`.child-project-row[data-parent-id="${targetId}"]`);

    const isExpanded = targetRow.getAttribute('data-expanded') === 'true';

    if (isExpanded) {
        // Collapse: hide all direct and nested descendant rows
        hideDescendantRows(targetRow);
        targetRow.setAttribute('data-expanded', 'false');
        toggleBtns.forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        if (chevronIcon) {
            chevronIcon.classList.remove('rotate-90');
        }
        return;
    }

    // Expanding: If child rows already loaded previously, show direct children
    if (existingChildRows.length > 0) {
        existingChildRows.forEach(row => row.classList.remove('hidden'));
        targetRow.setAttribute('data-expanded', 'true');
        toggleBtns.forEach(btn => btn.setAttribute('aria-expanded', 'true'));
        if (chevronIcon) {
            chevronIcon.classList.add('rotate-90');
        }
        return;
    }

    // Determine depth: parent rows are depth 0 (children depth 1), child rows have data-depth
    const currentDepth = targetRow.hasAttribute('data-depth') ? parseInt(targetRow.getAttribute('data-depth'), 10) : 0;
    const nextDepth = explicitDepth ? parseInt(explicitDepth, 10) : currentDepth + 1;
    const paddingLeftPx = 16 + (nextDepth * 24);

    // Child rows not yet loaded: Fetch via AJAX
    const loadingRow = document.createElement('tr');
    loadingRow.className = `child-project-row bg-bgray-50/50 dark:bg-darkblack-500/20 animate-pulse`;
    loadingRow.setAttribute('data-parent-id', targetId);
    loadingRow.setAttribute('data-depth', nextDepth);
    loadingRow.innerHTML = `
        <td colspan="5" class="py-3 px-4 text-xs text-bgray-500 dark:text-bgray-400" style="padding-left: ${paddingLeftPx}px;">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin text-success-500" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Loading child projects...</span>
            </div>
        </td>
    `;
    targetRow.after(loadingRow);

    targetRow.setAttribute('data-expanded', 'true');
    toggleBtns.forEach(btn => btn.setAttribute('aria-expanded', 'true'));
    if (chevronIcon) {
        chevronIcon.classList.add('rotate-90');
    }

    const filters = getActiveFilters();
    const url = `/project-analytics/parent-projects/${targetId}/children?depth=${nextDepth}`;

    try {
        const response = await fetchAnalyticsData(url, filters);
        loadingRow.remove();

        if (response && response.html) {
            // Insert returned child rows right after targetRow
            targetRow.insertAdjacentHTML('afterend', response.html);
        } else {
            const emptyChildRow = document.createElement('tr');
            emptyChildRow.className = `child-project-row bg-bgray-50/40 dark:bg-darkblack-500/20`;
            emptyChildRow.setAttribute('data-parent-id', targetId);
            emptyChildRow.setAttribute('data-depth', nextDepth);
            emptyChildRow.innerHTML = `
                <td colspan="5" class="py-3 px-4 text-xs italic text-bgray-500 dark:text-bgray-400" style="padding-left: ${paddingLeftPx}px;">
                    No child projects found.
                </td>
            `;
            targetRow.after(emptyChildRow);
        }
    } catch (err) {
        console.warn(`Failed to load child projects for project ${targetId}:`, err);
        loadingRow.innerHTML = `
            <td colspan="5" class="py-3 px-4 text-xs text-red-500" style="padding-left: ${paddingLeftPx}px;">
                Failed to load child projects. Click again to retry.
            </td>
        `;
        targetRow.setAttribute('data-expanded', 'false');
        toggleBtns.forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        if (chevronIcon) {
            chevronIcon.classList.remove('rotate-90');
        }
    }
}

/**
 * Load Server & Hosting Expire Soon table data.
 */
export async function loadExpiringHosting(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-section="expiring-hosting"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content]');
    const tbody = card.querySelector('[data-expiring-hosting-table-body]');
    const totalCountEl = card.querySelector('[data-expiring-hosting-total-count]');
    const emptyTitle = card.querySelector('[data-empty-title]');
    const emptySubtitle = card.querySelector('[data-empty-subtitle]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        const canView = data.can_view !== false;
        const records = data.records || [];

        if (loadingState) loadingState.classList.add('hidden');

        if (!canView || records.length === 0) {
            // Do not show the box if no data or no access
            card.classList.add('hidden');
            if (emptyState) emptyState.classList.add('hidden');
            if (content) content.classList.add('hidden');
            if (totalCountEl) totalCountEl.innerText = '0';
            return;
        }

        card.classList.remove('hidden');
        if (totalCountEl) totalCountEl.innerText = records.length.toLocaleString();
        if (emptyState) emptyState.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        if (tbody) {
            tbody.innerHTML = data.html || '';
        }
    } catch (err) {
        console.warn('Failed to load expiring hosting records:', err);
        if (loadingState) loadingState.classList.add('hidden');
        card.classList.add('hidden');
    }
}

/**
 * Load Server & Hosting Expired table data.
 */
export async function loadExpiredHosting(filters = getActiveFilters()) {
    const card = document.querySelector('[data-project-analytics-section="expired-hosting"]');
    if (!card) return;
    const url = card.dataset.url;
    if (!url) return;

    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content]');
    const tbody = card.querySelector('[data-expired-hosting-table-body]');
    const totalCountEl = card.querySelector('[data-expired-hosting-total-count]');
    const emptyTitle = card.querySelector('[data-empty-title]');
    const emptySubtitle = card.querySelector('[data-empty-subtitle]');

    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const data = await fetchAnalyticsData(url, filters);
        const canView = data.can_view !== false;
        const records = data.records || [];

        if (loadingState) loadingState.classList.add('hidden');

        if (!canView || records.length === 0) {
            // Do not show the box if no data or no access
            card.classList.add('hidden');
            if (emptyState) emptyState.classList.add('hidden');
            if (content) content.classList.add('hidden');
            if (totalCountEl) totalCountEl.innerText = '0';
            return;
        }

        card.classList.remove('hidden');
        if (totalCountEl) totalCountEl.innerText = records.length.toLocaleString();
        if (emptyState) emptyState.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        if (tbody) {
            tbody.innerHTML = data.html || '';
        }
    } catch (err) {
        console.warn('Failed to load expired hosting records:', err);
        if (loadingState) loadingState.classList.add('hidden');
        card.classList.add('hidden');
    }
}

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
    loadExpiringHosting(filters);
    loadExpiredHosting(filters);
    loadParentProjectsOverview(filters);
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
        if (customerEl) {
            if (data.customer_url) {
                customerEl.innerHTML = `<a href="${escapeHtml(data.customer_url)}" class="font-medium text-bgray-700 dark:text-bgray-300 transition hover:text-success-400">${escapeHtml(data.customer_name || '--')}</a>`;
            } else {
                customerEl.innerText = data.customer_name || '--';
            }
        }

        if (statusBadge) {
            const valEl = statusBadge.querySelector('[data-project-status-value]');
            if (valEl) {
                valEl.innerText = data.status?.name || '--';
            } else {
                statusBadge.innerHTML = `<span>Status :</span> <span data-project-status-value>${escapeHtml(data.status?.name || '--')}</span>`;
            }
            const color = data.status?.color || '#6B7280';
            statusBadge.style.backgroundColor = `${color}15`;
            statusBadge.style.color = color;
            statusBadge.style.borderColor = `${color}40`;
        }

        if (stageBadge) {
            const valEl = stageBadge.querySelector('[data-project-stage-value]');
            if (valEl) {
                valEl.innerText = data.stage?.name || '--';
            } else {
                stageBadge.innerHTML = `<span>Stage :</span> <span data-project-stage-value>${escapeHtml(data.stage?.name || '--')}</span>`;
            }
            const color = data.stage?.color || '#64748B';
            stageBadge.style.backgroundColor = `${color}15`;
            stageBadge.style.color = color;
            stageBadge.style.borderColor = `${color}40`;
        }

        const flowIndicator = section.querySelector('[data-project-flow-indicator], [data-project-flow-wrapper]');
        if (flowIndicator) {
            const isAgile = (data.project_flow || '').toLowerCase() === 'agile';
            const flowLabel = data.project_flow_label || (isAgile ? 'Agile' : 'Linear');
            const iconColor = isAgile ? 'text-purple-600 dark:text-purple-400' : 'text-blue-600 dark:text-blue-400';
            const svgIcon = isAgile
                ? `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>`
                : `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>`;
            flowIndicator.innerHTML = `
                <div class="inline-flex flex-wrap items-center gap-2 bg-white dark:bg-darkblack-600">
                    <span class="inline-flex items-center gap-1 rounded-full px-1 py-1 text-xs font-medium text-bgray-700 dark:text-bgray-300">
                        <span class="inline-flex shrink-0 items-center justify-center transition duration-150 h-5 w-5 rounded ${iconColor}">
                            ${svgIcon}
                        </span>
                        <span>${escapeHtml(flowLabel)}</span>
                    </span>
                </div>
            `;
        }

        if (serviceTypesContainer) {
            const types = data.service_types || [];
            if (types.length === 0) {
                serviceTypesContainer.innerHTML = '<span class="text-bgray-400">Unassigned</span>';
            } else {
                serviceTypesContainer.innerHTML = types.map(st => `
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-bgray-200 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300" title="${escapeHtml(st.code || '')}">
                        <span class="h-2 w-2 rounded-full" style="background-color: ${escapeHtml(st.color || '#9a9996')}"></span>
                        <span>${escapeHtml(st.name)}</span>
                    </span>
                `).join('');
            }
        }

        const dateRangeEl = section.querySelector('[data-project-date-range]');
        const startDateFormatted = data.dates?.formatted_start_date || '--';
        const endDateFormatted = data.dates?.formatted_end_date || '--';
        if (startDateEl && endDateEl) {
            startDateEl.innerText = startDateFormatted;
            endDateEl.innerText = endDateFormatted;
        } else if (dateRangeEl) {
            dateRangeEl.innerHTML = `<span data-project-start-date>${escapeHtml(startDateFormatted)}</span> to <span data-project-end-date>${escapeHtml(endDateFormatted)}</span>`;
        }
        if (contractDateEl) contractDateEl.innerText = data.dates?.formatted_timeline_end_date || data.dates?.formatted_end_date || data.dates?.formatted_contract_end_date || '--';

        const customerEndDateEl = section.querySelector('[data-project-customer-end-date]');
        const customerEndDateWrapper = section.querySelector('[data-project-customer-end-date-wrapper]');
        if (customerEndDateWrapper) {
            if (data.dates?.has_customer_end_date_permission) {
                customerEndDateWrapper.classList.remove('hidden');
                if (customerEndDateEl) {
                    customerEndDateEl.innerText = data.dates.formatted_customer_end_date || '--';
                }
            } else {
                customerEndDateWrapper.classList.add('hidden');
            }
        }

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

function formatHourValue(value) {
    const numericValue = Number(value);
    if (!Number.isFinite(numericValue)) return '0h';
    const normalizedValue = Number.isInteger(numericValue)
        ? String(numericValue)
        : numericValue.toFixed(2).replace(/\.?0+$/, '');
    return `${normalizedValue}h`;
}

/**
 * Render Milestone Journey Burn-up Line Chart.
 */
export function renderMilestoneBurnupChart(card, burnupData) {
    const canvasId = 'singleProjectBurnupChart';
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof window.Chart === 'undefined') return;

    destroyChart(canvasId);

    const labels = Array.isArray(burnupData.labels) ? burnupData.labels : [];
    const datasets = Array.isArray(burnupData.datasets) ? burnupData.datasets : [];
    const hasBurnupData = labels.length > 0 && datasets.some(d => Array.isArray(d?.data) && d.data.length > 0);

    const emptyState = card.querySelector('[data-chart-empty-state], [data-single-project-burnup-empty-state]');
    const content = card.querySelector('[data-chart-content], [data-single-project-burnup-chart-wrapper]');

    if (!hasBurnupData) {
        if (emptyState) emptyState.classList.remove('hidden');
        if (content) content.classList.add('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.remove('hidden');

    const isDark = document.documentElement.classList.contains('dark');
    const labelColor = isDark ? '#E2E8F0' : '#4A5568';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(148, 163, 184, 0.15)';

    const estimatedDataset = datasets[0] || {};
    const actualDataset = datasets[1] || {};
    const endLabel = typeof burnupData.end_label === 'string' ? burnupData.end_label : '__end__';
    const originLabel = typeof burnupData.origin_label === 'string' ? burnupData.origin_label : '__origin__';
    const maxHours = Number(burnupData.max_hours) > 0 ? Number(burnupData.max_hours) : undefined;
    const interval = Number(burnupData.interval) > 0 ? Number(burnupData.interval) : 10;
    const isOriginPoint = (context) => context?.raw?.y === originLabel;

    const formatMilestoneAxisLabel = (label) => {
        const chars = Array.from(String(label || ''));
        return chars.length > 12 ? `${chars.slice(0, 12).join('')}...` : chars.join('');
    };

    const estimatedHoursByMilestone = new Map(
        (Array.isArray(estimatedDataset.data) ? estimatedDataset.data : [])
            .filter((p) => p && typeof p === 'object' && p.y !== originLabel)
            .map((p) => [p.y, formatHourValue(p.x)])
    );

    const burnupPointLabels = {
        id: 'burnupPointLabels',
        afterDatasetsDraw(chart) {
            const { ctx, chartArea } = chart;
            ctx.save();
            ctx.font = '600 11px sans-serif';
            ctx.textBaseline = 'middle';

            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (!chart.isDatasetVisible(datasetIndex) || meta.hidden) return;

                const color = dataset.borderColor || dataset.backgroundColor || '#111827';

                meta.data.forEach((element, pointIndex) => {
                    const point = dataset.data?.[pointIndex];
                    if (!point || point.y === originLabel) return;

                    const text = formatHourValue(point.x);
                    const textWidth = ctx.measureText(text).width;
                    const pointPosition = element.tooltipPosition();
                    const isNearRightEdge = pointPosition.x + textWidth + 10 > chartArea.right;
                    const yOffset = datasetIndex === 0 ? -12 : 12;

                    ctx.fillStyle = color;
                    ctx.textAlign = isNearRightEdge ? 'right' : 'left';
                    ctx.fillText(text, pointPosition.x + (isNearRightEdge ? -8 : 8), pointPosition.y + yOffset);
                });
            });

            ctx.restore();
        }
    };

    const estimatedHoursLabel = window.translations?.labels?.estimated_hours || 'Estimated Hours';
    const spentHoursLabel = window.translations?.labels?.spent_hours || 'Spent Hours';

    const chart = new window.Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: estimatedHoursLabel,
                    data: Array.isArray(estimatedDataset.data) ? estimatedDataset.data : [],
                    borderColor: '#3B82F6',
                    backgroundColor: '#3B82F6',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    pointRadius(context) { return isOriginPoint(context) ? 0 : 4; },
                    pointHoverRadius(context) { return isOriginPoint(context) ? 0 : 6; },
                },
                {
                    label: spentHoursLabel,
                    data: Array.isArray(actualDataset.data) ? actualDataset.data : [],
                    borderColor: '#22C55E',
                    backgroundColor: '#22C55E',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    pointRadius(context) { return isOriginPoint(context) ? 0 : 4; },
                    pointHoverRadius(context) { return isOriginPoint(context) ? 0 : 6; },
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            parsing: false,
            layout: { padding: { right: 44 } },
            scales: {
                x: {
                    beginAtZero: true,
                    max: maxHours,
                    grace: '8%',
                    ticks: {
                        color: labelColor,
                        stepSize: interval,
                        callback(value) { return `${value}h`; }
                    },
                    grid: { color: gridColor },
                    title: { display: false }
                },
                y: {
                    type: 'category',
                    labels,
                    ticks: {
                        color: labelColor,
                        callback(value, index) {
                            const label = this.getLabelForValue(value) || labels[index] || '';
                            if (label === originLabel || label === endLabel) return '';
                            const estHours = estimatedHoursByMilestone.get(label);
                            const axisLabel = formatMilestoneAxisLabel(label);
                            return estHours ? `${axisLabel} (${estHours})` : axisLabel;
                        }
                    },
                    grid: { color: gridColor },
                    title: { display: false }
                }
            },
            plugins: {
                tooltip: {
                    filter(context) { return !isOriginPoint(context); },
                    callbacks: {
                        label(context) {
                            const rawX = Number(context.raw?.x ?? context.parsed?.x ?? 0);
                            return `${context.dataset.label}: ${rawX}h`;
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    labels: { color: labelColor }
                }
            }
        },
        plugins: [burnupPointLabels]
    });

    chartInstances.set(canvasId, chart);
}

/**
 * Load Single Project Overview Sections (Time Progress, Burn-up chart, and User Wise list).
 */
export async function loadSingleProjectOverview(filters = getActiveFilters()) {
    const singleProjectView = document.querySelector('[data-single-project-dashboard-view]');
    if (!singleProjectView || !filters.projectId) return;

    const overviewUrl = singleProjectView.dataset.overviewUrl || '/project-analytics/project-overview';

    const timeProgressCard = document.querySelector('[data-single-project-time-progress-section]');
    const burnupCard = document.querySelector('[data-single-project-burnup-section]');
    const userWiseCard = document.querySelector('[data-single-project-user-wise-section]');

    const setSectionLoading = (card, isLoading) => {
        if (!card) return;
        const loadingState = card.querySelector('[data-loading-state]');
        const content = card.querySelector('[data-content], [data-chart-content]');
        const emptyState = card.querySelector('[data-empty-state], [data-chart-empty-state]');

        if (isLoading) {
            loadingState?.classList.remove('hidden');
            content?.classList.add('hidden');
            emptyState?.classList.add('hidden');
        } else {
            loadingState?.classList.add('hidden');
        }
    };

    setSectionLoading(timeProgressCard, true);
    setSectionLoading(burnupCard, true);
    setSectionLoading(userWiseCard, true);

    try {
        const res = await fetchAnalyticsData(overviewUrl, filters);
        const data = res?.data !== undefined ? res.data : (res || {});

        // 1. Time Progress
        if (timeProgressCard) {
            setSectionLoading(timeProgressCard, false);
            const content = timeProgressCard.querySelector('[data-content]');
            const emptyState = timeProgressCard.querySelector('[data-empty-state]');
            const spentBadge = timeProgressCard.querySelector('[data-time-progress-spent]');
            const estBadge = timeProgressCard.querySelector('[data-time-progress-estimated]');
            const custBadge = timeProgressCard.querySelector('[data-time-progress-customer-estimated]');
            const custWrapper = timeProgressCard.querySelector('[data-time-progress-customer-badge]');

            if (data.has_timelines) {
                if (spentBadge) {
                    spentBadge.innerText = data.timeline_spent_formatted || data.total_timeline_worked_formatted || '--';
                }
                if (estBadge) {
                    estBadge.innerText = data.timeline_estimated_formatted || data.total_timeline_estimated_formatted || '--';
                }
                if (custWrapper) {
                    const hasCustomerEstimate = Boolean(data.has_customer_estimate || (Number(data.total_timeline_customer_estimate) > 0));
                    if (hasCustomerEstimate) {
                        if (custBadge) {
                            custBadge.innerText = data.timeline_customer_estimate_formatted || data.total_timeline_customer_estimate_formatted || '--';
                        }
                        custWrapper.classList.remove('hidden');
                    } else {
                        custWrapper.classList.add('hidden');
                    }
                }
            } else {
                if (spentBadge) spentBadge.innerText = '--';
                if (estBadge) estBadge.innerText = '--';
                if (custWrapper) custWrapper.classList.add('hidden');
            }

            if (data.has_timelines && data.time_progress_html) {
                if (content) {
                    content.innerHTML = data.time_progress_html;
                    content.classList.remove('hidden');
                }
                emptyState?.classList.add('hidden');
            } else {
                content?.classList.add('hidden');
                emptyState?.classList.remove('hidden');
            }
        }

        // 2. Milestone Journey Burn-up Chart
        if (burnupCard) {
            setSectionLoading(burnupCard, false);
            if (data.is_agile) {
                burnupCard.classList.remove('hidden');
                renderMilestoneBurnupChart(burnupCard, data.burnup_chart || {});
            } else {
                burnupCard.classList.add('hidden');
                destroyChart('singleProjectBurnupChart');
            }
        }

        // 3. User Wise Section (Card List)
        if (userWiseCard) {
            setSectionLoading(userWiseCard, false);
            const content = userWiseCard.querySelector('[data-content]');
            const emptyState = userWiseCard.querySelector('[data-empty-state]');
            const spentBadge = userWiseCard.querySelector('[data-user-wise-spent]');
            const countBadge = userWiseCard.querySelector('[data-user-wise-count]');

            if (spentBadge && data.total_worked_formatted) {
                const spentLabel = window.translations?.labels?.spent || 'Spent';
                spentBadge.innerText = `${data.total_worked_formatted} ${spentLabel}`;
            }
            if (countBadge) {
                const count = Number(data.assignee_count) || 0;
                countBadge.innerText = `${count} ${count === 1 ? 'user' : 'users'}`;
            }

            if (data.has_assignees && data.user_wise_html) {
                if (content) {
                    content.innerHTML = data.user_wise_html;
                    content.classList.remove('hidden');
                }
                emptyState?.classList.add('hidden');
            } else {
                content?.classList.add('hidden');
                emptyState?.classList.remove('hidden');
            }
        }

    } catch (err) {
        console.warn('Failed to load single project overview:', err);
        setSectionLoading(timeProgressCard, false);
        setSectionLoading(burnupCard, false);
        setSectionLoading(userWiseCard, false);
    }
}

/**
 * Load Server & Hosting records for a single project.
 */
export async function loadSingleProjectHosting(filters = getActiveFilters()) {
    const card = document.querySelector('[data-single-project-hosting-section]');
    if (!card) return;

    if (!filters.projectId) {
        card.classList.add('hidden');
        return;
    }

    const url = card.dataset.url || '/project-analytics/project-hosting';
    const loadingState = card.querySelector('[data-loading-state]');
    const emptyState = card.querySelector('[data-empty-state]');
    const content = card.querySelector('[data-content], [data-single-project-hosting-content]');
    const countBadge = card.querySelector('[data-single-project-hosting-count]');

    // Show card and loading skeleton
    card.classList.remove('hidden');
    if (loadingState) loadingState.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    if (content) content.classList.add('hidden');

    try {
        const res = await fetchAnalyticsData(url, filters);
        const data = res?.data !== undefined ? res.data : (res || {});
        const canView = data.can_view !== false;
        const count = Number(data.count) || 0;

        if (loadingState) loadingState.classList.add('hidden');

        if (!canView) {
            card.classList.add('hidden');
            return;
        }

        if (countBadge) {
            countBadge.innerText = count.toLocaleString();
        }

        if (content && data.html) {
            content.innerHTML = data.html;

            // Remove Action column in single project analytics page
            content.querySelectorAll('table thead tr th:last-child').forEach(th => th.remove());
            content.querySelectorAll('table tbody tr td:last-child').forEach(td => td.remove());

            content.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
        } else {
            if (content) content.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
        }
    } catch (err) {
        console.warn('Failed to load single project hosting records:', err);
        if (loadingState) loadingState.classList.add('hidden');
        card.classList.add('hidden');
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

    // Load Summary Counts (Project / Task tiles) for the active filters
    loadSummaryCounts(filters);

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
        loadSingleProjectOverview(filters);
        loadSingleProjectTaskStatusChart(filters);
        loadSingleProjectHosting(filters);
    } else {
        // --- Overall Dashboard Mode ---
        singleProjectView.classList.add('hidden');
        overallView.classList.remove('hidden');

        // Hide single project hosting section if still visible
        const singleProjectHostingCard = document.querySelector('[data-single-project-hosting-section]');
        if (singleProjectHostingCard) {
            singleProjectHostingCard.classList.add('hidden');
        }

        // Destroy single project charts
        destroyChart('singleProjectTaskStatusChart');
        destroyChart('singleProjectBurnupChart');
        destroyChart('singleProjectBurnupChart');

        // Load Overall Dashboard Sections
        loadAllOverallDashboardSections(filters);
    }
}

/**
 * Initialize Dashboard Tile Drill-down Modal.
 */
export function initTileDrilldownModal() {
    const tileModal = document.getElementById('dashboard-tile-modal');
    const tileModalContent = document.getElementById('dashboard-tile-modal-content');
    const summarySectionEl = document.querySelector('[data-project-analytics-summary-section]');

    if (!tileModal || !tileModalContent || !summarySectionEl) return;

    // Delegate click on tiles
    document.addEventListener('click', async (e) => {
        const tile = e.target.closest('[data-dashboard-tile]');
        if (!tile) return;

        const type = tile.getAttribute('data-dashboard-tile');
        if (!type) return;

        const tileUrl = summarySectionEl.getAttribute('data-tile-url') || summarySectionEl.getAttribute('data-dashboard-tile-url');
        if (!tileUrl) return;

        const filters = getActiveFilters();
        const url = new URL(tileUrl, window.location.origin);
        url.searchParams.set('type', type);
        if (filters.userId) url.searchParams.set('user_id', filters.userId);
        if (filters.projectId) url.searchParams.set('project_id', filters.projectId);

        // Show modal loading state
        tileModal.classList.remove('hidden');
        tileModal.classList.add('flex');
        tileModalContent.innerHTML = `
            <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Loading...</h3>
                <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="flex-1 p-6 flex justify-center items-center min-h-[200px]">
                <span class="animate-pulse text-bgray-500 font-semibold text-lg">Fetching records...</span>
            </div>
        `;

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to load tile details');

            const result = await response.json();
            if (result.success && result.html) {
                tileModalContent.innerHTML = result.html;
            } else {
                throw new Error(result.message || 'Failed to load details');
            }
        } catch (error) {
            console.error('Tile Details Load Error:', error);
            tileModalContent.innerHTML = `
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                    <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Error</h3>
                    <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex-1 p-6 flex justify-center items-center min-h-[200px]">
                    <span class="text-red-500 font-semibold">Failed to load details. Please try again.</span>
                </div>
            `;
        }
    });

    // Close modal on overlay click
    const tileModalOverlay = document.querySelector('[data-dashboard-tile-overlay]');
    if (tileModalOverlay) {
        tileModalOverlay.addEventListener('click', () => {
            tileModal.classList.add('hidden');
            tileModal.classList.remove('flex');
        });
    }

    // Close modal on close button click
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-dashboard-tile-close]')) {
            tileModal.classList.add('hidden');
            tileModal.classList.remove('flex');
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !tileModal.classList.contains('hidden')) {
            tileModal.classList.add('hidden');
            tileModal.classList.remove('flex');
        }
    });
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

    // Initialize drill-down tile modal
    initTileDrilldownModal();

    // Listen for parent projects and nested child projects accordion toggle
    dashboardRoot.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('[data-parent-project-toggle], [data-child-project-toggle]');
        if (toggleBtn) {
            e.preventDefault();
            const targetId = toggleBtn.getAttribute('data-parent-project-toggle') || toggleBtn.getAttribute('data-child-project-toggle');
            const depth = toggleBtn.getAttribute('data-depth');
            if (targetId) {
                toggleChildProjects(targetId, depth);
            }
        }
    });

    // Initial load
    switchDashboardMode();
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectAnalyticsDashboard);
} else {
    initProjectAnalyticsDashboard();
}
