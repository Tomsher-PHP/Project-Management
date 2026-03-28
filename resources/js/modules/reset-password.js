import Alert from "../alert";

// Submit email to get otp
$('#step-1-next').on('click', function (e) {

    e.preventDefault();

    let form = $('#forgot-form');
    let url = form.attr('action');
    let email = form.find('input[name="email"]').val();

    if (!email) {
        Alert.errorModal('Email is required', 'Error', 'multi-step-modal');
        return;
    }

    submitStepAjax({
        formId: '#forgot-form',
        data: { email: email },

        onSuccess: function (response) {
            $('#stored-email').val(response.email);
            $('#masked-email').text(maskEmail(response.email));

            Alert.successModal(response.message, 'Success', 'multi-step-modal');
            goToStep(2);
        }
    });
})

// Submit otp to verify
$('#step-2-next').on('click', function (e) {

    e.preventDefault();

    let form = $('#otp-form');
    let url = form.attr('action');
    let storedEmail = $('#stored-email').val();
    let otp = $('#final-otp').val();

    if (!otp) {
        Alert.errorModal('OTP is required', 'Error', 'multi-step-modal');
        return;
    }

    submitStepAjax({
        formId: '#otp-form',
        data: {
            email: storedEmail,
            otp: otp
        },

        onSuccess: function (response) {
            Alert.successModal(response.message, 'Success', 'multi-step-modal');
            goToStep(3);
        }
    });
});

// Submit reset new password
$('#step-3-next').on('click', function (e) {

    e.preventDefault();

    let form = $('#reset-password-form');
    let url = form.attr('action');
    let storedEmail = $('#stored-email').val();
    let password = $('#reset-new-password').val();
    let confirmPassword = $('#reset-confirm-password').val();

    if (!password || !confirmPassword) {
        Alert.errorModal('All fields are required', 'Error', 'multi-step-modal');
        return;
    }

    submitStepAjax({
        formId: '#reset-password-form',
        data: {
            email: storedEmail,
            password: password,
            password_confirmation: confirmPassword
        },

        onSuccess: function (response) {
            $('#multi-step-modal').addClass('hidden');
            Alert.success(response.message, 'Success');
        }
    });
});

// Verify OTP form elements

$('.otp-input').on('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');

    if (this.value.length === 1) {
        $(this).next('.otp-input').focus();
    }

    updateOtpValue();
});

$('.otp-input').on('keydown', function (e) {
    if (e.key === "Backspace" && !this.value) {
        $(this).prev('.otp-input').focus();
    }
});

$('.otp-input').on('paste', function (e) {
    let pasteData = e.originalEvent.clipboardData.getData('text');
    if (/^\d{5}$/.test(pasteData)) {
        $('.otp-input').each(function (index) {
            $(this).val(pasteData[index]);
        });
        updateOtpValue();
        e.preventDefault();
    }
});

$('#open-forgot-password').on('click', function () {
    resetMultiStepModal();
    $('#multi-step-modal').removeClass('hidden');
});

// Ajax function
function submitStepAjax({ formId, data, onSuccess }) {

    let form = $(formId);
    let url = form.attr('action');
    let btn = form.find('button[type="button"], button[type="submit"]');

    btn.prop('disabled', true);

    setButtonLoading(btn, true);

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ...data
        },

        success: function (response) {

            if (response.success) {
                if (typeof onSuccess === 'function') {
                    onSuccess(response);
                }
            } else {
                Alert.errorModal(response.message, 'Error', 'multi-step-modal');
            }
        },

        error: function (error) {
            Alert.errorModal(error.responseJSON.message, 'Error', 'multi-step-modal');
        },

        complete: function () {
            btn.prop('disabled', false);
            setButtonLoading(btn, false);
        }
    });
}

// Replace otp value for hidden
function updateOtpValue() {
    let otp = '';
    $('.otp-input').each(function () {
        otp += $(this).val();
    });
    $('#final-otp').val(otp);
}

// open modal functionality
function resetMultiStepModal() {

    const modal = $('#multi-step-modal');

    // 1️⃣ Reset all forms inside modal
    modal.find('form').each(function () {
        this.reset();
    });

    // 2️⃣ Clear hidden fields manually (important)
    modal.find('input[type="hidden"]').val('');

    // 3️⃣ Clear OTP inputs (if using separate boxes)
    modal.find('.otp-input').val('');

    // 4️⃣ Reset steps (show step-1 only)
    modal.find('.step-content').addClass('hidden');
    modal.find('.step-1').removeClass('hidden');

    // 5️⃣ Remove validation errors (if any)
    modal.find('.is-invalid').removeClass('is-invalid');
    modal.find('.error-text').remove();

    // 6️⃣ Optional: focus first input
    setTimeout(() => {
        modal.find('input[name=email]').focus();
    }, 100);
}

// mask the email
function maskEmail(email) {
    let [name, domain] = email.split('@');

    if (name.length <= 3) {
        return name[0] + '****@' + domain;
    }

    let visiblePart = name.substring(0, 3);
    return visiblePart + '****@' + domain;
}

// go to next form after success
function goToStep(stepNumber) {

    let modal = $('#multi-step-modal');

    // Hide all steps
    modal.find('.step-content').addClass('hidden');

    // Show requested step
    modal.find('.step-' + stepNumber).removeClass('hidden');

    // Optional: focus first input of that step
    setTimeout(function () {
        modal.find('.step-' + stepNumber)
            .find('input:visible:first')
            .focus();
    }, 100);
}

// button loader
function setButtonLoading(button, isLoading, loadingText = 'Processing...') {

    let $btn = $(button);

    if (isLoading) {
        $btn.data('original-text', $btn.html());
        $btn.prop('disabled', true);

        $btn.html(`
            <span class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5 text-white"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 24 24">
                    <circle class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"></circle>
                    <path class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                ${loadingText}
            </span>
        `);
    } else {
        $btn.prop('disabled', false);
        $btn.html($btn.data('original-text'));
    }
}