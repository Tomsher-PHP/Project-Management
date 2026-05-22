import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

export function initTimepicker(selector = ".timepicker", config = {}, root = document) {
    root.querySelectorAll(selector).forEach(el => {
        if (el._flatpickr) return;

        const is24 = el.dataset.mode === "24";
        const enableSeconds = el.dataset.enableSeconds === "true";
        let defaultDate = null;

        if (el.value) {
            const [h, m, s = '0'] = el.value.split(':');
            const hour = parseInt(h);
            const minute = parseInt(m);
            const second = parseInt(s);

            // Create a Date object (works for both 12h and 24h)
            defaultDate = new Date();
            defaultDate.setHours(hour, minute, Number.isNaN(second) ? 0 : second, 0);
        }

        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            enableSeconds,
            time_24hr: is24,
            dateFormat: is24
                ? (enableSeconds ? "H:i:S" : "H:i")
                : (enableSeconds ? "h:i:S K" : "h:i K"),
            defaultDate: defaultDate,
            ...config,
        });
    });
}
