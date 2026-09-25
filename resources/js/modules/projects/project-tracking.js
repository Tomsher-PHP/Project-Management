const projectTrackingState = {
    initialized: false,
    listenersBound: false,
    formBound: false,
    attachmentHandlingBound: false,
};


/*
|--------------------------------------------------------------------------
| Create / Edit Modal
|--------------------------------------------------------------------------
*/

const projectTrackingModal = {
    modal: null,

    initialize(root = document) {
        this.modal = root?.querySelector
            ? root.querySelector('[data-project-tracking-modal]')
            : null;

        /*
         * The History tab can be loaded dynamically.
         * Always fall back to the document.
         */
        if (!this.modal) {
            this.modal = document.querySelector(
                '[data-project-tracking-modal]'
            );
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

        const closeButtons = this.modal.querySelectorAll(
            '[data-project-tracking-modal-close]'
        );

        closeButtons.forEach((button) => {
            if (
                button.dataset.projectTrackingModalBound === 'true'
            ) {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectTrackingModalBound = 'true';
        });
    },

    bindEscape() {
        if (!this.modal) {
            return;
        }

        if (
            this.modal.dataset.projectTrackingEscapeBound === 'true'
        ) {
            return;
        }

        document.addEventListener('keydown', (event) => {
            if (
                event.key !== 'Escape' ||
                !this.modal ||
                this.modal.classList.contains('hidden')
            ) {
                return;
            }

            this.close();
        });

        this.modal.dataset.projectTrackingEscapeBound = 'true';
    },

    open() {
        if (!this.modal) {
            return;
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

    reset() {
        if (!this.modal) {
            return;
        }

        const form = this.modal.querySelector(
            '[data-project-tracking-form]'
        );

        if (form) {
            form.reset();

            form.action =
                form.dataset.storeAction || form.action;

            /*
             * Remove all pending attachment deletion inputs.
             */
            form.querySelectorAll(
                '[data-project-tracking-remove-attachment]'
            ).forEach((input) => {
                input.remove();
            });
        }

        const method = this.modal.querySelector(
            '[data-project-tracking-method]'
        );

        if (method) {
            method.value = 'POST';
        }

        const trackingId = this.modal.querySelector(
            '[data-project-tracking-id]'
        );

        if (trackingId) {
            trackingId.value = '';
        }

        const date = this.modal.querySelector(
            '[data-project-tracking-date]'
        );

        if (date) {
            date.value = this.getToday();
        }

        const existingFiles = this.modal.querySelector(
            '[data-project-tracking-existing-files]'
        );

        if (existingFiles) {
            existingFiles.innerHTML = '';
            existingFiles.classList.add('hidden');
        }

        const selectedFiles = this.modal.querySelector(
            '[data-project-tracking-selected-files]'
        );

        if (selectedFiles) {
            selectedFiles.innerHTML = '';
        }

        const attachmentInput = this.modal.querySelector(
            '[data-project-tracking-attachments]'
        );

        if (attachmentInput) {
            attachmentInput.value = '';
        }

        this.clearErrors();
    },

    setCreateMode() {
        if (!this.modal) {
            return;
        }

        const title = this.modal.querySelector(
            '[data-project-tracking-modal-title]'
        );

        const description = this.modal.querySelector(
            '[data-project-tracking-modal-description]'
        );

        const saveButton = this.modal.querySelector(
            '[data-project-tracking-save]'
        );

        const form = this.modal.querySelector(
            '[data-project-tracking-form]'
        );

        const method = this.modal.querySelector(
            '[data-project-tracking-method]'
        );

        if (title) {
            title.textContent = 'Add Project Tracking';
        }

        if (description) {
            description.textContent =
                'Add a tracking update for this project.';
        }

        if (saveButton) {
            saveButton.textContent = 'Save';
        }

        if (form) {
            form.action =
                form.dataset.storeAction || form.action;
        }

        if (method) {
            method.value = 'POST';
        }
    },

    setEditMode() {
        if (!this.modal) {
            return;
        }

        const title = this.modal.querySelector(
            '[data-project-tracking-modal-title]'
        );

        const description = this.modal.querySelector(
            '[data-project-tracking-modal-description]'
        );

        const saveButton = this.modal.querySelector(
            '[data-project-tracking-save]'
        );

        const method = this.modal.querySelector(
            '[data-project-tracking-method]'
        );

        if (title) {
            title.textContent = 'Edit Project Tracking';
        }

        if (description) {
            description.textContent =
                'Update the tracking information for this project.';
        }

        if (saveButton) {
            saveButton.textContent = 'Update';
        }

        if (method) {
            method.value = 'PUT';
        }
    },

    setLoadingMode() {
        if (!this.modal) {
            return;
        }

        const title = this.modal.querySelector(
            '[data-project-tracking-modal-title]'
        );

        const description = this.modal.querySelector(
            '[data-project-tracking-modal-description]'
        );

        if (title) {
            title.textContent = 'Loading...';
        }

        if (description) {
            description.textContent =
                'Loading project tracking details...';
        }
    },

    showLoadError(message) {
        if (!this.modal) {
            return;
        }

        const title = this.modal.querySelector(
            '[data-project-tracking-modal-title]'
        );

        const description = this.modal.querySelector(
            '[data-project-tracking-modal-description]'
        );

        if (title) {
            title.textContent = 'Edit Project Tracking';
        }

        if (description) {
            description.innerHTML = `
                <span class="text-error-400">
                    ${escapeHtml(message)}
                </span>
            `;
        }
    },

    getToday() {
        const date = new Date();

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    },

    clearErrors() {
        if (!this.modal) {
            return;
        }

        const errorElements = this.modal.querySelectorAll(
            '[data-project-tracking-date-error], ' +
            '[data-project-tracking-title-error], ' +
            '[data-project-tracking-description-error], ' +
            '[data-project-tracking-attachment-error], ' +
            '[data-project-tracking-files-error]'
        );

        errorElements.forEach((element) => {
            element.textContent = '';
            element.classList.add('hidden');
        });
    },

    showErrors(errors = {}) {
        if (!this.modal) {
            return;
        }

        this.clearErrors();

        Object.entries(errors).forEach(([field, messages]) => {
            let selector = null;

            if (field === 'date') {
                selector =
                    '[data-project-tracking-date-error]';
            }

            if (field === 'title') {
                selector =
                    '[data-project-tracking-title-error]';
            }

            if (field === 'description') {
                selector =
                    '[data-project-tracking-description-error]';
            }

            if (
                field === 'attachments' ||
                field.startsWith('attachments.')
            ) {
                selector =
                    '[data-project-tracking-attachment-error]';
            }

            if (
                field === 'remove_attachments' ||
                field.startsWith('remove_attachments.')
            ) {
                selector =
                    '[data-project-tracking-attachment-error]';
            }

            if (!selector) {
                return;
            }

            const element = this.modal.querySelector(selector);

            if (!element) {
                return;
            }

            const message = Array.isArray(messages)
                ? messages[0]
                : messages;

            element.textContent = message;
            element.classList.remove('hidden');
        });
    },
};


/*
|--------------------------------------------------------------------------
| View Modal
|--------------------------------------------------------------------------
*/

const projectTrackingViewModal = {
    modal: null,
    content: null,

    initialize() {
        /*
         * The View modal MUST be outside the tracking list
         * because #project-tracking-history is replaced after
         * create/update/delete.
         */
        this.modal = document.querySelector(
            '[data-project-tracking-view-modal]'
        );

        if (!this.modal) {
            this.content = null;
            return;
        }

        this.content = this.modal.querySelector(
            '[data-project-tracking-view-content]'
        );

        this.bindCloseButtons();
        this.bindEscape();
    },

    bindCloseButtons() {
        if (!this.modal) {
            return;
        }

        const closeButtons = this.modal.querySelectorAll(
            '[data-project-tracking-view-modal-close]'
        );

        closeButtons.forEach((button) => {
            if (
                button.dataset.projectTrackingViewModalBound ===
                'true'
            ) {
                return;
            }

            button.addEventListener('click', () => {
                this.close();
            });

            button.dataset.projectTrackingViewModalBound =
                'true';
        });
    },

    bindEscape() {
        if (!this.modal) {
            return;
        }

        if (
            this.modal.dataset.projectTrackingViewEscapeBound ===
            'true'
        ) {
            return;
        }

        document.addEventListener('keydown', (event) => {
            if (
                event.key !== 'Escape' ||
                !this.modal ||
                this.modal.classList.contains('hidden')
            ) {
                return;
            }

            this.close();
        });

        this.modal.dataset.projectTrackingViewEscapeBound =
            'true';
    },

    open() {
        if (!this.modal) {
            return;
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

    loading() {
        if (!this.content) {
            return;
        }

        this.content.innerHTML = `
            <div class="flex items-center justify-center py-12">
                <span class="text-sm text-bgray-500 dark:text-bgray-400">
                    Loading...
                </span>
            </div>
        `;
    },

    setContent(html) {
        if (!this.content) {
            return;
        }

        this.content.innerHTML = html;
    },

    showError(message) {
        if (!this.content) {
            return;
        }

        this.content.innerHTML = `
            <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-4 text-sm text-error-400">
                ${escapeHtml(message)}
            </div>
        `;
    },
};


/*
|--------------------------------------------------------------------------
| Project Tracking Form
|--------------------------------------------------------------------------
*/

const projectTrackingForm = {
    initialize() {
        if (projectTrackingState.formBound) {
            return;
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest(
                '[data-project-tracking-form]'
            );

            if (!form) {
                return;
            }

            event.preventDefault();

            this.submit(form);
        });

        projectTrackingState.formBound = true;
    },

    async submit(form) {
        const submitButton = form.querySelector(
            '[data-project-tracking-save]'
        );

        const originalButtonText = submitButton
            ? submitButton.textContent
            : 'Save';

        if (submitButton) {
            submitButton.disabled = true;

            submitButton.textContent = 'Saving...';

            submitButton.classList.add(
                'opacity-70',
                'cursor-not-allowed'
            );
        }

        projectTrackingModal.clearErrors();

        try {
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const data = await parseResponse(response);

            /*
             * Laravel validation error.
             */
            if (response.status === 422) {
                projectTrackingModal.showErrors(
                    data.errors || {}
                );

                if (data.message) {
                    showProjectTrackingMessage(
                        data.message,
                        'error'
                    );
                }

                return;
            }

            if (!response.ok) {
                throw new Error(
                    data.message ||
                    'Unable to save the project tracking.'
                );
            }

            if (!data.success) {
                throw new Error(
                    data.message ||
                    'Unable to save the project tracking.'
                );
            }

            /*
             * Refresh only the tracking list.
             */
            if (data.html) {
                replaceTrackingHistory(data.html);
            }

            projectTrackingModal.close();
            projectTrackingModal.reset();
            projectTrackingModal.setCreateMode();

            showProjectTrackingMessage(
                data.message ||
                'Project tracking saved successfully.'
            );

        } catch (error) {
            console.error(
                'Project tracking save error:',
                error
            );

            showProjectTrackingMessage(
                error.message ||
                'Unable to save the project tracking.',
                'error'
            );

        } finally {
            if (submitButton) {
                submitButton.disabled = false;

                submitButton.textContent =
                    originalButtonText || 'Save';

                submitButton.classList.remove(
                    'opacity-70',
                    'cursor-not-allowed'
                );
            }
        }
    },
};


/*
|--------------------------------------------------------------------------
| Initialize Project Tracking
|--------------------------------------------------------------------------
*/

const initializeProjectTracking = (root = document) => {
    projectTrackingModal.initialize(root);

    projectTrackingViewModal.initialize();

    initializeAttachmentHandling();

    projectTrackingForm.initialize();

    bindProjectTrackingActions();

    projectTrackingState.initialized = true;
};


/*
|--------------------------------------------------------------------------
| Attachment Handling
|--------------------------------------------------------------------------
*/

const initializeAttachmentHandling = () => {
    const attachmentInput = document.querySelector(
        '[data-project-tracking-attachments]'
    );

    const selectedFilesList = document.querySelector(
        '[data-project-tracking-selected-files]'
    );

    const attachmentError = document.querySelector(
        '[data-project-tracking-attachment-error]'
    );

    if (!attachmentInput) {
        return;
    }

    if (
        attachmentInput.dataset.projectTrackingFilesBound ===
        'true'
    ) {
        return;
    }

    attachmentInput.addEventListener('change', () => {
        const files = Array.from(
            attachmentInput.files || []
        );

        if (attachmentError) {
            attachmentError.classList.add('hidden');
            attachmentError.textContent = '';
        }

        /*
         * Maximum 5 new files selected at once.
         *
         * Backend also checks the total:
         * remaining existing + new files <= 5.
         */
        if (files.length > 5) {
            if (attachmentError) {
                attachmentError.textContent =
                    'You can upload a maximum of 5 attachments.';

                attachmentError.classList.remove('hidden');
            }

            attachmentInput.value = '';

            if (selectedFilesList) {
                selectedFilesList.innerHTML = '';
            }

            return;
        }

        /*
         * Maximum 15 MB per file.
         */
        const oversizedFile = files.find(
            (file) =>
                file.size > 15 * 1024 * 1024
        );

        if (oversizedFile) {
            if (attachmentError) {
                attachmentError.textContent =
                    `"${oversizedFile.name}" exceeds the 15MB file size limit.`;

                attachmentError.classList.remove('hidden');
            }

            attachmentInput.value = '';

            if (selectedFilesList) {
                selectedFilesList.innerHTML = '';
            }

            return;
        }

        if (selectedFilesList) {
            selectedFilesList.innerHTML = files
                .map(
                    (file) => `
                        <div class="rounded-full bg-success-50 px-3 py-1 text-sm text-success-400">
                            ${escapeHtml(file.name)}
                        </div>
                    `
                )
                .join('');
        }
    });

    attachmentInput.dataset.projectTrackingFilesBound =
        'true';
};


/*
|--------------------------------------------------------------------------
| Event Handlers
|--------------------------------------------------------------------------
*/

const bindProjectTrackingActions = () => {
    if (projectTrackingState.listenersBound) {
        return;
    }

    /*
     * CREATE
     */
    document.addEventListener('click', (event) => {
        const createButton = event.target.closest(
            '[data-project-tracking-modal-open]'
        );

        if (!createButton) {
            return;
        }

        event.preventDefault();

        projectTrackingModal.initialize(document);

        if (!projectTrackingModal.modal) {
            console.error(
                'Project tracking create/edit modal was not found.'
            );

            return;
        }

        projectTrackingModal.reset();
        projectTrackingModal.setCreateMode();
        projectTrackingModal.open();
    });


    /*
     * VIEW
     */
    document.addEventListener('click', async (event) => {
        const viewButton = event.target.closest(
            '[data-project-tracking-view]'
        );

        if (!viewButton) {
            return;
        }

        event.preventDefault();

        const url = viewButton.getAttribute('href');

        if (!url) {
            console.error(
                'Project tracking view URL was not found.'
            );

            return;
        }

        projectTrackingViewModal.initialize();

        if (!projectTrackingViewModal.modal) {
            console.error(
                'Project tracking view modal was not found. ' +
                'Make sure [data-project-tracking-view-modal] ' +
                'exists outside #project-tracking-history.'
            );

            return;
        }

        await loadTrackingView(url);
    });


    /*
     * EDIT
     */
    document.addEventListener('click', async (event) => {
        const editButton = event.target.closest(
            '[data-project-tracking-edit]'
        );

        if (!editButton) {
            return;
        }

        event.preventDefault();

        const url = editButton.getAttribute('href');

        if (!url) {
            console.error(
                'Project tracking edit URL was not found.'
            );

            return;
        }

        projectTrackingModal.initialize(document);

        if (!projectTrackingModal.modal) {
            console.error(
                'Project tracking edit modal was not found.'
            );

            return;
        }

        await loadTrackingForEdit(url);
    });


    /*
     * DELETE
     */
    /*
 * DELETE
 */
document.addEventListener(
    'click',
    async (event) => {
        const deleteButton = event.target.closest(
            '[data-project-tracking-delete]'
        );

        if (!deleteButton) {
            return;
        }

        /*
         * Prevent the normal button/form action.
         */
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const form = deleteButton.closest(
            '.project-tracking-delete-form'
        );

        if (!form) {
            console.error(
                'Project tracking delete form was not found.'
            );

            return;
        }

        try {
            const result = await Alert.confirm({
                title: 'Delete Project Tracking?',
                text: 'Are you sure you want to delete this project tracking entry? This action cannot be undone.',
                confirmText: 'Yes, delete',
                cancelText: 'Cancel',
            });

            /*
             * Alert.confirm() may return either:
             *
             * 1. true / false
             * 2. SweetAlert-style result:
             *    { isConfirmed: true/false, ... }
             *
             * Handle both formats safely.
             */
            const confirmed =
                result === true ||
                result?.isConfirmed === true;

            /*
             * CANCEL / DISMISS
             *
             * Absolutely do not call deleteTracking().
             */
            if (!confirmed) {
                console.log(
                    'Project tracking deletion cancelled.'
                );

                return;
            }

            /*
             * Only confirmed deletion reaches here.
             */
            await deleteTracking(
                form,
                deleteButton
            );

        } catch (error) {
            console.error(
                'Project tracking confirmation error:',
                error
            );
        }
    },
    true
);


    /*
     * REMOVE EXISTING ATTACHMENT
     *
     * This is delegated because attachment rows are
     * generated dynamically when Edit is opened.
     */
    document.addEventListener('click', (event) => {
        const removeButton = event.target.closest(
            '[data-project-tracking-remove-existing]'
        );

        if (!removeButton) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        removeExistingTrackingAttachment(
            removeButton
        );
    });


    projectTrackingState.listenersBound = true;
};


/*
|--------------------------------------------------------------------------
| View Tracking
|--------------------------------------------------------------------------
*/

const loadTrackingView = async (url) => {
    projectTrackingViewModal.loading();
    projectTrackingViewModal.open();

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        const data = await parseResponse(response);

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                'Unable to load the project tracking.'
            );
        }

        projectTrackingViewModal.setContent(
            data.html || ''
        );

    } catch (error) {
        console.error(
            'Project tracking view error:',
            error
        );

        projectTrackingViewModal.showError(
            error.message ||
            'Unable to load the project tracking.'
        );
    }
};


/*
|--------------------------------------------------------------------------
| Load Tracking For Edit
|--------------------------------------------------------------------------
*/

const loadTrackingForEdit = async (url) => {
    projectTrackingModal.reset();

    projectTrackingModal.setEditMode();

    projectTrackingModal.setLoadingMode();

    projectTrackingModal.open();

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        const data = await parseResponse(response);

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                'Unable to load the project tracking.'
            );
        }

        const tracking =
            data.tracking ||
            data.projectTracking ||
            data.data;

        if (!tracking) {
            throw new Error(
                'Project tracking details were not returned.'
            );
        }

        populateTrackingForm(tracking);

        projectTrackingModal.setEditMode();

    } catch (error) {
        console.error(
            'Project tracking edit load error:',
            error
        );

        projectTrackingModal.showLoadError(
            error.message ||
            'Unable to load the project tracking.'
        );
    }
};


