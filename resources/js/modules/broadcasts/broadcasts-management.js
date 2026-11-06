import Alert from '../../alert';
import { initTomSelect } from '../../components/tom-select';
import { initDatepicker } from '../../components/datepicker';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-broadcasts-root]');
    if (!root) return;

    const modal = root.querySelector('[data-broadcast-modal]');
    const modalContent = root.querySelector('[data-broadcast-modal-content]');
    const modalLoading = root.querySelector('[data-broadcast-modal-loading]');
    let activeController = null;

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const parseResponse = async (response) => {
        try {
            return await response.json();
        } catch (error) {
            return { status: false, message: response.status === 403 ? 'Unauthorized operation.' : 'Unexpected server error.' };
        }
    };

    const cleanupModalComponents = () => {
        modalContent.querySelectorAll('select, input').forEach((field) => {
            field.tomselect?.destroy();
            field._flatpickr?.destroy();
        });
    };

    const showModal = () => {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        activeController?.abort();
        activeController = null;
        cleanupModalComponents();
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        modalContent.classList.add('hidden');
        modalLoading.classList.remove('hidden');
        modalContent.replaceChildren();
        document.body.classList.remove('overflow-hidden');
    };

    const setAudienceVisibility = (form, clearInactive = false) => {
        const audience = form.querySelector('[data-broadcast-audience]');
        const teamField = form.querySelector('[data-broadcast-team-field]');
        const userField = form.querySelector('[data-broadcast-user-field]');
        const teamSelect = teamField?.querySelector('select');
        const userSelect = userField?.querySelector('select');
        const value = audience?.tomselect?.getValue() || audience?.value || 'all';

        teamField?.classList.toggle('hidden', value !== 'teams');
        userField?.classList.toggle('hidden', value !== 'users');

        if (!clearInactive) return;
        if (value !== 'teams') {
            teamSelect?.tomselect?.clear();
            if (teamSelect && !teamSelect.tomselect) teamSelect.value = '';
        }
        if (value !== 'users') {
            userSelect?.tomselect?.clear();
            if (userSelect && !userSelect.tomselect) userSelect.value = '';
        }
    };

    const initializeForm = () => {
        const form = modalContent.querySelector('[data-broadcast-form]');
        if (!form) return;

        initTomSelect(form);
        initDatepicker('.datepicker', {}, form);

        const editorElement = form.querySelector('[data-broadcast-editor]');
        let editor = null;
        if (editorElement && window.Quill) {
            editor = new window.Quill(editorElement, {
                theme: 'snow',
                placeholder: 'Write the broadcast content...',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link', 'blockquote', 'code-block', 'clean'],
                    ],
                },
            });
        }

        const audience = form.querySelector('[data-broadcast-audience]');
        audience?.addEventListener('change', () => setAudienceVisibility(form, true));
        setAudienceVisibility(form);

        const startDate = form.querySelector('[name="start_date"]');
        const endDate = form.querySelector('[name="end_date"]');
        const syncEndMinimum = () => endDate?._flatpickr?.set('minDate', startDate?.value || null);
        startDate?.addEventListener('change', syncEndMinimum);
        syncEndMinimum();

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitter = event.submitter || form.querySelector('[data-broadcast-submit]');
            const statusInput = form.querySelector('[data-broadcast-status-input]');
            const contentInput = form.querySelector('[data-broadcast-content-input]');
            const errorBox = form.querySelector('[data-broadcast-errors]');
            const errorList = form.querySelector('[data-broadcast-error-list]');

            if (submitter?.dataset.submitStatus && statusInput) {
                statusInput.value = submitter.dataset.submitStatus;
            }
            if (contentInput) {
                contentInput.value = editor ? editor.root.innerHTML : (editorElement?.innerHTML || '');
            }

            errorBox?.classList.add('hidden');
            if (errorList) errorList.textContent = '';

            const buttons = Array.from(form.querySelectorAll('[data-broadcast-submit]'));
            buttons.forEach((button) => {
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
            });
            const submitLabel = submitter?.querySelector('[data-submit-label]');
            const originalLabel = submitLabel?.textContent;
            if (submitLabel) submitLabel.textContent = 'Saving...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: new FormData(form),
                });
                const result = await parseResponse(response);

                if (response.ok && result.status) {
                    Alert.success(result.message || 'Broadcast saved successfully.');
                    closeModal();
                    window.location.reload();
                    return;
                }

                const messages = response.status === 422
                    ? Object.values(result.errors || {}).flat()
                    : [result.message || (response.status === 403 ? 'Unauthorized operation.' : 'Unexpected server error.')];
                if (errorList) errorList.textContent = messages.map((message) => `• ${message}`).join('\n');
                errorBox?.classList.remove('hidden');
                const uploadFailed = Object.keys(result.errors || {}).some((key) => key.startsWith('attachments'));
                Alert.error(response.status === 422 && !uploadFailed ? 'Validation failed.' : messages[0]);
            } catch (error) {
                Alert.error('Unexpected server error. Please try again.');
            } finally {
                buttons.forEach((button) => {
                    button.disabled = false;
                    button.classList.remove('opacity-60', 'cursor-not-allowed');
                });
                if (submitLabel && originalLabel) submitLabel.textContent = originalLabel;
            }
        });
    };

    const loadModal = async (url, formMode = false) => {
        showModal();
        cleanupModalComponents();
        modalContent.classList.add('hidden');
        modalContent.replaceChildren();
        modalLoading.classList.remove('hidden');
        activeController?.abort();
        activeController = new AbortController();

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                signal: activeController.signal,
            });
            const result = await parseResponse(response);
            if (!response.ok || !result.status || !result.html) {
                throw new Error(result.message || 'Unable to load the broadcast.');
            }

            modalContent.innerHTML = result.html;
            modalLoading.classList.add('hidden');
            modalContent.classList.remove('hidden');
            if (formMode) initializeForm();
        } catch (error) {
            if (error.name === 'AbortError') return;
            closeModal();
            Alert.error(error.message || 'Unable to load the broadcast.');
        } finally {
            activeController = null;
        }
    };

    root.addEventListener('click', async (event) => {
        const closeButton = event.target.closest('[data-broadcast-modal-close]');
        if (closeButton) {
            closeModal();
            return;
        }

        const createButton = event.target.closest('[data-broadcast-create]');
        if (createButton) {
            await loadModal(createButton.dataset.url, true);
            return;
        }

        const editButton = event.target.closest('[data-broadcast-edit]');
        if (editButton) {
            await loadModal(editButton.dataset.url, true);
            return;
        }

        const viewButton = event.target.closest('[data-broadcast-view]');
        if (viewButton) {
            await loadModal(viewButton.dataset.url);
            return;
        }

        const attachmentDelete = event.target.closest('[data-broadcast-attachment-delete]');
        if (attachmentDelete) {
            const confirmation = await Alert.confirm({
                title: 'Remove attachment?',
                text: 'This attachment will be permanently removed.',
                confirmText: 'Remove',
            });
            if (!confirmation.isConfirmed) return;

            attachmentDelete.disabled = true;
            try {
                const response = await fetch(attachmentDelete.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.status) throw new Error(result.message || 'Attachment removal failed.');
                attachmentDelete.closest('[data-attachment-item]')?.remove();
                Alert.success(result.message || 'Attachment removed successfully.');
            } catch (error) {
                attachmentDelete.disabled = false;
                Alert.error(error.message || 'Attachment removal failed.');
            }
            return;
        }

        const statusButton = event.target.closest('[data-broadcast-status]');
        if (statusButton) {
            const action = statusButton.dataset.action || 'update';
            const confirmation = await Alert.confirm({
                title: `${action.charAt(0).toUpperCase()}${action.slice(1)} broadcast?`,
                text: action === 'publish'
                    ? 'The broadcast will become available according to its audience and visibility dates.'
                    : 'The broadcast will no longer be visible to its audience.',
                confirmText: action === 'publish' ? 'Publish' : 'Archive',
            });
            if (!confirmation.isConfirmed) return;

            statusButton.disabled = true;
            try {
                const response = await fetch(statusButton.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ status: Number(statusButton.dataset.status) }),
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.status) {
                    const message = Object.values(result.errors || {}).flat()[0] || result.message || `Unable to ${action} broadcast.`;
                    throw new Error(message);
                }
                Alert.success(result.message);
                window.location.reload();
            } catch (error) {
                statusButton.disabled = false;
                Alert.error(error.message || `Unable to ${action} broadcast.`);
            }
            return;
        }

        const deleteButton = event.target.closest('[data-broadcast-delete]');
        if (deleteButton) {
            const confirmation = await Alert.confirm({
                title: 'Delete broadcast?',
                text: `The broadcast “${deleteButton.dataset.title || ''}” and its attachments will be permanently deleted.`,
                confirmText: 'Delete',
                confirmColor: '#ef4444',
            });
            if (!confirmation.isConfirmed) return;

            deleteButton.disabled = true;
            try {
                const response = await fetch(deleteButton.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.status) throw new Error(result.message || 'Unable to delete broadcast.');
                Alert.success(result.message);
                window.location.reload();
            } catch (error) {
                deleteButton.disabled = false;
                Alert.error(error.message || 'Unable to delete broadcast.');
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
});

