import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

export function initWeekPicker(selector = ".weekPicker") {

    const input = document.querySelector(selector);
    if (!input) return;

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

            // Call your AJAX loader
            loadWeek(startDateStr);
        }
    });
}