/*
|--------------------------------------------------------------------------
| Populate Edit Form
|--------------------------------------------------------------------------
*/

const populateTrackingForm = (tracking) => {
    const modal = projectTrackingModal.modal;

    if (!modal) {
        return;
    }

    const trackingId = modal.querySelector(
        '[data-project-tracking-id]'
    );

    const date = modal.querySelector(
        '[data-project-tracking-date]'
    );

    const title = modal.querySelector(
        '[data-project-tracking-title]'
    );

    const description = modal.querySelector(
        '[data-project-tracking-description]'
    );

    const form = modal.querySelector(
        '[data-project-tracking-form]'
    );

    const method = modal.querySelector(
        '[data-project-tracking-method]'
    );

    if (trackingId) {
        trackingId.value = tracking.id || '';
    }

    if (date) {
        date.value = formatTrackingDate(
            tracking.date
        );
    }

    if (title) {
        title.value = tracking.title || '';
    }

    if (description) {
        description.value =
            tracking.description || '';
    }

    if (method) {
        method.value = 'PUT';
    }

    if (form) {
        if (tracking.update_url) {
            form.action = tracking.update_url;
        } else if (tracking.updateUrl) {
            form.action = tracking.updateUrl;
        }
    }

    renderExistingAttachments(
        tracking.attachments || []
    );
};


