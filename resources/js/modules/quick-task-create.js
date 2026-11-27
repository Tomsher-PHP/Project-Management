import Alert from '../alert';
import { initTomSelect } from '../components/tom-select';

const getFieldErrorTarget = (field) => field?.tomselect?.control || field;

const clearQuickTaskErrors = (form) => {
    form.querySelectorAll('[data-quick-task-create-error]').forEach((node) => {
        node.textContent = '';
        node.classList.add('hidden');
    });

    form.querySelectorAll('input, select').forEach((field) => {
        getFieldErrorTarget(field)?.classList.remove('border-red-500');
    });
};

const applyQuickTaskErrors = (form, errors = {}) => {
    Object.entries(errors).forEach(([fieldKey, messages]) => {
        const message = Array.isArray(messages) ? messages[0] : messages;
        const errorNode = form.querySelector(`[data-quick-task-create-error="${fieldKey}"]`);
        const field = form.querySelector(`[name="${fieldKey}"]`);

        if (errorNode && message) {
            errorNode.textContent = message;
            errorNode.classList.remove('hidden');
        }

        if (field) {
            getFieldErrorTarget(field)?.classList.add('border-red-500');
        }
    });
};

const restoreDefaultProject = (form) => {
    const projectSelect = form.querySelector('[name="project_id"]');
    if (!projectSelect) {
        return;
    }

    const defaultId = String(form.dataset.defaultProjectId || '').trim();
    const defaultName = String(form.dataset.defaultProjectName || '').trim();
    const defaultCode = String(form.dataset.defaultProjectCode || '--').trim();

    if (projectSelect.tomselect) {
        const instance = projectSelect.tomselect;
        instance.clear(true);
        instance.clearOptions();

        if (defaultId && defaultName) {
            instance.addOption({
                id: defaultId,
                value: defaultId,
                name: defaultName,
                text: defaultName,
                subtype: defaultCode,
                project_code: defaultCode,
            });
            instance.setValue(defaultId, true);
        }

        return;
    }

    projectSelect.value = defaultId;
};

const setSubmittingState = (form, isSubmitting, activeAction = 'save') => {
    const buttons = form.querySelectorAll('[data-quick-task-submit]');

    buttons.forEach((button) => {
        button.disabled = isSubmitting;
        const action = button.dataset.quickTaskSubmit || 'save';

        if (isSubmitting && action === activeAction) {
            button.textContent = button.dataset.loadingLabel || 'Saving...';
        } else {
            button.textContent = button.dataset.defaultLabel || button.textContent;
        }
    });
};

const initQuickTaskCreate = () => {
    const modal = document.getElementById('quick_task_create_modal');
    if (!modal || modal.dataset.quickTaskInitialized === '1') {
        return;
    }

    const form = modal.querySelector('[data-quick-task-create-form]');
    if (!form) {
        return;
    }

    modal.dataset.quickTaskInitialized = '1';
    initTomSelect(modal);

    let isSubmitting = false;

    const openModal = () => {
        clearQuickTaskErrors(form);

        const nameInput = form.querySelector('[name="name"]');
        if (nameInput) {
            nameInput.value = '';
        }

        restoreDefaultProject(form);

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        window.setTimeout(() => {
            nameInput?.focus();
        }, 50);
    };

    const closeModal = () => {
        if (isSubmitting) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        clearQuickTaskErrors(form);
    };

    const submitQuickTask = async (startTimer = false) => {
        if (isSubmitting) {
            return;
        }

        clearQuickTaskErrors(form);

        const storeUrl = form.dataset.storeUrl;
        if (!storeUrl) {
            Alert.errorModal('Unable to create quick task right now.');
            return;
        }

        const activeAction = startTimer ? 'save_and_start' : 'save';
        isSubmitting = true;
        setSubmittingState(form, true, activeAction);

        try {
            const formData = new FormData(form);
            formData.set('start_timer', startTimer ? '1' : '0');

            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: formData,
            });

            const result = await response.json().catch(() => ({}));

            if (response.status === 422 && result.errors) {
                applyQuickTaskErrors(form, result.errors);
                isSubmitting = false;
                setSubmittingState(form, false);
                return;
            }

            if (!response.ok || !result.status) {
                throw new Error(result.message || 'Unable to create quick task.');
            }

            const nameInput = form.querySelector('[name="name"]');
            if (nameInput) {
                nameInput.value = '';
            }
            restoreDefaultProject(form);

            isSubmitting = false;
            setSubmittingState(form, false);
            closeModal();

            if (result.navbar_timer && typeof result.navbar_timer === 'object') {
                window.dispatchEvent(new CustomEvent('running-task-navbar:update', {
                    detail: {
                        response: {
                            navbar_timer: result.navbar_timer,
                        },
                    },
                }));
            }

            Alert.success(
                result.message || (startTimer
                    ? 'Task created and timer started successfully.'
                    : 'Task created successfully.')
            );

            window.location.reload();
        } catch (error) {
            isSubmitting = false;
            setSubmittingState(form, false);
            Alert.errorModal(error.message || 'Unable to create quick task.');
        }
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-quick-task-create-open]')) {
            event.preventDefault();
            openModal();
            return;
        }

        if (event.target.closest('[data-quick-task-create-close]')) {
            event.preventDefault();
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitQuickTask(false);
    });

    const saveAndStartButton = form.querySelector('[data-quick-task-submit="save_and_start"]');
    saveAndStartButton?.addEventListener('click', (event) => {
        event.preventDefault();
        submitQuickTask(true);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initQuickTaskCreate);
} else {
    initQuickTaskCreate();
}
