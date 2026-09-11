import Alert from "../../alert";
import { initDatepicker } from "../../components/datepicker";

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("meeting_modal");
    if (!modal) return;

    const openCreateBtn = document.getElementById("open_create_meeting_modal_btn");
    const closeBtns = document.querySelectorAll("[data-meeting-modal-close]");
    const form = document.getElementById("meeting_form");
    const formMethodInput = document.getElementById("meeting_form_method");
    const modalTitle = document.getElementById("meeting_modal_title");
    const submitBtn = document.getElementById("meeting_submit_btn");
    const descriptionInput = document.getElementById("meeting_description_input");
    const descriptionEditorEl = document.getElementById("meeting_description_editor");

    const startInputEl = document.getElementById("meeting_start_at");
    const durationInputEl = document.getElementById("meeting_duration_minutes");
    const endInputEl = document.getElementById("meeting_end_at");

    const internalParticipantsSelect = document.getElementById("meeting_internal_participants");
    const externalParticipantsContainer = document.getElementById("external_participants_container");
    const addExternalParticipantBtn = document.getElementById("add_external_participant_btn");
    const externalTemplate = document.getElementById("external_participant_row_template");

    const attachmentsInput = document.getElementById("meeting_attachments_input");
    const selectedFilesContainer = document.getElementById("selected_meeting_files");
    const existingAttachmentsSection = document.getElementById("existing_meeting_attachments_section");
    const existingAttachmentsList = document.getElementById("existing_meeting_attachments_list");

    let externalIndex = 0;
    let quillEditor = null;
    let pendingMeetingFiles = [];

    // Helper: boolean filter check
    const isBool = (val) => val === true || val === 1 || val === "1" || val === "true";

    function syncInputFiles() {
        if (!attachmentsInput) return;
        try {
            const dt = new DataTransfer();
            pendingMeetingFiles.forEach((file) => dt.items.add(file));
            attachmentsInput.files = dt.files;
        } catch (e) {
            // DataTransfer fallback
        }
    }

    function renderSelectedFiles() {
        if (!selectedFilesContainer) return;
        if (!pendingMeetingFiles.length) {
            selectedFilesContainer.innerHTML = "";
            return;
        }

        selectedFilesContainer.innerHTML = pendingMeetingFiles
            .map((file, index) => `
                <div class="inline-flex items-center gap-1.5 rounded-full bg-success-50 border border-success-200 px-3 py-1 text-xs font-medium text-success-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span class="truncate max-w-[200px]" title="${file.name}">${file.name} (${(file.size / 1024).toFixed(1)} KB)</span>
                    <button type="button" class="remove-pending-file-btn ml-1 font-bold text-success-600 hover:text-red-500 transition leading-none focus:outline-none" data-file-index="${index}" title="Remove file">
                        &times;
                    </button>
                </div>
            `)
            .join("");
    }

    // File input change listener for previewing selected files
    if (attachmentsInput && selectedFilesContainer) {
        attachmentsInput.addEventListener("change", () => {
            const newlySelected = Array.from(attachmentsInput.files || []);
            if (!newlySelected.length) return;

            if (newlySelected.length > 5) {
                Alert.error("You can select a maximum of 5 files at a time.");
                pendingMeetingFiles = newlySelected.slice(0, 5);
            } else {
                pendingMeetingFiles = newlySelected;
            }

            syncInputFiles();
            renderSelectedFiles();
        });
    }

    // 1. Initialize Quill Rich Text Editor
    if (descriptionEditorEl && window.Quill && !quillEditor) {
        quillEditor = new window.Quill(descriptionEditorEl, {
            theme: "snow",
            placeholder: "Enter meeting agenda, overview, or notes...",
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ["bold", "italic", "underline", "strike"],
                    [{ list: "ordered" }, { list: "bullet" }],
                    ["link", "clean"],
                ],
            },
        });
    }

    // 2. Initialize Datepickers
    initDatepicker(".datepicker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altFormat: "Y-m-d H:i",
    });

    // 3. Duration & End Date Calculation
    function parseDateTimeStr(str) {
        if (!str) return null;
        const parts = str.trim().split(" ");
        if (parts.length < 2) return null;
        const [ymd, hm] = parts;
        if (!ymd || !hm) return null;
        const [year, month, day] = ymd.split("-").map(Number);
        const [hours, minutes] = hm.split(":").map(Number);
        if (isNaN(year) || isNaN(month) || isNaN(day) || isNaN(hours) || isNaN(minutes)) return null;
        const dt = new Date(year, month - 1, day, hours, minutes);
        return isNaN(dt.getTime()) ? null : dt;
    }

    function calculateEndAt() {
        if (!startInputEl || !endInputEl) return;

        const startVal = startInputEl.value ? startInputEl.value.trim() : "";
        const durationMinutes = parseInt(durationInputEl ? durationInputEl.value : "60", 10) || 60;

        if (!startVal) {
            endInputEl.value = "";
            return;
        }

        const startDate = parseDateTimeStr(startVal);
        if (!startDate) return;

        const endDate = new Date(startDate.getTime() + durationMinutes * 60 * 1000);

        const pad = (n) => String(n).padStart(2, "0");
        const formattedEnd = `${endDate.getFullYear()}-${pad(endDate.getMonth() + 1)}-${pad(endDate.getDate())} ${pad(endDate.getHours())}:${pad(endDate.getMinutes())}`;
        endInputEl.value = formattedEnd;
    }

    if (startInputEl) {
        startInputEl.addEventListener("change", calculateEndAt);
        startInputEl.addEventListener("input", calculateEndAt);
    }
    if (durationInputEl) {
        durationInputEl.addEventListener("change", calculateEndAt);
        durationInputEl.addEventListener("input", calculateEndAt);
    }

    // 4. External Participant Helpers
    function addExternalParticipantRow(data = null) {
        if (!externalTemplate || !externalParticipantsContainer) return;

        const index = externalIndex++;
        const displayIndex = externalParticipantsContainer.querySelectorAll(".external-participant-row").length + 1;

        let html = externalTemplate.innerHTML
            .replace(/{INDEX}/g, index)
            .replace(/{DISPLAY_INDEX}/g, displayIndex);

        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = html.trim();
        const row = tempDiv.firstElementChild;

        externalParticipantsContainer.appendChild(row);

        const removeBtn = row.querySelector(".remove-external-participant-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => {
                row.remove();
                const remaining = externalParticipantsContainer.querySelectorAll(".external-participant-row");
                remaining.forEach((r, i) => {
                    const label = r.querySelector("span");
                    if (label) label.textContent = `Guest #${i + 1}`;
                });
            });
        }

        if (data) {
            const nameInput = row.querySelector(".external-name-input");
            const emailInput = row.querySelector(".external-email-input");
            const phoneInput = row.querySelector(".external-phone-input");
            const sendEmailCheck = row.querySelector(".external-send-email-check");

            if (nameInput && data.name) nameInput.value = data.name;
            if (emailInput && data.email) emailInput.value = data.email;
            if (phoneInput && data.phone) phoneInput.value = data.phone;
            if (sendEmailCheck && isBool(data.send_email)) sendEmailCheck.checked = true;
        }
    }

    if (addExternalParticipantBtn) {
        addExternalParticipantBtn.addEventListener("click", () => addExternalParticipantRow());
    }

    // 5. Open Create Modal
    function openCreateModal() {
        form.reset();
        form.action = form.dataset.createUrl;
        formMethodInput.value = "POST";
        modalTitle.textContent = "Add New Meeting";
        submitBtn.textContent = "Save Meeting";

        // Reset TomSelects & prefill default values
        const projectSelect = document.getElementById("meeting_project_id");
        if (projectSelect && projectSelect.tomselect) {
            projectSelect.tomselect.clear();
        }

        const typeSelect = document.getElementById("meeting_type_id");
        if (typeSelect && typeSelect.tomselect) {
            const defId = typeSelect.dataset.defaultId;
            if (defId) typeSelect.tomselect.setValue(defId);
            else typeSelect.tomselect.clear();
        }

        const locationSelect = document.getElementById("meeting_location_id");
        if (locationSelect && locationSelect.tomselect) {
            const defId = locationSelect.dataset.defaultId;
            if (defId) locationSelect.tomselect.setValue(defId);
            else locationSelect.tomselect.clear();
        }

        const statusSelect = document.getElementById("meeting_status_id");
        if (statusSelect && statusSelect.tomselect) {
            const defId = statusSelect.dataset.defaultId;
            if (defId) statusSelect.tomselect.setValue(defId);
            else statusSelect.tomselect.clear();
        }

        const organizerSelect = document.getElementById("meeting_organizer_id");
        if (organizerSelect && organizerSelect.tomselect) {
            const defId = organizerSelect.dataset.defaultId;
            if (defId) organizerSelect.tomselect.setValue(defId);
            else organizerSelect.tomselect.clear();
        }

        const tagsSelect = document.getElementById("meeting_tag_ids");
        if (tagsSelect && tagsSelect.tomselect) {
            tagsSelect.tomselect.clear();
        }

        // Reset Participants
        if (internalParticipantsSelect && internalParticipantsSelect.tomselect) {
            internalParticipantsSelect.tomselect.clear();
        }
        if (externalParticipantsContainer) {
            externalParticipantsContainer.innerHTML = "";
            externalIndex = 0;
        }

        // Default Duration
        if (durationInputEl) {
            durationInputEl.value = "60";
        }
        calculateEndAt();

        // Reset Quill
        if (quillEditor) {
            quillEditor.setContents([]);
        }

        // Reset Attachments
        pendingMeetingFiles = [];
        if (attachmentsInput) attachmentsInput.value = "";
        if (selectedFilesContainer) selectedFilesContainer.innerHTML = "";
        if (existingAttachmentsSection) existingAttachmentsSection.classList.add("hidden");
        modal.classList.remove("hidden");

        const titleInput = document.getElementById("meeting_title");
        if (titleInput) {
            setTimeout(() => {
                titleInput.focus();
            }, 50);
        }
    }

    // 6. Close Modal
    function closeModal() {
        modal.classList.add("hidden");
    }

    if (openCreateBtn) {
        openCreateBtn.addEventListener("click", openCreateModal);
    }

    closeBtns.forEach((btn) => {
        btn.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // 7. Edit Meeting Modal Handler
    async function openEditMeetingModal(editUrl, updateUrl) {
        if (!editUrl || !updateUrl) return;

        try {
            const response = await fetch(editUrl, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            const result = await response.json();
            if (!response.ok || !result.status) {
                Alert.error(result.message || "Failed to load meeting details.");
                return;
            }

            const data = result.data;

            // Populate Form Basics
            form.action = updateUrl;
            formMethodInput.value = "PUT";
            modalTitle.textContent = "Edit Meeting";
            submitBtn.textContent = "Update Meeting";

            document.getElementById("meeting_title").value = data.title || "";
            document.getElementById("meeting_url").value = data.url || "";
            document.getElementById("meeting_location_details").value = data.location_details || "";

            // Start & Duration
            const startAtStr = data.start_at ? data.start_at.replace("T", " ").substring(0, 16) : "";
            const endAtStr = data.end_at ? data.end_at.replace("T", " ").substring(0, 16) : "";

            if (startInputEl) startInputEl.value = startAtStr;
            if (endInputEl) endInputEl.value = endAtStr;

            if (startAtStr && endAtStr) {
                const startDate = new Date(startAtStr.replace(/-/g, "/"));
                const endDate = new Date(endAtStr.replace(/-/g, "/"));
                const diffMinutes = Math.round((endDate.getTime() - startDate.getTime()) / (60 * 1000));
                if (durationInputEl) durationInputEl.value = diffMinutes > 0 ? diffMinutes : 60;
            } else if (durationInputEl) {
                durationInputEl.value = 60;
            }
            calculateEndAt();

            // TomSelect Fields
            const projectSelect = document.getElementById("meeting_project_id");
            if (projectSelect && projectSelect.tomselect) {
                if (data.project_id && data.project) {
                    projectSelect.tomselect.addOption({
                        value: String(data.project.id),
                        text: data.project.name,
                        subtype: data.project.project_code || "",
                    });
                    projectSelect.tomselect.setValue(String(data.project_id));
                } else if (data.project_id) {
                    projectSelect.tomselect.setValue(String(data.project_id));
                } else {
                    projectSelect.tomselect.clear();
                }
            }

            const typeSelect = document.getElementById("meeting_type_id");
            if (typeSelect && typeSelect.tomselect) {
                if (data.meeting_type_id) typeSelect.tomselect.setValue(String(data.meeting_type_id));
                else typeSelect.tomselect.clear();
            }

            const locationSelect = document.getElementById("meeting_location_id");
            if (locationSelect && locationSelect.tomselect) {
                if (data.meeting_location_id) locationSelect.tomselect.setValue(String(data.meeting_location_id));
                else locationSelect.tomselect.clear();
            }

            const statusSelect = document.getElementById("meeting_status_id");
            if (statusSelect && statusSelect.tomselect) {
                if (data.meeting_status_id) statusSelect.tomselect.setValue(String(data.meeting_status_id));
                else statusSelect.tomselect.clear();
            }

            const organizerSelect = document.getElementById("meeting_organizer_id");
            if (organizerSelect && organizerSelect.tomselect) {
                if (data.organizer_id) organizerSelect.tomselect.setValue(String(data.organizer_id));
                else organizerSelect.tomselect.clear();
            }

            const tagsSelect = document.getElementById("meeting_tag_ids");
            if (tagsSelect && tagsSelect.tomselect) {
                const tagIds = (data.tags || []).map((t) => t.id);
                tagsSelect.tomselect.setValue(tagIds);
            }

            // Quill Description
            if (quillEditor) {
                quillEditor.clipboard.dangerouslyPasteHTML(data.description || "");
            }

            // Populate Participants
            if (internalParticipantsSelect && internalParticipantsSelect.tomselect) {
                internalParticipantsSelect.tomselect.clear();
            }
            if (externalParticipantsContainer) {
                externalParticipantsContainer.innerHTML = "";
                externalIndex = 0;
            }

            if (data.participants && data.participants.length > 0) {
                const internalUserIds = [];
                data.participants.forEach((p) => {
                    const isExt = isBool(p.is_external) || !p.user_id;
                    if (!isExt && p.user_id) {
                        internalUserIds.push(String(p.user_id));
                    } else {
                        addExternalParticipantRow(p);
                    }
                });

                if (internalParticipantsSelect && internalParticipantsSelect.tomselect) {
                    internalParticipantsSelect.tomselect.setValue(internalUserIds);
                }
            }

            // Populate Attachments
            pendingMeetingFiles = [];
            if (attachmentsInput) attachmentsInput.value = "";
            if (selectedFilesContainer) selectedFilesContainer.innerHTML = "";

            if (existingAttachmentsSection && existingAttachmentsList) {
                if (data.attachments && data.attachments.length > 0) {
                    existingAttachmentsList.innerHTML = data.attachments.map((att) => {
                        const isImage = (att.file_type && att.file_type.startsWith("image/")) ||
                            /\.(jpg|jpeg|png)$/i.test(att.original_name);
                        const fileSizeKb = att.file_size ? (att.file_size / 1024).toFixed(1) : "0";

                        return `
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-bgray-200 bg-white p-2.5 dark:border-darkblack-400 dark:bg-darkblack-500" id="meeting_att_${att.id}">
                                <a href="${att.url}" target="_blank" class="flex items-center gap-2.5 min-w-0 flex-1 hover:text-success-300">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-bgray-100 dark:bg-darkblack-600 overflow-hidden">
                                        ${isImage ? `<img src="${att.url}" alt="${att.original_name}" class="h-full w-full object-cover" />` : `
                                            <svg class="h-5 w-5 text-bgray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.5L14.5 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        `}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-bgray-900 dark:text-white" title="${att.original_name}">${att.original_name}</p>
                                        <p class="text-[10px] text-bgray-500">${fileSizeKb} KB</p>
                                    </div>
                                </a>
                                <button type="button" class="delete-meeting-attachment-btn text-xs text-red-500 hover:text-red-700 hover:underline shrink-0 px-2 py-1" data-delete-url="${att.delete_url}" data-attachment-id="${att.id}">
                                    Remove
                                </button>
                            </div>
                        `;
                    }).join("");
                    existingAttachmentsSection.classList.remove("hidden");
                } else {
                    existingAttachmentsList.innerHTML = "";
                    existingAttachmentsSection.classList.add("hidden");
                }
            }

            if (window.closeDayMeetings && typeof window.closeDayMeetings === "function") {
                window.closeDayMeetings();
            }
            if (window.closeMeetingPreview && typeof window.closeMeetingPreview === "function") {
                window.closeMeetingPreview();
            }
            modal.classList.remove("hidden");
        } catch (err) {
            console.error("Error loading meeting details:", err);
            Alert.error("An error occurred while fetching meeting details.");
        }
    }

    // Delegation handler for any click on .edit-meeting-btn, .delete-meeting-attachment-btn, or .remove-pending-file-btn
    document.addEventListener("click", async (e) => {
        const removePendingBtn = e.target.closest(".remove-pending-file-btn");
        if (removePendingBtn) {
            e.preventDefault();
            const index = parseInt(removePendingBtn.dataset.fileIndex, 10);
            if (!isNaN(index) && index >= 0 && index < pendingMeetingFiles.length) {
                pendingMeetingFiles.splice(index, 1);
                syncInputFiles();
                renderSelectedFiles();
            }
            return;
        }

        const btn = e.target.closest(".edit-meeting-btn");
        if (btn) {
            e.preventDefault();
            const editUrl = btn.dataset.url;
            const updateUrl = btn.dataset.updateUrl;
            if (editUrl && updateUrl) {
                openEditMeetingModal(editUrl, updateUrl);
            }
            return;
        }

        const removeAttBtn = e.target.closest(".delete-meeting-attachment-btn");
        if (removeAttBtn) {
            e.preventDefault();
            const deleteUrl = removeAttBtn.dataset.deleteUrl;
            const attachmentId = removeAttBtn.dataset.attachmentId;
            if (!deleteUrl) return;

            const confirmResult = await Alert.confirm({
                title: "Remove Attachment",
                text: "Are you sure you want to remove this attachment?",
                confirmText: "Yes, remove",
                cancelText: "Cancel",
                confirmColor: "#ef4444",
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(deleteUrl, {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                    },
                });

                const resData = await res.json();
                if (!res.ok || !resData.status) {
                    Alert.error(resData.message || "Failed to remove attachment.");
                    return;
                }

                Alert.success(resData.message || "Attachment removed successfully.");

                const cardEl = document.getElementById(`meeting_att_${attachmentId}`);
                if (cardEl) {
                    cardEl.remove();
                }

                if (existingAttachmentsList && existingAttachmentsList.children.length === 0) {
                    if (existingAttachmentsSection) {
                        existingAttachmentsSection.classList.add("hidden");
                    }
                }
            } catch (err) {
                console.error("Error removing attachment:", err);
                Alert.error("An error occurred while removing attachment.");
            }
        }
    });

    // 8. Form Submit Handler (Sync Participants, Quill, Client Validation & AJAX Submit)
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        calculateEndAt();

        if (quillEditor) {
            descriptionInput.value = quillEditor.root.innerHTML === "<p><br></p>" ? "" : quillEditor.root.innerHTML;
        }

        // Clean existing dynamic participant hidden inputs
        form.querySelectorAll(".dynamic-participant-input").forEach((el) => el.remove());

        // Add marker so participants field payload is tracked on submit
        const dummyParticipantInput = document.createElement("input");
        dummyParticipantInput.type = "hidden";
        dummyParticipantInput.name = "participants_submitted";
        dummyParticipantInput.value = "1";
        dummyParticipantInput.className = "dynamic-participant-input";
        form.appendChild(dummyParticipantInput);

        let pIndex = 0;

        // Internal Participants
        if (internalParticipantsSelect && internalParticipantsSelect.tomselect) {
            const val = internalParticipantsSelect.tomselect.getValue();
            const userIds = Array.isArray(val) ? val : (val ? [val] : []);
            userIds.forEach((uId) => {
                if (!uId) return;

                const uInput = document.createElement("input");
                uInput.type = "hidden";
                uInput.name = `participants[${pIndex}][user_id]`;
                uInput.value = uId;
                uInput.className = "dynamic-participant-input";

                const extInput = document.createElement("input");
                extInput.type = "hidden";
                extInput.name = `participants[${pIndex}][is_external]`;
                extInput.value = "0";
                extInput.className = "dynamic-participant-input";

                form.appendChild(uInput);
                form.appendChild(extInput);
                pIndex++;
            });
        }

        // External Participants
        if (externalParticipantsContainer) {
            const rows = externalParticipantsContainer.querySelectorAll(".external-participant-row");
            rows.forEach((row) => {
                const name = row.querySelector(".external-name-input")?.value?.trim();
                const email = row.querySelector(".external-email-input")?.value?.trim();
                const phone = row.querySelector(".external-phone-input")?.value?.trim();
                const sendEmail = row.querySelector(".external-send-email-check")?.checked ? "1" : "0";

                if (name || email) {
                    const extInput = document.createElement("input");
                    extInput.type = "hidden";
                    extInput.name = `participants[${pIndex}][is_external]`;
                    extInput.value = "1";
                    extInput.className = "dynamic-participant-input";

                    const nameInput = document.createElement("input");
                    nameInput.type = "hidden";
                    nameInput.name = `participants[${pIndex}][name]`;
                    nameInput.value = name || "";
                    nameInput.className = "dynamic-participant-input";

                    const emailInput = document.createElement("input");
                    emailInput.type = "hidden";
                    emailInput.name = `participants[${pIndex}][email]`;
                    emailInput.value = email || "";
                    emailInput.className = "dynamic-participant-input";

                    const phoneInput = document.createElement("input");
                    phoneInput.type = "hidden";
                    phoneInput.name = `participants[${pIndex}][phone]`;
                    phoneInput.value = phone || "";
                    phoneInput.className = "dynamic-participant-input";

                    const sendInput = document.createElement("input");
                    sendInput.type = "hidden";
                    sendInput.name = `participants[${pIndex}][send_email]`;
                    sendInput.value = sendEmail;
                    sendInput.className = "dynamic-participant-input";

                    form.appendChild(extInput);
                    form.appendChild(nameInput);
                    form.appendChild(emailInput);
                    form.appendChild(phoneInput);
                    form.appendChild(sendInput);
                    pIndex++;
                }
            });
        }

        const startVal = startInputEl ? startInputEl.value : "";
        const endVal = endInputEl ? endInputEl.value : "";
        const startDateObj = parseDateTimeStr(startVal);
        const endDateObj = parseDateTimeStr(endVal);

        if (startDateObj && endDateObj && endDateObj < startDateObj) {
            Alert.error("The end date and time must be equal to or after the start date and time.");
            return;
        }

        if (pendingMeetingFiles.length > 5) {
            Alert.error("You can upload a maximum of 5 files at a time.");
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add("opacity-50", "cursor-not-allowed");
        }

        try {
            const formData = new FormData(form);
            formData.delete("attachments[]");
            pendingMeetingFiles.forEach((file) => {
                formData.append("attachments[]", file);
            });
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                    ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                },
                body: formData,
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                if (response.status === 422 && result.errors) {
                    const firstErrorKey = Object.keys(result.errors)[0];
                    const firstErrorMsg = result.errors[firstErrorKey]?.[0] || "Validation failed.";
                    Alert.error(firstErrorMsg);
                } else {
                    Alert.error(result.message || "Failed to save meeting.");
                }
                return;
            }

            Alert.success(result.message || "Meeting saved successfully.");
            closeModal();

            setTimeout(() => {
                window.location.reload();
            }, 400);

        } catch (error) {
            console.error("Error saving meeting:", error);
            Alert.error("An unexpected error occurred while saving the meeting.");
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove("opacity-50", "cursor-not-allowed");
            }
        }
    });

    // 9. Delete Confirmation Handler
    document.querySelectorAll(".delete-meeting-btn").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            const targetForm = btn.closest("form");
            if (!targetForm) return;

            const result = await Alert.confirm({
                title: "Delete Meeting",
                text: "Are you sure you want to delete this meeting?",
                confirmText: "Yes, delete it",
                cancelText: "Cancel",
                confirmColor: "#ef4444",
            });

            if (result.isConfirmed) {
                targetForm.submit();
            }
        });
    });

    // 10. Auto Edit Trigger from URL Query Param ?edit=ID
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get("edit");
    if (editId) {
        const targetBtn = document.querySelector(`.edit-meeting-btn[data-id="${editId}"]`);
        if (targetBtn) {
            targetBtn.click();
        }
    }

    // 11. Function to open create meeting for a specific date (prefilling Start & End)
    function openCreateMeetingForDate(dateKey) {
        if (!dateKey) return;

        const now = new Date();
        const todayMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        const dateParts = dateKey.split("-").map(Number);
        const clickedDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);

        if (clickedDate < todayMidnight) {
            return; // Past date rule
        }

        const startDateObj = new Date(
            clickedDate.getFullYear(),
            clickedDate.getMonth(),
            clickedDate.getDate(),
            now.getHours(),
            now.getMinutes()
        );

        const pad = (n) => String(n).padStart(2, "0");
        const formattedStart = `${startDateObj.getFullYear()}-${pad(startDateObj.getMonth() + 1)}-${pad(startDateObj.getDate())} ${pad(startDateObj.getHours())}:${pad(startDateObj.getMinutes())}`;

        openCreateModal();

        if (startInputEl) {
            startInputEl.value = formattedStart;
            if (startInputEl._flatpickr) {
                startInputEl._flatpickr.setDate(formattedStart, true);
            }
        }
        if (durationInputEl) {
            durationInputEl.value = "60";
        }
        calculateEndAt();
    }

    // Expose helpers globally
    window.openCreateModal = openCreateModal;
    window.openEditMeetingModal = openEditMeetingModal;
    window.closeMeetingModal = closeModal;
    window.openCreateMeetingForDate = openCreateMeetingForDate;
});