/*
|--------------------------------------------------------------------------
| Existing Attachments
|--------------------------------------------------------------------------
*/

const renderExistingAttachments = (attachments = []) => {
    const container = document.querySelector(
        '[data-project-tracking-existing-files]'
    );

    if (!container) {
        console.error(
            'Project tracking existing attachment container was not found.'
        );

        return;
    }

    /*
     * Clear previous attachment rows.
     */
    container.innerHTML = '';

    /*
     * Show the existing attachment area.
     */
    container.classList.remove('hidden');

    if (!attachments.length) {
        container.innerHTML = `
            <p class="text-sm text-bgray-500 dark:text-bgray-400">
                No existing attachments.
            </p>
        `;

        return;
    }

    const wrapper = document.createElement('div');

    wrapper.className = 'space-y-2';

    attachments.forEach((attachment) => {
        const item = document.createElement('div');

        /*
         * IMPORTANT:
         *
         * This is the exact selector used by
         * removeExistingTrackingAttachment().
         */
        item.setAttribute(
            'data-project-tracking-attachment-item',
            ''
        );

        item.setAttribute(
            'data-attachment-id',
            attachment.id
        );

        item.className =
            'flex items-center justify-between gap-3 rounded-lg ' +
            'border border-bgray-200 px-3 py-2 ' +
            'dark:border-darkblack-400';

        item.innerHTML = `
            <div class="flex min-w-0 items-center gap-3">
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md
                           bg-bgray-100 dark:bg-darkblack-500"
                >
                    <svg
                        class="h-4 w-4 text-bgray-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 101.414 1.414L16.586 8.414a4 4 0 00-5.657-5.657L4.343 9.343a6 6 0 108.485 8.485L19 11.657"
                        />
                    </svg>
                </div>

                <a
                    href="${escapeHtml(attachment.url || '#')}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="min-w-0 truncate text-sm font-medium
                           text-bgray-700 hover:text-success-400
                           dark:text-bgray-200"
                    title="${escapeHtml(
                        attachment.original_name ||
                        attachment.file_name ||
                        ''
                    )}"
                >
                    ${escapeHtml(
                        attachment.original_name ||
                        attachment.file_name ||
                        'Attachment'
                    )}
                </a>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-md px-2 py-1 text-sm
                       font-medium text-red-500
                       transition hover:bg-red-50 hover:text-red-600
                       dark:hover:bg-red-500/10"
                data-project-tracking-remove-existing
                data-attachment-id="${attachment.id}"
            >
                Remove
            </button>
        `;

        wrapper.appendChild(item);
    });

    container.appendChild(wrapper);
};


