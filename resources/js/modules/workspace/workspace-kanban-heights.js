document.addEventListener('DOMContentLoaded', () => {
    const kanbanContainer = document.getElementById('kanban-container');

    if (!kanbanContainer) {
        return;
    }

    const emptyBoardHeight = 310;
    let frameId = null;

    const updateKanbanBoardHeights = () => {
        const boards = Array.from(kanbanContainer.querySelectorAll('.kanban-board'));
        let sharedBoardHeight = emptyBoardHeight;

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
        });
    };

    const queueKanbanBoardHeightUpdate = () => {
        if (frameId !== null) {
            window.cancelAnimationFrame(frameId);
        }

        frameId = window.requestAnimationFrame(() => {
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