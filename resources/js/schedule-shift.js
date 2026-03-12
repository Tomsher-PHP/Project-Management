import { initTomSelect } from './components/tom-select';
import { initWeekPicker } from './components/weekpicker';
import { Loader } from './helpers/loader';

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
            console.error("Failed to load schedule:");
        })
        .finally(() => {
            Loader.hide();
        });
}

// Initialize schedule shift events
export function initScheduleShift(startOfWeek) {
    currentWeek = startOfWeek ? new Date(startOfWeek) : new Date();

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

    // Get selected users and redirect to schedule shift create page
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

    if (!window.userRowToggleListener) {

        // Toggle checkbox when user name is clicked
        $(document).on("click", ".label-user-name", function () {
            const row = $(this).closest("tr");
            const checkbox = row.find(".user-checkbox");

            checkbox.prop("checked", !checkbox.prop("checked"));
        });

        // Select all users checkbox
        $(document).on("click", "#select-all-users", function () {
            const checked = $(this).is(":checked");
            $(".user-checkbox").prop("checked", checked);
        });

        window.userRowToggleListener = true;
    }

    // SHIFT EDIT MODAL EVENTS

    const modal = document.getElementById("shiftModal");
    const modalSelect = document.getElementById("modalShiftSelect");
    let currentUserId = null;
    let currentDate = null;

    // Event delegation with jQuery
    $(document).on("click", ".open-shift-modal", function () {
        currentUserId = $(this).data("user");
        currentDate = $(this).data("date");

        // Pre-select current shift if available
        const currentShift = $(this).closest("td").find(".shift-view div span").first().text() || "";
        const option = $("#modalShiftSelect option").filter(function () {
            return $(this).text() === currentShift;
        }).val() || "";

        $("#modalShiftSelect").val(option);

        $("#shiftModal").removeClass("hidden").addClass("flex");
    });

    // Cancel modal
    $(document).on("click", "#modalCancel", function () {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    });

    // Save modal
    $(document).on("click", "#modalSave", function () {
        const shiftId = modalSelect.value;
        if (!shiftId) return;

        fetch("/schedule-shift/update", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ users: [currentUserId], date_from: currentDate, date_to: currentDate, shift_id: shiftId })
        })
            .then(res => res.json())
            .then(() => {
                modal.classList.add("hidden");
                modal.classList.remove("flex");
                loadWeek(currentWeek); // reload current week so table stays updated
            });
    });

});
