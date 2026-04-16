import Sortable from "sortablejs";

document.addEventListener("DOMContentLoaded", () => {

    const container = document.getElementById('kanban-container');
    const buttons = document.querySelectorAll('.flow-btn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let currentFlow = localStorage.getItem('kanban_flow') || 'agile';

        /** ================= FUNCTIONS ================= */

    const initKanbanDrag = () => {
        document.querySelectorAll(".kanban-board").forEach(board => {
            new Sortable(board, sortableOptions);
        });
    };

    const handleDrop = (evt) => {
        const toColumn = evt.to;
        const statusId = toColumn.dataset.statusId;
        const movedTaskId = evt.item.dataset.taskId;

        const taskIds = [...toColumn.querySelectorAll("[data-task-id]")]
            .map(el => el.dataset.taskId);

        toggleLoading(evt.item, true);

        fetch(`/tasks/transition-status`, {
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                status_id: statusId,
                task_ids: taskIds,
                moved_task_id: movedTaskId,
            })
        })
            .then(handleFetchError)
            .then(() => toggleLoading(evt.item, false))
            .catch(err => {
                toggleLoading(evt.item, false);
                rollback(evt);
                Alert.error(err.message || "Something went wrong");
            });
    };

    const loadKanban = (flow) => {
        toggleLoading(container, true);

        fetch(`/tasks/kanban?flow=${flow}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                toggleLoading(container, false);
                initKanbanDrag(); // re-init
            })
            .catch(() => {
                toggleLoading(container, false);
                Alert.error('Failed to load board');
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