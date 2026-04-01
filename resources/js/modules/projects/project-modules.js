import Alert from '../../alert';

const initializeProjectModuleModal = () => {
    const modal = document.getElementById('project-module-modal');

    if (!modal || modal.dataset.projectModuleModalInitialized === 'true') {
        return;
    }

    const librarySelect = modal.querySelector('#library_module_id');
    const nameInput = modal.querySelector('[name="name"]');
    const colorInput = modal.querySelector('[name="color"]');
    const descriptionInput = modal.querySelector('[name="description"]');
    const descriptionCount = modal.querySelector('[data-project-module-description-count]');

    if (!librarySelect || !nameInput || !colorInput || !descriptionInput) {
        modal.dataset.projectModuleModalInitialized = 'true';
        return;
    }

    const updateDescriptionCount = () => {
        if (descriptionCount) {
            descriptionCount.textContent = String(descriptionInput.value.length);
        }
    };

    const fillFromLibraryOption = () => {
        const selectedOption = librarySelect.options[librarySelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            updateDescriptionCount();
            return;
        }

        nameInput.value = selectedOption.dataset.name || '';
        colorInput.value = selectedOption.dataset.color || '#000000';
        descriptionInput.value = selectedOption.dataset.description || '';
        updateDescriptionCount();
    };

    descriptionInput.addEventListener('input', updateDescriptionCount);
    librarySelect.addEventListener('change', fillFromLibraryOption);
    updateDescriptionCount();

    document.addEventListener('click', function (event) {
        const createButton = event.target.closest('.modal-open[data-module-context="project-module"]');
        const editButton = event.target.closest('.edit-record[data-module-context="project-module"]');

        if (!createButton && !editButton) {
            return;
        }

        window.setTimeout(() => {
            librarySelect.value = '';
            updateDescriptionCount();
        }, 0);
    });

    modal.dataset.projectModuleModalInitialized = 'true';
};

