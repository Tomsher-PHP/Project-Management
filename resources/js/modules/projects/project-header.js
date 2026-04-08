import Alert from '../../alert';
import { initDatepicker } from '../../components/datepicker';

const getProjectHeaderStorageKey = (projectId) => `project-header-expanded:${projectId}`;

const getProjectChangeModal = () => document.getElementById('project-change-modal');

const parseJsonResponse = async (response) => {
    try {
        return await response.json();
    } catch (error) {
        return {};
    }
};

const updateProjectChangeRemarksCount = (modal) => {
    if (!modal) {
        return;
    }

    const remarksField = modal.querySelector('[name="remarks"]');
    const counter = modal.querySelector('[data-project-change-remarks-count]');

    if (!remarksField || !counter) {
        return;
    }

    counter.textContent = String(remarksField.value.length);
};

const clearProjectChangeErrors = (form) => {
    if (!form) {
        return;
    }

    form.querySelectorAll('[data-project-change-error-for]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, textarea').forEach((field) => {
        field.classList.remove('border-red-500');
    });
};

const applyProjectChangeErrors = (form, errors = {}) => {
    clearProjectChangeErrors(form);

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const normalizedFieldName = fieldName.split('.')[0];
        const field = form.querySelector(`[name="${normalizedFieldName}"]`);
        const errorNode = form.querySelector(`[data-project-change-error-for="${normalizedFieldName}"]`);

        field?.classList.add('border-red-500');

        if (errorNode) {
            errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
            errorNode.classList.remove('hidden');
        }
    });
};

const setProjectChangeDateValue = (input, value) => {
    if (!input) {
        return;
    }

    if (input._flatpickr) {
        input._flatpickr.setDate(value || '', true, 'Y-m-d');
        return;
    }

    input.value = value || '';
};

const resetProjectChangeModal = (modal) => {
    if (!modal) {
        return;
    }

    const form = modal.querySelector('[data-project-change-form]');
    const hiddenInput = modal.querySelector('[data-project-change-value]');
    const title = modal.querySelector('[data-project-change-title]');
    const description = modal.querySelector('[data-project-change-description]');
    const selectedName = modal.querySelector('[data-project-change-selected-name]');
    const selectedColor = modal.querySelector('[data-project-change-selected-color]');
    const submitButton = modal.querySelector('[data-project-change-submit]');
    const dateField = modal.querySelector('[name="change_date"]');
    const remarksField = modal.querySelector('[name="remarks"]');

    clearProjectChangeErrors(form);

    if (form) {
        form.reset();
        form.dataset.actionUrl = '';
        form.dataset.fieldName = '';
        form.dataset.submitLabel = '';
    }

    if (hiddenInput) {
        hiddenInput.name = '';
        hiddenInput.value = '';
    }

    if (title) {
        title.textContent = 'Change Project Value';
    }

    if (description) {
        description.textContent = 'Select an option to continue.';
    }

    if (selectedName) {
        selectedName.textContent = 'No Option Selected';
    }

    if (selectedColor) {
        selectedColor.style.backgroundColor = '#9CA3AF';
    }

    setProjectChangeDateValue(dateField, modal.dataset.defaultDate || '');

    if (remarksField) {
        remarksField.value = '';
    }

    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Update';
    }

    updateProjectChangeRemarksCount(modal);
};

const closeProjectChangeModal = () => {
    const modal = getProjectChangeModal();

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    resetProjectChangeModal(modal);
};

const openProjectChangeModal = (option) => {
    const modal = getProjectChangeModal();

    if (!modal || !option) {
        return;
    }

    const form = modal.querySelector('[data-project-change-form]');
    const hiddenInput = modal.querySelector('[data-project-change-value]');
    const title = modal.querySelector('[data-project-change-title]');
    const description = modal.querySelector('[data-project-change-description]');
    const selectedName = modal.querySelector('[data-project-change-selected-name]');
    const selectedColor = modal.querySelector('[data-project-change-selected-color]');
    const submitButton = modal.querySelector('[data-project-change-submit]');
    const dateField = modal.querySelector('[name="change_date"]');
    const remarksField = modal.querySelector('[name="remarks"]');

    resetProjectChangeModal(modal);

    if (!form || !hiddenInput) {
        return;
    }

    form.dataset.actionUrl = option.dataset.url || '';
    form.dataset.fieldName = option.dataset.field || '';
    form.dataset.submitLabel = option.dataset.submitLabel || 'Update';
    hiddenInput.name = option.dataset.field || '';
    hiddenInput.value = option.dataset.value ?? '';

    if (title) {
        title.textContent = option.dataset.modalTitle || 'Change Project Value';
    }

    if (description) {
        description.textContent = option.dataset.modalDescription || 'Select an option to continue.';
    }

    if (selectedName) {
        selectedName.textContent = option.dataset.itemName || 'Selected Option';
    }

    if (selectedColor) {
        selectedColor.style.backgroundColor = option.dataset.itemColor || '#9CA3AF';
    }

    if (submitButton) {
        submitButton.textContent = form.dataset.submitLabel;
    }

    setProjectChangeDateValue(dateField, modal.dataset.defaultDate || '');

    if (remarksField) {
        remarksField.value = '';
    }

    updateProjectChangeRemarksCount(modal);
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    window.setTimeout(() => {
        dateField?.focus();
    }, 50);
};

