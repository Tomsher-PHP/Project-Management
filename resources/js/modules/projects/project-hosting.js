import { initTomSelect } from '../../components/tom-select';
import { initDatepicker } from '../../components/datepicker';

/**
 * State and lifecycle tracking for Project Hosting module
 */
const projectHostingState = {
    initialized: false,
    listenersBound: false,
    activeFilters: {
        search: '',
        hosting_provider_id: '',
        hosting_service_id: '',
        hosting_service_status_id: '',
        ownership_type: '',
        expiry_status: '',
        page: 1,
    },
    searchDebounceTimer: null,
    isRequestPending: false,
};

/*
|--------------------------------------------------------------------------
| Create / Edit Modal
|--------------------------------------------------------------------------
*/
const projectHostingModal = {
    modal: null,

    getModal() {
        if (!this.modal || !document.contains(this.modal)) {
            this.modal = document.querySelector('[data-project-hosting-modal]');
            if (this.modal) {
                this.bindCloseButtons();
                this.bindEscape();
            }
        }
        return this.modal;
    },

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
        const modal = this.getModal();
        if (!modal) {
            return;
        }

        const closeButtons = modal.querySelectorAll('[data-project-hosting-modal-close]');
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
        const modal = this.getModal();
        if (!modal || modal.dataset.projectHostingEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            const currentModal = this.getModal();
            if (event.key !== 'Escape' || !currentModal || currentModal.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        modal.dataset.projectHostingEscapeBound = 'true';
    },

    open() {
        const modal = this.getModal();
        if (!modal) {
            console.error('Project hosting modal element not found in DOM.');
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        // Initialize pickers and selects inside the modal
        initDatepicker('.datepicker', {}, modal);
        initTomSelect(modal);
    },

    close() {
        const modal = this.getModal();
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        const form = modal.querySelector('[data-project-hosting-form]');
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

        const modal = this.getModal();
        const titleEl = modal?.querySelector('[data-project-hosting-modal-title]');
        if (titleEl) {
            titleEl.textContent = 'Add Hosting Record';
        }

        const descEl = modal?.querySelector('[data-project-hosting-modal-description]');
        if (descEl) {
            descEl.textContent = 'Add a hosting or server record associated with this project.';
        }

        // Reset TomSelect controls if present
        form.querySelectorAll('select').forEach((select) => {
            if (select.tomselect) {
                let defaultVal = '';
                if (select.id === 'project_hosting_ownership') {
                    defaultVal = 'company';
                } else if (select.id === 'project_hosting_status') {
                    defaultVal = select.dataset.defaultValue || select.querySelector('option[selected]')?.value || '';
                } else if (select.id === 'project_hosting_currency_code') {
                    defaultVal = select.querySelector('option[selected]')?.value || 'AED';
                }
                select.tomselect.setValue(defaultVal, true);
            }
        });

        // Clear Flatpickr date inputs if present
        form.querySelectorAll('input.datepicker').forEach((input) => {
            if (input._flatpickr) {
                input._flatpickr.clear();
            }
        });

        // Clear errors
        form.querySelectorAll('[data-project-hosting-error]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });

        const saveButton = modal?.querySelector('[data-project-hosting-save]');
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = 'Save';
        }
    },
};

/*
|--------------------------------------------------------------------------
| Renewal Modal
|--------------------------------------------------------------------------
*/
const projectHostingRenewalModal = {
    modal: null,

    getModal() {
        if (!this.modal || !document.contains(this.modal)) {
            this.modal = document.querySelector('[data-project-hosting-renewal-modal]');
            if (this.modal) {
                this.bindCloseButtons();
                this.bindEscape();
            }
        }
        return this.modal;
    },

    initialize(root = document) {
        this.modal = root?.querySelector
            ? root.querySelector('[data-project-hosting-renewal-modal]')
            : null;

        if (!this.modal) {
            this.modal = document.querySelector('[data-project-hosting-renewal-modal]');
        }

        if (!this.modal) {
            return;
        }

        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        const modal = this.getModal();
        if (!modal) return;

        const closeButtons = modal.querySelectorAll('[data-project-hosting-renewal-modal-close]');
        closeButtons.forEach((button) => {
            if (button.dataset.projectHostingRenewalBound === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectHostingRenewalBound = 'true';
        });
    },

    bindEscape() {
        const modal = this.getModal();
        if (!modal || modal.dataset.projectHostingRenewalEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            const currentModal = this.getModal();
            if (event.key !== 'Escape' || !currentModal || currentModal.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        modal.dataset.projectHostingRenewalEscapeBound = 'true';
    },

    open(recordData = null) {
        const modal = this.getModal();
        if (!modal) {
            console.error('Project hosting renewal modal not found in DOM.');
            return;
        }

        if (recordData) {
            this.populate(recordData);
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        // Initialize pickers and selects inside the renewal modal
        initDatepicker('.datepicker', {}, modal);
        initTomSelect(modal);
    },

    close() {
        const modal = this.getModal();
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        const form = modal.querySelector('[data-project-hosting-renewal-form]');
        if (form) {
            this.resetForm(form);
        }
    },

    populate(data) {
        const modal = this.getModal();
        if (!modal || !data) return;

        const form = modal.querySelector('[data-project-hosting-renewal-form]');
        if (!form) return;

        form.action = data.renew_url || '';

        const idInput = form.querySelector('[data-renewal-hosting-id]');
        if (idInput) idInput.value = data.id || '';

        const nameEl = modal.querySelector('[data-renewal-record-name]');
        if (nameEl) nameEl.textContent = data.name || '';

        const domainEl = modal.querySelector('[data-renewal-record-domain]');
        if (domainEl) domainEl.textContent = data.domain ? `(${data.domain})` : '';

        const currentExpiryEl = modal.querySelector('[data-renewal-current-expiry]');
        if (currentExpiryEl) {
            currentExpiryEl.textContent = data.expiry_date_formatted || data.expiry_date || '—';
        }

        // Set renewal_date to today
        const renewalDateInput = form.querySelector('[data-renewal-field="renewal_date"]');
        const todayStr = new Date().toISOString().split('T')[0];
        if (renewalDateInput) {
            if (renewalDateInput._flatpickr) {
                renewalDateInput._flatpickr.setDate(todayStr);
            } else {
                renewalDateInput.value = todayStr;
            }
        }

        // Clear new_expiry_date or prefill if none
        const newExpiryInput = form.querySelector('[data-renewal-field="new_expiry_date"]');
        if (newExpiryInput) {
            if (newExpiryInput._flatpickr) {
                newExpiryInput._flatpickr.clear();
            } else {
                newExpiryInput.value = '';
            }
        }

        // Optional new_start_date: set to current expiry or today
        const newStartInput = form.querySelector('[data-renewal-field="new_start_date"]');
        if (newStartInput) {
            if (newStartInput._flatpickr) {
                newStartInput._flatpickr.clear();
            } else {
                newStartInput.value = '';
            }
        }

        // Billing Cycle
        const cycleSelect = form.querySelector('[data-renewal-field="hosting_billing_cycle_id"]');
        if (cycleSelect) {
            const val = data.hosting_billing_cycle_id || '';
            if (cycleSelect.tomselect) {
                cycleSelect.tomselect.setValue(val, true);
            } else {
                cycleSelect.value = val;
            }
        }

        // Billing Amount
        const amountInput = form.querySelector('[data-renewal-field="billing_amount"]');
        if (amountInput) {
            amountInput.value = data.billing_amount !== null && data.billing_amount !== undefined ? data.billing_amount : '';
        }

        // Currency
        const currencySelect = form.querySelector('[data-renewal-field="currency_code"]');
        if (currencySelect) {
            const val = data.currency_code || 'AED';
            if (currencySelect.tomselect) {
                currencySelect.tomselect.setValue(val, true);
            } else {
                currencySelect.value = val;
            }
        }

        // Reference
        const refInput = form.querySelector('[data-renewal-field="reference"]');
        if (refInput) {
            refInput.value = '';
        }

        // Notes
        const notesInput = form.querySelector('[data-renewal-field="notes"]');
        if (notesInput) {
            notesInput.value = '';
        }

        // Clear errors
        form.querySelectorAll('[data-renewal-error]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    },

    resetForm(form) {
        form.reset();
        form.querySelectorAll('[data-renewal-error]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        const submitBtn = form.querySelector('[data-project-hosting-renewal-submit]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Renewal';
        }
    },
};

/*
|--------------------------------------------------------------------------
| Renewal History Modal
|--------------------------------------------------------------------------
*/
const projectHostingRenewalHistoryModal = {
    modal: null,
    body: null,
    currentRecordId: null,
    currentActionUrl: null,

    getModal() {
        if (!this.modal || !document.contains(this.modal)) {
            this.modal = document.querySelector('[data-project-hosting-renewal-history-modal]');
            if (this.modal) {
                this.body = this.modal.querySelector('[data-project-hosting-renewal-history-body]');
                this.bindCloseButtons();
                this.bindEscape();
            }
        }
        return this.modal;
    },

    initialize(root = document) {
        this.modal = root?.querySelector
            ? root.querySelector('[data-project-hosting-renewal-history-modal]')
            : null;

        if (!this.modal) {
            this.modal = document.querySelector('[data-project-hosting-renewal-history-modal]');
        }

        if (!this.modal) {
            return;
        }

        this.body = this.modal.querySelector('[data-project-hosting-renewal-history-body]');
        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        const modal = this.getModal();
        if (!modal) return;

        const closeButtons = modal.querySelectorAll('[data-project-hosting-renewal-history-modal-close]');
        closeButtons.forEach((button) => {
            if (button.dataset.projectHostingRenewalHistoryBound === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectHostingRenewalHistoryBound = 'true';
        });
    },

    bindEscape() {
        const modal = this.getModal();
        if (!modal || modal.dataset.projectHostingRenewalHistoryEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            const currentModal = this.getModal();
            if (event.key !== 'Escape' || !currentModal || currentModal.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        modal.dataset.projectHostingRenewalHistoryEscapeBound = 'true';
    },

    open(recordId, recordName = '', actionUrl = '') {
        const modal = this.getModal();
        if (!modal) {
            console.error('Project hosting renewal history modal not found in DOM.');
            return;
        }

        this.currentRecordId = recordId ? String(recordId) : null;
        this.currentActionUrl = actionUrl || null;

        const nameEl = modal.querySelector('[data-renewal-history-record-name]');
        if (nameEl) {
            nameEl.textContent = recordName || 'Hosting Service';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        if (actionUrl) {
            fetchRenewalHistory(actionUrl);
        }
    },

    close() {
        if (typeof activeRenewalHistoryAbortController !== 'undefined' && activeRenewalHistoryAbortController) {
            activeRenewalHistoryAbortController.abort();
            activeRenewalHistoryAbortController = null;
        }
        if (typeof activeRenewalHistorySeq !== 'undefined') {
            activeRenewalHistorySeq++;
        }

        const modal = this.getModal();
        if (!modal || modal.classList.contains('hidden')) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Only remove overflow-hidden if drawer and status history modal are not open
        const { drawer } = projectHostingDrawer.getElements();
        const statusModal = projectHostingStatusHistoryModal.getModal();
        const renewalModal = projectHostingRenewalModal.getModal();

        const isAnyOtherOpen = (drawer && !drawer.classList.contains('hidden')) ||
            (statusModal && !statusModal.classList.contains('hidden')) ||
            (renewalModal && !renewalModal.classList.contains('hidden'));

        if (!isAnyOtherOpen) {
            document.body.classList.remove('overflow-hidden');
        }
    },

    isOpenForRecord(recordId) {
        const modal = this.getModal();
        if (!modal || modal.classList.contains('hidden')) {
            return false;
        }
        return this.currentRecordId !== null && String(this.currentRecordId) === String(recordId);
    },

    refresh() {
        if (this.currentActionUrl) {
            fetchRenewalHistory(this.currentActionUrl);
        }
    },
};

/*
|--------------------------------------------------------------------------
| Status History Modal
|--------------------------------------------------------------------------
*/
const projectHostingStatusHistoryModal = {
    modal: null,
    body: null,
    currentRecordId: null,
    currentActionUrl: null,

    getModal() {
        if (!this.modal || !document.contains(this.modal)) {
            this.modal = document.querySelector('[data-project-hosting-status-history-modal]');
            if (this.modal) {
                this.body = this.modal.querySelector('[data-project-hosting-status-history-body]');
                this.bindCloseButtons();
                this.bindEscape();
            }
        }
        return this.modal;
    },

    initialize(root = document) {
        this.modal = root?.querySelector
            ? root.querySelector('[data-project-hosting-status-history-modal]')
            : null;

        if (!this.modal) {
            this.modal = document.querySelector('[data-project-hosting-status-history-modal]');
        }

        if (!this.modal) {
            return;
        }

        this.body = this.modal.querySelector('[data-project-hosting-status-history-body]');
        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        const modal = this.getModal();
        if (!modal) return;

        const closeButtons = modal.querySelectorAll('[data-project-hosting-status-history-modal-close]');
        closeButtons.forEach((button) => {
            if (button.dataset.projectHostingStatusHistoryBound === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectHostingStatusHistoryBound = 'true';
        });
    },

    bindEscape() {
        const modal = this.getModal();
        if (!modal || modal.dataset.projectHostingStatusHistoryEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            const currentModal = this.getModal();
            if (event.key !== 'Escape' || !currentModal || currentModal.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        modal.dataset.projectHostingStatusHistoryEscapeBound = 'true';
    },

    open(recordId, recordName = '', actionUrl = '') {
        const modal = this.getModal();
        if (!modal) {
            console.error('Project hosting status history modal not found in DOM.');
            return;
        }

        this.currentRecordId = recordId ? String(recordId) : null;
        this.currentActionUrl = actionUrl || null;

        const nameEl = modal.querySelector('[data-status-history-record-name]');
        if (nameEl) {
            nameEl.textContent = recordName || 'Hosting Service';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        if (actionUrl) {
            fetchStatusHistory(actionUrl);
        }
    },

    close() {
        if (typeof activeStatusHistoryAbortController !== 'undefined' && activeStatusHistoryAbortController) {
            activeStatusHistoryAbortController.abort();
            activeStatusHistoryAbortController = null;
        }
        if (typeof activeStatusHistorySeq !== 'undefined') {
            activeStatusHistorySeq++;
        }

        const modal = this.getModal();
        if (!modal || modal.classList.contains('hidden')) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Only remove overflow-hidden if drawer and renewal history modal are not open
        const { drawer } = projectHostingDrawer.getElements();
        const renewalHistoryModal = projectHostingRenewalHistoryModal.getModal();
        const renewalModal = projectHostingRenewalModal.getModal();

        const isAnyOtherOpen = (drawer && !drawer.classList.contains('hidden')) ||
            (renewalHistoryModal && !renewalHistoryModal.classList.contains('hidden')) ||
            (renewalModal && !renewalModal.classList.contains('hidden'));

        if (!isAnyOtherOpen) {
            document.body.classList.remove('overflow-hidden');
        }
    },

    isOpenForRecord(recordId) {
        const modal = this.getModal();
        if (!modal || modal.classList.contains('hidden')) {
            return false;
        }
        return this.currentRecordId !== null && String(this.currentRecordId) === String(recordId);
    },
};

/*
|--------------------------------------------------------------------------
| View Details Right-Side Drawer
|--------------------------------------------------------------------------
*/
const projectHostingDrawer = {
    drawer: null,
    backdrop: null,
    panel: null,
    content: null,

    getElements() {
        if (!this.drawer || !document.contains(this.drawer)) {
            this.drawer = document.querySelector('[data-project-hosting-drawer], [data-project-hosting-view-modal]');
            if (this.drawer) {
                this.backdrop = this.drawer.querySelector('[data-project-hosting-drawer-backdrop], [data-project-hosting-view-modal-close]');
                this.panel = this.drawer.querySelector('[data-project-hosting-drawer-panel]');
                this.content = this.drawer.querySelector('[data-project-hosting-drawer-content], [data-project-hosting-view-content]');
                this.bindCloseButtons();
                this.bindEscape();
            }
        }
        return {
            drawer: this.drawer,
            backdrop: this.backdrop,
            panel: this.panel,
            content: this.content,
        };
    },

    initialize(root = document) {
        this.drawer = root?.querySelector
            ? root.querySelector('[data-project-hosting-drawer], [data-project-hosting-view-modal]')
            : null;

        if (!this.drawer) {
            this.drawer = document.querySelector('[data-project-hosting-drawer], [data-project-hosting-view-modal]');
        }

        if (!this.drawer) {
            return;
        }

        this.backdrop = this.drawer.querySelector('[data-project-hosting-drawer-backdrop], [data-project-hosting-view-modal-close]');
        this.panel = this.drawer.querySelector('[data-project-hosting-drawer-panel]');
        this.content = this.drawer.querySelector('[data-project-hosting-drawer-content], [data-project-hosting-view-content]');

        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        const { drawer } = this.getElements();
        if (!drawer) return;

        const closeButtons = drawer.querySelectorAll('[data-project-hosting-drawer-close], [data-project-hosting-view-modal-close]');
        closeButtons.forEach((button) => {
            if (button.dataset.projectHostingDrawerBound === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectHostingDrawerBound = 'true';
        });
    },

    bindEscape() {
        const { drawer } = this.getElements();
        if (!drawer || drawer.dataset.projectHostingEscapeBound === 'true') {
            return;
        }

        document.addEventListener('keydown', (event) => {
            const { drawer } = this.getElements();
            if (event.key !== 'Escape' || !drawer || drawer.classList.contains('hidden')) {
                return;
            }
            this.close();
        });

        drawer.dataset.projectHostingEscapeBound = 'true';
    },

    open(contentHtml = null) {
        const { drawer, backdrop, panel, content } = this.getElements();
        if (!drawer) {
            console.error('Project hosting drawer element not found in DOM.');
            return;
        }

        if (content && contentHtml) {
            content.innerHTML = contentHtml;
        }

        drawer.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Trigger slide-over CSS transitions
        requestAnimationFrame(() => {
            if (backdrop) {
                backdrop.classList.remove('opacity-0');
            }
            if (panel) {
                panel.classList.remove('translate-x-full');
            }
        });
    },

    close() {
        if (typeof activeViewAbortController !== 'undefined' && activeViewAbortController) {
            activeViewAbortController.abort();
            activeViewAbortController = null;
        }
        if (typeof activeViewRequestSeq !== 'undefined') {
            activeViewRequestSeq++;
        }

        const { drawer, backdrop, panel } = this.getElements();
        if (!drawer || drawer.classList.contains('hidden')) {
            return;
        }

        if (backdrop) {
            backdrop.classList.add('opacity-0');
        }
        if (panel) {
            panel.classList.add('translate-x-full');
        }

        setTimeout(() => {
            drawer.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
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

        // Refresh list with current active filters & update summary counts
        fetchHostingList();

        if (data.summary) {
            updateSummaryCards(data.summary);
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

async function handleRenewalFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const actionUrl = form.action;
    if (!actionUrl) return;

    const submitBtn = form.querySelector('[data-project-hosting-renewal-submit]');
    const formData = new FormData(form);

    // Clear previous errors
    form.querySelectorAll('[data-renewal-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
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
                Object.entries(data.errors).forEach(([field, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    const errorEl = form.querySelector(`[data-renewal-error="${field}"]`);
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    }
                });
            } else {
                const message = data.message || 'An error occurred while processing the renewal.';
                if (window.Toast) {
                    window.Toast.error(message);
                } else {
                    alert(message);
                }
            }
            return;
        }

        if (window.Toast) {
            window.Toast.success(data.message || 'Hosting record renewed successfully.');
        }

        // Close renewal modal
        projectHostingRenewalModal.close();

        // Refresh list with current active filters & update summary cards
        fetchHostingList();

        if (data.summary) {
            updateSummaryCards(data.summary);
        }

        // Update details drawer if open or drawer_html provided
        if (data.drawer_html) {
            const { drawer } = projectHostingDrawer.getElements();
            if (drawer && !drawer.classList.contains('hidden')) {
                projectHostingDrawer.open(data.drawer_html);
            }
        }

        // Refresh renewal history if Renewal History modal is currently open for this hosting record
        const renewalHostingId = form.querySelector('[data-renewal-hosting-id]')?.value;
        if (renewalHostingId && projectHostingRenewalHistoryModal.isOpenForRecord(renewalHostingId)) {
            projectHostingRenewalHistoryModal.refresh();
        }
    } catch (error) {
        console.error('Error renewing hosting record:', error);
        if (window.Toast) {
            window.Toast.error('An unexpected network error occurred.');
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Renewal';
        }
    }
}

let activeRenewalHistorySeq = 0;
let activeRenewalHistoryAbortController = null;

async function fetchRenewalHistory(url) {
    if (activeRenewalHistoryAbortController) {
        activeRenewalHistoryAbortController.abort();
    }
    activeRenewalHistoryAbortController = new AbortController();
    const currentSeq = ++activeRenewalHistorySeq;

    const modal = projectHostingRenewalHistoryModal.getModal();
    const body = modal?.querySelector('[data-project-hosting-renewal-history-body]');

    if (body) {
        body.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 text-center" data-project-hosting-renewal-history-loading>
                <svg class="h-8 w-8 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="mt-3 text-xs font-medium text-bgray-600 dark:text-bgray-300">Loading renewal history...</span>
            </div>
        `;
    }

    try {
        const response = await fetch(url, {
            signal: activeRenewalHistoryAbortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to load renewal history (${response.status})`);
        }

        const data = await response.json();

        if (currentSeq !== activeRenewalHistorySeq) {
            return;
        }

        const currentModal = projectHostingRenewalHistoryModal.getModal();
        if (!currentModal || currentModal.classList.contains('hidden')) {
            return;
        }

        if (body && data.html) {
            body.innerHTML = data.html;
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        if (currentSeq !== activeRenewalHistorySeq) {
            return;
        }

        console.error('Error fetching renewal history:', error);
        if (body) {
            body.innerHTML = `
                <div class="py-12 text-center" data-project-hosting-renewal-history-error>
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-error-50 text-error-300 dark:bg-darkblack-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                        Unable to load renewal history
                    </h5>
                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                        An error occurred while retrieving renewal history. Please try again.
                    </p>
                    <div class="mt-4">
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-retry-renewal-history data-action="${url}">
                            Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }
}

let activeStatusHistorySeq = 0;
let activeStatusHistoryAbortController = null;

async function fetchStatusHistory(url) {
    if (activeStatusHistoryAbortController) {
        activeStatusHistoryAbortController.abort();
    }
    activeStatusHistoryAbortController = new AbortController();
    const currentSeq = ++activeStatusHistorySeq;

    const modal = projectHostingStatusHistoryModal.getModal();
    const body = modal?.querySelector('[data-project-hosting-status-history-body]');

    if (body) {
        body.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 text-center" data-project-hosting-status-history-loading>
                <svg class="h-8 w-8 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="mt-3 text-xs font-medium text-bgray-600 dark:text-bgray-300">Loading status history...</span>
            </div>
        `;
    }

    try {
        const response = await fetch(url, {
            signal: activeStatusHistoryAbortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to load status history (${response.status})`);
        }

        const data = await response.json();

        if (currentSeq !== activeStatusHistorySeq) {
            return;
        }

        const currentModal = projectHostingStatusHistoryModal.getModal();
        if (!currentModal || currentModal.classList.contains('hidden')) {
            return;
        }

        if (body && data.html) {
            body.innerHTML = data.html;
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        if (currentSeq !== activeStatusHistorySeq) {
            return;
        }

        console.error('Error fetching status history:', error);
        if (body) {
            body.innerHTML = `
                <div class="py-12 text-center" data-project-hosting-status-history-error>
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-error-50 text-error-300 dark:bg-darkblack-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                        Unable to load status history
                    </h5>
                    <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-400">
                        An error occurred while retrieving status history. Please try again.
                    </p>
                    <div class="mt-4">
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-retry-status-history data-action="${url}">
                            Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }
}

/*
|--------------------------------------------------------------------------
| View & Edit Record Actions
|--------------------------------------------------------------------------
*/
let activeViewRequestSeq = 0;
let activeViewAbortController = null;

async function handleViewRecord(url) {
    // Abort any existing in-flight drawer request
    if (activeViewAbortController) {
        activeViewAbortController.abort();
    }
    activeViewAbortController = new AbortController();
    const currentSeq = ++activeViewRequestSeq;

    projectHostingDrawer.open(
        `<div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="h-9 w-9 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="mt-4 text-sm font-medium text-bgray-600 dark:text-bgray-300">Loading hosting details...</span>
        </div>`
    );

    try {
        const response = await fetch(url, {
            signal: activeViewAbortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to load record (${response.status})`);
        }

        const data = await response.json();

        // If another request started or drawer was closed, ignore stale payload
        if (currentSeq !== activeViewRequestSeq) {
            return;
        }

        const { drawer } = projectHostingDrawer.getElements();
        if (!drawer || drawer.classList.contains('hidden')) {
            return;
        }

        if (data.html) {
            projectHostingDrawer.open(data.html);
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            return; // Cleanly ignored aborted request
        }

        if (currentSeq !== activeViewRequestSeq) {
            return;
        }

        console.error('Error fetching hosting details:', error);
        const { content, drawer } = projectHostingDrawer.getElements();
        if (content && drawer && !drawer.classList.contains('hidden')) {
            content.innerHTML = `
                <div class="p-8 text-center">
                    <p class="text-sm font-medium text-error-300">Unable to load hosting details. Please try again.</p>
                    <button type="button" class="mt-4 rounded-lg bg-bgray-200 px-4 py-2 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-300 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-drawer-close>
                        Close
                    </button>
                </div>
            `;
        }
    }
}

async function handleEditRecord(url) {
    // If details drawer is open, close it cleanly first
    projectHostingDrawer.close();

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
                if (value && !input.tomselect.options[value]) {
                    input.tomselect.addOption({
                        value: String(value),
                        text: String(value),
                    });
                }
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
| Refresh, Summary Cards & Table Updates
|--------------------------------------------------------------------------
*/
function updateHostingList(html) {
    const container = document.getElementById('project-hosting-history');
    if (container) {
        container.innerHTML = html;
    }
}

function updateSummaryCards(summary) {
    if (!summary || typeof summary !== 'object') return;

    Object.entries(summary).forEach(([key, count]) => {
        const countEl = document.querySelector(`[data-summary-count="${key}"]`);
        if (countEl) {
            countEl.textContent = count;
        }
    });
}

function showTableLoading(show = true) {
    const container = document.getElementById('project-hosting-history');
    if (!container) return;

    let overlay = container.querySelector('[data-project-hosting-loading-overlay]');
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.setAttribute('data-project-hosting-loading-overlay', 'true');
            overlay.className = 'absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-[1px] dark:bg-darkblack-600/70 transition-opacity duration-200';
            overlay.innerHTML = `
                <div class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-semibold text-bgray-800 shadow-md dark:bg-darkblack-500 dark:text-white">
                    <svg class="h-4 w-4 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Loading...</span>
                </div>
            `;
            container.appendChild(overlay);
        }
        overlay.classList.remove('hidden');
    } else if (overlay) {
        overlay.remove();
    }
}

async function fetchHostingList() {
    if (projectHostingState.isRequestPending) return;

    const container = document.querySelector('[data-project-hosting-container]');
    const indexUrl = container?.dataset.indexUrl;
    if (!indexUrl) return;

    projectHostingState.isRequestPending = true;
    showTableLoading(true);

    try {
        const url = new URL(indexUrl, window.location.origin);
        Object.entries(projectHostingState.activeFilters).forEach(([key, val]) => {
            if (val !== '' && val !== null && val !== undefined) {
                url.searchParams.set(key, val);
            }
        });

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch records (${response.status})`);
        }

        const data = await response.json();
        if (data.html) {
            updateHostingList(data.html);
        }
        if (data.summary) {
            updateSummaryCards(data.summary);
        }
    } catch (error) {
        console.error('Failed to load hosting records:', error);
        if (window.Toast) {
            window.Toast.error('Unable to refresh hosting records. Please try again.');
        }
    } finally {
        projectHostingState.isRequestPending = false;
        showTableLoading(false);
    }
}

/*
|--------------------------------------------------------------------------
| Search & Filters Handling
|--------------------------------------------------------------------------
*/
function handleFilterChange(filterKey, value) {
    projectHostingState.activeFilters[filterKey] = value;
    projectHostingState.activeFilters.page = 1; // Always reset to page 1 on filter change
    fetchHostingList();
}

function handleSearchInput(value) {
    if (projectHostingState.searchDebounceTimer) {
        clearTimeout(projectHostingState.searchDebounceTimer);
    }

    projectHostingState.searchDebounceTimer = setTimeout(() => {
        handleFilterChange('search', value.trim());
    }, 350);
}

function resetAllFilters() {
    projectHostingState.activeFilters = {
        search: '',
        hosting_provider_id: '',
        hosting_service_id: '',
        hosting_service_status_id: '',
        ownership_type: '',
        expiry_status: '',
        page: 1,
    };

    // Reset toolbar DOM inputs
    const filterBar = document.querySelector('[data-project-hosting-filter-bar]');
    if (filterBar) {
        const searchInput = filterBar.querySelector('[data-project-hosting-filter="search"]');
        if (searchInput) {
            searchInput.value = '';
        }

        filterBar.querySelectorAll('select[data-project-hosting-filter]').forEach((select) => {
            if (select.tomselect) {
                select.tomselect.setValue('', true);
            } else {
                select.value = '';
            }
        });
    }

    fetchHostingList();
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

    // Delegated Clicks
    document.addEventListener('click', (event) => {
        // Open create modal
        const createBtn = event.target.closest('[data-project-hosting-modal-open]');
        if (createBtn) {
            event.preventDefault();
            projectHostingModal.open();
            return;
        }

        // View record (opens right-side drawer)
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

        // Renew record
        const renewBtn = event.target.closest('[data-project-hosting-renew]');
        if (renewBtn) {
            event.preventDefault();
            const rawJson = renewBtn.dataset.recordJson;
            let recordData = null;
            if (rawJson) {
                try {
                    recordData = JSON.parse(rawJson);
                } catch (e) {
                    console.error('Invalid record JSON on renew button:', e);
                }
            }
            if (!recordData) {
                const actionUrl = renewBtn.getAttribute('href') || renewBtn.dataset.action;
                recordData = {
                    renew_url: actionUrl,
                };
            }
            projectHostingRenewalModal.open(recordData);
            return;
        }

        // Close drawer buttons
        const drawerCloseBtn = event.target.closest('[data-project-hosting-drawer-close], [data-project-hosting-view-modal-close]');
        if (drawerCloseBtn) {
            event.preventDefault();
            projectHostingDrawer.close();
            return;
        }

        // Close renewal modal buttons
        const renewalCloseBtn = event.target.closest('[data-project-hosting-renewal-modal-close]');
        if (renewalCloseBtn) {
            event.preventDefault();
            projectHostingRenewalModal.close();
            return;
        }

        // Open Renewal History modal
        const renewalHistoryBtn = event.target.closest('[data-project-hosting-renewal-history]');
        if (renewalHistoryBtn) {
            event.preventDefault();
            const recordId = renewalHistoryBtn.dataset.hostingId;
            const recordName = renewalHistoryBtn.dataset.hostingName || '';
            const actionUrl = renewalHistoryBtn.getAttribute('href') || renewalHistoryBtn.dataset.action;
            projectHostingRenewalHistoryModal.open(recordId, recordName, actionUrl);
            return;
        }

        // Open Status History modal
        const statusHistoryBtn = event.target.closest('[data-project-hosting-status-history]');
        if (statusHistoryBtn) {
            event.preventDefault();
            const recordId = statusHistoryBtn.dataset.hostingId;
            const recordName = statusHistoryBtn.dataset.hostingName || '';
            const actionUrl = statusHistoryBtn.getAttribute('href') || statusHistoryBtn.dataset.action;
            projectHostingStatusHistoryModal.open(recordId, recordName, actionUrl);
            return;
        }

        // Retry Renewal History
        const retryRenewalBtn = event.target.closest('[data-project-hosting-retry-renewal-history]');
        if (retryRenewalBtn) {
            event.preventDefault();
            const actionUrl = retryRenewalBtn.dataset.action;
            if (actionUrl) {
                fetchRenewalHistory(actionUrl);
            }
            return;
        }

        // Retry Status History
        const retryStatusBtn = event.target.closest('[data-project-hosting-retry-status-history]');
        if (retryStatusBtn) {
            event.preventDefault();
            const actionUrl = retryStatusBtn.dataset.action;
            if (actionUrl) {
                fetchStatusHistory(actionUrl);
            }
            return;
        }

        // Close Renewal History modal buttons
        const renewalHistoryCloseBtn = event.target.closest('[data-project-hosting-renewal-history-modal-close]');
        if (renewalHistoryCloseBtn) {
            event.preventDefault();
            projectHostingRenewalHistoryModal.close();
            return;
        }

        // Close Status History modal buttons
        const statusHistoryCloseBtn = event.target.closest('[data-project-hosting-status-history-modal-close]');
        if (statusHistoryCloseBtn) {
            event.preventDefault();
            projectHostingStatusHistoryModal.close();
            return;
        }

        // Reset Filters Button
        const resetBtn = event.target.closest('[data-project-hosting-reset]');
        if (resetBtn) {
            event.preventDefault();
            resetAllFilters();
            return;
        }

        // Pagination buttons
        const pageBtn = event.target.closest('[data-project-hosting-pagination] button[data-page]');
        if (pageBtn) {
            event.preventDefault();
            const pageNum = parseInt(pageBtn.dataset.page, 10);
            if (pageNum && pageNum !== projectHostingState.activeFilters.page) {
                projectHostingState.activeFilters.page = pageNum;
                fetchHostingList();
            }
            return;
        }
    });

    // Delegated Change (for dropdown filters in filter bar)
    document.addEventListener('change', (event) => {
        const filterSelect = event.target.closest('[data-project-hosting-filter-bar] select[data-project-hosting-filter]');
        if (filterSelect) {
            const filterKey = filterSelect.dataset.projectHostingFilter;
            handleFilterChange(filterKey, filterSelect.value);
        }
    });

    // Delegated Input (for search keyword with debounce)
    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-project-hosting-filter="search"]')) {
            handleSearchInput(event.target.value);
        }
    });

    // Form submit
    document.addEventListener('submit', (event) => {
        if (event.target.matches('[data-project-hosting-form]')) {
            handleHostingFormSubmit(event);
        } else if (event.target.matches('[data-project-hosting-renewal-form]')) {
            handleRenewalFormSubmit(event);
        }
    });

    // Listen to delete alert completion event if dispatched
    document.addEventListener('ajax-form:rendered', (event) => {
        if (event.detail?.selector === '#project-hosting-history') {
            // After delete, reload summary cards
            fetchHostingList();
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
    projectHostingRenewalModal.initialize(root);
    projectHostingRenewalHistoryModal.initialize(root);
    projectHostingStatusHistoryModal.initialize(root);
    projectHostingDrawer.initialize(root);

    // Initialize TomSelect for the filter dropdowns if present
    const filterBar = root?.querySelector
        ? root.querySelector('[data-project-hosting-filter-bar]')
        : document.querySelector('[data-project-hosting-filter-bar]');
    if (filterBar) {
        initTomSelect(filterBar);
    }

    bindDelegatedEvents();
}

// Auto-init on DOMContentLoaded or tab switch
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initProjectHosting(document));
    } else {
        initProjectHosting(document);
    }

    // Tab activation event listeners (project-tabs.js dispatches 'project-tab:loaded')
    const handleTabSwitch = (event) => {
        if (event.detail?.tab === 'hosting') {
            const root = event.detail?.panel || document;
            initProjectHosting(root);
        } else if (event.detail?.tab && event.detail.tab !== 'hosting') {
            // Close hosting drawer if active when navigating away from hosting tab
            projectHostingDrawer.close();
        }
    };

    document.addEventListener('project-tab:loaded', handleTabSwitch);
    document.addEventListener('project-tab-loaded', handleTabSwitch);
}
