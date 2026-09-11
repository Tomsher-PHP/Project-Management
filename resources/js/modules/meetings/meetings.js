import Alert from "../../alert";

document.addEventListener("DOMContentLoaded", () => {
    // 1. Meeting Quick Preview Drawer Handlers
    const previewDrawer = document.getElementById("meeting-preview-drawer");
    const previewBackdrop = document.getElementById("meeting-preview-backdrop");
    const previewPanel = document.getElementById("meeting-preview-panel");
    const previewBody = document.getElementById("meeting-preview-body");

    let minutesQuillEditor = null;

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    async function openMeetingPreview(meetingId) {
        if (!previewDrawer || !previewBody) return;

        // Reset & Show Drawer Shell
        previewDrawer.classList.remove("hidden");
        requestAnimationFrame(() => {
            if (previewBackdrop) previewBackdrop.classList.remove("opacity-0");
            if (previewPanel) previewPanel.classList.remove("translate-x-full");
        });

        // Set Loading State
        previewBody.innerHTML = `
            <div id="meeting-preview-loading" class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="h-9 w-9 animate-spin text-success-300" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="mt-4 text-sm font-medium text-bgray-600 dark:text-bgray-300">Loading meeting details...</span>
            </div>
        `;

        try {
            const response = await fetch(`/meetings/${meetingId}/preview`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            const result = await response.json();
            if (response.ok && result.status) {
                previewBody.innerHTML = result.html;
                bindPreviewDrawerEvents();
            } else {
                previewBody.innerHTML = `
                    <div class="p-8 text-center">
                        <p class="text-sm font-medium text-red-500">${result.message || "Failed to load meeting details."}</p>
                        <button type="button" onclick="closeMeetingPreview()" class="mt-4 rounded-lg bg-bgray-200 px-4 py-2 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">Close</button>
                    </div>
                `;
            }
        } catch (err) {
            console.error("Error loading meeting preview:", err);
            previewBody.innerHTML = `
                <div class="p-8 text-center">
                    <p class="text-sm font-medium text-red-500">An error occurred while loading meeting details.</p>
                    <button type="button" onclick="closeMeetingPreview()" class="mt-4 rounded-lg bg-bgray-200 px-4 py-2 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">Close</button>
                </div>
            `;
        }
    }

    function closeMeetingPreview() {
        if (!previewDrawer) return;

        if (previewBackdrop) previewBackdrop.classList.add("opacity-0");
        if (previewPanel) previewPanel.classList.add("translate-x-full");

        minutesQuillEditor = null;

        setTimeout(() => {
            previewDrawer.classList.add("hidden");
        }, 300);
    }

    function bindPreviewDrawerEvents() {
        const closeBtn = document.getElementById("close-meeting-preview-btn");
        if (closeBtn) {
            closeBtn.addEventListener("click", closeMeetingPreview);
        }

        const editBtns = previewBody.querySelectorAll(".edit-meeting-btn");
        editBtns.forEach((btn) => {
            btn.addEventListener("click", closeMeetingPreview);
        });

        const addMinutesBtn = document.getElementById("add_minutes_btn");
        const editMinutesBtn = document.getElementById("edit_minutes_btn");
        const cancelMinutesBtn = document.getElementById("cancel_minutes_btn");
        const saveMinutesBtn = document.getElementById("save_minutes_btn");
        const editorWrapper = document.getElementById("minutes_editor_wrapper");
        const staticView = document.getElementById("minutes_static_view");
        const viewActions = document.getElementById("minutes_view_actions");
        const editorEl = document.getElementById("meeting_minutes_quill_editor");

        const startEditingMinutes = () => {
            if (!editorWrapper || !editorEl) return;

            if (staticView) staticView.classList.add("hidden");
            if (viewActions) viewActions.classList.add("hidden");
            editorWrapper.classList.remove("hidden");

            const initialHtml = staticView ? staticView.querySelector(".prose")?.innerHTML || "" : "";

            if (window.Quill && !minutesQuillEditor) {
                minutesQuillEditor = new window.Quill(editorEl, {
                    theme: "snow",
                    placeholder: "Enter meeting minutes, key decisions, and action items...",
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

            if (minutesQuillEditor) {
                minutesQuillEditor.clipboard.dangerouslyPasteHTML(initialHtml);
            }
        };

        if (addMinutesBtn) addMinutesBtn.addEventListener("click", startEditingMinutes);
        if (editMinutesBtn) editMinutesBtn.addEventListener("click", startEditingMinutes);

        if (cancelMinutesBtn) {
            cancelMinutesBtn.addEventListener("click", () => {
                if (editorWrapper) editorWrapper.classList.add("hidden");
                if (staticView) staticView.classList.remove("hidden");
                if (viewActions) viewActions.classList.remove("hidden");
            });
        }

        if (saveMinutesBtn && editorWrapper) {
            saveMinutesBtn.addEventListener("click", async () => {
                const saveUrl = editorWrapper.dataset.saveUrl;
                if (!saveUrl) return;

                let minutesContent = "";
                if (minutesQuillEditor) {
                    minutesContent = minutesQuillEditor.root.innerHTML === "<p><br></p>" ? "" : minutesQuillEditor.root.innerHTML;
                }

                const spinner = document.getElementById("save_minutes_spinner");
                saveMinutesBtn.disabled = true;
                if (spinner) spinner.classList.remove("hidden");

                try {
                    const response = await fetch(saveUrl, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": getCsrfToken(),
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                        },
                        body: JSON.stringify({ minutes: minutesContent }),
                    });

                    const result = await response.json();
                    if (response.ok && result.status) {
                        Alert.success(result.message || "Meeting minutes saved successfully.");
                        if (result.html && previewBody) {
                            previewBody.innerHTML = result.html;
                            bindPreviewDrawerEvents();
                        }
                    } else {
                        Alert.error(result.message || "Failed to save meeting minutes.");
                    }
                } catch (err) {
                    console.error("Error saving meeting minutes:", err);
                    Alert.error("An error occurred while saving meeting minutes.");
                } finally {
                    saveMinutesBtn.disabled = false;
                    if (spinner) spinner.classList.add("hidden");
                }
            });
        }
    }

    if (previewBackdrop) {
        previewBackdrop.addEventListener("click", closeMeetingPreview);
    }

    // Delegated click handler for preview meeting buttons
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".preview-meeting-btn");
        if (btn) {
            e.preventDefault();
            const meetingId = btn.dataset.id;
            if (meetingId) {
                openMeetingPreview(meetingId);
            }
        }
    });

    window.openMeetingPreview = openMeetingPreview;
    window.closeMeetingPreview = closeMeetingPreview;

    // 2. Attendance-Style Day Meetings Modal Handlers
    const dayModal = document.getElementById("dayMeetingsModal");
    const dayModalContent = document.getElementById("dayMeetingsContent");
    const dayModalTitle = document.getElementById("dayMeetingsTitle");

    async function showDayMeetings(dateKey) {
        if (!dayModal || !dayModalContent || !dayModalTitle) return;

        const dateParts = dateKey.split("-").map(Number);
        const formattedDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]).toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });

        dayModalTitle.innerText = "Meetings - " + formattedDate;
        dayModalContent.innerHTML = `<div class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">Loading meetings...</div>`;
        dayModal.classList.remove("hidden");

        const fetchUrl = dayModal.dataset.dayMeetingsUrl || "/meetings/day-meetings";

        try {
            const response = await fetch(`${fetchUrl}?date=${encodeURIComponent(dateKey)}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            const result = await response.json();
            if (result.status) {
                if (result.title) {
                    dayModalTitle.innerText = result.title;
                }
                dayModalContent.innerHTML = result.html;
            } else {
                dayModalContent.innerHTML = `<div class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">Failed to load meetings.</div>`;
            }
        } catch (err) {
            console.error("Error loading day meetings content:", err);
            dayModalContent.innerHTML = `<div class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">An error occurred while loading meetings.</div>`;
        }
    }

    function closeDayMeetings() {
        if (dayModal) {
            dayModal.classList.add("hidden");
        }
    }

    window.showDayMeetings = showDayMeetings;
    window.closeDayMeetings = closeDayMeetings;

    if (dayModal) {
        dayModal.addEventListener("click", (e) => {
            if (!e.target.closest(".relative.z-10") || e.target.classList.contains("bg-gray-900/60")) {
                closeDayMeetings();
            }
        });
    }

    // 3. FullCalendar Integration
    const calendarEl = document.getElementById("meeting_calendar");
    const calendarLoadingEl = document.getElementById("meeting_calendar_loading");

    if (calendarEl && window.initCalendar) {
        const calendarUrl = calendarEl.dataset.url;
        let abortController = null;

        function fetchMeetingsForRange(startStr, endStr) {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            if (calendarLoadingEl) {
                calendarLoadingEl.classList.remove("hidden");
            }
            calendarEl.classList.add("hidden");

            const params = new URLSearchParams(window.location.search);
            params.set("start", startStr);
            params.set("end", endStr);

            fetch(`${calendarUrl}?${params.toString()}`, {
                signal: abortController.signal,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            })
                .then((res) => {
                    if (!res.ok) throw new Error("Failed to fetch meeting calendar data");
                    return res.json();
                })
                .then((events) => {
                    if (calendarLoadingEl) calendarLoadingEl.classList.add("hidden");
                    calendarEl.classList.remove("hidden");

                    if (meetingCalendar) {
                        meetingCalendar.removeAllEvents();
                        if (events && events.length > 0) {
                            meetingCalendar.addEventSource(events);
                        }
                    }
                })
                .catch((err) => {
                    if (err.name === "AbortError") return;
                    console.error("Error loading meeting calendar data:", err);
                    if (calendarLoadingEl) calendarLoadingEl.classList.add("hidden");
                    calendarEl.classList.remove("hidden");
                });
        }

        const meetingCalendar = window.initCalendar(calendarEl, {
            events: [],
            initialView: "dayGridMonth",
            firstDay: 1,
            dayMaxEvents: 3,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            editable: false,
            selectable: true,
            datesSet: function (info) {
                fetchMeetingsForRange(info.startStr, info.endStr);
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                if (info.event.id) {
                    openMeetingPreview(info.event.id);
                }
            },
            dateClick: function (info) {
                if (window.openCreateMeetingForDate) {
                    window.openCreateMeetingForDate(info.dateStr);
                }
            },
            select: function (info) {
                if (info.startStr && window.openCreateMeetingForDate) {
                    window.openCreateMeetingForDate(info.startStr);
                }
            },
        });
    }
});
