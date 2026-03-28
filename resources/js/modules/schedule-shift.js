import { autoTomSelect } from '../components/tom-select';
import { initWeekPicker } from '../components/weekpicker';
import { Loader } from '../helpers/loader';

// Load week via AJAX
const loadWeek = async (date) => {
    if (!date) return;

    // If date is a Date object
    if (date instanceof Date) {
        date = date.toISOString().split("T")[0]; // "YYYY-MM-DD"
    }

    // If string already in YYYY-MM-DD format
    if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        date;
    }

    Loader.show();

    try {
        const res = await fetch(`/schedule-shift?week=${date}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!res.ok) throw new Error("Network error");

        const data = await res.json();

        document.querySelector("#schedule-table").innerHTML = data.html;
        document.getElementById("week-date-range").innerText = data.weekRange;

        currentWeek = date;

    } catch (err) {
        console.error("Failed to load schedule:", err);
    } finally {
        Loader.hide();
    }
};

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
    if (!window.shiftModalListener) {
        const modal = document.getElementById("shiftModal");
        const modalSelect = document.getElementById("modalShiftSelect");

        let currentUserId = null;
        let currentDate = null;
        let currentUserName = null;
        let formattedDate = null;

        // Event delegation with jQuery
        $(document).on("click", ".open-shift-modal", function () {
            currentUserId = $(this).data("user");
            currentDate = $(this).data("date");
            currentUserName = $(this).data("username");
            formattedDate = formatDate(currentDate);

            // Display existing details
            $("#modalUserName").text(currentUserName);
            $("#modalDate").text(formattedDate);

            // Detect current shift in cell
            const currentShift = $(this)
                .closest("td")
                .find(".shift-view div span")
                .first()
                .text()
                .trim();

            const option = $("#modalShiftSelect option").filter(function () {
                return $(this).text().trim() === currentShift;
            }).val() || "";

            autoTomSelect("modalShiftSelect", option);

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

            const dateObj = new Date(currentDate);
            const formattedDate = dateObj.toISOString().split("T")[0];

            fetch("/schedule-shift/update", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ users: [currentUserId], date_from: formattedDate, date_to: formattedDate, shift_id: shiftId })
            })
                .then(res => res.json())
                .then(() => {
                    modal.classList.add("hidden");
                    modal.classList.remove("flex");

                    loadWeek(currentWeek);
                });
        });

        window.shiftModalListener = true;
    }

    const formatDate = (date) => {
        const dateObj = new Date(date);

        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = dateObj.getFullYear();

        return `${day}/${month}/${year}`;
    };

});
