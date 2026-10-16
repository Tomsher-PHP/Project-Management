import { initTomSelect } from '../../components/tom-select';
import { initDatepicker } from '../../components/datepicker';

/**
 * State and lifecycle tracking for Project Hosting module
 */
const projectHostingState = {
    initialized: false,
    listenersBound: false,
};

/*
|--------------------------------------------------------------------------
| Create / Edit Modal
|--------------------------------------------------------------------------
*/
const projectHostingModal = {
    modal: null,

    initialize(root = document) {
        this.modal = root?.querySelector
            ? root.querySelector('[data-project-hosting-modal]')
            : null;

        if (!this.modal) {
            this.modal = document.querySelector('[data-project-hosting-modal]');
        }

        if (!this.modal) {
            return;
        }

        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        if (!this.modal) {
            return;
        }

        const closeButtons = this.modal.querySelectorAll('[data-project-hosting-modal-close]');
        closeButtons.forEach((button) => {
            if (button.dataset.projectHostingModalBound === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectHostingModalBound = 'true';
        });
    },

    bindEscape() {
        if (!this.modal || this.modal.dataset.projectHostingEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !this.modal || this.modal.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        this.modal.dataset.projectHostingEscapeBound = 'true';
    },

    open() {
        if (!this.modal) {
            return;
        }

        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        // Initialize pickers and selects inside the modal
        initDatepicker('.datepicker', {}, this.modal);
        initTomSelect(this.modal);
    },

    close() {
        if (!this.modal) {
            return;
        }

        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        const form = this.modal.querySelector('[data-project-hosting-form]');
        if (form) {
            this.resetForm(form);
        }
    },

    resetForm(form) {
        form.reset();

        const methodInput = form.querySelector('[data-project-hosting-method]');
        if (methodInput) {
            methodInput.value = 'POST';
        }

        const idInput = form.querySelector('[data-project-hosting-id]');
        if (idInput) {
            idInput.value = '';
        }

        const storeAction = form.dataset.storeAction;
        if (storeAction) {
            form.action = storeAction;
        }

        const titleEl = this.modal.querySelector('[data-project-hosting-modal-title]');
        if (titleEl) {
            titleEl.textContent = 'Add Hosting Record';
        }

        const descEl = this.modal.querySelector('[data-project-hosting-modal-description]');
        if (descEl) {
            descEl.textContent = 'Add a hosting or server record associated with this project.';
        }

        // Reset TomSelect controls if present
        form.querySelectorAll('select').forEach((select) => {
            if (select.tomselect) {
                const defaultVal = select.id === 'project_hosting_ownership' ? 'company' : '';
                select.tomselect.setValue(defaultVal, true);
            }
        });

        // Clear errors
        form.querySelectorAll('[data-project-hosting-error]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });

        const saveButton = this.modal.querySelector('[data-project-hosting-save]');
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = 'Save';
        }
    },
};

/*
|--------------------------------------------------------------------------
| View Details Modal
|--------------------------------------------------------------------------
*/
const projectHostingViewModal = {
    modal: null,

    initialize(root = document) {
        this.modal = root?.querySelector
            ? root.querySelector('[data-project-hosting-view-modal]')
            : null;

        if (!this.modal) {
            this.modal = document.querySelector('[data-project-hosting-view-modal]');
        }

        if (!this.modal) {
            return;
        }

        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        if (!this.modal) {
            return;
        }

        const closeButtons = this.modal.querySelectorAll('[data-project-hosting-view-modal-close]');
        closeButtons.forEach((button) => {
            if (button.dataset.projectHostingViewModalBound === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectHostingViewModalBound = 'true';
        });
    },

    bindEscape() {
        if (!this.modal || this.modal.dataset.projectHostingViewEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !this.modal || this.modal.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        this.modal.dataset.projectHostingViewEscapeBound = 'true';
    },

    open(contentHtml = null) {
        if (!this.modal) {
            return;
        }

        const content = this.modal.querySelector('[data-project-hosting-view-content]');
        if (content && contentHtml) {
            content.innerHTML = contentHtml;
        }

        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    },

    close() {
        if (!this.modal) {
            return;
        }

        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    },
};

/*
|--------------------------------------------------------------------------
| Form Submission & Error Handling
|--------------------------------------------------------------------------
*/
function clearFieldErrors(form) {
    form.querySelectorAll('[data-project-hosting-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function displayFieldErrors(form, errors = {}) {
    clearFieldErrors(form);

    Object.entries(errors).forEach(([field, messages]) => {
        const message = Array.isArray(messages) ? messages[0] : messages;
        const errorEl = form.querySelector(`[data-project-hosting-error="${field}"]`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    });
}

async function handleHostingFormSubmit(event) {
    event.preventDefault();

    const form = event.target.closest('[data-project-hosting-form]');
    if (!form) return;

    const modal = form.closest('[data-project-hosting-modal]');
    const saveButton = modal?.querySelector('[data-project-hosting-save]');

    clearFieldErrors(form);

    const formData = new FormData(form);
    const methodInput = form.querySelector('[data-project-hosting-method]');
    const method = (methodInput?.value || 'POST').toUpperCase();
    const actionUrl = form.action;

    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }

    try {
        const response = await fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                displayFieldErrors(form, data.errors);
            } else {
                const message = data.message || 'An error occurred while saving the hosting record.';
                if (window.Toast) {
                    window.Toast.error(message);
                } else {
                    alert(message);
                }
            }
            return;
        }

        if (window.Toast) {
            window.Toast.success(data.message || 'Hosting record saved successfully.');
        }

        projectHostingModal.close();

        // Refresh list
        if (data.html) {
            updateHostingList(data.html);
        } else {
            reloadHostingList();
        }
    } catch (error) {
        console.error('Error saving hosting record:', error);
        if (window.Toast) {
            window.Toast.error('An unexpected network error occurred.');
        }
    } finally {
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = 'Save';
        }
    }
}

/*
|--------------------------------------------------------------------------
| View & Edit Record Actions
|--------------------------------------------------------------------------
*/
async function handleViewRecord(url) {
    projectHostingViewModal.open(
        '<div class="flex items-center justify-center py-12"><span class="text-sm text-bgray-500 dark:text-bgray-400">Loading details...</span></div>'
    );

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to load record');
        }

        const data = await response.json();
        if (data.html) {
            projectHostingViewModal.open(data.html);
        }
    } catch (error) {
        console.error('Error fetching hosting details:', error);
        projectHostingViewModal.open(
            '<div class="py-8 text-center text-sm text-error-300">Unable to load hosting details. Please try again.</div>'
        );
    }
}

