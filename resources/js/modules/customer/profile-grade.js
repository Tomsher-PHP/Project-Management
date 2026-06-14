import Alert from '../../alert';
import { initTomSelect } from '../../components/tom-select';

const initializeCustomerProfileGrade = () => {
    const root = document.querySelector('[data-customer-detail-tabs]');

    if (!root || root.dataset.profileGradeInitialized === 'true') {
        return;
    }

    const modal = root.querySelector('[data-customer-profile-grade-modal]');
    const form = modal?.querySelector('[data-customer-profile-grade-form]');
    const gradeSelect = form?.querySelector('[name="customer_profile_grade_id"]');
    const descriptionsContainer = form?.querySelector('[data-customer-profile-grade-descriptions]');
    const submitButton = form?.querySelector('[data-customer-profile-grade-submit]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const currentDescriptions = parseJsonData(modal?.querySelector('[data-customer-profile-grade-current-descriptions]'), []);
    let initialGradeId = String(gradeSelect?.value || '');
    let isSubmitting = false;

    if (modal) {
        initTomSelect(modal);
    }

    const setActiveTab = (tabName) => {
        root.querySelectorAll('[data-customer-tab-trigger]').forEach((trigger) => {
            const isActive = trigger.dataset.customerTabTrigger === tabName;
            trigger.setAttribute('aria-selected', String(isActive));
            trigger.classList.toggle('border-success-300', isActive);
            trigger.classList.toggle('text-success-400', isActive);
            trigger.classList.toggle('border-transparent', !isActive);
            trigger.classList.toggle('text-bgray-700', !isActive);
            trigger.classList.toggle('dark:text-bgray-300', !isActive);
        });

        root.querySelectorAll('[data-customer-tab-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.customerTabPanel !== tabName);
        });
    };

    const clearErrors = () => {
        form?.querySelectorAll('[data-customer-profile-grade-error]').forEach((node) => {
            node.textContent = '';
            node.classList.add('hidden');
        });

        form?.querySelectorAll('input, select').forEach((field) => {
            field.classList.remove('border-red-500');
        });
    };

    const showErrors = (errors = {}) => {
        clearErrors();

        Object.entries(errors).forEach(([fieldName, messages]) => {
            const normalizedName = fieldName.startsWith('descriptions') ? 'descriptions' : fieldName;
            const errorNode = form?.querySelector(`[data-customer-profile-grade-error="${normalizedName}"]`);
            const field = normalizedName === 'descriptions'
                ? descriptionsContainer?.querySelector('input')
                : form?.querySelector(`[name="${normalizedName}"]`);

            field?.classList.add('border-red-500');

            if (errorNode) {
                errorNode.textContent = Array.isArray(messages) ? messages[0] : String(messages || '');
                errorNode.classList.remove('hidden');
            }
        });
    };

    const addDescriptionRow = (value = '') => {
        if (!descriptionsContainer) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'flex items-start gap-2';
        row.dataset.customerProfileGradeDescriptionRow = '';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'descriptions[]';
        input.value = value;
        input.maxLength = 1000;
        input.placeholder = 'Enter description point';
        input.className = 'min-w-0 flex-1 rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white';

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.dataset.customerProfileGradeRemoveDescription = '';
        removeButton.className = 'inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-lg border border-bgray-200 text-bgray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300';
        removeButton.setAttribute('aria-label', 'Remove description point');
        removeButton.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
        `;

        row.append(input, removeButton);
        descriptionsContainer.appendChild(row);
    };

    const setDescriptionRows = (descriptions = []) => {
        if (!descriptionsContainer) {
            return;
        }

        descriptionsContainer.innerHTML = '';
        const values = Array.isArray(descriptions) && descriptions.length ? descriptions : [''];
        values.forEach((description) => addDescriptionRow(String(description || '')));
    };

    const resetModal = () => {
        clearErrors();

        if (gradeSelect?.tomselect) {
            gradeSelect.tomselect.setValue(initialGradeId, true);
        } else if (gradeSelect) {
            gradeSelect.value = initialGradeId;
        }

        setDescriptionRows(currentDescriptions);
        isSubmitting = false;

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Save Profile Grade';
        }
    };

    const openModal = () => {
        if (!modal) {
            return;
        }

        resetModal();
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => descriptionsContainer?.querySelector('input')?.focus(), 50);
    };

    const closeModal = () => {
        if (!modal || isSubmitting) {
            return;
        }

        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        resetModal();
    };

    root.addEventListener('click', (event) => {
        const tabTrigger = event.target.closest('[data-customer-tab-trigger]');
        const openTrigger = event.target.closest('[data-customer-profile-grade-open]');
        const closeTrigger = event.target.closest('[data-customer-profile-grade-close]');
        const addTrigger = event.target.closest('[data-customer-profile-grade-add-description]');
        const removeTrigger = event.target.closest('[data-customer-profile-grade-remove-description]');

        if (tabTrigger) {
            setActiveTab(tabTrigger.dataset.customerTabTrigger);
        }

        if (openTrigger) {
            openModal();
        }

        if (closeTrigger) {
            closeModal();
        }

        if (addTrigger) {
            addDescriptionRow();
            descriptionsContainer?.lastElementChild?.querySelector('input')?.focus();
        }

        if (removeTrigger) {
            removeTrigger.closest('[data-customer-profile-grade-description-row]')?.remove();

            if (!descriptionsContainer?.children.length) {
                addDescriptionRow();
            }
        }
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        clearErrors();

        const selectedGradeId = String(gradeSelect?.value || '');

        if (!selectedGradeId) {
            showErrors({ customer_profile_grade_id: ['Please select a profile grade.'] });
            return;
        }

        const descriptionValues = Array.from(descriptionsContainer?.querySelectorAll('input[name="descriptions[]"]') || [])
            .map((input) => input.value.trim())
            .filter(Boolean);

        if (descriptionValues.length > 20) {
            showErrors({ descriptions: ['A grade can have a maximum of 20 description points.'] });
            return;
        }

        isSubmitting = true;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        try {
            const formData = new FormData();
            formData.set('_method', 'PATCH');
            formData.set('customer_profile_grade_id', selectedGradeId);
            descriptionValues.forEach((description) => formData.append('descriptions[]', description));

            const response = await fetch(form.dataset.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });
            const result = await response.json();

            if (response.status === 422 && result.errors) {
                showErrors(result.errors);
                throw new Error(result.message || 'Please correct the highlighted fields.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to update the customer profile grade.');
            }

            const profileGradePanel = root.querySelector('[data-customer-tab-panel="profile-grade"]');

            if (profileGradePanel && result.html) {
                profileGradePanel.innerHTML = result.html;
            }

            initialGradeId = selectedGradeId;
            currentDescriptions.splice(0, currentDescriptions.length, ...descriptionValues);
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            isSubmitting = false;
            Alert.success(result.message || 'Customer profile grade updated successfully.');
        } catch (error) {
            Alert.error(error.message || 'Unable to update the customer profile grade.');
        } finally {
            isSubmitting = false;

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Save Profile Grade';
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal?.classList.contains('hidden')) {
            closeModal();
        }
    });

    setDescriptionRows(currentDescriptions);
    setActiveTab(root.dataset.defaultTab || 'contact');
    root.dataset.profileGradeInitialized = 'true';
};

const parseJsonData = (element, fallback) => {
    if (!element?.textContent) {
        return fallback;
    }

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        return fallback;
    }
};

document.addEventListener('DOMContentLoaded', initializeCustomerProfileGrade);