const applyProjectHeaderExpandedState = (headerCard, isExpanded) => {
    if (!headerCard) {
        return;
    }

    const expandable = headerCard.querySelector('[data-project-header-expandable]');
    const toggle = headerCard.querySelector('[data-project-header-collapse-toggle]');
    const icon = headerCard.querySelector('[data-project-header-collapse-icon]');

    if (expandable) {
        expandable.classList.toggle('hidden', !isExpanded);
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        toggle.setAttribute('aria-label', isExpanded ? 'Collapse project header details' : 'Expand project header details');
    }

    if (icon) {
        icon.style.transform = isExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
    }
};

const syncProjectHeaderExpandedState = (headerRoot = document.getElementById('project-header')) => {
    const headerCard = headerRoot?.querySelector('[data-project-header-card]');

    if (!headerCard) {
        return;
    }

    const projectId = headerCard.dataset.projectId;
    const savedState = projectId ? window.localStorage.getItem(getProjectHeaderStorageKey(projectId)) : null;
    const isExpanded = savedState === 'true';

    applyProjectHeaderExpandedState(headerCard, isExpanded);
};

const initializeProjectHeader = () => {
    if (document.body.dataset.projectHeaderInitialized === 'true') {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const modal = getProjectChangeModal();

    if (modal) {
        initDatepicker('.datepicker', {}, modal);
        resetProjectChangeModal(modal);
    }

    const closeAllMenus = (exceptDropdown = null) => {
        document.querySelectorAll('[data-project-header-dropdown]').forEach((dropdown) => {
            if (exceptDropdown && dropdown === exceptDropdown) {
                return;
            }

            dropdown.querySelector('[data-project-header-menu]')?.classList.add('hidden');
        });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-project-header-trigger]');

        if (trigger) {
            const dropdown = trigger.closest('[data-project-header-dropdown]');
            const menu = dropdown?.querySelector('[data-project-header-menu]');

            if (!dropdown || !menu) {
                return;
            }

            const shouldOpen = menu.classList.contains('hidden');
            closeAllMenus(dropdown);
            menu.classList.toggle('hidden', !shouldOpen);
            return;
        }

        const collapseToggle = event.target.closest('[data-project-header-collapse-toggle]');

        if (collapseToggle) {
            const headerCard = collapseToggle.closest('[data-project-header-card]');

            if (!headerCard) {
                return;
            }

            const projectId = headerCard.dataset.projectId;
            const isExpanded = collapseToggle.getAttribute('aria-expanded') === 'true';
            const nextState = !isExpanded;

            applyProjectHeaderExpandedState(headerCard, nextState);

            if (projectId) {
                window.localStorage.setItem(getProjectHeaderStorageKey(projectId), nextState ? 'true' : 'false');
            }

            return;
        }

        const changeOption = event.target.closest('[data-project-change-option]');

        if (changeOption) {
            event.preventDefault();
            closeAllMenus();

            const field = changeOption.dataset.field;
            const url = changeOption.dataset.url;
            const value = changeOption.dataset.value ?? '';
            const currentValue = changeOption.dataset.currentValue ?? '';

            if (!field || !url || value === currentValue) {
                return;
            }

            openProjectChangeModal(changeOption);
            return;
        }

        const modalCloseTrigger = event.target.closest('[data-project-change-modal-close]');

        if (modalCloseTrigger) {
            event.preventDefault();
            closeProjectChangeModal();
            return;
        }

        if (!event.target.closest('[data-project-header-dropdown]')) {
            closeAllMenus();
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('#project_change_remarks')) {
            updateProjectChangeRemarksCount(getProjectChangeModal());
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (!getProjectChangeModal()?.classList.contains('hidden')) {
                closeProjectChangeModal();
                return;
            }

            closeAllMenus();
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-project-change-form]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const modalElement = form.closest('#project-change-modal');
        const submitButton = form.querySelector('[data-project-change-submit]');
        const actionUrl = form.dataset.actionUrl || '';
        const fieldName = form.dataset.fieldName || '';
        const submitLabel = form.dataset.submitLabel || 'Update';

        clearProjectChangeErrors(form);

        if (!actionUrl || !fieldName) {
            Alert.error('Unable to update this project value.');
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Updating...';
        }

        try {
            const formData = new FormData(form);
            formData.set('_method', 'PATCH');

            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });
            const result = await parseJsonResponse(response);

            if (response.status === 422 && result.errors) {
                applyProjectChangeErrors(form, result.errors);
                throw new Error(result.message || 'Please correct the highlighted fields.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to update this project value.');
            }

            const header = document.getElementById('project-header');

            if (header && result.project_header) {
                header.innerHTML = result.project_header;
                syncProjectHeaderExpandedState(header);
            }

            closeProjectChangeModal();
            Alert.success(result.message || 'Project updated successfully.');
        } catch (error) {
            Alert.error(error.message || 'Unable to update this project value.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitLabel;
            }

            if (modalElement?.classList.contains('hidden')) {
                resetProjectChangeModal(modalElement);
            }
        }
    });

    document.body.dataset.projectHeaderInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', initializeProjectHeader);
document.addEventListener('DOMContentLoaded', () => syncProjectHeaderExpandedState());
