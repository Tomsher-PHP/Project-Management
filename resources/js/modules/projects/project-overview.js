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
    const formatMilestoneAxisLabel = (label) => {
        const characters = Array.from(String(label || ''));

        return characters.length > 12
            ? `${characters.slice(0, 12).join('')}...`
            : characters.join('');
    };
    const estimatedHoursByMilestone = new Map(
        (Array.isArray(estimatedDataset.data) ? estimatedDataset.data : [])
            .filter((point) => point && typeof point === 'object' && point.y !== originLabel)
            .map((point) => [point.y, formatHourValue(point.x)]),
    );

    const burnupAxisLabelTooltip = {
        id: 'burnupAxisLabelTooltip',
        afterInit(chart) {
            const tooltip = document.createElement('div');

            tooltip.className = 'pointer-events-none fixed z-50 hidden max-w-xs rounded-lg bg-bgray-900 px-3 py-2 text-xs font-semibold text-bgray-700 shadow-lg dark:bg-white dark:text-bgray-900';
            tooltip.setAttribute('role', 'tooltip');
            document.body.appendChild(tooltip);
            chart.$axisLabelTooltip = tooltip;
        },
        afterEvent(chart, { event }) {
            const tooltip = chart.$axisLabelTooltip;
            const yScale = chart.scales.y;
            const hideTooltip = () => {
                tooltip?.classList.add('hidden');
                chart.canvas.style.cursor = '';
            };

            if (!tooltip || !yScale || event.type === 'mouseout') {
                hideTooltip();
                return;
            }

            const isInsideYAxis = event.x >= yScale.left
                && event.x <= yScale.right
                && event.y >= yScale.top
                && event.y <= yScale.bottom;

            if (!isInsideYAxis) {
                hideTooltip();
                return;
            }

            const tickPixels = labels.map((label, index) => ({
                label,
                pixel: yScale.getPixelForTick(index),
            }));
            const tickSpacing = tickPixels.length > 1
                ? Math.abs(tickPixels[1].pixel - tickPixels[0].pixel)
                : 24;
            const hitRadius = Math.max(6, Math.min(12, tickSpacing / 2));
            const hoveredTick = tickPixels.find(({ label, pixel }) => (
                label !== originLabel
                && label !== endLabel
                && Math.abs(event.y - pixel) <= hitRadius
            ));

            if (!hoveredTick) {
                hideTooltip();
                return;
            }

            tooltip.textContent = String(hoveredTick.label);
            tooltip.classList.remove('hidden');
            chart.canvas.style.cursor = 'help';

            const canvasRect = chart.canvas.getBoundingClientRect();
            const clientX = event.native?.clientX ?? canvasRect.left + event.x;
            const clientY = event.native?.clientY ?? canvasRect.top + event.y;
            const left = Math.max(8, Math.min(clientX + 12, window.innerWidth - tooltip.offsetWidth - 8));
            const top = Math.max(8, Math.min(clientY + 12, window.innerHeight - tooltip.offsetHeight - 8));

            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
        },
        beforeDestroy(chart) {
            chart.$axisLabelTooltip?.remove();
            chart.canvas.style.cursor = '';
            delete chart.$axisLabelTooltip;
        },
    };

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
                    label: window.translations.labels.estimated_hours,
                    data: Array.isArray(estimatedDataset.data) ? estimatedDataset.data : [],
                    borderColor: '#3B82F6',
                    backgroundColor: '#3B82F6',
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
                    label: window.translations.labels.spent_hours,
                    data: Array.isArray(actualDataset.data) ? actualDataset.data : [],
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
                        display: false,
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
                            const axisLabel = formatMilestoneAxisLabel(label);

                            return estimatedHours ? `${axisLabel} (${estimatedHours})` : axisLabel;
                        },
                    },
                    title: {
                        display: false,
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
        plugins: [burnupPointLabels, burnupAxisLabelTooltip],
    });

    getChartRegistry(projectId).burnup = chart;
    scheduleChartResize(chart);
};