async function handleEditRecord(url) {
    projectHostingModal.open();

    const form = document.querySelector('[data-project-hosting-form]');
    if (!form) return;

    const modal = form.closest('[data-project-hosting-modal]');
    const titleEl = modal?.querySelector('[data-project-hosting-modal-title]');
    const descEl = modal?.querySelector('[data-project-hosting-modal-description]');

    if (titleEl) titleEl.textContent = 'Edit Hosting Record';
    if (descEl) descEl.textContent = 'Update details for this hosting record.';

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to load edit data');
        }

        const data = await response.json();
        const record = data.record;
        if (!record) return;

        // Populate fields
        form.action = record.update_url || form.action;

        const methodInput = form.querySelector('[data-project-hosting-method]');
        if (methodInput) methodInput.value = 'PUT';

        const idInput = form.querySelector('[data-project-hosting-id]');
        if (idInput) idInput.value = record.id;

        const setFieldValue = (fieldName, value) => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (!input) return;

            if (input.tagName === 'SELECT' && input.tomselect) {
                input.tomselect.setValue(value ?? '', true);
            } else if (input._flatpickr) {
                input._flatpickr.setDate(value || '', true);
            } else {
                input.value = value ?? '';
            }
        };

        setFieldValue('name', record.name);
        setFieldValue('hosting_provider_id', record.hosting_provider_id);
        setFieldValue('hosting_service_id', record.hosting_service_id);
        setFieldValue('hosting_service_status_id', record.hosting_service_status_id);
        setFieldValue('hosting_billing_cycle_id', record.hosting_billing_cycle_id);
        setFieldValue('hosting_environment_id', record.hosting_environment_id);
        setFieldValue('ownership_type', record.ownership_type);
        setFieldValue('domain', record.domain);
        setFieldValue('reference', record.reference);
        setFieldValue('start_date', record.start_date);
        setFieldValue('expiry_date', record.expiry_date);
        setFieldValue('next_renewal_date', record.next_renewal_date);
        setFieldValue('billing_amount', record.billing_amount);
        setFieldValue('currency_code', record.currency_code);
        setFieldValue('description', record.description);
        setFieldValue('notes', record.notes);
    } catch (error) {
        console.error('Error fetching record for editing:', error);
        if (window.Toast) {
            window.Toast.error('Unable to load record for editing.');
        }
        projectHostingModal.close();
    }
}

