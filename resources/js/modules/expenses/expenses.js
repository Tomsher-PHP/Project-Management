import Alert from "../../alert";
import { initTomSelect } from "../../components/tom-select";
import { initDatepicker } from "../../components/datepicker";

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("expense_modal");
    if (!modal) return;

    const openCreateBtn = document.getElementById("open_create_expense_modal_btn");
    const closeBtns = document.querySelectorAll("[data-expense-modal-close]");
    const form = document.getElementById("expense_form");
    const formMethodInput = document.getElementById("expense_form_method");
    const modalTitle = document.getElementById("expense_modal_title");
    const submitBtn = document.getElementById("expense_submit_btn");
    const errorContainer = document.getElementById("expense_form_errors");
    const errorList = document.getElementById("expense_errors_list");

    // Form fields
    const expenseIdInput = document.getElementById("expense_id_input");
    const paymentModeSelect = document.getElementById("expense_payment_mode_id");
    const vatPaymentSelect = document.getElementById("expense_vat_payment");
    const localIntlSelect = document.getElementById("expense_local_intl_payment");
    const paidDateInput = document.getElementById("expense_paid_date");
    const invoiceDateInput = document.getElementById("expense_invoice_date");
    const paymentCurrencyInput = document.getElementById("expense_payment_currency");
    const otherCurrencyInput = document.getElementById("expense_other_currency");
    const otherAmountInput = document.getElementById("expense_other_amount");
    const paymentAmountInput = document.getElementById("expense_payment_amount");
    const vatAmountInput = document.getElementById("expense_vat_amount");
    const bankChargesInput = document.getElementById("expense_bank_charges");
    const invoiceNumberInput = document.getElementById("expense_invoice_number");
    const serviceProviderSelect = document.getElementById("expense_service_provider_id");
    const categorySelect = document.getElementById("expense_category_id");
    const serviceProductInput = document.getElementById("expense_service_product");
    const customerSelect = document.getElementById("expense_customer_id");
    const commentInput = document.getElementById("expense_comment");

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

    function setSelectValue(selectEl, value) {
        if (!selectEl) return;
        const valStr = value !== null && value !== undefined ? String(value) : "";

        if (selectEl.tomselect) {
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
        if (expenseIdInput) expenseIdInput.value = "";
        if (formMethodInput) formMethodInput.value = "POST";
        if (form) form.action = form.dataset.createUrl;

        if (modalTitle) modalTitle.textContent = "Add New Expense";
        if (submitBtn) {
            submitBtn.textContent = "Save Expense";
            submitBtn.disabled = false;
        }

        // Reset TomSelects
        const defaultPaymentMode = paymentModeSelect?.dataset?.defaultValue || paymentModeSelect?.querySelector('option[selected]')?.value || "";
        const defaultServiceProvider = serviceProviderSelect?.dataset?.defaultValue || serviceProviderSelect?.querySelector('option[selected]')?.value || "";
        const defaultCategory = categorySelect?.dataset?.defaultValue || categorySelect?.querySelector('option[selected]')?.value || "";

        setSelectValue(paymentModeSelect, defaultPaymentMode);
        setSelectValue(vatPaymentSelect, "");
        setSelectValue(localIntlSelect, "");
        setSelectValue(serviceProviderSelect, defaultServiceProvider);
        setSelectValue(categorySelect, defaultCategory);
        setSelectValue(customerSelect, "");

        // Set Today Date for Datepickers
        const todayStr = getTodayDateString();
        setDatepickerValue(paidDateInput, todayStr);
        setDatepickerValue(invoiceDateInput, todayStr);

        // Reset defaults
        if (paymentCurrencyInput) paymentCurrencyInput.value = "AED";
        if (otherCurrencyInput) otherCurrencyInput.value = "";
        if (otherAmountInput) otherAmountInput.value = "";
        if (paymentAmountInput) paymentAmountInput.value = "";
        if (vatAmountInput) vatAmountInput.value = "";
        if (bankChargesInput) bankChargesInput.value = "";
        if (invoiceNumberInput) invoiceNumberInput.value = "";
        if (serviceProductInput) serviceProductInput.value = "";
        if (commentInput) commentInput.value = "";
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

    // Open Edit Modal
    document.addEventListener("click", async (e) => {
        const editBtn = e.target.closest(".open-edit-expense-modal-btn");
        if (!editBtn) return;

        e.preventDefault();
        resetForm();

        const updateUrl = editBtn.dataset.updateUrl;
        const fetchUrl = editBtn.dataset.fetchUrl;
        const inlineDataRaw = editBtn.dataset.expense;

        if (modalTitle) modalTitle.textContent = "Edit Expense";
        if (submitBtn) submitBtn.textContent = "Update Expense";

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
                console.error("Failed to parse inline expense data:", err);
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
                console.error("Failed to fetch expense details:", err);
            }
        }

        if (data) {
            populateForm(data);
        }
    });

    function populateForm(data) {
        if (expenseIdInput) expenseIdInput.value = data.id || "";
        setSelectValue(paymentModeSelect, data.payment_mode_id);
        setSelectValue(vatPaymentSelect, data.vat_payment);
        setSelectValue(localIntlSelect, data.local_intl_payment);
        setSelectValue(serviceProviderSelect, data.service_provider_id);
        setSelectValue(categorySelect, data.category_id);
        setSelectValue(customerSelect, data.customer_id);

        setDatepickerValue(paidDateInput, data.paid_date || getTodayDateString());
        setDatepickerValue(invoiceDateInput, data.invoice_date || getTodayDateString());

        if (paymentCurrencyInput) paymentCurrencyInput.value = data.payment_currency || "AED";
        if (otherCurrencyInput) otherCurrencyInput.value = data.other_currency || "";
        if (otherAmountInput) otherAmountInput.value = data.other_amount !== null && data.other_amount !== undefined ? data.other_amount : "";
        if (paymentAmountInput) paymentAmountInput.value = data.payment_amount !== null && data.payment_amount !== undefined ? data.payment_amount : "";
        if (vatAmountInput) vatAmountInput.value = data.vat_amount !== null && data.vat_amount !== undefined ? data.vat_amount : "";
        if (bankChargesInput) bankChargesInput.value = data.bank_charges !== null && data.bank_charges !== undefined ? data.bank_charges : "";
        if (invoiceNumberInput) invoiceNumberInput.value = data.invoice_number || "";
        if (serviceProductInput) serviceProductInput.value = data.service_product || "";
        if (commentInput) commentInput.value = data.comment || "";
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
                        window.Alert.success(result.message || "Expense saved successfully.");
                    }
                    closeModal();
                    window.location.reload();
                } else if (response.status === 422) {
                    displayErrors(result.errors || result.message || "Validation failed.");
                } else {
                    displayErrors(result.message || "An error occurred while saving the expense.");
                }
            } catch (err) {
                console.error("Expense form submission error:", err);
                displayErrors("An unexpected network error occurred.");
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = formMethodInput.value === "PUT" ? "Update Expense" : "Save Expense";
                }
            }
        });
    }
});
