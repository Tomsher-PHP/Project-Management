
import { initMonthpicker } from "../../components/monthpicker";

document.addEventListener("DOMContentLoaded", () => {
    /*
     * ==========================================================
     * Attendance Month Picker
     * ==========================================================
     */
    const monthPickerInput = document.getElementById(
        "attendance_month_picker"
    );

    const monthPickerWrapper = document.getElementById(
        "attendance_month_picker_wrapper"
    );

    const monthPickerBtn = document.getElementById(
        "attendance_month_picker_btn"
    );

    if (monthPickerInput) {
        initMonthpicker("#attendance_month_picker", {
            onChange: (selectedDates, dateStr) => {
                if (!dateStr) {
                    return;
                }

                const currentUrl = new URL(
                    window.location.href
                );

                /*
                 * Preserve all existing filters and only
                 * change the calendar month.
                 */
                currentUrl.searchParams.set(
                    "calendar_date",
                    `${dateStr}-01`
                );

                window.location.href =
                    currentUrl.toString();
            },
        });

        /*
         * Open month picker from calendar icon.
         */
        const openPicker = (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (monthPickerInput._flatpickr) {
                monthPickerInput._flatpickr.open();
            }
        };

        if (monthPickerBtn) {
            monthPickerBtn.addEventListener(
                "click",
                openPicker
            );
        }

        /*
         * Allow clicking anywhere inside the picker wrapper.
         */
        if (monthPickerWrapper) {
            monthPickerWrapper.addEventListener(
                "click",
                (event) => {
                    if (
                        event.target !== monthPickerBtn &&
                        !monthPickerBtn?.contains(
                            event.target
                        )
                    ) {
                        openPicker(event);
                    }
                }
            );
        }
    }
});
