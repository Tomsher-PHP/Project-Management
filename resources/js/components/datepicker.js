// Import Flatpickr
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // base styles

const parseBoolean = (value, defaultValue = false) => {
    if (value === undefined || value === null || value === "") {
        return defaultValue;
    }

    return ["true", "1", "yes", "on"].includes(String(value).toLowerCase());
};

export function initDatepicker(selector = ".datepicker", config = {}, root = document) {
    root.querySelectorAll(selector).forEach(el => {
        // Prevent double init
        if (el._flatpickr) return;

        const mode = el.dataset.mode || "single"; // "single" or "range"
        const dateFormat = el.dataset.format || "Y-m-d";
        const minDate = el.dataset.minDate || null;
        const maxDate = el.dataset.maxDate || null;
        const openToDate = el.dataset.openToDate || null;
        const enableTime = parseBoolean(el.dataset.enableTime, false);
        const enableSeconds = parseBoolean(el.dataset.enableSeconds, false);
        const noCalendar = parseBoolean(el.dataset.noCalendar, false);
        const time24hr = parseBoolean(el.dataset.time24hr, true);
        const altInput = parseBoolean(el.dataset.altInput, false);
        const altFormat = el.dataset.altFormat || null;
        const minuteIncrement = Number(el.dataset.minuteIncrement || 5);
        const defaultHour = Number(el.dataset.defaultHour || 9);
        const defaultMinute = Number(el.dataset.defaultMinute || 0);
        const providedOnOpen = config.onOpen;

        flatpickr(el, {
            ...config,
            mode: mode,
            dateFormat: dateFormat,
            enableTime: enableTime,
            enableSeconds: enableSeconds,
            noCalendar: noCalendar,
            time_24hr: time24hr,
            altInput: altInput,
            altFormat: altFormat,
            minuteIncrement: Number.isNaN(minuteIncrement) ? 5 : minuteIncrement,
            defaultHour: Number.isNaN(defaultHour) ? 9 : defaultHour,
            defaultMinute: Number.isNaN(defaultMinute) ? 0 : defaultMinute,
            allowInput: true,
            minDate: minDate,
            maxDate: maxDate,
            onOpen: (selectedDates, dateStr, instance) => {
                if (!dateStr && openToDate) {
                    instance.jumpToDate(openToDate);
                }

                if (typeof providedOnOpen === 'function') {
                    providedOnOpen(selectedDates, dateStr, instance);
                }
            },
        });
    });
}
