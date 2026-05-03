const overviewCharts = new Map();

const getContrastColor = (color) => {
    if (typeof color !== 'string') {
        return '#FFFFFF';
    }

    const normalizedColor = color.replace('#', '');

    if (!/^[0-9A-Fa-f]{6}$/.test(normalizedColor)) {
        return '#FFFFFF';
    }

    const red = Number.parseInt(normalizedColor.slice(0, 2), 16);
    const green = Number.parseInt(normalizedColor.slice(2, 4), 16);
    const blue = Number.parseInt(normalizedColor.slice(4, 6), 16);
    const brightness = ((red * 299) + (green * 587) + (blue * 114)) / 1000;

    return brightness > 150 ? '#1A202C' : '#FFFFFF';
};

const parseChartData = (overviewRoot) => {
    const dataElement = overviewRoot.querySelector('[data-project-overview-chart-data]');

    if (!dataElement) {
        return [];
    }

    try {
        const parsedData = JSON.parse(dataElement.textContent || '[]');

        return Array.isArray(parsedData) ? parsedData : [];
    } catch (error) {
        return [];
    }
};

const destroyOverviewChart = (projectId) => {
    const existingChart = overviewCharts.get(projectId);

    if (!existingChart) {
        return;
    }

    existingChart.destroy();
    overviewCharts.delete(projectId);
};

const renderOverviewChart = (overviewRoot) => {
    const projectId = overviewRoot.dataset.projectId;
    const canvas = overviewRoot.querySelector('[data-project-overview-chart]');
    const chartWrapper = overviewRoot.querySelector('[data-project-overview-chart-wrapper]');
    const emptyState = overviewRoot.querySelector('[data-project-overview-empty-state]');
    const totalNode = overviewRoot.querySelector('[data-project-overview-chart-total]');
    const chartData = parseChartData(overviewRoot).filter((item) => Number(item?.value) > 0);

    if (!projectId || !canvas || typeof window.Chart === 'undefined') {
        return;
    }

    destroyOverviewChart(projectId);

    if (!chartData.length) {
        if (totalNode) {
            totalNode.textContent = '0';
        }

        chartWrapper?.classList.add('hidden');
        emptyState?.classList.remove('hidden');
        return;
    }

    chartWrapper?.classList.remove('hidden');
    emptyState?.classList.add('hidden');

    const datasetColors = chartData.map((item) => item.color || '#CBD5E1');
    const totalCount = chartData.reduce((sum, item) => sum + (Number(item.value) || 0), 0);

    if (totalNode) {
        totalNode.textContent = String(totalCount);
    }

    const customDatalabels = {
        id: 'customDatalabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;

            ctx.save();

            chart.getDatasetMeta(0).data.forEach((element, index) => {
                const value = Number(chartData[index]?.value) || 0;

                if (value <= 0) {
                    return;
                }

                const position = element.tooltipPosition();

                ctx.font = 'bold 12px sans-serif';
                ctx.fillStyle = getContrastColor(datasetColors[index]);
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(String(value), position.x, position.y);
            });

            ctx.restore();
        },
    };

    const chart = new window.Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: chartData.map((item) => item.label),
            datasets: [{
                data: chartData.map((item) => Number(item.value) || 0),
                backgroundColor: datasetColors,
                borderColor: datasetColors.map((color) => getContrastColor(color)),
                hoverOffset: 18,
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 10,
                    bottom: 10,
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const label = context.label || 'Status';
                            const value = Number(context.raw) || 0;
                            const percentage = totalCount > 0 ? Math.round((value / totalCount) * 100) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        },
                    },
                },
            },
        },
        plugins: [customDatalabels],
    });

    overviewCharts.set(projectId, chart);
};

const initializeOverviewPanel = (panel) => {
    const overviewRoot = panel?.querySelector('[data-project-overview]');

    if (!overviewRoot) {
        return;
    }

    renderOverviewChart(overviewRoot);
};

document.addEventListener('project-tab:loaded', (event) => {
    if (event.detail?.tab !== 'overview') {
        return;
    }

    initializeOverviewPanel(event.detail.panel);
});

document.addEventListener('project-tab:invalidate', (event) => {
    if (event.detail?.tab !== 'overview') {
        return;
    }

    const projectId = window.ProjectApp?.id;

    if (!projectId) {
        return;
    }

    destroyOverviewChart(String(projectId));
});
