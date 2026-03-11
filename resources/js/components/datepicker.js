// Import Flatpickr
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css"; // base styles

export function initDatepicker(selector = ".datepicker", config = {}) {
    document.querySelectorAll(selector).forEach(el => {
        // Prevent double init
        if (el._flatpickr) return;

        const mode = el.dataset.mode || "single"; // "single" or "range"
        const dateFormat = el.dataset.format || "Y-m-d";
        const minDate = el.dataset.minDate || null;

        flatpickr(el, {
            mode: mode,
            dateFormat: dateFormat,
            allowInput: true,
            minDate: minDate,
        });
    });
}