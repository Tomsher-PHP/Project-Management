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
    let pendingFiles = [];
    const fileUrls = new Map();

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const parseResponse = async (response) => {
        try {
            return await response.json();
        } catch (error) {
            return { status: false, message: response.status === 403 ? 'Unauthorized operation.' : 'Unexpected server error.' };
        }
    };

    const closeAllBroadcastStatusMenus = () => {
        document.querySelectorAll('[data-broadcast-status-dropdown]').forEach((dropdown) => {
            const menu = dropdown.querySelector('[data-broadcast-status-menu]');
            if (menu) {
                menu.classList.add('hidden');
            }
            dropdown.querySelector('[data-broadcast-status-trigger]')?.setAttribute('aria-expanded', 'false');
        });
    };

    const positionBroadcastStatusMenu = (trigger, menu) => {
        if (!trigger || !menu) return;

        const triggerRect = trigger.getBoundingClientRect();
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const gutter = 8;
        menu.style.position = 'fixed';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';
        menu.style.top = '0px';
        menu.style.left = '0px';
        menu.style.minWidth = `${Math.round(Math.max(150, triggerRect.width))}px`;
        menu.style.width = 'max-content';

        const measuredRect = menu.getBoundingClientRect();
        const menuWidth = Math.max(measuredRect.width || 0, 150);
        const menuHeight = measuredRect.height || 0;

        let left = triggerRect.left;
        let top = triggerRect.bottom + 4;

        if (left < gutter) {
            left = gutter;
        }
        if ((left + menuWidth) > (viewportWidth - gutter)) {
            left = Math.max(gutter, viewportWidth - menuWidth - gutter);
        }
        if ((top + menuHeight) > (viewportHeight - gutter)) {
            top = Math.max(gutter, triggerRect.top - menuHeight - 4);
        }

        menu.style.left = `${Math.round(left)}px`;
        menu.style.top = `${Math.round(top)}px`;
    };

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-broadcast-status-dropdown]')) {
            closeAllBroadcastStatusMenus();
        }
    });
    window.addEventListener('scroll', closeAllBroadcastStatusMenus, true);
    window.addEventListener('resize', closeAllBroadcastStatusMenus);

    const revokeAllFileUrls = () => {
        fileUrls.forEach((url) => URL.revokeObjectURL(url));
        fileUrls.clear();
        pendingFiles = [];
    };

    const cleanupModalComponents = () => {
        revokeAllFileUrls();
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

    modal?.addEventListener('click', (event) => {
        const insideModalContent = event.target.closest('[data-broadcast-modal-content]')
            || event.target.closest('[data-broadcast-modal-loading]');
        const isCloseButton = event.target.closest('[data-broadcast-modal-close]');

        if (isCloseButton || !insideModalContent) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

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

    const isQuillEmpty = (quillInstance) => {
        if (!quillInstance) return true;
        const text = quillInstance.getText().trim();
        const hasMedia = !!quillInstance.root.querySelector('img, video, iframe');
        return text.length === 0 && !hasMedia;
    };

    const initializeForm = () => {
        const form = modalContent.querySelector('[data-broadcast-form]');
        if (!form) return;

        pendingFiles = [];
        revokeAllFileUrls();

        initTomSelect(form);

        const dateRangeInput = form.querySelector('#broadcast_date_range');
        if (dateRangeInput && !dateRangeInput.dataset.minDate) {
            const today = new Date().toISOString().split('T')[0];
            dateRangeInput.dataset.minDate = today;
        }

        initDatepicker('.datepicker', {}, form);

        if (dateRangeInput?._flatpickr && dateRangeInput.dataset.minDate) {
            dateRangeInput._flatpickr.set('minDate', dateRangeInput.dataset.minDate);
        }

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

        const restoreMultiSelectValues = (selectEl) => {
            if (!selectEl) return;
            const instance = selectEl.tomselect;
            let rawSelected = [];
            if (selectEl.dataset.selectedIds) {
                try {
                    rawSelected = JSON.parse(selectEl.dataset.selectedIds);
                } catch (e) {
                    rawSelected = [];
                }
            }
            if (!rawSelected.length) {
                rawSelected = Array.from(selectEl.options)
                    .filter((opt) => opt.selected && opt.value)
                    .map((opt) => String(opt.value));
            }
            const values = (Array.isArray(rawSelected) ? rawSelected : [rawSelected])
                .map((v) => String(v))
                .filter(Boolean);

            if (values.length && instance) {
                const current = instance.getValue();
                const currentArr = Array.isArray(current) ? current.map(String) : (current ? [String(current)] : []);
                const isSynced = values.every((v) => currentArr.includes(v));
                if (!isSynced) {
                    instance.setValue(values, true);
                }
            }
        };

        const teamSelect = form.querySelector('#broadcast_team_ids');
        const userSelect = form.querySelector('#broadcast_user_ids');
        restoreMultiSelectValues(teamSelect);
        restoreMultiSelectValues(userSelect);
        setTimeout(() => {
            restoreMultiSelectValues(teamSelect);
            restoreMultiSelectValues(userSelect);
        }, 10);

        const audience = form.querySelector('[data-broadcast-audience]');
        let previousAudience = audience?.tomselect?.getValue() || audience?.value || 'all';
        const handleAudienceChange = () => {
            const currentAudience = audience?.tomselect?.getValue() || audience?.value || 'all';
            if (currentAudience !== previousAudience) {
                previousAudience = currentAudience;
                setAudienceVisibility(form, true);
            }
        };

        audience?.addEventListener('change', handleAudienceChange);
        if (audience?.tomselect) {
            audience.tomselect.on('change', handleAudienceChange);
        }
        setAudienceVisibility(form, false);

        // Attachments management
        const attachmentsInput = form.querySelector('[data-broadcast-attachments-input]');
        const selectedFilesContainer = form.querySelector('[data-selected-broadcast-files]');

        const getFileUrl = (file) => {
            if (!fileUrls.has(file)) {
                fileUrls.set(file, URL.createObjectURL(file));
            }
            return fileUrls.get(file);
        };

        const syncInputFiles = () => {
            if (!attachmentsInput) return;
            try {
                const dt = new DataTransfer();
                pendingFiles.forEach((file) => dt.items.add(file));
                attachmentsInput.files = dt.files;
            } catch (e) {
                // DataTransfer fallback
            }
            if (!pendingFiles.length) {
                attachmentsInput.value = '';
            }
        };

        const renderSelectedFiles = () => {
            if (!selectedFilesContainer) return;

            if (!pendingFiles.length) {
                selectedFilesContainer.innerHTML = '';
                return;
            }

            selectedFilesContainer.innerHTML = pendingFiles.map((file, index) => {
                const url = getFileUrl(file);
                const sizeKb = (file.size / 1024).toFixed(1);
                const isImage = file.type.startsWith('image/');

                return `
                    <div class="inline-flex items-center gap-2 rounded-full bg-success-50 border border-success-200 pl-3 pr-2 py-1 text-xs font-medium text-success-500 dark:bg-success-300/10 dark:border-success-300/30 dark:text-success-300">
                        <a href="${url}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 hover:underline truncate max-w-[220px]" title="Click to view ${file.name}">
                            ${isImage
                                ? `<img src="${url}" alt="${file.name}" class="h-4 w-4 rounded-full object-cover shrink-0" />`
                                : `<svg class="h-3.5 w-3.5 shrink-0 text-success-500 dark:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                   </svg>`
                            }
                            <span class="truncate">${file.name}</span>
                            <span class="text-[11px] opacity-75 shrink-0">(${sizeKb} KB)</span>
                        </a>
                        <button type="button" class="remove-pending-file-btn p-0.5 font-bold hover:text-red-500 focus:outline-none transition leading-none ml-1 text-base text-success-600 dark:text-success-400 dark:hover:text-red-400" data-file-index="${index}" title="Remove file" aria-label="Remove ${file.name}">
                            &times;
                        </button>
                    </div>
                `;
            }).join('');
        };

        attachmentsInput?.addEventListener('change', () => {
            const newlySelected = Array.from(attachmentsInput.files || []);
            if (!newlySelected.length) return;

            const allowedExtensions = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
            const maxBytes = 15 * 1024 * 1024;
            const validNewFiles = [];

            for (const file of newlySelected) {
                const ext = file.name.split('.').pop()?.toLowerCase() || '';
                if (!allowedExtensions.includes(ext)) {
                    clearFieldError('attachments');
                    setFieldError('attachments', `File "${file.name}" has an unsupported format. Allowed types: ${allowedExtensions.join(', ')}.`);
                    continue;
                }
                if (file.size > maxBytes) {
                    clearFieldError('attachments');
                    setFieldError('attachments', `File "${file.name}" exceeds the 15 MB size limit.`);
                    continue;
                }
                const alreadySelected = pendingFiles.some((f) => f.name === file.name && f.size === file.size);
                if (!alreadySelected) {
                    validNewFiles.push(file);
                }
            }

            if (pendingFiles.length + validNewFiles.length > 5) {
                setFieldError('attachments', 'You can upload a maximum of 5 files at a time.');
                const remainingSlots = Math.max(0, 5 - pendingFiles.length);
                pendingFiles.push(...validNewFiles.slice(0, remainingSlots));
            } else {
                clearFieldError('attachments');
                pendingFiles.push(...validNewFiles);
            }

            syncInputFiles();
            renderSelectedFiles();
        });

        selectedFilesContainer?.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-pending-file-btn');
            if (!removeBtn) return;
            e.preventDefault();
            const index = parseInt(removeBtn.dataset.fileIndex, 10);
            if (!isNaN(index) && index >= 0 && index < pendingFiles.length) {
                const removed = pendingFiles.splice(index, 1)[0];
                if (removed && fileUrls.has(removed)) {
                    URL.revokeObjectURL(fileUrls.get(removed));
                    fileUrls.delete(removed);
                }
                syncInputFiles();
                renderSelectedFiles();
                clearFieldError('attachments');
            }
        });

        // Field error handling
        const clearFormErrors = () => {
            form.querySelectorAll('[data-error-for]').forEach((el) => {
                el.textContent = '';
                el.classList.add('hidden');
                el.classList.remove('block');
            });
            form.querySelectorAll('.border-red-500').forEach((el) => {
                el.classList.remove('border-red-500');
            });
            form.querySelectorAll('.ts-control').forEach((el) => {
                el.classList.remove('border-red-500');
            });
        };

        const clearFieldError = (fieldKey) => {
            const normalizedKey = fieldKey.replace(/\.\d+$/, '');
            let errorEl = form.querySelector(`[data-error-for="${normalizedKey}"]`)
                || form.querySelector(`[data-error-for="${fieldKey}"]`);

            if (!errorEl && (normalizedKey === 'start_date' || normalizedKey === 'end_date')) {
                errorEl = form.querySelector('[data-error-for="date_range"]');
            }

            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
                errorEl.classList.remove('block');
            }

            let input = form.querySelector(`[name="${fieldKey}"]`)
                || form.querySelector(`[name="${normalizedKey}"]`)
                || form.querySelector(`[name="${normalizedKey}[]"]`);

            if (!input && (normalizedKey === 'start_date' || normalizedKey === 'end_date')) {
                input = form.querySelector('[name="date_range"]');
            }

            if (input) {
                input.classList.remove('border-red-500');
                if (input.tomselect?.control) {
                    input.tomselect.control.classList.remove('border-red-500');
                }
            } else if (normalizedKey === 'content') {
                form.querySelector('[data-broadcast-editor]')?.classList.remove('border-red-500');
            }
        };

        const setFieldError = (fieldKey, message) => {
            const normalizedKey = fieldKey.replace(/\.\d+$/, '');
            let errorEl = form.querySelector(`[data-error-for="${normalizedKey}"]`)
                || form.querySelector(`[data-error-for="${fieldKey}"]`);

            if (!errorEl && (normalizedKey === 'start_date' || normalizedKey === 'end_date')) {
                errorEl = form.querySelector('[data-error-for="date_range"]');
            }

            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
                errorEl.classList.add('block');
            }

            let input = form.querySelector(`[name="${fieldKey}"]`)
                || form.querySelector(`[name="${normalizedKey}"]`)
                || form.querySelector(`[name="${normalizedKey}[]"]`);

            if (!input && (normalizedKey === 'start_date' || normalizedKey === 'end_date')) {
                input = form.querySelector('[name="date_range"]');
            }

            if (input) {
                input.classList.add('border-red-500');
                if (input.tomselect?.control) {
                    input.tomselect.control.classList.add('border-red-500');
                }
            } else if (normalizedKey === 'content') {
                form.querySelector('[data-broadcast-editor]')?.classList.add('border-red-500');
            }
        };

        // Real-time error clearing when user interacts with fields
        form.addEventListener('input', (e) => {
            const name = e.target.name?.replace(/\[\]$/, '');
            if (name) {
                clearFieldError(name);
                if (name === 'date_range') {
                    clearFieldError('start_date');
                    clearFieldError('end_date');
                }
            }
        });

        form.addEventListener('change', (e) => {
            const name = e.target.name?.replace(/\[\]$/, '');
            if (name) {
                clearFieldError(name);
                if (name === 'date_range') {
                    clearFieldError('start_date');
                    clearFieldError('end_date');
                }
            }
        });

        editor?.on('text-change', () => {
            if (!isQuillEmpty(editor)) {
                clearFieldError('content');
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitter = event.submitter || form.querySelector('[data-broadcast-submit]');
            const statusInput = form.querySelector('[data-broadcast-status-input]');
            const contentInput = form.querySelector('[data-broadcast-content-input]');

            if (submitter?.dataset.submitStatus && statusInput) {
                statusInput.value = submitter.dataset.submitStatus;
            }

            const rawContent = editor ? editor.root.innerHTML : (editorElement?.innerHTML || '');
            const contentEmpty = editor ? isQuillEmpty(editor) : (rawContent.trim() === '' || rawContent === '<p><br></p>');

            if (contentInput) {
                contentInput.value = contentEmpty ? '' : rawContent;
            }

            clearFormErrors();

            // Client-side content validation
            if (contentEmpty) {
                setFieldError('content', 'The content field is required.');
                form.querySelector('[data-broadcast-editor]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

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

                if (response.status === 422 && result.errors) {
                    let firstErrorTarget = null;
                    Object.entries(result.errors).forEach(([fieldKey, messages]) => {
                        const message = Array.isArray(messages) ? messages[0] : messages;
                        setFieldError(fieldKey, message);
                        if (!firstErrorTarget) {
                            const normalized = fieldKey.replace(/\.\d+$/, '');
                            firstErrorTarget = form.querySelector(`[data-error-for="${normalized}"]`)
                                || form.querySelector(`[name="${normalized}"]`)
                                || form.querySelector(`[name="${normalized}[]"]`);
                        }
                    });
                    firstErrorTarget?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    Alert.error(result.message || (response.status === 403 ? 'Unauthorized operation.' : 'Unexpected server error.'));
                }
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

        // Focus title field when creating a new broadcast
        if (form.dataset.mode === 'create') {
            window.setTimeout(() => {
                form.querySelector('#broadcast_title')?.focus();
            }, 100);
        }
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
            event.preventDefault();
            await loadModal(createButton.dataset.url, true);
            return;
        }

        const editButton = event.target.closest('[data-broadcast-edit]');
        if (editButton) {
            event.preventDefault();
            const url = editButton.dataset.url || editButton.getAttribute('href');
            if (url && url !== '#' && !url.startsWith('javascript:')) {
                await loadModal(url, true);
            }
            return;
        }

        const viewButton = event.target.closest('[data-broadcast-view]');
        if (viewButton) {
            event.preventDefault();
            const url = viewButton.dataset.url || viewButton.getAttribute('href');
            if (url && url !== '#' && !url.startsWith('javascript:')) {
                await loadModal(url);
            }
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

        const statusTrigger = event.target.closest('[data-broadcast-status-trigger]');
        if (statusTrigger) {
            event.preventDefault();
            event.stopPropagation();
            const dropdown = statusTrigger.closest('[data-broadcast-status-dropdown]');
            const menu = dropdown?.querySelector('[data-broadcast-status-menu]');
            if (!menu) return;

            const isHidden = menu.classList.contains('hidden');
            closeAllBroadcastStatusMenus();

            if (isHidden) {
                menu.classList.remove('hidden');
                menu.style.visibility = 'hidden';
                window.requestAnimationFrame(() => {
                    positionBroadcastStatusMenu(statusTrigger, menu);
                    menu.style.visibility = '';
                });
                statusTrigger.setAttribute('aria-expanded', 'true');
            } else {
                menu.classList.add('hidden');
                statusTrigger.setAttribute('aria-expanded', 'false');
            }
            return;
        }

        const statusOption = event.target.closest('[data-broadcast-status-option]');
        if (statusOption) {
            event.preventDefault();
            event.stopPropagation();

            const statusId = Number(statusOption.dataset.statusId);
            const currentStatusId = Number(statusOption.dataset.currentStatusId);
            const updateUrl = statusOption.dataset.updateUrl;
            const statusName = statusOption.dataset.statusName || 'Status';
            const broadcastTitle = statusOption.dataset.broadcastTitle || '';

            closeAllBroadcastStatusMenus();

            if (!statusId || !updateUrl || statusId === currentStatusId) {
                return;
            }

            let confirmTitle = `Change status to ${statusName}?`;
            let confirmText = `Are you sure you want to change the status of “${broadcastTitle}” to ${statusName}?`;
            let confirmBtnText = 'Confirm';

            if (statusId === 2) {
                confirmTitle = 'Publish broadcast?';
                confirmText = `Are you sure you want to publish “${broadcastTitle}”? The broadcast will become visible to its target audience.`;
                confirmBtnText = 'Publish';
            } else if (statusId === 3) {
                confirmTitle = 'Archive broadcast?';
                confirmText = `Are you sure you want to archive “${broadcastTitle}”? The broadcast will no longer be visible to its audience.`;
                confirmBtnText = 'Archive';
            } else if (statusId === 1) {
                confirmTitle = 'Move broadcast to Draft?';
                confirmText = `Are you sure you want to change “${broadcastTitle}” to Draft? It will not be visible to recipients.`;
                confirmBtnText = 'Move to Draft';
            }

            const confirmation = await Alert.confirm({
                title: confirmTitle,
                text: confirmText,
                confirmText: confirmBtnText,
            });
            if (!confirmation.isConfirmed) return;

            statusOption.disabled = true;
            try {
                const response = await fetch(updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ status: statusId }),
                });
                const result = await parseResponse(response);
                if (!response.ok || !result.status) {
                    const message = Object.values(result.errors || {}).flat()[0] || result.message || 'Unable to update broadcast status.';
                    throw new Error(message);
                }
                Alert.success(result.message || 'Broadcast status updated successfully.');
                window.location.reload();
            } catch (error) {
                statusOption.disabled = false;
                Alert.error(error.message || 'Unable to update broadcast status.');
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
