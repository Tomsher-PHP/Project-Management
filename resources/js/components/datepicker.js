// Import Flatpickr
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // base styles

export function initDatepicker(selector = ".datepicker", config = {}, root = document) {
    root.querySelectorAll(selector).forEach(el => {
        // Prevent double init
        if (el._flatpickr) return;

        const mode = el.dataset.mode || "single"; // "single" or "range"
        const dateFormat = el.dataset.format || "Y-m-d";
        const minDate = el.dataset.minDate || null;
        const maxDate = el.dataset.maxDate || null;
        const openToDate = el.dataset.openToDate || null;
        const providedOnOpen = config.onOpen;

        flatpickr(el, {
            ...config,
            mode: mode,
            dateFormat: dateFormat,
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
