import Sortable from "sortablejs";
import '../task-insights-modal';
import '../task-comments';

const CONTROLLER_KEY = '__workspaceKanbanBoardController';

document.addEventListener("DOMContentLoaded", () => {

    const container = document.getElementById('kanban-container');
    const buttons = document.querySelectorAll('.flow-btn');
    const sortDropdown = document.querySelector('[data-kanban-sort-dropdown]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const kanbanEndpoint = container?.dataset.kanbanUrl || '/tasks/kanban';
    const shouldRefreshFlowCounts = Boolean(document.querySelector('[data-workspace-kanban-flow-counts]'));
    const flowBadgeContainers = new Map(
        [...buttons].map((button) => [button.dataset.flow, button.parentElement])
    );
    let isKanbanLoading = false;
    let isDragInProgress = false;

    let currentFlow = localStorage.getItem('kanban_flow') || initialFlowType || 'agile';
    let currentSort = sortDropdown?.dataset.selectedSort || new URLSearchParams(window.location.search).get('sort') || '';
    const defaultSortLabel = sortDropdown?.querySelector('[data-kanban-sort-label]')?.textContent?.trim() || 'Sort Tasks';

    let activeBoardAbortController = null;
    const activeColumnAbortControllers = new Map();
    let currentBoardToken = 0;

    /** ================= FUNCTIONS ================= */

    const abortAllKanbanRequests = () => {
        if (activeBoardAbortController) {
            activeBoardAbortController.abort();
            activeBoardAbortController = null;
        }

        for (const controller of activeColumnAbortControllers.values()) {
            controller.abort();
        }
        activeColumnAbortControllers.clear();
    };

    const initKanbanDrag = () => {
        document.querySelectorAll(".kanban-board").forEach(board => {
            new Sortable(board, sortableOptions);
        });
    };

    const syncAutoStoppedTimer = (payload = null, navbarTimer = null) => {
        const taskId = payload?.task_id ? String(payload.task_id) : '';

        if (!taskId) {
            return;
        }

        document.dispatchEvent(new CustomEvent('task-timer:stopped-remotely', {
            detail: {
                taskId,
                totalSeconds: payload?.total_seconds ?? 0,
            },
        }));
        document.dispatchEvent(new CustomEvent('task-timer:refresh'));

        const navbarState = window.navbarRunningTaskTimer?.getState?.();

        if (String(navbarState?.taskId || '') === taskId) {
            if (navbarTimer?.active === true || navbarTimer?.active === '1' || navbarTimer?.shouldShowTimer === true) {
                document.dispatchEvent(new CustomEvent('navbar-running-task-timer:update', {
                    detail: navbarTimer,
                }));
            } else {
                document.dispatchEvent(new CustomEvent('navbar-running-task-timer:hide'));
            }
        }
    };

    const updateNewTaskBadge = (flow, delta) => {
        const badgeContainer = flowBadgeContainers.get(flow);

        if (!badgeContainer || !delta) {
            return;
        }

        let badge = badgeContainer.querySelector('span');
        const nextCount = Math.max(Number(badge?.textContent?.trim() || 0) + delta, 0);

        if (nextCount === 0) {
            if (badge?.matches('[data-flow-count]')) {
                badge.textContent = '0';
                badge.classList.add('hidden');
            } else {
                badge?.remove();
            }
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'pointer-events-none absolute -right-1 -top-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white z-10';
            badgeContainer.appendChild(badge);
        }

        badge.textContent = String(nextCount);
        badge.classList.remove('hidden');
    };

    const setNewTaskBadge = (flow, count) => {
        const badge = document.querySelector(`[data-flow-count="${flow}"]`);

        if (!badge) {
            return;
        }

        const nextCount = Math.max(Number(count || 0), 0);
        badge.textContent = String(nextCount);
        badge.classList.toggle('hidden', nextCount === 0);
    };

    const initKanbanScroll = () => {
        document.querySelectorAll(".kanban-board").forEach(board => {
            board.addEventListener("scroll", () => {
                maybeLoadMore(board);
            });

            maybeLoadMore(board);
        });
    };

    const handleDrop = (evt) => {
        const fromColumn = evt.from;
        const toColumn = evt.to;
        const statusId = toColumn.dataset.statusId;
        const movedTaskId = String(evt.item.dataset.taskId);
        const movedBetweenStatuses = fromColumn !== toColumn;
        const movedFromDefault = fromColumn.dataset.isDefault === '1';
        const movedToDefault = toColumn.dataset.isDefault === '1';
        const taskFlow = currentFlow;
        const requestToken = currentBoardToken;

        const previousFromTaskIds = [...getBoardTaskIds(fromColumn)];
        const previousToTaskIds = fromColumn === toColumn
            ? previousFromTaskIds
            : [...getBoardTaskIds(toColumn)];
        const nextFromTaskIds = buildBoardTaskIds(fromColumn, previousFromTaskIds);
        const nextToTaskIds = buildBoardTaskIds(toColumn, previousToTaskIds);

        setBoardTaskIds(fromColumn, nextFromTaskIds);
        setBoardTaskIds(toColumn, nextToTaskIds);

        if (fromColumn !== toColumn) {
            updateColumnCount(fromColumn, -1);
            updateColumnCount(toColumn, 1);
        }

        toggleLoading(evt.item, true);
        setDragState(true);

        return fetch(`/tasks/transition-status`, {
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                status_id: statusId,
                task_ids: nextToTaskIds,
                moved_task_id: movedTaskId,
            })
        })
            .then(handleFetchError)
            .then((response) => {
                if (requestToken !== currentBoardToken || taskFlow !== currentFlow) {
                    return;
                }

                syncAutoStoppedTimer(response.timer_stopped, response.navbar_timer);

                if (movedBetweenStatuses && movedFromDefault !== movedToDefault) {
                    updateNewTaskBadge(taskFlow, movedToDefault ? 1 : -1);
                }

                document.dispatchEvent(new CustomEvent('task-status:changed', {
                    detail: {
                        taskId: movedTaskId,
                        statusId: Number(statusId || 0),
                        statusType: response.status_type,
                        response,
                    },
                }));
                replaceMovedCard(evt.item, response.html);
            })
            .catch(err => {
                if (requestToken !== currentBoardToken || taskFlow !== currentFlow) {
                    return;
                }

                setBoardTaskIds(fromColumn, previousFromTaskIds);
                setBoardTaskIds(toColumn, previousToTaskIds);

                if (fromColumn !== toColumn) {
                    updateColumnCount(fromColumn, 1);
                    updateColumnCount(toColumn, -1);
                }

                toggleLoading(evt.item, false);
                rollback(evt);
                Alert.error(err.message || "Something went wrong");
            })
            .finally(() => {
                setDragState(false);
            });
    };

    const loadKanban = (flow) => {
        if (!container) {
            return Promise.resolve(false);
        }

        abortAllKanbanRequests();
        currentBoardToken += 1;
        const requestToken = currentBoardToken;
        const requestFlow = flow;

        activeBoardAbortController = new AbortController();

        isKanbanLoading = true;
        container.dataset.loading = 'true';
        toggleLoading(container, true);

        return fetch(buildKanbanUrl({ flow }), {
            signal: activeBoardAbortController.signal,
            headers: {
                Accept: shouldRefreshFlowCounts ? 'application/json' : 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
            .then(res => shouldRefreshFlowCounts ? handleFetchError(res) : res.text())
            .then(response => {
                if (requestToken !== currentBoardToken || requestFlow !== currentFlow) {
                    return false;
                }

                container.innerHTML = shouldRefreshFlowCounts ? response.html : response;

                if (shouldRefreshFlowCounts && response.flowCounts) {
                    Object.entries(response.flowCounts).forEach(([flowKey, count]) => {
                        setNewTaskBadge(flowKey, count);
                    });
                }

                initKanbanDrag();
                initKanbanScroll();
                document.dispatchEvent(new CustomEvent('workspace:kanban-refreshed'));
                return true;
            })
            .catch((err) => {
                if (err?.name === 'AbortError' || requestToken !== currentBoardToken || requestFlow !== currentFlow) {
                    return false;
                }
                Alert.error('Failed to load board');
                return false;
            })
            .finally(() => {
                if (requestToken === currentBoardToken) {
                    isKanbanLoading = false;
                    container.dataset.loading = 'false';
                    toggleLoading(container, false);
                }
            });
    };

    const loadMoreStatusTasks = (board) => {
        if (
            !board ||
            board.dataset.hasMore !== 'true' ||
            board.dataset.loading === 'true' ||
            board.dataset.loadFailed === 'true'
        ) {
            return;
        }

        const statusId = board.dataset.statusId;
        if (!statusId) {
            return;
        }

        const requestToken = currentBoardToken;
        const requestFlow = currentFlow;

        if (activeColumnAbortControllers.has(statusId)) {
            activeColumnAbortControllers.get(statusId).abort();
        }

        const columnController = new AbortController();
        activeColumnAbortControllers.set(statusId, columnController);

        board.dataset.loading = 'true';
        toggleLoadIndicator(board, true);

        fetch(buildKanbanUrl({
            flow: currentFlow,
            status_id: statusId,
            page: board.dataset.nextPage || 1,
        }), {
            signal: columnController.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
            .then(handleFetchError)
            .then((response) => {
                if (requestToken !== currentBoardToken || requestFlow !== currentFlow || !document.body.contains(board)) {
                    return;
                }

                appendCards(board, response.html);
                board.dataset.hasMore = response.hasMore ? 'true' : 'false';
                board.dataset.nextPage = response.nextPage ?? '';
                board.dataset.loadFailed = 'false';

                if (Array.isArray(response.taskIds)) {
                    setBoardTaskIds(board, response.taskIds);
                }
            })
            .catch((err) => {
                if (err?.name === 'AbortError' || requestToken !== currentBoardToken || requestFlow !== currentFlow || !document.body.contains(board)) {
                    return;
                }

                board.dataset.loadFailed = 'true';
                board.dataset.hasMore = 'false';
                Alert.error(err?.message || 'Failed to load more tasks');
            })
            .finally(() => {
                activeColumnAbortControllers.delete(statusId);

                if (requestToken === currentBoardToken && requestFlow === currentFlow && document.body.contains(board)) {
                    board.dataset.loading = 'false';
                    toggleLoadIndicator(board, false);

                    if (board.dataset.loadFailed !== 'true' && board.dataset.hasMore === 'true') {
                        maybeLoadMore(board);
                    }
                }
            });
    };

    const setActiveButton = (flow) => {
        buttons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.flow === flow);
        });
    };

    const toggleLoading = (el, state) => {
        el.classList.toggle('opacity-50', state);
    };

    const setDragState = (state) => {
        isDragInProgress = state;

        if (container) {
            container.dataset.dragging = state ? 'true' : 'false';
        }
    };

    const rollback = (evt) => {
        if (evt.from) {
            evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
        }
    };

    const buildKanbanUrl = (extraParams = {}) => {
        const url = new URL(kanbanEndpoint, window.location.origin);
        const params = new URLSearchParams(window.location.search);

        params.set('kanban', '1');
        params.delete('status_id');
        params.delete('page');

        if (currentSort) {
            params.set('sort', currentSort);
        } else {
            params.delete('sort');
        }

        Object.entries(extraParams).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                params.delete(key);
                return;
            }

            params.set(key, value);
        });

        url.search = params.toString();

        return url.toString();
    };

    const closeSortMenus = (exceptDropdown = null) => {
        document.querySelectorAll('[data-kanban-sort-dropdown]').forEach((dropdown) => {
            if (exceptDropdown && dropdown === exceptDropdown) {
                return;
            }

            dropdown.querySelector('[data-kanban-sort-menu]')?.classList.add('hidden');
            dropdown.querySelector('[data-kanban-sort-trigger]')?.setAttribute('aria-expanded', 'false');
        });
    };

    const syncSortDropdown = () => {
        if (!sortDropdown) {
            return;
        }

        const activeOption = sortDropdown.querySelector(`[data-kanban-sort-option][data-value="${currentSort}"]`);
        const label = activeOption?.dataset.label || defaultSortLabel;

        sortDropdown.dataset.selectedSort = currentSort;
        sortDropdown.querySelector('[data-kanban-sort-label]').textContent = label;

        sortDropdown.querySelectorAll('[data-kanban-sort-option]').forEach((option) => {
            option.querySelector('span')?.classList.toggle('text-success-400', option.dataset.value === currentSort);
            option.querySelector('span')?.classList.toggle('dark:text-success-300', option.dataset.value === currentSort);
        });
    };

    const updateBrowserSortState = () => {
        const url = new URL(window.location.href);

        if (currentSort) {
            url.searchParams.set('sort', currentSort);
        } else {
            url.searchParams.delete('sort');
        }

        window.history.replaceState({}, '', url);
    };

    const getBoardTaskIds = (board) => {
        if (!board?.dataset.taskIds) {
            return [];
        }

        try {
            const taskIds = JSON.parse(board.dataset.taskIds);
            return Array.isArray(taskIds) ? taskIds.map(String) : [];
        } catch (error) {
            return [];
        }
    };

    const setBoardTaskIds = (board, taskIds) => {
        if (!board) {
            return;
        }

        board.dataset.taskIds = JSON.stringify(taskIds.map(String));
    };

    const getVisibleTaskIds = (board) => {
        return [...board.querySelectorAll("[data-task-id]")]
            .map((el) => String(el.dataset.taskId));
    };

    const buildBoardTaskIds = (board, previousTaskIds = []) => {
        const visibleTaskIds = getVisibleTaskIds(board);
        const hiddenTaskIds = previousTaskIds
            .map(String)
            .filter((taskId) => !visibleTaskIds.includes(taskId));

        return [...visibleTaskIds, ...hiddenTaskIds];
    };

    const appendCards = (board, html) => {
        if (!html) {
            return;
        }

        const indicator = board.querySelector('[data-kanban-load-indicator]');

        if (indicator) {
            indicator.insertAdjacentHTML('beforebegin', html);
            return;
        }

        board.insertAdjacentHTML('beforeend', html);
    };

    const toggleLoadIndicator = (board, state) => {
        board.querySelector('[data-kanban-load-indicator]')?.classList.toggle('hidden', !state);
    };

    const maybeLoadMore = (board) => {
        if (
            !board ||
            board.dataset.hasMore !== 'true' ||
            board.dataset.loading === 'true' ||
            board.dataset.loadFailed === 'true' ||
            !document.body.contains(board)
        ) {
            return;
        }

        const threshold = 120;
        const isNearBottom = board.scrollTop + board.clientHeight >= board.scrollHeight - threshold;
        const isUnderfilled = board.scrollHeight <= board.clientHeight + threshold;

        if (isNearBottom || isUnderfilled) {
            loadMoreStatusTasks(board);
        }
    };

    const updateColumnCount = (board, delta) => {
        const countNode = board.closest('.flex-shrink-0')?.querySelector('[data-kanban-total-count]');

        if (!countNode) {
            return;
        }

        const nextCount = Math.max(Number(countNode.textContent || 0) + delta, 0);
        countNode.textContent = String(nextCount);
    };

    const replaceMovedCard = (item, html) => {
        if (!html) {
            toggleLoading(item, false);
            return;
        }

        const template = document.createElement("template");
        template.innerHTML = html.trim();

        const nextItem = template.content.firstElementChild;

        if (!nextItem) {
            toggleLoading(item, false);
            return;
        }

        item.replaceWith(nextItem);
    };

    const handleFetchError = (res) => {
        if (!res.ok) {
            return res.json().then(err => Promise.reject(err));
        }
        return res.json();
    };

    /** ================= COMMON SORTABLE CONFIG ================= */
    const sortableOptions = {
        group: { name: "kanban", pull: true, put: true },
        animation: 180,
        easing: "cubic-bezier(0.2, 0, 0, 1)",
        ghostClass: "kanban-ghost",
        chosenClass: "kanban-chosen",
        dragClass: "kanban-drag",
        onStart: () => {
            setDragState(true);
        },
        onEnd: handleDrop,
    };

    const createKanbanController = () => ({
        reload: () => {
            if (!container || isKanbanLoading || isDragInProgress) {
                return Promise.resolve(false);
            }

            return loadKanban(currentFlow);
        },
        isBusy: () => {
            if (!container) {
                return false;
            }

            return isKanbanLoading
                || isDragInProgress
                || container.dataset.loading === 'true'
                || container.dataset.dragging === 'true'
                || [...document.querySelectorAll('.kanban-board')].some((board) => board.dataset.loading === 'true');
        },
    });

    /** ================= INIT ================= */
    if (container) {
        container.dataset.loading = 'false';
        container.dataset.dragging = 'false';
    }

    syncSortDropdown();
    setActiveButton(currentFlow);
    loadKanban(currentFlow);
    window[CONTROLLER_KEY] = createKanbanController();

    /** ================= EVENTS ================= */
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const flow = btn.dataset.flow;
            if (flow === currentFlow) return;

            currentFlow = flow;
            localStorage.setItem('kanban_flow', flow);

            setActiveButton(flow);
            loadKanban(flow);
        });
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-kanban-sort-trigger]');

        if (trigger) {
            const dropdown = trigger.closest('[data-kanban-sort-dropdown]');
            const menu = dropdown?.querySelector('[data-kanban-sort-menu]');

            if (!dropdown || !menu) {
                return;
            }

            const shouldOpen = menu.classList.contains('hidden');
            closeSortMenus(dropdown);
            menu.classList.toggle('hidden', !shouldOpen);
            trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            return;
        }

        const option = event.target.closest('[data-kanban-sort-option]');

        if (option) {
            const nextSort = option.dataset.value || '';

            closeSortMenus();

            if (nextSort === currentSort || isKanbanLoading || isDragInProgress) {
                return;
            }

            const previousSort = currentSort;
            currentSort = nextSort;
            syncSortDropdown();
            updateBrowserSortState();

            loadKanban(currentFlow).then((success) => {
                if (success) {
                    return;
                }

                currentSort = previousSort;
                syncSortDropdown();
                updateBrowserSortState();
            });

            return;
        }

        if (!event.target.closest('[data-kanban-sort-dropdown]')) {
            closeSortMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSortMenus();
        }
    });

});