const initializeProjectModuleSection = (section = document.querySelector('[data-project-module-section]')) => {
    if (!section || section.dataset.projectModuleSectionInitialized === 'true') {
        return;
    }

    const moduleList = section.querySelector('[data-project-module-list]');
    const reorderToggleButton = section.querySelector('[data-project-module-reorder-toggle]');
    const reorderToggleLabel = section.querySelector('[data-project-module-reorder-toggle-label]');
    const reorderSaveButton = section.querySelector('[data-project-module-reorder-save]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let draggedModuleCard = null;
    let dragHandleCard = null;
    let reorderModeEnabled = false;
    let originalModuleOrder = [];

    if (!moduleList || !moduleList.dataset.reorderUrl || !csrfToken || !reorderToggleButton || !reorderSaveButton || !reorderToggleLabel) {
        section.dataset.projectModuleSectionInitialized = 'true';
        return;
    }

    const getModuleCards = () => Array.from(moduleList.querySelectorAll('[data-project-module-card]'));
    const getModuleIds = () => getModuleCards().map((card) => Number(card.dataset.moduleId));

    const applyModuleOrder = (moduleIds) => {
        const cardsById = new Map(getModuleCards().map((card) => [Number(card.dataset.moduleId), card]));

        moduleIds.forEach((moduleId) => {
            const card = cardsById.get(Number(moduleId));

            if (card) {
                moduleList.appendChild(card);
            }
        });
    };

    const setReorderMode = (enabled) => {
        reorderModeEnabled = enabled;
        reorderSaveButton.disabled = !enabled;
        reorderToggleLabel.textContent = enabled ? 'Cancel' : 'Change Order';

        getModuleCards().forEach((card) => {
            card.classList.toggle('ring-2', enabled);
            card.classList.toggle('ring-success-200', enabled);
            card.classList.toggle('dark:ring-success-900/30', enabled);
        });
    };

    const syncVisibleOrderBadges = () => {
        getModuleCards().forEach((card, index) => {
            const badge = card.querySelector('[data-project-module-order-badge]');

            if (badge) {
                badge.textContent = `Order ${index + 1}`;
            }
        });
    };

    const persistModuleOrder = async () => {
        const moduleIds = getModuleIds();

        try {
            const response = await fetch(moduleList.dataset.reorderUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ module_ids: moduleIds }),
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                throw new Error(result.message || 'Unable to save the new module order.');
            }

            syncVisibleOrderBadges();
            originalModuleOrder = moduleIds;
            setReorderMode(false);
            Alert.success(result.message || 'Module order updated successfully.');
        } catch (error) {
            Alert.error(error.message || 'Unable to save the new module order.');
            window.location.reload();
        }
    };

    const resetDraggedCardState = () => {
        if (draggedModuleCard) {
            draggedModuleCard.classList.remove('opacity-60', 'scale-[0.99]');
            draggedModuleCard.setAttribute('draggable', 'false');
        }

        if (dragHandleCard) {
            dragHandleCard.setAttribute('draggable', 'false');
        }

        draggedModuleCard = null;
        dragHandleCard = null;
    };

    moduleList.addEventListener('mousedown', function (event) {
        if (!reorderModeEnabled) {
            return;
        }

        const handle = event.target.closest('[data-project-module-drag-handle]');

        if (!handle) {
            return;
        }

        const card = handle.closest('[data-project-module-card]');

        if (!card) {
            return;
        }

        dragHandleCard = card;
        card.setAttribute('draggable', 'true');
    });

    moduleList.addEventListener('mouseup', function () {
        if (!draggedModuleCard && dragHandleCard) {
            dragHandleCard.setAttribute('draggable', 'false');
            dragHandleCard = null;
        }
    });

    moduleList.addEventListener('mouseleave', function () {
        if (!draggedModuleCard && dragHandleCard) {
            dragHandleCard.setAttribute('draggable', 'false');
            dragHandleCard = null;
        }
    });

    moduleList.addEventListener('dragstart', function (event) {
        const card = event.target.closest('[data-project-module-card]');

        if (!reorderModeEnabled || !card || card !== dragHandleCard) {
            event.preventDefault();
            return;
        }

        draggedModuleCard = card;
        card.classList.add('opacity-60', 'scale-[0.99]');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.moduleId || '');
        }
    });

    moduleList.addEventListener('dragover', function (event) {
        const targetCard = event.target.closest('[data-project-module-card]');

        if (!draggedModuleCard || !targetCard || targetCard === draggedModuleCard) {
            return;
        }

        event.preventDefault();

        const targetBounds = targetCard.getBoundingClientRect();
        const insertAfterTarget = event.clientY > targetBounds.top + (targetBounds.height / 2);

        if (insertAfterTarget) {
            moduleList.insertBefore(draggedModuleCard, targetCard.nextElementSibling);
            return;
        }

        moduleList.insertBefore(draggedModuleCard, targetCard);
    });

    moduleList.addEventListener('drop', function (event) {
        if (!draggedModuleCard) {
            return;
        }

        event.preventDefault();
    });

    moduleList.addEventListener('dragend', function () {
        resetDraggedCardState();
    });

    reorderToggleButton.addEventListener('click', function () {
        if (!reorderModeEnabled) {
            originalModuleOrder = getModuleIds();
            setReorderMode(true);
            return;
        }

        applyModuleOrder(originalModuleOrder);
        syncVisibleOrderBadges();
        resetDraggedCardState();
        setReorderMode(false);
    });

    reorderSaveButton.addEventListener('click', function () {
        if (!reorderModeEnabled) {
            return;
        }

        persistModuleOrder();
    });

    section.dataset.projectModuleSectionInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', function () {
    initializeProjectModuleModal();
    initializeProjectModuleSection();
});

document.addEventListener('ajax-form:rendered', function (event) {
    if (event.detail?.selector !== '[data-project-module-section]') {
        return;
    }

    initializeProjectModuleSection(event.detail.root);
});
