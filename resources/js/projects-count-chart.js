document.addEventListener('DOMContentLoaded', () => {
    const cardSection = document.querySelector('[data-projects-count-section]');
    if (!cardSection) {
        return;
    }

    const fetchUrl = cardSection.getAttribute('data-projects-count-url');
    const canvas = cardSection.querySelector('[data-projects-count-chart]');
    const chartWrapper = cardSection.querySelector('[data-projects-count-chart-wrapper]');
    const emptyState = cardSection.querySelector('[data-projects-count-empty-state]');
    const loadingOverlay = cardSection.querySelector('[data-projects-count-loading]');

    const filterToggleBtn = cardSection.querySelector('[data-projects-count-filter-toggle]');
    const filtersWrapper = cardSection.querySelector('[data-projects-count-filters-wrapper]');
    const filterBadge = cardSection.querySelector('[data-projects-count-filter-badge]');

    const flowFilter = document.getElementById('projects-count-flow-filter');
    const categoryFilter = document.getElementById('projects-count-category-filter');
    const customerFilter = document.getElementById('projects-count-customer-filter');
    const monthFilter = document.getElementById('projects-count-month-filter');
    const statusFilter = document.getElementById('projects-count-status-filter');

    if (!canvas || !fetchUrl || typeof window.Chart === 'undefined') {
        return;
    }

    let chartInstance = null;

    if (filterToggleBtn && filtersWrapper) {
        filterToggleBtn.addEventListener('click', () => {
            const isCollapsed = filtersWrapper.classList.contains('max-h-0');
            if (isCollapsed) {
                filtersWrapper.classList.remove('max-h-0', 'opacity-0', 'pointer-events-none', 'mt-0');
                filtersWrapper.classList.add('max-h-[600px]', 'opacity-100', 'mt-4');
                filterToggleBtn.setAttribute('aria-expanded', 'true');
            } else {
                filtersWrapper.classList.remove('max-h-[600px]', 'opacity-100', 'mt-4');
                filtersWrapper.classList.add('max-h-0', 'opacity-0', 'pointer-events-none', 'mt-0');
                filterToggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const getSelectedFlow = () => {
        if (!flowFilter) return '';
        if (flowFilter.tomselect) return flowFilter.tomselect.getValue() || '';
        return flowFilter.value || '';
    };

    const getSelectedCategories = () => {
        if (!categoryFilter) return [];
        if (categoryFilter.tomselect) {
            const val = categoryFilter.tomselect.getValue();
            if (Array.isArray(val)) return val;
            return val ? [val] : [];
        }
        return Array.from(categoryFilter.selectedOptions).map((opt) => opt.value).filter(Boolean);
    };

    const getSelectedCustomer = () => {
        if (!customerFilter) return '';
        if (customerFilter.tomselect) return customerFilter.tomselect.getValue() || '';
        return customerFilter.value || '';
    };

    const getSelectedMonth = () => {
        if (!monthFilter) return '';
        if (monthFilter.tomselect) return monthFilter.tomselect.getValue() || '';
        return monthFilter.value || '';
    };

    const getSelectedStatuses = () => {
        if (!statusFilter) return [];
        if (statusFilter.tomselect) {
            const val = statusFilter.tomselect.getValue();
            if (Array.isArray(val)) return val;
            return val ? [val] : [];
        }
        return Array.from(statusFilter.selectedOptions).map((opt) => opt.value).filter(Boolean);
    };

    const updateFilterBadge = () => {
        const flow = getSelectedFlow();
        const categories = getSelectedCategories();
        const customer = getSelectedCustomer();
        const month = getSelectedMonth();
        const statuses = getSelectedStatuses();

        let activeCount = 0;
        if (flow) activeCount++;
        if (categories.length > 0) activeCount++;
        if (customer) activeCount++;
        if (month) activeCount++;
        if (statuses.length > 0) activeCount++;

        if (filterBadge) {
            if (activeCount > 0) {
                filterBadge.textContent = String(activeCount);
                filterBadge.classList.remove('hidden');
            } else {
                filterBadge.textContent = '0';
                filterBadge.classList.add('hidden');
            }
        }

        if (filterToggleBtn) {
            if (activeCount > 0) {
                filterToggleBtn.classList.add('border-success-300', 'bg-success-50/80', 'text-success-400', 'dark:border-success-900/30', 'dark:bg-darkblack-500', 'dark:text-success-300');
            } else {
                filterToggleBtn.classList.remove('border-success-300', 'bg-success-50/80', 'text-success-400', 'dark:border-success-900/30', 'dark:bg-darkblack-500', 'dark:text-success-300');
            }
        }
    };

    const destroyChart = () => {
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
    };

    const showLoading = () => {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('hidden');
        }
    };

    const hideLoading = () => {
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
    };

    const loadChartData = async () => {
        showLoading();
        updateFilterBadge();

        try {
            const params = new URLSearchParams();

            const flow = getSelectedFlow();
            if (flow) params.append('project_flow', flow);

            const categories = getSelectedCategories();
            categories.forEach((id) => {
                if (id) params.append('project_category_ids[]', id);
            });

            const customer = getSelectedCustomer();
            if (customer) params.append('customer_id', customer);

            const month = getSelectedMonth();
            if (month) params.append('month', month);

            const statuses = getSelectedStatuses();
            statuses.forEach((id) => {
                if (id) params.append('status_id[]', id);
            });

            const requestUrl = `${fetchUrl}${params.toString() ? '?' + params.toString() : ''}`;

            const response = await fetch(requestUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Projects count chart fetch failed');
            }

            const result = await response.json();

            if (result.success && result.data) {
                renderChart(result.data.labels || [], result.data.data || []);
            } else {
                renderChart([], []);
            }
        } catch (error) {
            console.error('Projects Count Chart Error:', error);
            renderChart([], []);
        } finally {
            hideLoading();
        }
    };

    const renderChart = (labels, dataValues) => {
        destroyChart();

        const hasData = Array.isArray(labels) && labels.length > 0 && Array.isArray(dataValues) && dataValues.some((v) => Number(v) > 0);

        if (!hasData) {
            chartWrapper?.classList.add('hidden');
            emptyState?.classList.remove('hidden');
            return;
        }

        chartWrapper?.classList.remove('hidden');
        emptyState?.classList.add('hidden');

        const maxValue = Math.max(...dataValues.map((v) => Number(v) || 0), 1);
        let yStep = 1;
        if (maxValue > 100) {
            yStep = 20;
        } else if (maxValue > 50) {
            yStep = 10;
        } else if (maxValue > 20) {
            yStep = 5;
        } else if (maxValue > 10) {
            yStep = 2;
        } else {
            yStep = 1;
        }
        const yMax = Math.ceil(maxValue / yStep) * yStep;

        const isDark = document.documentElement.classList.contains('dark');
        const labelColor = isDark ? '#E2E8F0' : '#4A5568';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(148, 163, 184, 0.15)';

        chartInstance = new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels.map((l) => String(l)),
                datasets: [
                    {
                        label: 'Projects Count',
                        data: dataValues.map((v) => Number(v) || 0),
                        backgroundColor: '#3B82F6',
                        borderColor: '#3B82F6',
                        borderWidth: 0,
                        borderRadius: 4,
                        borderSkipped: false,
                        barThickness: labels.length > 8 ? undefined : 36,
                        maxBarThickness: 48,
                        categoryPercentage: 0.6,
                        barPercentage: 0.9,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                layout: {
                    padding: {
                        top: 20,
                        right: 20,
                        bottom: 5,
                        left: 5,
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: labelColor,
                            font: {
                                weight: '600',
                            },
                        },
                        title: {
                            display: true,
                            text: 'Year',
                            color: labelColor,
                            font: {
                                weight: '600',
                                size: 12,
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        max: yMax,
                        ticks: {
                            color: labelColor,
                            precision: 0,
                            stepSize: yStep,
                        },
                        grid: {
                            color: gridColor,
                        },
                        title: {
                            display: true,
                            text: 'Count',
                            color: labelColor,
                            font: {
                                weight: '600',
                                size: 12,
                            },
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const value = Number(context.raw) || 0;
                                return `Projects: ${value}`;
                            },
                        },
                    },
                },
            },
        });
    };

    const boundElements = new Set();
    const bindFilterEvent = (element) => {
        if (!element || boundElements.has(element)) return;

        if (element.tomselect) {
            element.tomselect.on('change', loadChartData);
            boundElements.add(element);
        } else {
            element.addEventListener('change', loadChartData);
            boundElements.add(element);
        }
    };

    const bindAllFilterEvents = () => {
        [flowFilter, categoryFilter, customerFilter, monthFilter, statusFilter].forEach(bindFilterEvent);
    };

    bindAllFilterEvents();
    document.addEventListener('tomselect:ready', bindAllFilterEvents);
    setTimeout(bindAllFilterEvents, 200);
    setTimeout(bindAllFilterEvents, 500);

    // Initial lazy load of chart data
    loadChartData();

    // Re-render chart colors when dark/light theme changes
    const themeObserver = new MutationObserver(() => {
        if (chartInstance) {
            loadChartData();
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
