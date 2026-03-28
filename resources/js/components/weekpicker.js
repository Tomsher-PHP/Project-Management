import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

export function initWeekPicker(selector = ".weekPicker", onWeekChange) {

    const input = document.querySelector(selector);
    if (!input) return;

    // Prevent multiple initialization
    if (input._flatpickr) return;

    flatpickr(input, {
        dateFormat: "Y-m-d",
        allowInput: true,
        onChange: function (selectedDates, dateStr) {
            if (!selectedDates.length) return;

            // Always get the Sunday of that week (start of week)
            const selectedDate = selectedDates[0];
            const day = selectedDate.getDay(); // 0 = Sunday
            const startOfWeek = new Date(selectedDate);
            startOfWeek.setDate(selectedDate.getDate() - day);

            // Format in local YYYY-MM-DD (avoid timezone shift)
            const startDateStr = `${startOfWeek.getFullYear()}-${String(startOfWeek.getMonth() + 1).padStart(2, '0')}-${String(startOfWeek.getDate()).padStart(2, '0')}`;

            // Call the callback passed from schedule-shift.js
            if (typeof onWeekChange === "function") {
                onWeekChange(startDateStr);
            }
        }
    });
}