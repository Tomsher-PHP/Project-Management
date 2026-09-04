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

    const flowFilter = document.getElementById('projects-count-flow-filter');
    const categoryFilter = document.getElementById('projects-count-category-filter');
    const customerFilter = document.getElementById('projects-count-customer-filter');
    const monthFilter = document.getElementById('projects-count-month-filter');
    const statusFilter = document.getElementById('projects-count-status-filter');

    if (!canvas || !fetchUrl || typeof window.Chart === 'undefined') {
        return;
    }

    let chartInstance = null;

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
                            color: '#64748B',
                            font: {
                                weight: '600',
                            },
                        },
                        title: {
                            display: true,
                            text: 'Year',
                            color: '#64748B',
                            font: {
                                weight: '600',
                                size: 12,
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#64748B',
                            precision: 0,
                            stepSize: 1,
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)',
                        },
                        title: {
                            display: true,
                            text: 'Count',
                            color: '#64748B',
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
});
