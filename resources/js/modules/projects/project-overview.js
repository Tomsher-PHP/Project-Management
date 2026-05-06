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

const getChartRegistry = (projectId) => {
    if (!overviewCharts.has(projectId)) {
        overviewCharts.set(projectId, {
            status: null,
            burnup: null,
        });
    }

    return overviewCharts.get(projectId);
};

const parseJsonScript = (overviewRoot, selector, fallback) => {
    const dataElement = overviewRoot.querySelector(selector);

    if (!dataElement) {
        return fallback;
    }

    try {
        return JSON.parse(dataElement.textContent || JSON.stringify(fallback));
    } catch (error) {
        return fallback;
    }
};

const parseChartData = (overviewRoot) => {
    const parsedData = parseJsonScript(overviewRoot, '[data-project-overview-chart-data]', []);

    return Array.isArray(parsedData) ? parsedData : [];
};

const parseBurnupChartData = (overviewRoot) => {
    const parsedData = parseJsonScript(overviewRoot, '[data-project-overview-burnup-data]', {});

    if (!parsedData || typeof parsedData !== 'object') {
        return {
            labels: [],
            end_label: null,
            origin_label: null,
            interval: 10,
            max_hours: 10,
            datasets: [],
        };
    }

    return {
        labels: Array.isArray(parsedData.labels) ? parsedData.labels : [],
        end_label: typeof parsedData.end_label === 'string' ? parsedData.end_label : null,
        origin_label: typeof parsedData.origin_label === 'string' ? parsedData.origin_label : null,
        interval: Number(parsedData.interval) > 0 ? Number(parsedData.interval) : 10,
        max_hours: Number(parsedData.max_hours) > 0 ? Number(parsedData.max_hours) : 10,
        datasets: Array.isArray(parsedData.datasets) ? parsedData.datasets : [],
    };
};

const destroyOverviewChart = (projectId, chartKey) => {
    const chartRegistry = overviewCharts.get(projectId);

    if (!chartRegistry?.[chartKey]) {
        return;
    }

    chartRegistry[chartKey].destroy();
    chartRegistry[chartKey] = null;

    if (!chartRegistry.status && !chartRegistry.burnup) {
        overviewCharts.delete(projectId);
    }
};

const formatHourValue = (value) => {
    const numericValue = Number(value);

    if (!Number.isFinite(numericValue)) {
        return '0h';
    }

    const normalizedValue = Number.isInteger(numericValue)
        ? String(numericValue)
        : numericValue.toFixed(2).replace(/\.?0+$/, '');

    return `${normalizedValue}h`;
};

const scheduleChartResize = (chart) => {
    if (!chart) {
        return;
    }

    const resizeChart = () => {
        chart.resize();
        chart.update('none');
    };

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(resizeChart);
    });

    window.setTimeout(resizeChart, 180);
};

const waitForVisiblePanel = (panel, callback) => {
    if (!panel) {
        return;
    }

    const runCallback = () => {
        if (typeof callback === 'function') {
            callback();
        }
    };

    if (!panel.classList.contains('hidden')) {
        runCallback();
        return;
    }

    const observer = new MutationObserver(() => {
        if (panel.classList.contains('hidden')) {
            return;
        }

        observer.disconnect();
        runCallback();
    });

    observer.observe(panel, {
        attributes: true,
        attributeFilter: ['class'],
    });
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

    destroyOverviewChart(projectId, 'status');

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

    getChartRegistry(projectId).status = chart;
    scheduleChartResize(chart);
};

