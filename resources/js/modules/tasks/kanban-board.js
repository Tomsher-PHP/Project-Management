import Sortable from "sortablejs";

const CONTROLLER_KEY = '__workspaceKanbanBoardController';

document.addEventListener("DOMContentLoaded", () => {

    const container = document.getElementById('kanban-container');
    const buttons = document.querySelectorAll('.flow-btn');
    const sortDropdown = document.querySelector('[data-kanban-sort-dropdown]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const kanbanEndpoint = container?.dataset.kanbanUrl || '/tasks/kanban';
    let isKanbanLoading = false;
    let isDragInProgress = false;

    let currentFlow = localStorage.getItem('kanban_flow') || initialFlowType || 'agile';
    let currentSort = sortDropdown?.dataset.selectedSort || new URLSearchParams(window.location.search).get('sort') || '';
    const defaultSortLabel = sortDropdown?.querySelector('[data-kanban-sort-label]')?.textContent?.trim() || 'Sort Tasks';

    /** ================= FUNCTIONS ================= */

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
                syncAutoStoppedTimer(response.timer_stopped, response.navbar_timer);
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

        isKanbanLoading = true;
        container.dataset.loading = 'true';
        toggleLoading(container, true);

        return fetch(buildKanbanUrl({ flow }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                initKanbanDrag();
                initKanbanScroll();
                return true;
            })
            .catch(() => {
                Alert.error('Failed to load board');
                return false;
            })
            .finally(() => {
                isKanbanLoading = false;
                container.dataset.loading = 'false';
                toggleLoading(container, false);
            });
    };

    const loadMoreStatusTasks = (board) => {
        if (!board || board.dataset.hasMore !== 'true' || board.dataset.loading === 'true') {
            return;
        }

        board.dataset.loading = 'true';
        toggleLoadIndicator(board, true);

        fetch(buildKanbanUrl({
            flow: currentFlow,
            status_id: board.dataset.statusId,
            page: board.dataset.nextPage || 1,
        }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
            .then(handleFetchError)
            .then((response) => {
                appendCards(board, response.html);
                board.dataset.hasMore = response.hasMore ? 'true' : 'false';
                board.dataset.nextPage = response.nextPage ?? '';

                if (Array.isArray(response.taskIds)) {
                    setBoardTaskIds(board, response.taskIds);
                }
            })
            .catch((err) => {
                Alert.error(err.message || 'Failed to load more tasks');
            })
            .finally(() => {
                board.dataset.loading = 'false';
                toggleLoadIndicator(board, false);
                maybeLoadMore(board);
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
        if (!board || board.dataset.hasMore !== 'true' || board.dataset.loading === 'true') {
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
