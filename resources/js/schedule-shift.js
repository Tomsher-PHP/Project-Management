import { initWeekPicker } from './components/weekpicker';

let currentWeek = null;

// Load week via AJAX
const loadWeek = (date) => {
    fetch(`/schedule-shift?week=${date}`, { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(res => res.json())
        .then(data => {
            // Update table and week label
            document.querySelector("#schedule-table").innerHTML = data.html;
            document.getElementById("week-date-range").innerText = data.weekRange;
            currentWeek = date;
        });
}

// Initialize schedule shift events
export function initScheduleShift(startOfWeek) {
    currentWeek = startOfWeek;

    // Event delegation for toggle edit mode
    document.body.addEventListener("click", function (e) {
        const btn = e.target.closest(".edit-shift");
        if (!btn) return;
        const td = btn.closest("td");
        const view = td.querySelector(".shift-view");
        const edit = td.querySelector(".shift-edit");
        if (!view || !edit) return;
        view.classList.toggle("hidden");
        edit.classList.toggle("hidden");
    });

    // Event delegation for shift select
    document.body.addEventListener("change", function (e) {
        if (!e.target.classList.contains("shift-select")) return;
        const select = e.target;
        const userId = select.dataset.user;
        const date = select.dataset.date;
        const shiftId = select.value;
        fetch("/shift/update", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ user_id: userId, date: date, shift_id: shiftId })
        }).then(res => res.json()).then(data => location.reload());
    });

    // Only attach next/prev listeners **once**
    if (!window.scheduleShiftListenersAttached) {
        document.getElementById("nextWeek").addEventListener("click", () => {
            let next = new Date(currentWeek);
            next.setDate(next.getDate() + 7);
            loadWeek(next.toISOString().split('T')[0]);
        });

        document.getElementById("prevWeek").addEventListener("click", () => {
            let prev = new Date(currentWeek);
            prev.setDate(prev.getDate() - 7);
            loadWeek(prev.toISOString().split('T')[0]);
        });

        document.getElementById("weekPickerBtn").addEventListener("click", () => {
            document.querySelector(".weekPicker")._flatpickr.open();
        });

        window.scheduleShiftListenersAttached = true;
    }

    // Initialize week picker once
    initWeekPicker(".weekPicker", loadWeek);
}

// Auto-init
document.addEventListener("DOMContentLoaded", () => {
    const input = document.querySelector(".weekPicker");
    if (input) {
        initScheduleShift(input.value);
    }
});