/*
|--------------------------------------------------------------------------
| Refresh & Table Updates
|--------------------------------------------------------------------------
*/
function updateHostingList(html) {
    const container = document.getElementById('project-hosting-history');
    if (container) {
        container.innerHTML = html;
    }
}

async function reloadHostingList() {
    const container = document.querySelector('[data-project-hosting-container]');
    const indexUrl = container?.dataset.indexUrl;
    if (!indexUrl) return;

    try {
        const response = await fetch(indexUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) return;

        const data = await response.json();
        if (data.html) {
            updateHostingList(data.html);
        }
    } catch (error) {
        console.error('Failed to reload hosting list:', error);
    }
}

/*
|--------------------------------------------------------------------------
| Delegated Global Event Listeners
|--------------------------------------------------------------------------
*/
function bindDelegatedEvents() {
    if (projectHostingState.listenersBound) {
        return;
    }

    document.addEventListener('click', (event) => {
        // Open create modal
        const createBtn = event.target.closest('[data-project-hosting-modal-open]');
        if (createBtn) {
            event.preventDefault();
            projectHostingModal.open();
            return;
        }

        // View record
        const viewBtn = event.target.closest('[data-project-hosting-view]');
        if (viewBtn) {
            event.preventDefault();
            const actionUrl = viewBtn.getAttribute('href') || viewBtn.dataset.action;
            if (actionUrl) {
                handleViewRecord(actionUrl);
            }
            return;
        }

        // Edit record
        const editBtn = event.target.closest('[data-project-hosting-edit]');
        if (editBtn) {
            event.preventDefault();
            const actionUrl = editBtn.getAttribute('href') || editBtn.dataset.action;
            if (actionUrl) {
                handleEditRecord(actionUrl);
            }
            return;
        }
    });

    // Form submit
    document.addEventListener('submit', (event) => {
        if (event.target.matches('[data-project-hosting-form]')) {
            handleHostingFormSubmit(event);
        }
    });

    projectHostingState.listenersBound = true;
}

/*
|--------------------------------------------------------------------------
| Module Initialization
|--------------------------------------------------------------------------
*/
export function initProjectHosting(root = document) {
    projectHostingModal.initialize(root);
    projectHostingViewModal.initialize(root);
    bindDelegatedEvents();
}

// Auto-init on DOMContentLoaded or tab switch
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initProjectHosting(document));
    } else {
        initProjectHosting(document);
    }

    // Tab activation event listener
    document.addEventListener('project-tab-loaded', (event) => {
        if (event.detail?.tab === 'hosting') {
            initProjectHosting(document);
        }
    });
}
