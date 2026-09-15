import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";
import "flatpickr/dist/plugins/monthSelect/style.css";

export function initMonthpicker(selector = ".monthpicker", config = {}, root = document) {
    root.querySelectorAll(selector).forEach((el) => {
        if (el._flatpickr) return;

        const openToDate = el.dataset.openToDate || null;
        const providedOnChange = config.onChange;

        flatpickr(el, {
            ...config,
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y",
                    theme: "light",
                }),
            ],
            defaultDate: el.value || null,
            onOpen: (selectedDates, dateStr, instance) => {
                if (!dateStr && openToDate) {
                    instance.jumpToDate(openToDate);
                }
                if (typeof config.onOpen === "function") {
                    config.onOpen(selectedDates, dateStr, instance);
                }
            },
            onChange: (selectedDates, dateStr, instance) => {
                if (typeof providedOnChange === "function") {
                    providedOnChange(selectedDates, dateStr, instance);
                }
            },
        });
    });
}
