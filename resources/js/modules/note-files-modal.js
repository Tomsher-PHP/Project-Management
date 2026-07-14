export const createNoteFilesModalController = ({
    modalSelector,
    openSelector,
    closeSelector,
}) => {
    let modal = null;
    let editor = null;
    let resetComposer = () => {};

    const close = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        resetComposer();
    };

    const open = () => {
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        window.setTimeout(() => {
            editor?.focus();
        }, 50);
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest(openSelector)) {
            open();
            return;
        }

        const closeButton = event.target.closest(closeSelector);

        if (closeButton && modal?.contains(closeButton)) {
            close();
            return;
        }

        if (event.target === modal) {
            close();
        }
    });

    return {
        initialize(root, currentEditor, currentResetComposer) {
            const currentModal = root?.querySelector?.(modalSelector);

            if (currentModal) {
                modal = currentModal;
            } else if (modal && !modal.isConnected) {
                modal = null;
            }

            editor = currentEditor;
            resetComposer = currentResetComposer;
        },
        close,
    };
};
