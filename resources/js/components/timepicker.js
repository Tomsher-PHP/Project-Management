import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

export function initTimepicker(selector = ".timepicker", config = {}) {
    document.querySelectorAll(selector).forEach(el => {
        if (el._flatpickr) return;

        const is24 = el.dataset.mode === "24";
        let defaultDate = null;

        if (el.value) {
            const [h, m] = el.value.split(':');
            const hour = parseInt(h);
            const minute = parseInt(m);

            // Create a Date object (works for both 12h and 24h)
            defaultDate = new Date();
            defaultDate.setHours(hour, minute, 0, 0);
        }

        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            time_24hr: is24,
            dateFormat: is24 ? "H:i" : "h:i K",
            defaultDate: defaultDate,
        });
    });
}