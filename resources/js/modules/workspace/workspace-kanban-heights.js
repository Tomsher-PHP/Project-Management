document.addEventListener('DOMContentLoaded', () => {
    const kanbanContainer = document.getElementById('kanban-container');

    if (!kanbanContainer) {
        return;
    }

    const emptyBoardHeight = 310;
    let frameId = null;
    let deferredTimerId = null;

    const getBoards = () => Array.from(kanbanContainer.querySelectorAll('.kanban-board'));

    const hasLoadingBoards = () => getBoards().some((board) => board.dataset.loading === 'true');

    const updateKanbanBoardHeights = () => {
        const boards = getBoards();
        let sharedBoardHeight = emptyBoardHeight;
        const scrollPositions = new Map();

        boards.forEach((board) => {
            scrollPositions.set(board, board.scrollTop);
        });

        boards.forEach((board) => {
            const cards = Array.from(board.querySelectorAll(':scope > .card[data-task-id]'));

            board.style.height = 'auto';
            board.style.minHeight = '0px';
            board.style.maxHeight = 'none';

            if (cards.length === 0) {
                return;
            }

            const boardStyle = window.getComputedStyle(board);
            const rowGap = parseFloat(boardStyle.rowGap || boardStyle.gap || '0');
            const paddingTop = parseFloat(boardStyle.paddingTop || '0');
            const paddingBottom = parseFloat(boardStyle.paddingBottom || '0');

            const visibleCards = cards.slice(0, 3);
            const visibleCardsHeight = visibleCards.reduce((total, card) => {
                return total + card.offsetHeight;
            }, 0);

            const visibleGapsHeight = rowGap * Math.max(visibleCards.length - 1, 0);

            const boardHeight = Math.ceil(
                visibleCardsHeight +
                visibleGapsHeight +
                paddingTop +
                paddingBottom
            );

            sharedBoardHeight = Math.max(sharedBoardHeight, boardHeight);
        });

        boards.forEach((board) => {
            board.style.minHeight = `${sharedBoardHeight}px`;
            board.style.maxHeight = `${sharedBoardHeight}px`;
            board.scrollTop = scrollPositions.get(board) ?? 0;
        });
    };

    const scheduleDeferredHeightUpdate = () => {
        if (deferredTimerId !== null) {
            window.clearTimeout(deferredTimerId);
        }

        deferredTimerId = window.setTimeout(() => {
            deferredTimerId = null;
            queueKanbanBoardHeightUpdate();
        }, 80);
    };

    const queueKanbanBoardHeightUpdate = () => {
        if (hasLoadingBoards()) {
            scheduleDeferredHeightUpdate();
            return;
        }

        if (frameId !== null) {
            window.cancelAnimationFrame(frameId);
        }

        frameId = window.requestAnimationFrame(() => {
            if (hasLoadingBoards()) {
                frameId = null;
                scheduleDeferredHeightUpdate();
                return;
            }

            updateKanbanBoardHeights();
            frameId = null;
        });
    };

    const observer = new MutationObserver(() => {
        queueKanbanBoardHeightUpdate();
    });

    observer.observe(kanbanContainer, {
        childList: true,
        subtree: true,
    });

    window.addEventListener('resize', queueKanbanBoardHeightUpdate);

    queueKanbanBoardHeightUpdate();
});
