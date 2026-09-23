import Alert from "../../alert";
import { initTomSelect } from "../../components/tom-select";
import { initDatepicker } from "../../components/datepicker";

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("cheque_modal");
    if (!modal) return;

    const openCreateBtn = document.getElementById("open_create_cheque_modal_btn");
    const closeBtns = document.querySelectorAll("[data-cheque-modal-close]");
    const form = document.getElementById("cheque_form");
    const formMethodInput = document.getElementById("cheque_form_method");
    const modalTitle = document.getElementById("cheque_modal_title");
    const submitBtn = document.getElementById("cheque_submit_btn");
    const errorContainer = document.getElementById("cheque_form_errors");
    const errorList = document.getElementById("cheque_errors_list");

    // Form fields
    const chequeIdInput = document.getElementById("cheque_id_input");
    const chequeNumberInput = document.getElementById("cheque_number");
    const amountInput = document.getElementById("cheque_amount");
    const chequeDateInput = document.getElementById("cheque_date");
    const chequeGivenInput = document.getElementById("cheque_given");
    const chequeToInput = document.getElementById("cheque_to");
    const purposeInput = document.getElementById("cheque_purpose");
    const statusSelect = document.getElementById("cheque_status");
    const debitedDateInput = document.getElementById("cheque_debited_date");

    // Initialize TomSelect and Datepicker inside modal
    initTomSelect(modal);
    initDatepicker(".datepicker", {}, modal);

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    }

    function clearErrors() {
        if (errorContainer) errorContainer.classList.add("hidden");
        if (errorList) errorList.innerHTML = "";
    }

    function displayErrors(errors) {
        if (!errorContainer || !errorList) return;
        errorList.innerHTML = "";

        if (typeof errors === "object" && errors !== null) {
            Object.values(errors).forEach((errArr) => {
                const messages = Array.isArray(errArr) ? errArr : [errArr];
                messages.forEach((msg) => {
                    const li = document.createElement("li");
                    li.textContent = msg;
                    errorList.appendChild(li);
                });
            });
        } else if (typeof errors === "string") {
            const li = document.createElement("li");
            li.textContent = errors;
            errorList.appendChild(li);
        }

        errorContainer.classList.remove("hidden");
        errorContainer.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    function setSelectValue(selectEl, value, label = null) {
        if (!selectEl) return;
        const valStr = value !== null && value !== undefined ? String(value) : "";

        if (selectEl.tomselect) {
            if (valStr && !selectEl.tomselect.options[valStr]) {
                const optText = label || valStr;
                selectEl.tomselect.addOption({ value: valStr, text: optText });
            }
            selectEl.tomselect.setValue(valStr, true);
        } else {
            selectEl.value = valStr;
        }
    }

    function setDatepickerValue(inputEl, value) {
        if (!inputEl) return;
        if (inputEl._flatpickr) {
            if (value) {
                inputEl._flatpickr.setDate(value, true);
            } else {
                inputEl._flatpickr.clear();
            }
        } else {
            inputEl.value = value || "";
        }
    }

    function getTodayDateString() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function resetForm() {
        clearErrors();
        if (form) form.reset();
        if (chequeIdInput) chequeIdInput.value = "";
        if (formMethodInput) formMethodInput.value = "POST";
        if (form) form.action = form.dataset.createUrl;

        if (modalTitle) modalTitle.textContent = "Add New Cheque Expense";
        if (submitBtn) {
            submitBtn.textContent = "Save Cheque Expense";
            submitBtn.disabled = false;
        }

        // Reset TomSelect status to default
        const defaultStatus = statusSelect?.dataset?.defaultValue || statusSelect?.querySelector('option[selected]')?.value || "Pending";
        setSelectValue(statusSelect, defaultStatus);

        // Reset Datepickers
        const todayStr = getTodayDateString();
        setDatepickerValue(chequeDateInput, todayStr);
        setDatepickerValue(chequeGivenInput, todayStr);
        setDatepickerValue(debitedDateInput, "");

        // Reset inputs
        if (chequeNumberInput) chequeNumberInput.value = "";
        if (amountInput) amountInput.value = "";
        if (chequeToInput) chequeToInput.value = "";
        if (purposeInput) purposeInput.value = "";
    }

    function openModal() {
        modal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
    }

    function closeModal() {
        modal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
        resetForm();
    }

    // Open Create Modal
    if (openCreateBtn) {
        openCreateBtn.addEventListener("click", () => {
            resetForm();
            openModal();
        });
    }

    // Close Modal triggers
    closeBtns.forEach((btn) => {
        btn.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Show Modal elements
    const showModal = document.getElementById("cheque_show_modal");
    const showModalContent = document.getElementById("cheque_show_modal_content");
    const closeShowBtns = document.querySelectorAll("[data-cheque-show-modal-close]");

    function openShowModal() {
        if (!showModal) return;
        showModal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
    }

    function closeShowModal() {
        if (!showModal) return;
        showModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
        if (showModalContent) {
            showModalContent.innerHTML = `<div class="flex items-center justify-center py-12 text-bgray-500"><svg class="animate-spin h-8 w-8 text-success-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>`;
        }
    }

    closeShowBtns.forEach((btn) => {
        btn.addEventListener("click", closeShowModal);
    });

    if (showModal) {
        showModal.addEventListener("click", (e) => {
            if (e.target === showModal) {
                closeShowModal();
            }
        });
    }

    // Open Show or Edit Modal
    document.addEventListener("click", async (e) => {
        const showBtn = e.target.closest(".open-show-cheque-modal-btn");
        if (showBtn) {
            e.preventDefault();
            const showUrl = showBtn.dataset.showUrl;
            if (!showUrl) return;

            openShowModal();

            try {
                const response = await fetch(showUrl, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });
                const resJson = await response.json();
                if (resJson.status && resJson.html && showModalContent) {
                    showModalContent.innerHTML = resJson.html;
                }
            } catch (err) {
                console.error("Failed to fetch cheque details:", err);
                if (showModalContent) {
                    showModalContent.innerHTML = `<div class="p-6 text-center text-red-600">Failed to load cheque details. Please try again.</div>`;
                }
            }
            return;
        }

        const editBtn = e.target.closest(".open-edit-cheque-modal-btn");
        if (!editBtn) return;

        e.preventDefault();
        resetForm();

        const updateUrl = editBtn.dataset.updateUrl;
        const fetchUrl = editBtn.dataset.fetchUrl;
        const inlineDataRaw = editBtn.dataset.cheque;

        if (modalTitle) modalTitle.textContent = "Edit Cheque Expense";
        if (submitBtn) submitBtn.textContent = "Update Cheque Expense";

        if (form && updateUrl) {
            form.action = updateUrl;
            if (formMethodInput) formMethodInput.value = "PUT";
        }

        openModal();

        let data = null;
        if (inlineDataRaw) {
            try {
                data = typeof inlineDataRaw === "string" ? JSON.parse(inlineDataRaw) : inlineDataRaw;
            } catch (err) {
                console.error("Failed to parse inline cheque data:", err);
            }
        }

        if (!data && fetchUrl) {
            try {
                const response = await fetch(fetchUrl, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });
                const resJson = await response.json();
                if (resJson.status && resJson.data) {
                    data = resJson.data;
                }
            } catch (err) {
                console.error("Failed to fetch cheque details:", err);
            }
        }

        if (data) {
            populateForm(data);
        }
    });

    function populateForm(data) {
        if (chequeIdInput) chequeIdInput.value = data.id || "";

        if (chequeNumberInput) chequeNumberInput.value = data.cheque_number || "";
        if (amountInput) amountInput.value = data.amount !== null && data.amount !== undefined ? data.amount : "";
        if (chequeToInput) chequeToInput.value = data.cheque_to || "";
        if (purposeInput) purposeInput.value = data.purpose || "";

        setSelectValue(statusSelect, data.cheque_status);

        setDatepickerValue(chequeDateInput, data.cheque_date || getTodayDateString());
        setDatepickerValue(chequeGivenInput, data.cheque_given || getTodayDateString());
        setDatepickerValue(debitedDateInput, data.debited_date || "");
    }

    // Form Submission Handler
    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearErrors();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Saving...";
            }

            const formData = new FormData(form);
            const url = form.action;

            try {
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": getCsrfToken(),
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: formData,
                });

                const result = await response.json();

                if (response.ok && result.status) {
                    if (window.Alert && typeof window.Alert.success === "function") {
                        window.Alert.success(result.message || "Cheque expense saved successfully.");
                    }
                    closeModal();
                    window.location.reload();
                } else if (response.status === 422) {
                    displayErrors(result.errors || result.message || "Validation failed.");
                } else {
                    displayErrors(result.message || "An error occurred while saving the cheque expense.");
                }
            } catch (err) {
                console.error("Cheque form submission error:", err);
                displayErrors("An unexpected network error occurred.");
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = formMethodInput.value === "PUT" ? "Update Cheque Expense" : "Save Cheque Expense";
                }
            }
        });
    }
});
