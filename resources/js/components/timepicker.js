import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

/**
 * Determines whether to use 24-hour format or 12-hour (AM/PM) format.
 * Checks dataset overrides on the element first, then window/meta global settings from AppServiceProvider.
 */
export function getSystemTime24hr(el = null) {
    if (el) {
        if (el.dataset.mode === "24") return true;
        if (el.dataset.mode === "12") return false;
        if (el.dataset.time24hr !== undefined && el.dataset.time24hr !== null && el.dataset.time24hr !== "") {
            return ["true", "1", "yes", "on"].includes(String(el.dataset.time24hr).toLowerCase());
        }
    }

    const fmt = window.globalTimeFormat ||
        document.querySelector('meta[name="time-format"]')?.content ||
        "H:i";

    if (/H|G/.test(fmt)) {
        return true;
    }
    if (/h|g|a|A/.test(fmt)) {
        return false;
    }

    return true;
}

export function initTimepicker(selector = ".timepicker", config = {}, root = document) {
    root.querySelectorAll(selector).forEach(el => {
        if (el._flatpickr) return;

        const is24 = getSystemTime24hr(el);
        const enableSeconds = el.dataset.enableSeconds === "true";
        let defaultDate = null;

        if (el.value) {
            let val = el.value.trim();
            const isPm = /pm$/i.test(val);
            const isAm = /am$/i.test(val);
            val = val.replace(/(am|pm)/i, '').trim();

            const parts = val.split(':');
            if (parts.length >= 2) {
                let hour = parseInt(parts[0], 10);
                const minute = parseInt(parts[1], 10);
                const second = parts[2] ? parseInt(parts[2], 10) : 0;

                if (!Number.isNaN(hour) && !Number.isNaN(minute)) {
                    if (isPm && hour < 12) hour += 12;
                    if (isAm && hour === 12) hour = 0;

                    defaultDate = new Date();
                    defaultDate.setHours(hour, minute, Number.isNaN(second) ? 0 : second, 0);
                }
            }
        }

        const defaultFormat = is24
            ? (enableSeconds ? "H:i:S" : "H:i")
            : (enableSeconds ? "h:i:S K" : "h:i K");

        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            enableSeconds,
            time_24hr: is24,
            dateFormat: el.dataset.format || defaultFormat,
            defaultDate: defaultDate,
            ...config,
        });
    });
}
