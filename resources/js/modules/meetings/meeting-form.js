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
    const participantsContainer = document.getElementById("meeting_participants_container");
    const addParticipantBtn = document.getElementById("add_participant_btn");
    const participantTemplate = document.getElementById("participant_row_template");

    let participantIndex = 0;
    let quillEditor = null;

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

    // 3. Open Create Modal
    function openCreateModal() {
        form.reset();
        form.action = form.dataset.createUrl;
        formMethodInput.value = "POST";
        modalTitle.textContent = "Add New Meeting";
        submitBtn.textContent = "Save Meeting";

        // Reset TomSelects
        form.querySelectorAll("select.tom-select").forEach((select) => {
            if (select.tomselect) {
                select.tomselect.clear();
            }
        });

        // Reset Quill
        if (quillEditor) {
            quillEditor.setContents([]);
        }

        // Reset Participants (Add 1 default internal user row)
        if (participantsContainer) {
            participantsContainer.innerHTML = "";
            participantIndex = 0;
            addParticipantRow();
        }

        modal.classList.remove("hidden");
    }

    // 4. Close Modal
    function closeModal() {
        modal.classList.add("hidden");
    }

    if (openCreateBtn) {
        openCreateBtn.addEventListener("click", openCreateModal);
    }

    closeBtns.forEach((btn) => {
        btn.addEventListener("click", closeModal);
    });

    // Close on background click
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // 5. Add Participant Row
    function addParticipantRow(data = null) {
        if (!participantTemplate || !participantsContainer) return;

        const index = participantIndex++;
        let html = participantTemplate.innerHTML.replace(/{INDEX}/g, index);

        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = html.trim();
        const row = tempDiv.firstElementChild;

        participantsContainer.appendChild(row);

        // Bind Radio Toggle (Internal vs External)
        const radios = row.querySelectorAll(".participant-type-toggle");
        const internalFields = row.querySelector(".internal-user-fields");
        const externalFields = row.querySelector(".external-user-fields");
        const userSelect = row.querySelector(".tom-select-participant");

        radios.forEach((radio) => {
            radio.addEventListener("change", (e) => {
                if (e.target.value === "1") {
                    internalFields.classList.add("hidden");
                    externalFields.classList.remove("hidden");
                    externalFields.classList.add("grid");
                } else {
                    externalFields.classList.add("hidden");
                    externalFields.classList.remove("grid");
                    internalFields.classList.remove("hidden");
                }
            });
        });

        // Remove Row Button
        const removeBtn = row.querySelector(".remove-participant-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => {
                row.remove();
            });
        }

        // Initialize TomSelect on user select
        if (userSelect && window.TomSelect && !userSelect.tomselect) {
            new window.TomSelect(userSelect, {
                create: false,
                placeholder: "Select Internal User",
            });
        }

        // Populate data if editing
        if (data) {
            const isExternal = data.is_external || !data.user_id;
            const isExtRadio = row.querySelector(`.participant-type-toggle[value="${isExternal ? 1 : 0}"]`);
            if (isExtRadio) {
                isExtRadio.checked = true;
                isExtRadio.dispatchEvent(new Event("change"));
            }

            if (!isExternal && data.user_id && userSelect && userSelect.tomselect) {
                userSelect.tomselect.setValue(data.user_id);
            }

            if (data.name) {
                const nameInput = row.querySelector(`input[name="participants[${index}][name]"]`);
                if (nameInput) nameInput.value = data.name;
            }

            if (data.email) {
                const emailInput = row.querySelector(`input[name="participants[${index}][email]"]`);
                if (emailInput) emailInput.value = data.email;
            }

            if (data.phone) {
                const phoneInput = row.querySelector(`input[name="participants[${index}][phone]"]`);
                if (phoneInput) phoneInput.value = data.phone;
            }

            if (data.send_email) {
                const sendEmailCheck = row.querySelector(`input[name="participants[${index}][send_email]"]`);
                if (sendEmailCheck) sendEmailCheck.checked = true;
            }
        }
    }

    if (addParticipantBtn) {
        addParticipantBtn.addEventListener("click", () => addParticipantRow());
    }

    // 6. Edit Meeting Modal Handler
    document.querySelectorAll(".edit-meeting-btn").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const editUrl = btn.dataset.url;
            const updateUrl = btn.dataset.updateUrl;

            try {
                const response = await fetch(editUrl, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });

                const result = await response.json();
                if (!response.ok || !result.status) {
                    alert(result.message || "Failed to load meeting details.");
                    return;
                }

                const data = result.data;

                // Populate Form
                form.action = updateUrl;
                formMethodInput.value = "PUT";
                modalTitle.textContent = "Edit Meeting";
                submitBtn.textContent = "Update Meeting";

                document.getElementById("meeting_title").value = data.title || "";
                document.getElementById("meeting_start_at").value = data.start_at ? data.start_at.replace("T", " ").substring(0, 16) : "";
                document.getElementById("meeting_end_at").value = data.end_at ? data.end_at.replace("T", " ").substring(0, 16) : "";
                document.getElementById("meeting_url").value = data.url || "";
                document.getElementById("meeting_location_details").value = data.location_details || "";

                // TomSelect Fields
                const projectSelect = document.getElementById("meeting_project_id");
                if (projectSelect && projectSelect.tomselect) projectSelect.tomselect.setValue(data.project_id || "");

                const typeSelect = document.getElementById("meeting_type_id");
                if (typeSelect && typeSelect.tomselect) typeSelect.tomselect.setValue(data.meeting_type_id || "");

                const locationSelect = document.getElementById("meeting_location_id");
                if (locationSelect && locationSelect.tomselect) locationSelect.tomselect.setValue(data.meeting_location_id || "");

                const statusSelect = document.getElementById("meeting_status_id");
                if (statusSelect && statusSelect.tomselect) statusSelect.tomselect.setValue(data.meeting_status_id || "");

                const organizerSelect = document.getElementById("meeting_organizer_id");
                if (organizerSelect && organizerSelect.tomselect) organizerSelect.tomselect.setValue(data.organizer_id || "");

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
                if (participantsContainer) {
                    participantsContainer.innerHTML = "";
                    participantIndex = 0;

                    if (data.participants && data.participants.length > 0) {
                        data.participants.forEach((p) => addParticipantRow(p));
                    } else {
                        addParticipantRow();
                    }
                }

                modal.classList.remove("hidden");
            } catch (err) {
                console.error("Error loading meeting details:", err);
                alert("An error occurred while fetching meeting details.");
            }
        });
    });

    // 7. Form Submit Handler (Sync Quill and Client Validation)
    form.addEventListener("submit", (e) => {
        if (quillEditor) {
            descriptionInput.value = quillEditor.root.innerHTML === "<p><br></p>" ? "" : quillEditor.root.innerHTML;
        }

        const startVal = document.getElementById("meeting_start_at").value;
        const endVal = document.getElementById("meeting_end_at").value;

        if (startVal && endVal && new Date(endVal) < new Date(startVal)) {
            e.preventDefault();
            alert("The end date and time must be equal to or after the start date and time.");
        }
    });

    // 8. Delete Confirmation Handler
    document.querySelectorAll(".delete-meeting-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            if (confirm("Are you sure you want to delete this meeting?")) {
                btn.closest("form").submit();
            }
        });
    });

    // 9. Auto Edit Trigger from URL Query Param ?edit=ID
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get("edit");
    if (editId) {
        const targetBtn = document.querySelector(`.edit-meeting-btn[data-id="${editId}"]`);
        if (targetBtn) {
            targetBtn.click();
        }
    }

    // 10. Function to open create meeting for a specific date (prefilling Start & End)
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

        const endDateObj = new Date(startDateObj.getTime() + 60 * 60 * 1000);

        const pad = (n) => String(n).padStart(2, "0");
        const formattedStart = `${startDateObj.getFullYear()}-${pad(startDateObj.getMonth() + 1)}-${pad(startDateObj.getDate())} ${pad(startDateObj.getHours())}:${pad(startDateObj.getMinutes())}`;
        const formattedEnd = `${endDateObj.getFullYear()}-${pad(endDateObj.getMonth() + 1)}-${pad(endDateObj.getDate())} ${pad(endDateObj.getHours())}:${pad(endDateObj.getMinutes())}`;

        openCreateModal();

        const startInput = document.getElementById("meeting_start_at");
        const endInput = document.getElementById("meeting_end_at");

        if (startInput) {
            startInput.value = formattedStart;
            if (startInput._flatpickr) {
                startInput._flatpickr.setDate(formattedStart, true);
            }
        }

        if (endInput) {
            endInput.value = formattedEnd;
            if (endInput._flatpickr) {
                endInput._flatpickr.setDate(formattedEnd, true);
            }
        }
    }

    // Expose helpers globally
    window.openCreateModal = openCreateModal;
    window.closeMeetingModal = closeModal;
    window.openCreateMeetingForDate = openCreateMeetingForDate;
});
