document.addEventListener("DOMContentLoaded", () => {
    // 1. Attendance-Style Day Meetings Modal Handlers
    const dayModal = document.getElementById("dayMeetingsModal");
    const dayModalContent = document.getElementById("dayMeetingsContent");
    const dayModalTitle = document.getElementById("dayMeetingsTitle");

    function showDayMeetings(dateKey) {
        if (!dayModal || !dayModalContent || !dayModalTitle) return;

        const calendarMeetings = window.calendarMeetings || {};
        const meetings = calendarMeetings[dateKey] || [];

        const dateParts = dateKey.split("-").map(Number);
        const formattedDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]).toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });

        dayModalTitle.innerText = "Meetings - " + formattedDate;

        if (!meetings.length) {
            dayModalContent.innerHTML = `<div class="py-8 text-center text-sm text-bgray-500">No meetings scheduled for this date.</div>`;
        } else {
            dayModalContent.innerHTML = meetings
                .map(
                    (m) => `
                <div class="rounded-lg border border-bgray-200 p-3 dark:border-darkblack-400 flex items-center justify-between" style="border-left: 4px solid ${m.color}">
                    <div>
                        <a href="${m.url}" class="text-sm font-bold text-bgray-900 dark:text-white hover:text-success-300">${m.title}</a>
                        <div class="text-xs text-bgray-500 mt-0.5">
                            ${m.time_range} • ${m.type} • Status: ${m.status}
                        </div>
                        <div class="text-xs text-bgray-400 mt-0.5">Organizer: ${m.organizer}</div>
                    </div>
                    <a href="${m.url}" class="rounded-md bg-bgray-100 dark:bg-darkblack-500 px-3 py-1.5 text-xs font-semibold text-bgray-700 dark:text-bgray-200 hover:bg-bgray-200">
                        View
                    </a>
                </div>
            `
                )
                .join("");
        }

        dayModal.classList.remove("hidden");
    }

    function closeDayMeetings() {
        if (dayModal) {
            dayModal.classList.add("hidden");
        }
    }

    // Expose helpers globally for Blade onclick handlers
    window.showDayMeetings = showDayMeetings;
    window.closeDayMeetings = closeDayMeetings;

    // Close day modal on background click
    if (dayModal) {
        dayModal.addEventListener("click", (e) => {
            if (e.target === dayModal) {
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
                const showUrl = info.event.extendedProps?.show_url;
                if (showUrl) {
                    window.location.href = showUrl;
                } else if (info.event.id) {
                    window.location.href = `/meetings/${info.event.id}`;
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
