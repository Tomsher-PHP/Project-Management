// Import Flatpickr
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // base styles

export function initTimepicker(selector = ".timepicker", config = {}) {
    document.querySelectorAll(selector).forEach(el => {
        // Prevent double init
        if (el._flatpickr) return;

        const is24 = el.dataset.mode === "24"; // true if 24h

        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            time_24hr: is24,
            dateFormat: is24 ? "H:i" : "h:i K",
            defaultDate: el.value || null,
            ...config
        });
    });
}