const renderAssigneeBarChart = (overviewRoot) => {
    const canvas = overviewRoot.querySelector('[data-assignee-bar-chart]');
    const chartWrapper = overviewRoot.querySelector('[data-assignee-bar-chart-wrapper]');
    const scrollContainer = overviewRoot.querySelector('[data-assignee-chart-scroll]');
    const dataElement = overviewRoot.querySelector('[data-assignee-bar-chart-data]');

    if (!canvas || !dataElement || typeof window.Chart === 'undefined') {
        return;
    }

    let chartData = [];

    try {
        chartData = JSON.parse(dataElement.textContent || '[]');
    } catch (error) {
        console.error('Unable to parse assignee bar chart data:', error);
        return;
    }

    if (!Array.isArray(chartData)) {
        chartData = [];
    }

    chartData = chartData.filter((item) => item);

    if (!chartData.length) {
        return;
    }

    /*
     * Destroy existing chart
     */
    if (canvas._assigneeBarChart) {
        canvas._assigneeBarChart.destroy();
        canvas._assigneeBarChart = null;
    }

    /*
     * Prepare data
     */
    const labels = chartData.map((item) => {
        return String(item.name || 'Unassigned');
    });

    const estimatedHours = chartData.map((item) => {
        return Number(item.estimated_time_seconds || 0) / 3600;
    });

    const workedHours = chartData.map((item) => {
        return Number(item.worked_time_seconds || 0) / 3600;
    });

    /*
     * Worked bar colors
     *
     * 0% / no worked time = green
     * Worked <= Estimated = green
     * Worked > Estimated = blue
     */
    const workedColors = chartData.map((item) => {
        const estimated = Number(item.estimated_time_seconds || 0);
        const worked = Number(item.worked_time_seconds || 0);

        // No worked time = green
        if (worked === 0) {
            return '#22C55E';
        }

        // Worked exceeds estimated = RED
        if (estimated > 0 && worked > estimated) {
            return '#EF4444';
        }

        // Worked is within estimate = GREEN
        return '#22C55E';
    });

    /*
     * Make the chart wider when there are many users.
     *
     * This allows the X-axis to move horizontally while
     * the Y-axis remains fixed in the visible chart area.
     */
    const minimumWidth = 650;
    const widthPerUser = 110;

    const requiredWidth = Math.max(
        minimumWidth,
        labels.length * widthPerUser
    );

    const wrapperWidth = chartWrapper?.clientWidth || minimumWidth;

    /*
     * Canvas is wider than the visible area only when
     * there are enough users.
     */
    canvas.style.width = `${requiredWidth}px`;
    canvas.style.height = '320px';

    /*
     * Keep the visible graph area fixed.
     */
    if (scrollContainer) {
        scrollContainer.style.width = '100%';
        scrollContainer.style.height = '320px';
        scrollContainer.style.overflowX = 'hidden';
        scrollContainer.style.overflowY = 'hidden';
        scrollContainer.style.position = 'relative';
    }

    /*
     * Determine maximum Y value.
     */
    const maxValue = Math.max(
        ...estimatedHours,
        ...workedHours,
        1
    );

    const yStep = maxValue <= 10
        ? 2
        : maxValue <= 25
            ? 5
            : 10;

    const yMax = Math.ceil(maxValue / yStep) * yStep;

    /*
     * Plugin that moves only the chart's X-axis/plot area.
     */
    const assigneeDragPlugin = {
        id: 'assigneeDragPlugin',

        afterInit(chart) {
            chart.$assigneeDrag = {
                dragging: false,
                startX: 0,
                startOffset: 0,
                offset: 0,
            };

            const canvasElement = chart.canvas;

            canvasElement.style.cursor = 'grab';

            canvasElement.addEventListener('mousedown', (event) => {
                chart.$assigneeDrag.dragging = true;
                chart.$assigneeDrag.startX = event.clientX;
                chart.$assigneeDrag.startOffset =
                    chart.$assigneeDrag.offset;

                canvasElement.style.cursor = 'grabbing';
            });

            window.addEventListener('mousemove', (event) => {
                if (!chart.$assigneeDrag?.dragging) {
                    return;
                }

                const movement =
                    event.clientX - chart.$assigneeDrag.startX;

                chart.$assigneeDrag.offset =
                    chart.$assigneeDrag.startOffset - movement;

                const maxOffset = Math.max(
                    0,
                    requiredWidth - (chartWrapper?.clientWidth || requiredWidth)
                );

                chart.$assigneeDrag.offset = Math.max(
                    0,
                    Math.min(
                        chart.$assigneeDrag.offset,
                        maxOffset
                    )
                );

                chart.update('none');
            });

            window.addEventListener('mouseup', () => {
                if (!chart.$assigneeDrag?.dragging) {
                    return;
                }

                chart.$assigneeDrag.dragging = false;
                canvasElement.style.cursor = 'grab';
            });
        },

        beforeDraw(chart) {
            const offset = chart.$assigneeDrag?.offset || 0;

            if (!offset) {
                return;
            }

            const { ctx } = chart;

            ctx.save();

            /*
             * Move the complete chart horizontally.
             *
             * The Y-axis is redrawn in its original position
             * below, so it stays visually fixed.
             */
            ctx.translate(-offset, 0);

            chart.$assigneeDragOffset = offset;
        },

        afterDraw(chart) {
            const offset = chart.$assigneeDrag?.offset || 0;

            if (!offset) {
                return;
            }

            const { ctx, chartArea } = chart;

            ctx.restore();

            /*
             * Redraw the Y-axis area on top of the moved chart.
             */
            ctx.save();

            const yAxisWidth = chartArea.left;

            ctx.clearRect(
                0,
                0,
                yAxisWidth,
                chart.height
            );

            /*
             * Background of fixed Y-axis.
             */
            ctx.fillStyle =
                document.documentElement.classList.contains('dark')
                    ? '#171C24'
                    : '#FFFFFF';

            ctx.fillRect(
                0,
                0,
                yAxisWidth,
                chart.height
            );

            /*
             * Redraw Y-axis ticks.
             */
            const yScale = chart.scales.y;

            if (yScale) {
                yScale.draw(ctx);
            }

            ctx.restore();
        },
    };

    /*
     * Create Chart
     */
    const chart = new window.Chart(canvas, {
        type: 'bar',

        data: {
            labels,

            datasets: [
                {
                    label: window.translations.labels.estimated,
                    data: estimatedHours,

                    backgroundColor: '#3B82F6',
                    borderColor: '#3B82F6',

                    borderWidth: 0,
                    borderRadius: 0,
                    borderSkipped: false,

                    barThickness: 23,
                    maxBarThickness: 23,

                    categoryPercentage: 0.55,
                    barPercentage: 1,
                },

                {
                    label: window.translations.labels.spent,
                    data: workedHours,

                    backgroundColor: workedColors,
                    borderColor: workedColors,

                    borderWidth: 0,
                    borderRadius: 0,
                    borderSkipped: false,

                    barThickness: 23,
                    maxBarThickness: 23,

                    categoryPercentage: 0.55,
                    barPercentage: 1,
                },
            ],
        },

        options: {
            responsive: false,
            maintainAspectRatio: false,

            animation: false,

            /*
             * Keep normal vertical bar chart.
             */
            indexAxis: 'x',

            layout: {
                padding: {
                    top: 15,
                    right: 25,
                    bottom: 5,
                    left: 5,
                },
            },

            scales: {
                x: {
                    stacked: false,

                    grid: {
                        display: false,
                    },

                    ticks: {
                        color: '#64748B',

                        maxRotation: 0,
                        minRotation: 0,

                        callback(value) {
                            const label =
                                this.getLabelForValue(value);

                            if (!label) {
                                return '';
                            }

                            return label.length > 12
                                ? `${label.substring(0, 12)}...`
                                : label;
                        },
                    },

                    title: {
                        display: false,
                        text: 'Users',
                    },
                },

                y: {
                    beginAtZero: true,

                    max: yMax,

                    ticks: {
                        color: '#64748B',
                        precision: 0,

                        callback(value) {
                            return `${value}h`;
                        },
                    },

                    grid: {
                        color: 'rgba(148, 163, 184, 0.15)',
                    },

                    title: {
                        display: false,
                        text: 'Time',
                    },
                },
            },

            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',

                    labels: {
                        usePointStyle: true,
                        boxWidth: 10,
                        boxHeight: 10,
                        padding: 18,

                        generateLabels() {
                            return [
                                {
                                    text: window.translations.labels.estimated,
                                    fillStyle: '#3B82F6',
                                    strokeStyle: '#3B82F6',
                                    pointStyle: 'rect',
                                    lineWidth: 0,
                                },
                                {
                                    text: window.translations.labels.spent,
                                    fillStyle: '#22C55E',
                                    strokeStyle: '#22C55E',
                                    pointStyle: 'rect',
                                    lineWidth: 0,
                                },
                                {
                                    text: window.translations.labels.exceeded,
                                    fillStyle: '#EF4444',
                                    strokeStyle: '#EF4444',
                                    pointStyle: 'rect',
                                    lineWidth: 0,
                                },
                            ];
                        },
                    },
                },

                tooltip: {
                    callbacks: {
                        label(context) {
                            const value =
                                Number(context.raw) || 0;

                            return `${context.dataset.label}: ${value.toFixed(2)}h`;
                        },
                    },
                },
            },
        },

        plugins: [
            assigneeDragPlugin,
        ],
    });

    canvas._assigneeBarChart = chart;

    /*
     * Resize after the panel becomes visible.
     */
    window.requestAnimationFrame(() => {
        chart.resize();
        chart.update('none');
    });

    window.setTimeout(() => {
        chart.resize();
        chart.update('none');
    }, 180);
};
const initializeOverviewPanel = (panel) => {
    const overviewRoot = panel?.querySelector('[data-project-overview]');

    if (!overviewRoot) {
        return;
    }

    waitForVisiblePanel(panel, () => {
        renderOverviewChart(overviewRoot);
        renderBurnupChart(overviewRoot);
        renderAssigneeBarChart(overviewRoot);
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