const renderBurnupChart = (overviewRoot) => {
    const projectId = overviewRoot.dataset.projectId;
    const canvas = overviewRoot.querySelector('[data-project-overview-burnup-chart]');
    const chartWrapper = overviewRoot.querySelector('[data-project-overview-burnup-chart-wrapper]');
    const emptyState = overviewRoot.querySelector('[data-project-overview-burnup-empty-state]');
    const burnupData = parseBurnupChartData(overviewRoot);
    const labels = Array.isArray(burnupData.labels) ? burnupData.labels : [];
    const datasets = Array.isArray(burnupData.datasets) ? burnupData.datasets : [];
    const hasBurnupData = labels.length > 0 && datasets.some((dataset) => Array.isArray(dataset?.data) && dataset.data.length > 0);

    if (!projectId || !canvas || typeof window.Chart === 'undefined') {
        return;
    }

    destroyOverviewChart(projectId, 'burnup');

    if (!hasBurnupData) {
        chartWrapper?.classList.add('hidden');
        emptyState?.classList.remove('hidden');
        return;
    }

    chartWrapper?.classList.remove('hidden');
    emptyState?.classList.add('hidden');

    const estimatedDataset = datasets[0] || {};
    const actualDataset = datasets[1] || {};
    const endLabel = typeof burnupData.end_label === 'string' ? burnupData.end_label : null;
    const originLabel = typeof burnupData.origin_label === 'string' ? burnupData.origin_label : null;
    const maxHours = Number(burnupData.max_hours) > 0 ? Number(burnupData.max_hours) : undefined;
    const interval = Number(burnupData.interval) > 0 ? Number(burnupData.interval) : 10;
    const isOriginPoint = (context) => context?.raw?.y === originLabel;
    const estimatedHoursByMilestone = new Map(
        (Array.isArray(estimatedDataset.data) ? estimatedDataset.data : [])
            .filter((point) => point && typeof point === 'object' && point.y !== originLabel)
            .map((point) => [point.y, formatHourValue(point.x)]),
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

                if (!chart.isDatasetVisible(datasetIndex) || meta.hidden) {
                    return;
                }

                const labelColor = dataset.borderColor || dataset.backgroundColor || '#111827';

                meta.data.forEach((element, pointIndex) => {
                    const point = dataset.data?.[pointIndex];

                    if (!point || point.y === originLabel) {
                        return;
                    }

                    const text = formatHourValue(point.x);
                    const textWidth = ctx.measureText(text).width;
                    const pointPosition = element.tooltipPosition();
                    const isNearRightEdge = pointPosition.x + textWidth + 10 > chartArea.right;
                    const yOffset = datasetIndex === 0 ? -12 : 12;

                    ctx.fillStyle = labelColor;
                    ctx.textAlign = isNearRightEdge ? 'right' : 'left';
                    ctx.fillText(
                        text,
                        pointPosition.x + (isNearRightEdge ? -8 : 8),
                        pointPosition.y + yOffset,
                    );
                });
            });

            ctx.restore();
        },
    };

    const chart = new window.Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: estimatedDataset.label || 'Estimated Hours',
                    data: Array.isArray(estimatedDataset.data) ? estimatedDataset.data : [],
                    borderColor: '#22C55E',
                    backgroundColor: '#22C55E',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    pointRadius(context) {
                        return isOriginPoint(context) ? 0 : 4;
                    },
                    pointHoverRadius(context) {
                        return isOriginPoint(context) ? 0 : 6;
                    },
                },
                {
                    label: actualDataset.label || 'Actual Hours',
                    data: Array.isArray(actualDataset.data) ? actualDataset.data : [],
                    borderColor: '#EF4444',
                    backgroundColor: '#EF4444',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    pointRadius(context) {
                        return isOriginPoint(context) ? 0 : 4;
                    },
                    pointHoverRadius(context) {
                        return isOriginPoint(context) ? 0 : 6;
                    },
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            parsing: false,
            layout: {
                padding: {
                    right: 44,
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: maxHours,
                    grace: '8%',
                    ticks: {
                        stepSize: interval,
                        callback(value) {
                            return `${value}h`;
                        },
                    },
                    title: {
                        display: true,
                        text: 'Hours',
                    },
                },
                y: {
                    type: 'category',
                    labels,
                    ticks: {
                        callback(value, index) {
                            const label = this.getLabelForValue(value) || labels[index] || '';

                            if (label === originLabel || label === endLabel) {
                                return '';
                            }

                            const estimatedHours = estimatedHoursByMilestone.get(label);

                            return estimatedHours ? `${label} (${estimatedHours})` : label;
                        },
                    },
                    title: {
                        display: true,
                        text: 'Milestones',
                    },
                },
            },
            plugins: {
                tooltip: {
                    filter(context) {
                        return !isOriginPoint(context);
                    },
                    callbacks: {
                        label(context) {
                            const rawX = Number(context.raw?.x ?? context.parsed?.x ?? 0);
                            return `${context.dataset.label}: ${rawX}h`;
                        },
                    },
                },
                legend: {
                    position: 'bottom',
                },
            },
        },
        plugins: [burnupPointLabels],
    });

    getChartRegistry(projectId).burnup = chart;
    scheduleChartResize(chart);
};

const initializeOverviewPanel = (panel) => {
    const overviewRoot = panel?.querySelector('[data-project-overview]');

    if (!overviewRoot) {
        return;
    }

    waitForVisiblePanel(panel, () => {
        renderOverviewChart(overviewRoot);
        renderBurnupChart(overviewRoot);
    });
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

    destroyOverviewChart(String(projectId), 'status');
    destroyOverviewChart(String(projectId), 'burnup');
});
