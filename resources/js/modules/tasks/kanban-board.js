import Sortable from "sortablejs";

document.addEventListener("DOMContentLoaded", () => {

    const container = document.getElementById('kanban-container');
    const buttons = document.querySelectorAll('.flow-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let currentFlow = localStorage.getItem('kanban_flow') || initialFlowType || 'agile';

    /** ================= FUNCTIONS ================= */

    const initKanbanDrag = () => {
        document.querySelectorAll(".kanban-board").forEach(board => {
            new Sortable(board, sortableOptions);
        });
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

        fetch(`/tasks/transition-status`, {
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
            });
    };

    const loadKanban = (flow) => {
        toggleLoading(container, true);

        fetch(buildKanbanUrl({ flow }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                toggleLoading(container, false);
                initKanbanDrag();
                initKanbanScroll();
            })
            .catch(() => {
                toggleLoading(container, false);
                Alert.error('Failed to load board');
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

    const rollback = (evt) => {
        if (evt.from) {
            evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
        }
    };

    const buildKanbanUrl = (extraParams = {}) => {
        const params = new URLSearchParams(window.location.search);

        Object.entries(extraParams).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                params.delete(key);
                return;
            }

            params.set(key, value);
        });

        return `/tasks/kanban?${params.toString()}`;
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
        onEnd: handleDrop,
    };

    /** ================= INIT ================= */
    setActiveButton(currentFlow);
    loadKanban(currentFlow);

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

});
