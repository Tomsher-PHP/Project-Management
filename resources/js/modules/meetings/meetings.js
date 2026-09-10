document.addEventListener("DOMContentLoaded", () => {
    // 1. Attendance-Style Day Meetings Modal Handlers
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

    // Expose helpers globally for Blade onclick handlers
    window.showDayMeetings = showDayMeetings;
    window.closeDayMeetings = closeDayMeetings;

    // Close day modal on background or outside click
    if (dayModal) {
        dayModal.addEventListener("click", (e) => {
            if (!e.target.closest(".relative.z-10") || e.target.classList.contains("bg-gray-900/60")) {
                closeDayMeetings();
            }
        });
    }

    // 2. FullCalendar Integration (if #meeting_calendar element is present)
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
                const editUrl = info.event.extendedProps?.edit_url || `/meetings/${info.event.id}/edit`;
                const updateUrl = info.event.extendedProps?.update_url || `/meetings/${info.event.id}`;
                if (window.openEditMeetingModal) {
                    window.openEditMeetingModal(editUrl, updateUrl);
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
