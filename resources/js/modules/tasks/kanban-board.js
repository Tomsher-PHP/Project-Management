import Sortable from "sortablejs";

document.addEventListener("DOMContentLoaded", () => {
    const boards = document.querySelectorAll(".kanban-board");

    boards.forEach((board) => {
        new Sortable(board, {
            group: {
                name: "kanban",
                pull: true,
                put: true,
            },
            animation: 180,
            easing: "cubic-bezier(0.2, 0, 0, 1)",

            ghostClass: "kanban-ghost",
            chosenClass: "kanban-chosen",
            dragClass: "kanban-drag",

            onEnd: handleDrop,
        });
    });

    function handleDrop(evt) {
        const toColumn = evt.to;
        const statusId = toColumn.dataset.statusId;

        // collect all task ids in this column (order matters)
        const taskIds = [...toColumn.querySelectorAll("[data-task-id]")]
            .map(el => el.dataset.taskId);

        const movedTaskId = evt.item.dataset.taskId;

        // optional: loading UI
        evt.item.classList.add("opacity-50");

        fetch(`/tasks/transition-status`, {
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                status_id: statusId,
                task_ids: taskIds,
                moved_task_id: movedTaskId,
            })
        })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => Promise.reject(err));
                }
                return res.json();
            })
            .then(data => {
                evt.item.classList.remove("opacity-50");

                if (!data.success) {
                    rollback(evt);
                    Alert.error(data.message);
                }
            })
            .catch(err => {
                evt.item.classList.remove("opacity-50");
                rollback(evt);
                Alert.error(err.message || "Something went wrong");
            });
    }

    // rollback if API fails
    function rollback(evt) {
        console.log('rech here');

        if (evt.from) {
            console.log('kkkkkkkkkkkkkkkkk');
            evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
        }
    }
});