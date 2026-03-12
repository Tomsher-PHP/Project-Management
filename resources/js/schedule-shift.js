import { initTomSelect } from './components/tom-select';
import { initWeekPicker } from './components/weekpicker';
import { Loader } from './helpers/loader';

let currentWeek = null;

// Load week via AJAX
const loadWeek = (date) => {
    Loader.show();

    fetch(`/schedule-shift?week=${date}`, { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(res => res.json())
        .then(data => {
            // Update table and week label
            document.querySelector("#schedule-table").innerHTML = data.html;
            document.getElementById("week-date-range").innerText = data.weekRange;
            currentWeek = date;

            initTomSelect();
        })
        .catch(err => {
            console.error("Failed to load schedule:", err);
        })
        .finally(() => {
            Loader.hide();
        });
}

// Initialize schedule shift events
export function initScheduleShift(startOfWeek) {
    currentWeek = startOfWeek;

    // Attach global listeners only once
    if (!window.scheduleShiftGlobalListeners) {
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
            fetch("/schedule-shift/update", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ users: [userId], date_from: date, date_to: date, shift_id: shiftId })
            }).then(res => res.json()).then(data => location.reload());
        });

        window.scheduleShiftGlobalListeners = true;
    }

    // Attach navigation listeners once
    if (!window.scheduleShiftNavListeners) {

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
            const picker = document.querySelector(".weekPicker")?._flatpickr;
            if (picker) picker.open();
        });

        window.scheduleShiftNavListeners = true;
    }
}

document.addEventListener("DOMContentLoaded", () => {

    const input = document.querySelector(".weekPicker");

    if (!input) return;

    initWeekPicker(".weekPicker", loadWeek);
    initScheduleShift(input.value);

    const btn = document.getElementById('schedule-shift-btn');
    if (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const selectedUsers = [...document.querySelectorAll('.user-checkbox:checked')]
                .map(cb => cb.value);

            sessionStorage.setItem('preSelectedUsers', JSON.stringify(selectedUsers));
            window.location.href = this.href;
        });
    }

});

if (!window.userRowToggleListener) {

    $(document).on("click", ".label-user-name", function () {
        const row = $(this).closest("tr");
        const checkbox = row.find(".user-checkbox");

        checkbox.prop("checked", !checkbox.prop("checked"));
    });

    $(document).on("click", "#select-all-users", function () {
        const checked = $(this).is(":checked");
        $(".user-checkbox").prop("checked", checked);
    });

    window.userRowToggleListener = true;
}