/*
|--------------------------------------------------------------------------
| Remove Existing Attachment
|--------------------------------------------------------------------------
*/

const removeExistingTrackingAttachment = (button) => {
    if (!button) {
        return;
    }

    const attachmentId =
        button.getAttribute('data-attachment-id');

    if (!attachmentId) {
        console.error(
            'Project tracking attachment ID was not found.'
        );

        return;
    }

    /*
     * Find the complete attachment row.
     *
     * This MUST match the attribute created inside
     * renderExistingAttachments().
     */
    const attachmentItem = button.closest(
        '[data-project-tracking-attachment-item]'
    );

    /*
     * Find the form.
     *
     * Use the modal first so another form elsewhere on
     * the page cannot accidentally be selected.
     */
    const modal = projectTrackingModal.modal;

    const form = modal
        ? modal.querySelector(
            '[data-project-tracking-form]'
        )
        : document.querySelector(
            '[data-project-tracking-form]'
        );

    if (!form) {
        console.error(
            'Project tracking form was not found.'
        );

        return;
    }

    /*
     * Check whether this attachment has already been
     * marked for removal.
     */
    const existingInput = form.querySelector(
        `[data-project-tracking-remove-attachment="${attachmentId}"]`
    );

    if (existingInput) {
        /*
         * If it was already marked for removal, simply
         * make sure the row is gone from the UI.
         */
        if (attachmentItem) {
            attachmentItem.remove();
        }

        return;
    }

    /*
     * Create hidden field:
     *
     * remove_attachments[]=123
     *
     * This is submitted only when Update is clicked.
     */
    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = 'remove_attachments[]';
    input.value = attachmentId;

    input.setAttribute(
        'data-project-tracking-remove-attachment',
        attachmentId
    );

    form.appendChild(input);

    /*
     * NOW remove the entire row from the UI.
     *
     * This is intentionally done AFTER the hidden input
     * is added so the server still knows which attachment
     * should be deleted when the form is submitted.
     */
    if (attachmentItem) {
        attachmentItem.remove();
    } else {
        /*
         * Fallback in case the data-project-tracking-
         * attachment-item attribute is missing for any reason.
         */
        const fallbackItem = button.closest(
            '[data-attachment-id]'
        );

        if (fallbackItem) {
            fallbackItem.remove();
        }
    }

    /*
     * If there are no attachment rows remaining,
     * hide the existing attachment container.
     */
    const existingFilesContainer = modal
        ? modal.querySelector(
            '[data-project-tracking-existing-files]'
        )
        : document.querySelector(
            '[data-project-tracking-existing-files]'
        );

    if (existingFilesContainer) {
        const remainingItems =
            existingFilesContainer.querySelectorAll(
                '[data-project-tracking-attachment-item]'
            );

        if (!remainingItems.length) {
            existingFilesContainer.classList.add('hidden');
        }
    }
};


