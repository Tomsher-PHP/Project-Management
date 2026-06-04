import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

const getStartOfWeek = (date) => {
    const startOfWeek = new Date(date);
    startOfWeek.setDate(date.getDate() - date.getDay());
    startOfWeek.setHours(0, 0, 0, 0);

    return startOfWeek;
};

const formatLocalDate = (date) => {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

const clearWeekHoverHighlight = (instance) => {
    instance.calendarContainer
        ?.querySelectorAll('[data-week-hover="true"]')
        .forEach((day) => {
            day.removeAttribute('data-week-hover');
            day.style.backgroundColor = '';
            day.style.borderColor = '';
            day.style.color = '';
        });
};

const applyWeekHoverHighlight = (instance, date) => {
    if (!instance?.calendarContainer || !(date instanceof Date)) return;

    clearWeekHoverHighlight(instance);

    const startOfWeek = getStartOfWeek(date);
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    instance.calendarContainer
        .querySelectorAll('.flatpickr-day')
        .forEach((day) => {
            const dayDate = day.dateObj;
            if (!(dayDate instanceof Date)) return;

            const normalizedDayDate = new Date(dayDate);
            normalizedDayDate.setHours(0, 0, 0, 0);

            if (normalizedDayDate < startOfWeek || normalizedDayDate > endOfWeek) return;

            day.dataset.weekHover = 'true';

            if (day.classList.contains('selected')) {
                return;
            }

            day.style.backgroundColor = 'rgba(22, 163, 74, 0.12)';
            day.style.borderColor = 'rgba(22, 163, 74, 0.35)';
            day.style.color = '#15803d';
        });
};

const attachWeekHoverBehavior = (input) => {
    if (!input?._flatpickr || input._weekPickerHoverBound) return;

    const { daysContainer, calendarContainer, selectedDates } = input._flatpickr;

    daysContainer?.addEventListener('mouseover', (event) => {
        const day = event.target.closest('.flatpickr-day');
        if (!day?.dateObj) return;

        applyWeekHoverHighlight(input._flatpickr, day.dateObj);
    });

    calendarContainer?.addEventListener('mouseleave', () => {
        clearWeekHoverHighlight(input._flatpickr);

        if (selectedDates?.[0]) {
            applyWeekHoverHighlight(input._flatpickr, selectedDates[0]);
        }
    });

    input._weekPickerHoverBound = true;
};

const syncWeekPickerValue = (input, date) => {
    if (!input || !date) return;

    const normalizedDate = date instanceof Date
        ? formatLocalDate(date)
        : String(date);

    input.value = normalizedDate;

    if (input._flatpickr) {
        input._flatpickr.setDate(normalizedDate, false, "Y-m-d");
        input._flatpickr.jumpToDate(normalizedDate);
    }
};

const buildWeekChangeHandler = (onWeekChange) => {
    return function (selectedDates) {
        if (!selectedDates.length) return;

        const startDateStr = formatLocalDate(getStartOfWeek(selectedDates[0]));

        if (typeof onWeekChange === "function") {
            onWeekChange(startDateStr);
        }
    };
};

const attachWeekChangeHandler = (input, onWeekChange) => {
    if (typeof onWeekChange !== "function" || !input?._flatpickr) return;

    const handler = buildWeekChangeHandler(onWeekChange);
    const existingHandlers = Array.isArray(input._flatpickr.config.onChange)
        ? input._flatpickr.config.onChange
        : [input._flatpickr.config.onChange].filter(Boolean);

    if (input._weekPickerChangeHandler) {
        input._flatpickr.config.onChange = existingHandlers
            .filter((callback) => callback !== input._weekPickerChangeHandler);
    }

    input._weekPickerChangeHandler = handler;
    input._flatpickr.config.onChange.push(handler);
};

export function initWeekPicker(selector = ".weekPicker", onWeekChange, root = document) {

    const input = root.querySelector(selector);
    if (!input) return;

    // Prevent multiple initialization
    if (input._flatpickr) {
        attachWeekChangeHandler(input, onWeekChange);
        attachWeekHoverBehavior(input);
        return;
    }

    flatpickr(input, {
        dateFormat: "Y-m-d",
        allowInput: true,
        onReady: function (_, __, instance) {
            if (instance.selectedDates?.[0]) {
                applyWeekHoverHighlight(instance, instance.selectedDates[0]);
            }
        },
        onOpen: function (_, __, instance) {
            if (input.value) {
                syncWeekPickerValue(input, input.value);
            }

            if (instance.selectedDates?.[0]) {
                applyWeekHoverHighlight(instance, instance.selectedDates[0]);
            }
        },
        onChange: buildWeekChangeHandler(onWeekChange)
    });

    if (typeof onWeekChange === "function") {
        input._weekPickerChangeHandler = input._flatpickr.config.onChange[0];
    }

    attachWeekHoverBehavior(input);
    syncWeekPickerValue(input, input.value);
}
