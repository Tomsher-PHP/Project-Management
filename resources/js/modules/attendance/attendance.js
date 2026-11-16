
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

    const openCreateBtn = document.getElementById("open_create_meeting_modal_btn");

    if (openCreateBtn) {
        openCreateBtn.addEventListener("click", openCreateModal);
    }


    /*
     * ==========================================================
     * Attendance Leave Form - Unpaid Leave Confirmation
     * ==========================================================
     */
    const attendanceLeaveForm = document.getElementById("markAttendanceForm");

    if (attendanceLeaveForm) {
        attendanceLeaveForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const form = attendanceLeaveForm;
            const confirmInput = document.getElementById("confirm_unpaid_leave");
            const submitButton = form.querySelector('button[type="submit"]');

            const originalButtonText = submitButton?.textContent;

            const submitAttendanceLeave = async () => {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = "Processing...";
                }

                try {
                    const response = await fetch(form.getAttribute("action"), {
                        method: form.method || "POST",
                        body: new FormData(form),
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    });

                    // Laravel redirects after successful leave creation.
                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }

                    const data = await response.json();

                    // Handle unpaid leave confirmation.
                    if (response.status === 422 && data.requires_confirmation) {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = originalButtonText;
                        }

                        const result = await Alert.confirm({
                            title: "Unpaid Leave Confirmation",
                            text: data.message,
                            confirmText: "Yes, Proceed",
                            cancelText: "No, Cancel",
                        });

                        const confirmed =
                            result === true ||
                            result?.isConfirmed === true ||
                            result?.confirmed === true;

                        if (confirmed) {
                            confirmInput.value = "1";
                            await submitAttendanceLeave();
                            return;
                        }

                        // Redirect to the employee's leave balance page.
                        if (data.cancel_url) {
                            window.location.href = data.cancel_url;
                        }

                        return;
                    }

                    if (!response.ok) {
                        const message = data.message ||
                            Object.values(data.errors || {}).flat()[0] ||
                            "Unable to add leave. Please try again.";

                        Alert.error({
                            title: "Unable to Add Leave",
                            text: message,
                        });

                        return;
                    }

                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }

                    window.location.reload();

                } catch (error) {
                    console.error("Attendance leave submission failed:", error);

                    Alert.error({
                        title: "Error",
                        text: "Something went wrong while adding the leave. Please try again.",
                    });
                } finally {
                    if (submitButton && submitButton.disabled) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText;
                    }
                }
            };

            await submitAttendanceLeave();
        });
    }

});