/*
|--------------------------------------------------------------------------
| Delete Tracking
|--------------------------------------------------------------------------
*/

// const handleDeleteTracking = async (
//     form,
//     deleteButton
// ) => {
//     const confirmed = await Alert.confirm({
//         title: 'Delete Project Tracking?',
//         text: 'Are you sure you want to delete this project tracking entry? This action cannot be undone.',
//         confirmText: 'Yes, delete',
//         cancelText: 'Cancel',
//     });

//     if (!confirmed) {
//         return;
//     }

//     await deleteTracking(
//         form,
//         deleteButton
//     );
// };


const deleteTracking = async (
    form,
    deleteButton
) => {
    const originalDisabled =
        deleteButton?.disabled ?? false;

    if (deleteButton) {
        deleteButton.disabled = true;

        deleteButton.classList.add(
            'opacity-50',
            'cursor-not-allowed'
        );
    }

    try {
        const formData = new FormData(form);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        const data = await parseResponse(response);

        if (!response.ok || !data.success) {
            throw new Error(
                data.message ||
                'Unable to delete the project tracking.'
            );
        }

        if (data.html) {
            replaceTrackingHistory(data.html);
        }

        showProjectTrackingMessage(
            data.message ||
            'Project tracking deleted successfully.'
        );

    } catch (error) {
        console.error(
            'Project tracking delete error:',
            error
        );

        showProjectTrackingMessage(
            error.message ||
            'Unable to delete the project tracking.',
            'error'
        );

    } finally {
        if (deleteButton) {
            deleteButton.disabled =
                originalDisabled;

            deleteButton.classList.remove(
                'opacity-50',
                'cursor-not-allowed'
            );
        }
    }
};


