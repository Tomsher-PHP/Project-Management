import Alert from "../../alert";
import { initTomSelect } from "../../components/tom-select";
import { initDatepicker } from "../../components/datepicker";

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("reimbursement_modal");
    if (!modal) return;

    const openCreateBtn = document.getElementById("open_create_reimbursement_modal_btn");
    const closeBtns = document.querySelectorAll("[data-reimbursement-modal-close]");
    const form = document.getElementById("reimbursement_form");
    const formMethodInput = document.getElementById("reimbursement_form_method");
    const modalTitle = document.getElementById("reimbursement_modal_title");
    const submitBtn = document.getElementById("reimbursement_submit_btn");
    const errorContainer = document.getElementById("reimbursement_form_errors");
    const errorList = document.getElementById("reimbursement_errors_list");

    // Form fields
    const reimbursementIdInput = document.getElementById("reimbursement_id_input");
    const userSelect = document.getElementById("reimbursement_user_id");
    const paymentModeSelect = document.getElementById("reimbursement_payment_mode_id");
    const vatPaymentSelect = document.getElementById("reimbursement_vat_payment");
    const localIntlSelect = document.getElementById("reimbursement_local_intl_payment");
    const paidDateInput = document.getElementById("reimbursement_paid_date");
    const invoiceDateInput = document.getElementById("reimbursement_invoice_date");
    const otherCurrencySelect = document.getElementById("reimbursement_other_currency");
    const otherAmountInput = document.getElementById("reimbursement_other_amount");
    const paymentAmountInput = document.getElementById("reimbursement_payment_amount");
    const vatAmountInput = document.getElementById("reimbursement_vat_amount");
    const bankChargesInput = document.getElementById("reimbursement_bank_charges");
    const invoiceNumberInput = document.getElementById("reimbursement_invoice_number");
    const serviceProviderSelect = document.getElementById("reimbursement_service_provider_id");
    const categorySelect = document.getElementById("reimbursement_category_id");
    const vendorSelect = document.getElementById("reimbursement_vendor_id");
    const serviceProductInput = document.getElementById("reimbursement_service_product");
    const customerSelect = document.getElementById("reimbursement_customer_id");
    const commentInput = document.getElementById("reimbursement_comment");

    // Reimbursement specific status controls (if present in DOM)
    const approvalStatusSelect = document.getElementById("reimbursement_approval_status");
    const amountReimbursedSelect = document.getElementById("reimbursement_amount_reimbursed");
    const reimbursementModeSelect = document.getElementById("reimbursement_reimbursement_mode");
    const employeeConfirmationSelect = document.getElementById("reimbursement_employee_confirmation");

    // Read-only status containers (if user lacks status_change permission)
    const readOnlyApprovalStatusText = document.getElementById("read_only_approval_status_text");
    const readOnlyAmountReimbursedText = document.getElementById("read_only_amount_reimbursed_text");
    const readOnlyReimbursementModeText = document.getElementById("read_only_reimbursement_mode_text");

    const approvalStatusLabels = {
        pending: "Pending",
        verify: "Verify",
        approved: "Approved",
        rejected: "Rejected",
    };

    const amountReimbursedLabels = {
        pending: "Pending",
        given: "Given",
    };

    const reimbursementModeLabels = {
        transfer: "Transfer",
        cash: "Cash",
        cheque: "Cheque",
    };

    const employeeConfirmationLabels = {
        pending: "Pending",
        received: "Received",
    };

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

    function updateVatAmount() {
        if (!vatPaymentSelect || !vatAmountInput || !paymentAmountInput) return;
        const vatPaymentVal = vatPaymentSelect.value;
        const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
        const defaultVatPct = parseFloat(form?.dataset?.defaultVatPercentage || 5);
        const vatPaymentVatVal = form?.dataset?.vatPaymentVat || "vat";

        if (vatPaymentVal === vatPaymentVatVal) {
            const calculatedVat = (paymentAmount * defaultVatPct) / 100;
            vatAmountInput.value = calculatedVat > 0 ? calculatedVat.toFixed(2) : "0.00";
        } else {
            vatAmountInput.value = "0.00";
        }
    }

    if (vatPaymentSelect) {
        vatPaymentSelect.addEventListener("change", updateVatAmount);
    }
    if (paymentAmountInput) {
        paymentAmountInput.addEventListener("input", updateVatAmount);
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
        if (reimbursementIdInput) reimbursementIdInput.value = "";
        if (formMethodInput) formMethodInput.value = "POST";
        if (form) form.action = form.dataset.createUrl;

        if (modalTitle) modalTitle.textContent = "Add New Reimbursement";
        if (submitBtn) {
            submitBtn.textContent = "Save Reimbursement";
            submitBtn.disabled = false;
        }

        // Reset TomSelects
        const defaultPaymentMode = paymentModeSelect?.dataset?.defaultValue || paymentModeSelect?.querySelector('option[selected]')?.value || "";
        const defaultVatPayment = vatPaymentSelect?.dataset?.defaultValue || vatPaymentSelect?.querySelector('option[selected]')?.value || "";
        const defaultLocalIntl = localIntlSelect?.dataset?.defaultValue || localIntlSelect?.querySelector('option[selected]')?.value || "";
        const defaultServiceProvider = serviceProviderSelect?.dataset?.defaultValue || serviceProviderSelect?.querySelector('option[selected]')?.value || "";
        const defaultCategory = categorySelect?.dataset?.defaultValue || categorySelect?.querySelector('option[selected]')?.value || "";

        const defaultApprovalStatus = approvalStatusSelect?.dataset?.defaultValue || "pending";
        const defaultAmountReimbursed = amountReimbursedSelect?.dataset?.defaultValue || "pending";
        const defaultEmployeeConfirmation = employeeConfirmationSelect?.dataset?.defaultValue || "pending";

        setSelectValue(userSelect, "");
        setSelectValue(paymentModeSelect, defaultPaymentMode);
        setSelectValue(vatPaymentSelect, defaultVatPayment);
        setSelectValue(localIntlSelect, defaultLocalIntl);
        setSelectValue(serviceProviderSelect, defaultServiceProvider);
        setSelectValue(categorySelect, defaultCategory);
        setSelectValue(vendorSelect, "");
        setSelectValue(customerSelect, "");
        setSelectValue(otherCurrencySelect, "");

        // Set Reimbursement specific status defaults (Create defaults)
        setSelectValue(approvalStatusSelect, defaultApprovalStatus);
        setSelectValue(amountReimbursedSelect, defaultAmountReimbursed);
        setSelectValue(reimbursementModeSelect, "");
        setSelectValue(employeeConfirmationSelect, defaultEmployeeConfirmation);

        // Update read-only status labels for create defaults if present
        if (readOnlyApprovalStatusText) {
            readOnlyApprovalStatusText.textContent = approvalStatusLabels[defaultApprovalStatus] || "Pending";
        }
        if (readOnlyAmountReimbursedText) {
            readOnlyAmountReimbursedText.textContent = amountReimbursedLabels[defaultAmountReimbursed] || "Pending";
        }
        if (readOnlyReimbursementModeText) {
            readOnlyReimbursementModeText.textContent = "--";
        }

        // Set Today Date for Datepickers
        const todayStr = getTodayDateString();
        setDatepickerValue(paidDateInput, todayStr);
        setDatepickerValue(invoiceDateInput, todayStr);

        // Reset defaults
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

    // Show Modal elements
    const showModal = document.getElementById("reimbursement_show_modal");
    const showModalContent = document.getElementById("reimbursement_show_modal_content");
    const closeShowBtns = document.querySelectorAll("[data-reimbursement-show-modal-close]");

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
        const showBtn = e.target.closest(".open-show-reimbursement-modal-btn");
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
                console.error("Failed to fetch reimbursement details:", err);
                if (showModalContent) {
                    showModalContent.innerHTML = `<div class="p-6 text-center text-red-600">Failed to load reimbursement details. Please try again.</div>`;
                }
            }
            return;
        }

        const editBtn = e.target.closest(".open-edit-reimbursement-modal-btn");
        if (!editBtn) return;

        e.preventDefault();
        resetForm();

        const updateUrl = editBtn.dataset.updateUrl;
        const fetchUrl = editBtn.dataset.fetchUrl;
        const inlineDataRaw = editBtn.dataset.reimbursement;

        if (modalTitle) modalTitle.textContent = "Edit Reimbursement";
        if (submitBtn) submitBtn.textContent = "Update Reimbursement";

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
                console.error("Failed to parse inline reimbursement data:", err);
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
                console.error("Failed to fetch reimbursement details:", err);
            }
        }

        if (data) {
            populateForm(data);
        }
    });

    function populateForm(data) {
        if (reimbursementIdInput) reimbursementIdInput.value = data.id || "";

        const userName = data.user_name || data.user?.name || null;
        const paymentModeName = data.payment_mode_name || data.payment_mode?.name || null;
        const serviceProviderName = data.service_provider_name || data.service_provider?.name || null;
        const categoryName = data.category_name || data.category?.name || null;
        const vendorName = data.vendor_name || data.vendor?.name || null;
        const customerName = data.customer_name || data.customer?.name || null;

        setSelectValue(userSelect, data.user_id, userName);
        setSelectValue(paymentModeSelect, data.payment_mode_id, paymentModeName);
        setSelectValue(vatPaymentSelect, data.vat_payment);
        setSelectValue(localIntlSelect, data.local_intl_payment);
        setSelectValue(serviceProviderSelect, data.service_provider_id, serviceProviderName);
        setSelectValue(categorySelect, data.category_id, categoryName);
        setSelectValue(vendorSelect, data.vendor_id, vendorName);
        setSelectValue(customerSelect, data.customer_id, customerName);
        setSelectValue(otherCurrencySelect, data.other_currency);

        // Populate saved status values on edit
        setSelectValue(approvalStatusSelect, data.approval_status || "pending");
        setSelectValue(amountReimbursedSelect, data.amount_reimbursed || "pending");
        setSelectValue(reimbursementModeSelect, data.reimbursement_mode || "");
        setSelectValue(employeeConfirmationSelect, data.employee_confirmation || "pending");

        // Update read-only status labels for edit data if user lacks status_change permission
        if (readOnlyApprovalStatusText) {
            readOnlyApprovalStatusText.textContent = approvalStatusLabels[data.approval_status] || data.approval_status || "Pending";
        }
        if (readOnlyAmountReimbursedText) {
            readOnlyAmountReimbursedText.textContent = amountReimbursedLabels[data.amount_reimbursed] || data.amount_reimbursed || "Pending";
        }
        if (readOnlyReimbursementModeText) {
            readOnlyReimbursementModeText.textContent = reimbursementModeLabels[data.reimbursement_mode] || data.reimbursement_mode || "--";
        }

        setDatepickerValue(paidDateInput, data.paid_date || getTodayDateString());
        setDatepickerValue(invoiceDateInput, data.invoice_date || getTodayDateString());

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
                        window.Alert.success(result.message || "Reimbursement saved successfully.");
                    }
                    closeModal();
                    window.location.reload();
                } else if (response.status === 422) {
                    displayErrors(result.errors || result.message || "Validation failed.");
                } else {
                    displayErrors(result.message || "An error occurred while saving the reimbursement.");
                }
            } catch (err) {
                console.error("Reimbursement form submission error:", err);
                displayErrors("An unexpected network error occurred.");
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = formMethodInput.value === "PUT" ? "Update Reimbursement" : "Save Reimbursement";
                }
            }
        });
    }
});
