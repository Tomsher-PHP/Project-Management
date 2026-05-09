document.addEventListener('DOMContentLoaded', () => {
    const stickyBar = document.getElementById('running-task-bar');
    const stickyProject = document.getElementById('running-task-project');
    const stickyName = document.getElementById('running-task-name');
    const stickyTimer = document.getElementById('running-task-timer');
    const stickyPauseButton = document.getElementById('running-task-pause');
    const totalBadge = document.getElementById('task-total-badge');
    const runningCount = document.getElementById('task-running-count');
    const doneCount = document.getElementById('task-done-count');
    const pendingCount = document.getElementById('task-pending-count');
    const timelineEl = document.getElementById('task-timeline');
    const timelineTooltip = document.getElementById('task-timeline-tooltip');
    const taskItems = Array.from(document.querySelectorAll('[data-task-item]'));
    const taskButtons = taskItems
        .map((task) => task.querySelector('[data-task-play-toggle]'))
        .filter(Boolean);
    let activeButton = null;
    let timerIntervalId = null;
    let stickyDisplayButton = null;
    let selectedTask = taskItems.find((task) => task.dataset.selected === 'true') || null;
    const undoTimers = new Map();
    const undoDelayMs = 4000;
    const timelineSlots = Array.from({
        length: 24
    }, (_, index) => {
        const hour = index % 12 === 0 ? 12 : index % 12;
        const suffix = index < 12 ? 'AM' : 'PM';

        return {
            label: `${hour} ${suffix}`,
            start: index * 60,
            end: (index + 1) * 60
        };
    });
    const timelineSegments = [{
            type: 'shift',
            title: 'Resolve campaign blocker',
            project: 'Q2 Marketing',
            details: 'Sprint push for campaign blocker resolution.',
            start: 540,
            end: 590,
            height: 76
        },
        {
            type: 'offline',
            title: 'Offline break',
            project: 'Status update',
            details: 'Connectivity drop during morning sync.',
            start: 590,
            end: 600,
            height: 76
        },
        {
            type: 'shift',
            title: 'Review API v3 auth spec',
            project: 'API v3',
            details: 'Security review and token validation pass.',
            start: 600,
            end: 655,
            height: 118
        },
        {
            type: 'offline',
            title: 'Offline handoff',
            project: 'Status update',
            details: 'Short offline period while waiting for stakeholder feedback.',
            start: 655,
            end: 667,
            height: 118
        },
        {
            type: 'shift',
            title: 'Update design token file',
            project: 'Design System',
            details: 'Token cleanup and spacing variable updates.',
            start: 667,
            end: 750,
            height: 132
        },
        {
            type: 'offline',
            title: 'Offline interruption',
            project: 'Status update',
            details: 'Brief interruption before lunch wrap-up.',
            start: 750,
            end: 765,
            height: 132
        },
        {
            type: 'shift',
            title: 'Write Q2 retro notes',
            project: 'Internal',
            details: 'Retro draft and action item capture.',
            start: 765,
            end: 830,
            height: 120
        },
        {
            type: 'shift',
            title: 'Schedule design review',
            project: 'Design System',
            details: 'Review scheduling and attendee confirmation.',
            start: 850,
            end: 915,
            height: 132
        },
        {
            type: 'offline',
            title: 'Offline review gap',
            project: 'Status update',
            details: 'Brief away period before the afternoon push.',
            start: 915,
            end: 925,
            height: 132
        },
        {
            type: 'shift',
            title: 'Resolve campaign blocker',
            project: 'Q2 Marketing',
            details: 'Final fixes and stakeholder follow-up.',
            start: 925,
            end: 1035,
            height: 138
        },
        {
            type: 'offline',
            title: 'Offline check-in',
            project: 'Status update',
            details: 'Short break before handoff updates.',
            start: 1035,
            end: 1048,
            height: 138
        },
        {
            type: 'shift',
            title: 'Review API v3 auth spec',
            project: 'API v3',
            details: 'Late-day verification and summary notes.',
            start: 1048,
            end: 1080,
            height: 124
        }
    ];
    const timelineShiftWindow = {
        start: 540,
        end: 1110
    };

    const playIcon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 3.5V12.5L12 8L5 3.5Z" fill="currentColor" /></svg>';
    const pauseIcon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 3H7V13H5V3Z" fill="currentColor" /><path d="M9 3H11V13H9V3Z" fill="currentColor" /></svg>';

    const minutesToLabel = (minutes) => {
        const hours24 = Math.floor(minutes / 60);
        const mins = String(minutes % 60).padStart(2, '0');
        const suffix = hours24 >= 12 ? 'PM' : 'AM';
        const hours12 = hours24 % 12 === 0 ? 12 : hours24 % 12;

        return `${hours12}:${mins} ${suffix}`;
    };

    const formatDuration = (totalSeconds) => {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const seconds = String(totalSeconds % 60).padStart(2, '0');

        return `${hours}:${minutes}:${seconds}`;
    };

    const clearTimelineHighlight = () => {
        timelineEl?.querySelectorAll('.timeline-slot').forEach((slot) => {
            slot.classList.remove('is-highlighted');
        });

        timelineEl?.querySelectorAll('.timeline-segment, .timeline-slot__worked-fill').forEach((segment) => {
            segment.classList.remove('is-active');
        });
    };

    const hideTimelineTooltip = () => {
        timelineTooltip?.classList.add('hidden');
        clearTimelineHighlight();
    };

    const showTimelineTooltip = (segment, event) => {
        if (!timelineEl || !timelineTooltip) {
            return;
        }

        clearTimelineHighlight();

        timelineEl.querySelectorAll(`[data-segment-id="${segment.id}"]`).forEach((entry) => {
            entry.classList.add('is-active');
        });

        timelineEl.querySelectorAll('.timeline-slot').forEach((slot) => {
            const slotStart = Number(slot.dataset.slotStart);
            const slotEnd = Number(slot.dataset.slotEnd);
            if (segment.start < slotEnd && segment.end > slotStart) {
                slot.classList.add('is-highlighted');
            }
        });

        const dotColor = segment.type === 'shift' ? '#16a34a' : '#ef4444';
        timelineTooltip.innerHTML = `
                  <div class="timeline-tooltip__label">
                      <span class="timeline-tooltip__dot" style="background:${dotColor}"></span>
                      <span>${segment.type}</span>
                  </div>
                  <p class="timeline-tooltip__title">${segment.title}</p>
                  <p class="timeline-tooltip__meta">${segment.project}</p>
                  <p class="timeline-tooltip__meta">${minutesToLabel(segment.start)} - ${minutesToLabel(segment.end)}</p>
                  <p class="timeline-tooltip__meta">${segment.details}</p>
              `;

        const timelineRect = timelineEl.getBoundingClientRect();
        const x = event.clientX - timelineRect.left + 16;
        const y = event.clientY - timelineRect.top - 22;

        timelineTooltip.classList.remove('hidden');
        const tooltipWidth = timelineTooltip.offsetWidth;
        const tooltipHeight = timelineTooltip.offsetHeight;
        const maxLeft = timelineRect.width - tooltipWidth - 8;
        const left = Math.max(8, Math.min(x, maxLeft));
        const top = Math.max(8, y - tooltipHeight);

        timelineTooltip.style.left = `${left}px`;
        timelineTooltip.style.top = `${top}px`;
    };

    const renderTimeline = () => {
        if (!timelineEl) {
            return;
        }

        const timelineStart = 0;
        const timelineEnd = 24 * 60;
        const totalMinutes = timelineEnd - timelineStart;
        const gridColumns = timelineSlots.map((slot) => `${slot.end - slot.start}fr`).join(' ');

        const slotMarkup = timelineSlots.map((slot, index) => {
            const overlappingShiftSegments = timelineSegments
                .map((segment, segmentIndex) => ({
                    ...segment,
                    id: segmentIndex
                }))
                .filter((segment) => segment.type === 'shift' && segment.start < slot.end && segment.end > slot.start);

            const workedMinutes = overlappingShiftSegments.reduce((total, segment) => {
                const overlapStart = Math.max(segment.start, slot.start);
                const overlapEnd = Math.min(segment.end, slot.end);
                return total + Math.max(0, overlapEnd - overlapStart);
            }, 0);

            const workedHeight = Math.max(0, Math.min(100, (workedMinutes / (slot.end - slot.start)) * 100));
            const primarySegment = overlappingShiftSegments[0];

            return `
                      <div class="timeline-slot" data-slot-index="${index}" data-slot-start="${slot.start}" data-slot-end="${slot.end}">
                          <div class="timeline-slot__top"></div>
                          ${primarySegment ? `
                        <button
                            type="button"
                            class="timeline-slot__worked-fill"
                            style="height:${workedHeight}%;"
                            data-segment-id="${primarySegment.id}"
                            aria-label="${primarySegment.title} from ${minutesToLabel(primarySegment.start)} to ${minutesToLabel(primarySegment.end)}"
                        ></button>
                    ` : ''}
                          <span class="timeline-slot__label">${slot.label}</span>
                      </div>
                  `;
        }).join('');

        const activityMarkup = timelineSegments.map((segment, index) => {
            const left = ((segment.start - timelineStart) / totalMinutes) * 100;
            const width = ((segment.end - segment.start) / totalMinutes) * 100;

            return `
                      <button
                          type="button"
                          class="timeline-segment timeline-segment--activity timeline-segment--${segment.type}"
                          style="left:${left}%;width:${width}%;"
                          data-segment-id="${index}"
                          aria-label="${segment.title} from ${minutesToLabel(segment.start)} to ${minutesToLabel(segment.end)}"
                      ></button>
                  `;
        }).join('');

        const shiftLeft = ((timelineShiftWindow.start - timelineStart) / totalMinutes) * 100;
        const shiftWidth = ((timelineShiftWindow.end - timelineShiftWindow.start) / totalMinutes) * 100;
        const shiftIndicatorMarkup = `
                  <div
                      class="timeline-shift-indicator"
                      style="left:${shiftLeft}%;width:${shiftWidth}%;"
                      aria-hidden="true"
                  ></div>
              `;

        timelineEl.innerHTML = `
                  <div class="timeline-grid" style="grid-template-columns:${gridColumns};">${slotMarkup}</div>
                  <div class="timeline-track timeline-track--activity" aria-hidden="true"></div>
                  <div class="timeline-track timeline-track--shift" aria-hidden="true"></div>
                  ${activityMarkup}
                  ${shiftIndicatorMarkup}
              `;

        timelineEl.querySelectorAll('.timeline-segment, .timeline-slot__worked-fill').forEach((node) => {
            const segment = timelineSegments[Number(node.dataset.segmentId)];
            segment.id = Number(node.dataset.segmentId);

            node.addEventListener('mouseenter', (event) => {
                showTimelineTooltip(segment, event);
            });

            node.addEventListener('mousemove', (event) => {
                showTimelineTooltip(segment, event);
            });

            node.addEventListener('mouseleave', () => {
                hideTimelineTooltip();
            });
        });

        timelineEl.querySelectorAll('.timeline-slot').forEach((slot) => {
            slot.addEventListener('mouseenter', () => {
                clearTimelineHighlight();
                slot.classList.add('is-highlighted');
            });
            slot.addEventListener('mouseleave', () => {
                clearTimelineHighlight();
            });
        });

        timelineEl.addEventListener('mouseleave', () => {
            hideTimelineTooltip();
        });
    };

    const formatTaskTime = (totalSeconds) => {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = String(totalSeconds % 60).padStart(2, '0');

        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, '0')}:${seconds}`;
        }

        return `${minutes}:${seconds}`;
    };

    const getTaskDetails = (button) => {
        const article = button.closest('[data-task-item]');
        const name = article?.querySelector('[data-task-name]')?.textContent.trim() || 'Task';
        const project = article?.querySelector('[data-task-project]')?.textContent.trim() || 'Project';

        return {
            name,
            project
        };
    };

    const getElapsedSeconds = (button) => {
        const baseElapsed = Number(button.dataset.elapsedSeconds || '0');
        const startedAt = Number(button.dataset.startedAt || '0');

        if (button.dataset.running === 'true' && startedAt > 0) {
            return baseElapsed + Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
        }

        return baseElapsed;
    };

    const renderTaskTime = (button) => {
        const article = button.closest('[data-task-item]');
        const timeLabel = article?.querySelector('[data-task-time]');

        if (timeLabel) {
            timeLabel.textContent = formatTaskTime(getElapsedSeconds(button));
        }
    };

    const updateSummary = () => {
        let running = 0;
        let done = 0;
        let pending = 0;
        let totalElapsed = 0;

        taskButtons.forEach((button) => {
            const article = button.closest('[data-task-item]');
            const isCompleted = article?.dataset.completed === 'true';
            const isRunning = button.dataset.running === 'true';
            const elapsed = getElapsedSeconds(button);

            totalElapsed += elapsed;
            renderTaskTime(button);

            if (isCompleted) {
                done += 1;
                return;
            }

            if (isRunning) {
                running += 1;
                return;
            }

            pending += 1;
        });

        if (totalBadge) totalBadge.textContent = `Total: ${formatDuration(totalElapsed)}`;
        if (runningCount) runningCount.textContent = String(running);
        if (doneCount) doneCount.textContent = String(done);
        if (pendingCount) pendingCount.textContent = String(pending);
    };

    const refreshSelection = () => {
        taskItems.forEach((task) => {
            const button = task.querySelector('[data-task-play-toggle]');
            const isCompleted = task.dataset.completed === 'true';
            const isUndoPending = task.dataset.undoPending === 'true';
            const isRunning = button?.dataset.running === 'true';
            const isSelected = selectedTask === task && !isCompleted && !isUndoPending && !isRunning;

            task.classList.toggle('task-row--selected', isSelected);
        });
    };

    const clearUndoTimer = (article) => {
        const existingTimer = undoTimers.get(article);

        if (existingTimer) {
            window.clearTimeout(existingTimer);
            undoTimers.delete(article);
        }
    };

    const syncStickyBar = (button, elapsedSeconds = 0) => {
        if (!button) {
            stickyDisplayButton = null;
            stickyBar?.classList.add('hidden');
            return;
        }

        const {
            name,
            project
        } = getTaskDetails(button);
        stickyDisplayButton = button;

        if (stickyProject) stickyProject.textContent = project;
        if (stickyName) stickyName.textContent = name;
        if (stickyTimer) stickyTimer.textContent = formatDuration(elapsedSeconds);
        stickyBar?.classList.remove('hidden');
    };

    const syncStickyAction = (button) => {
        if (!stickyPauseButton) {
            return;
        }

        const icon = stickyPauseButton.querySelector('span');
        const isRunning = button?.dataset.running === 'true';

        stickyPauseButton.setAttribute('aria-label', isRunning ? 'Pause task' : 'Resume task');

        if (icon) {
            icon.innerHTML = isRunning ? pauseIcon : playIcon;
        }
    };

    const clearRunningTimer = () => {
        if (timerIntervalId) {
            window.clearInterval(timerIntervalId);
            timerIntervalId = null;
        }
    };

    const persistElapsed = (button) => {
        if (!button) {
            return;
        }

        if (button.dataset.running === 'true') {
            button.dataset.elapsedSeconds = String(getElapsedSeconds(button));
        }

        delete button.dataset.startedAt;
    };

    const applyTaskState = (button) => {
        const article = button.closest('[data-task-item]');
        const completeToggle = article?.querySelector('[data-task-complete-toggle]');
        const doneButton = article?.querySelector('[data-task-done-button]');
        const undoButton = article?.querySelector('[data-task-undo-button]');
        const icon = button.querySelector('[data-task-play-icon]');
        const label = button.querySelector('[data-task-play-label]');
        const isCompleted = article?.dataset.completed === 'true';
        const isUndoPending = article?.dataset.undoPending === 'true';
        const isRunning = button.dataset.running === 'true' && !isCompleted;
        const taskName = article?.querySelector('[data-task-name]')?.textContent.trim() || 'task';

        article?.setAttribute('data-running', isRunning ? 'true' : 'false');
        article?.classList.toggle('is-undo-pending', isUndoPending);
        button.classList.toggle('is-running', isRunning);
        button.classList.toggle('is-hidden', isCompleted || isUndoPending);
        completeToggle?.classList.toggle('is-hidden', isUndoPending);
        doneButton?.classList.toggle('is-hidden', isCompleted || isUndoPending);
        undoButton?.classList.toggle('is-hidden', !isUndoPending);

        if (doneButton) {
            doneButton.disabled = isCompleted || isUndoPending;
        }

        if (completeToggle) {
            completeToggle.setAttribute('aria-pressed', isCompleted ? 'true' : 'false');
        }

        button.setAttribute('aria-label', `${isRunning ? 'Pause' : 'Start'} ${taskName}`);

        if (icon) {
            icon.innerHTML = isRunning ? pauseIcon : playIcon;
        }

        if (label) {
            label.textContent = isRunning ? 'Pause' : 'Play';
        }

        refreshSelection();
        updateSummary();
    };

    const pauseTask = (button) => {
        if (!button) {
            return;
        }

        persistElapsed(button);
        button.dataset.running = 'false';
        button.setAttribute('aria-pressed', 'false');
        applyTaskState(button);

        if (activeButton === button) {
            activeButton = null;
            clearRunningTimer();
            syncStickyBar(button, Number(button.dataset.elapsedSeconds || '0'));
        }

        syncStickyAction(button);
    };

    const finalizeCompletedTask = (article) => {
        if (!article) {
            return;
        }

        article.dataset.undoPending = 'false';
        clearUndoTimer(article);

        const button = article.querySelector('[data-task-play-toggle]');
        if (button) {
            applyTaskState(button);
        }
    };

    const startTask = (button) => {
        const article = button.closest('[data-task-item]');

        if (article?.dataset.completed === 'true' || article?.dataset.undoPending === 'true') {
            return;
        }

        if (activeButton && activeButton !== button) {
            pauseTask(activeButton);
        }

        clearRunningTimer();

        button.dataset.running = 'true';
        button.dataset.startedAt = String(Date.now());
        button.setAttribute('aria-pressed', 'true');
        selectedTask = article || null;
        applyTaskState(button);

        activeButton = button;

        const renderTimer = () => {
            const liveElapsed = getElapsedSeconds(button);

            syncStickyBar(button, liveElapsed);
            renderTaskTime(button);
            updateSummary();
        };

        renderTimer();
        syncStickyAction(button);
        timerIntervalId = window.setInterval(renderTimer, 1000);
    };

    const undoTask = (article) => {
        if (!article) {
            return;
        }

        clearUndoTimer(article);
        article.dataset.completed = 'false';
        article.dataset.undoPending = 'false';
        selectedTask = article;

        const button = article.querySelector('[data-task-play-toggle]');
        if (button) {
            button.dataset.running = 'false';
            button.setAttribute('aria-pressed', 'false');
            applyTaskState(button);
        }

        syncStickyBar(activeButton, activeButton ? getElapsedSeconds(activeButton) : 0);
        syncStickyAction(activeButton);
    };

    const completeTask = (article) => {
        if (!article || article.dataset.completed === 'true') {
            return;
        }

        const button = article.querySelector('[data-task-play-toggle]');

        if (button?.dataset.running === 'true') {
            pauseTask(button);
        }

        article.dataset.completed = 'true';
        article.dataset.undoPending = 'true';
        article.dataset.selected = 'false';
        if (selectedTask === article) {
            selectedTask = null;
        }

        if (button) {
            button.dataset.running = 'false';
            button.setAttribute('aria-pressed', 'false');
            applyTaskState(button);
        }

        clearUndoTimer(article);
        undoTimers.set(article, window.setTimeout(() => {
            finalizeCompletedTask(article);
        }, undoDelayMs));

        updateSummary();
        syncStickyBar(activeButton, activeButton ? getElapsedSeconds(activeButton) : 0);
        syncStickyAction(activeButton);
    };

    taskItems.forEach((task) => {
        const button = task.querySelector('[data-task-play-toggle]');
        const completeToggle = task.querySelector('[data-task-complete-toggle]');
        const doneButton = task.querySelector('[data-task-done-button]');
        const actions = task.querySelector('.task-row__actions');

        if (!button) {
            return;
        }

        if (actions && !task.querySelector('[data-task-undo-button]')) {
            const undoButton = document.createElement('button');
            undoButton.type = 'button';
            undoButton.className = 'task-action-btn task-action-btn--undo is-hidden';
            undoButton.setAttribute('data-task-undo-button', '');
            undoButton.textContent = 'Undo';
            actions.appendChild(undoButton);
        }

        const undoButton = task.querySelector('[data-task-undo-button]');

        if (!button.dataset.elapsedSeconds) {
            button.dataset.elapsedSeconds = '0';
        }

        button.dataset.running = 'false';
        button.setAttribute('aria-pressed', 'false');
        task.dataset.undoPending = 'false';
        applyTaskState(button);

        button.addEventListener('click', () => {
            if (button.dataset.running === 'true') {
                pauseTask(button);
                return;
            }

            startTask(button);
        });

        completeToggle?.addEventListener('click', () => {
            completeTask(task);
        });

        doneButton?.addEventListener('click', () => {
            completeTask(task);
        });

        undoButton?.addEventListener('click', () => {
            undoTask(task);
        });
    });

    stickyPauseButton?.addEventListener('click', () => {
        if (!stickyDisplayButton) {
            return;
        }

        if (stickyDisplayButton.dataset.running === 'true') {
            pauseTask(stickyDisplayButton);
            return;
        }

        startTask(stickyDisplayButton);
    });

    refreshSelection();
    updateSummary();
    renderTimeline();
    syncStickyBar(null);
    syncStickyAction(null);
});