/*
|--------------------------------------------------------------------------
| Replace Tracking History
|--------------------------------------------------------------------------
*/

const replaceTrackingHistory = (html) => {
    const currentHistory = document.querySelector(
        '#project-tracking-history'
    );

    if (!currentHistory) {
        return;
    }

    const temporaryContainer =
        document.createElement('div');

    temporaryContainer.innerHTML =
        html.trim();

    const replacement =
        temporaryContainer.querySelector(
            '#project-tracking-history'
        );

    if (!replacement) {
        console.error(
            'Updated project tracking history container was not found.'
        );

        return;
    }

    currentHistory.replaceWith(
        replacement
    );
};


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const formatTrackingDate = (date) => {
    if (!date) {
        return '';
    }

    if (typeof date === 'string') {
        return date.substring(0, 10);
    }

    return '';
};


const parseResponse = async (response) => {
    const contentType =
        response.headers.get('content-type') || '';

    if (
        contentType.includes(
            'application/json'
        )
    ) {
        return await response.json();
    }

    const text =
        await response.text();

    return {
        success: response.ok,
        html: text,
        message: response.ok
            ? ''
            : 'The server returned an unexpected response.',
    };
};


const showProjectTrackingMessage = (
    message,
    type = 'success'
) => {
    if (
        typeof window.showAlert === 'function'
    ) {
        window.showAlert(
            message,
            type
        );

        return;
    }

    if (
        typeof window.showToast === 'function'
    ) {
        window.showToast(
            message,
            type
        );

        return;
    }

    if (type === 'error') {
        console.error(message);
    } else {
        console.log(message);
    }
};


const escapeHtml = (value) => {
    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
};


/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initializeProjectTracking();
    }
);


/*
|--------------------------------------------------------------------------
| History Tab Loaded Dynamically
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'project-tab:loaded',
    (event) => {
        if (
            event.detail?.tab !== 'history'
        ) {
            return;
        }

        initializeProjectTracking(
            event.detail.panel
        );
    }
);
