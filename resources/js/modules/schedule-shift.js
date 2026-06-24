import { autoTomSelect } from '../components/tom-select';
import { initWeekPicker } from '../components/weekpicker';
import { Loader } from '../helpers/loader';

const getSelectedTeamId = () => document.getElementById("teamFilterSelect")?.value || "";
const getSelectedPerPage = () => document.querySelector("#schedule-table select[name='per_page']")?.value || "";

const syncWeekPickerInput = (date) => {
    const input = document.querySelector(".weekPicker");
    if (!input || !date) return;

    input.value = date;

    if (input._flatpickr) {
        input._flatpickr.setDate(date, false, "Y-m-d");
        input._flatpickr.jumpToDate(date);
    }
};

const updateScheduleUrl = (params) => {
    const url = new URL(window.location.href);
    url.search = params.toString();
    window.history.pushState({}, "", url);
};

// Load week via AJAX
const loadWeek = async (date, extraParams = {}) => {
    if (!date) return;

    // If date is a Date object
    if (date instanceof Date) {
        date = date.toISOString().split("T")[0]; // "YYYY-MM-DD"
    }

    // If string already in YYYY-MM-DD format
    if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        date;
    }

    Loader.show();

    try {
        const params = new URLSearchParams(extraParams);
        params.set("week", date);

        const teamId = getSelectedTeamId();
        const perPage = getSelectedPerPage();

        if (teamId) {
            params.set("team_id", teamId);
        } else {
            params.delete("team_id");
        }

        if (perPage && !params.has("per_page")) {
            params.set("per_page", perPage);
        }

        const res = await fetch(`/schedule-shift?${params.toString()}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        if (!res.ok) throw new Error("Network error");

        const data = await res.json();

        document.querySelector("#schedule-table").innerHTML = data.html;
        document.getElementById("week-date-range").innerText = data.weekRange;
        const selectAllUsers = document.getElementById("select-all-users");
        if (selectAllUsers) selectAllUsers.checked = false;

        currentWeek = date;
        syncWeekPickerInput(date);
        updateScheduleUrl(params);

        const toggleTodayHighlight = (dateStr) => {
            const todayBtn = document.getElementById("todayWeek");
            if (!todayBtn) return;
            
            const todayStr = todayBtn.dataset.today;
            if (!todayStr) return;
            
            const todayDate = new Date(todayStr);
            todayDate.setHours(0, 0, 0, 0);
            
            const startDate = new Date(dateStr);
            startDate.setHours(0, 0, 0, 0);
            
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 6);
        
            const isTodayInWeek = todayDate >= startDate && todayDate <= endDate;
            
            if (isTodayInWeek) {
                todayBtn.className = "rounded-md px-3 py-1.5 text-sm font-semibold transition bg-success-50 text-success-600 hover:bg-success-100 dark:bg-success-500/10 dark:text-success-400 dark:hover:bg-success-500/20";
            } else {
                todayBtn.className = "rounded-md px-3 py-1.5 text-sm font-semibold transition text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white";
            }
        };
        toggleTodayHighlight(date);

    } catch (err) {
        console.error("Failed to load schedule:", err);
    } finally {
        Loader.hide();
    }
};

// Initialize schedule shift events
export function initScheduleShift(startOfWeek) {
    currentWeek = startOfWeek ? new Date(startOfWeek) : new Date();

    // Attach navigation listeners once
    if (!window.scheduleShiftNavListeners) {

        document.getElementById("nextWeek").addEventListener("click", () => {
            let next = new Date(currentWeek);
            next.setDate(next.getDate() + 7);
            loadWeek(next.toISOString().split('T')[0]);
        });

        document.getElementById("prevWeek").addEventListener("click", () => {
            let prev = new Date(currentWeek);
            prev.setDate(prev.getDate() - 7);
            loadWeek(prev.toISOString().split('T')[0]);
        });

        document.getElementById("todayWeek")?.addEventListener("click", (event) => {
            const today = event.currentTarget.dataset.today;
            loadWeek(today, { page: 1 });
        });

        document.getElementById("weekPickerBtn").addEventListener("click", () => {
            const picker = document.querySelector(".weekPicker")?._flatpickr;
            if (picker) picker.open();
        });

        document.getElementById("teamFilterSelect")?.addEventListener("change", () => {
            loadWeek(currentWeek, { page: 1 });
        });

        window.scheduleShiftNavListeners = true;
    }
}

document.addEventListener("DOMContentLoaded", () => {

    const input = document.querySelector(".weekPicker");

    if (!input) return;

    initWeekPicker(".weekPicker", loadWeek);
    syncWeekPickerInput(input.value);
    initScheduleShift(input.value);

    // Get selected users and redirect to schedule shift create page
    const btn = document.getElementById('schedule-shift-btn');
    if (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const selectedUsers = [...document.querySelectorAll('.user-checkbox:checked')]
                .map(cb => cb.value);

            sessionStorage.setItem('preSelectedUsers', JSON.stringify(selectedUsers));
            window.location.href = this.href;
        });
    }

    if (!window.userRowToggleListener) {

        // Toggle checkbox when user name is clicked
        $(document).on("click", ".label-user-name", function () {
            const row = $(this).closest("tr");
            const checkbox = row.find(".user-checkbox");

            checkbox.prop("checked", !checkbox.prop("checked"));
        });

        // Select all users checkbox
        $(document).on("click", "#select-all-users", function () {
            const checked = $(this).is(":checked");
            $(".user-checkbox").prop("checked", checked);
        });

        window.userRowToggleListener = true;
    }

    if (!window.scheduleShiftPaginationListener) {
        document.addEventListener("click", (event) => {
            const link = event.target.closest("#schedule-table a[href]");
            if (!link) return;

            const url = new URL(link.href);
            if (!url.searchParams.has("page")) return;

            event.preventDefault();

            loadWeek(currentWeek, Object.fromEntries(url.searchParams));
        });

        document.addEventListener("change", (event) => {
            const perPageSelect = event.target.closest("#schedule-table select[name='per_page']");
            if (!perPageSelect) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            loadWeek(currentWeek, {
                page: 1,
                per_page: perPageSelect.value,
            });
        }, true);

        window.scheduleShiftPaginationListener = true;
    }

    // SHIFT EDIT MODAL EVENTS
    if (!window.shiftModalListener) {
        const modal = document.getElementById("shiftModal");
        const modalSelect = document.getElementById("modalShiftSelect");

        let currentUserId = null;
        let currentDate = null;
        let currentUserName = null;
        let formattedDate = null;
        const injectedShiftOptionIds = new Set();

        const removeInjectedShiftOptions = (currentShiftId) => {
            injectedShiftOptionIds.forEach((shiftId) => {
                if (shiftId === currentShiftId) return;

                if (modalSelect.tomselect) {
                    modalSelect.tomselect.removeOption(shiftId);
                } else {
                    Array.from(modalSelect.options)
                        .find((option) => option.value === shiftId)
                        ?.remove();
                }

                injectedShiftOptionIds.delete(shiftId);
            });
        };

        const ensureShiftOption = (shiftId, shiftName, shiftTime) => {
            if (!shiftId || !shiftName) return;

            const shiftOption = {
                value: shiftId,
                text: shiftName,
                subtype: shiftTime || '',
            };

            if (modalSelect.tomselect) {
                if (!modalSelect.tomselect.options[shiftId]) {
                    modalSelect.tomselect.addOption(shiftOption);
                    injectedShiftOptionIds.add(shiftId);
                    modalSelect.tomselect.refreshOptions(false);
                }

                return;
            }

            const exists = Array.from(modalSelect.options)
                .some((option) => option.value === shiftId);

            if (!exists) {
                const option = new Option(shiftName, shiftId);
                option.dataset.subtype = shiftTime || '';
                modalSelect.add(option);
                injectedShiftOptionIds.add(shiftId);
            }
        };

        // Event delegation with jQuery
        $(document).on("click", ".open-shift-modal", function () {
            currentUserId = $(this).data("user");
            currentDate = $(this).data("date");
            currentUserName = $(this).data("username");
            formattedDate = formatDate(currentDate);
            const currentShiftId = this.getAttribute("data-shift-id") || "";
            const currentShiftName = this.getAttribute("data-shift-name") || "";
            const currentShiftTime = this.getAttribute("data-shift-time") || "";
            removeInjectedShiftOptions(currentShiftId);

            // Display existing details
            $("#modalUserName").text(currentUserName);
            $("#modalDate").text(formattedDate);

            ensureShiftOption(currentShiftId, currentShiftName, currentShiftTime);

            if (modalSelect.tomselect) {
                autoTomSelect("modalShiftSelect", currentShiftId);
            } else {
                modalSelect.value = currentShiftId;
            }

            $("#shiftModal").removeClass("hidden").addClass("flex");
        });

        // Cancel modal
        $(document).on("click", "#modalCancel", function () {
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        });

        // Save modal
        $(document).on("click", "#modalSave", function () {
            const shiftId = modalSelect.tomselect ? modalSelect.tomselect.getValue() : modalSelect.value;
            if (!shiftId) return;

            const dateObj = new Date(currentDate);
            const formattedDate = dateObj.toISOString().split("T")[0];

            fetch("/schedule-shift/update", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ users: [currentUserId], date_from: formattedDate, date_to: formattedDate, shift_id: shiftId })
            })
                .then(res => res.json())
                .then(() => {
                    modal.classList.add("hidden");
                    modal.classList.remove("flex");

                    loadWeek(currentWeek);
                });
        });

        window.shiftModalListener = true;
    }

    const formatDate = (date) => {
        const dateObj = new Date(date);

        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = dateObj.getFullYear();

        return `${day}/${month}/${year}`;
    };

});
