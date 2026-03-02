import Alert from "./alert";

// Submit email to get otp
$('#step-1-next').on('click', function () {
    let form = $('#forgot-form');
    let url = form.attr('action');
    let email = form.find('input[name="email"]').val();

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            email: email
        },

        success: function (response) {
            if (response.success) {
                $('#stored-email').val(response.email);
                $('#masked-email').text(response.email);

                Alert.success(response.message);
            } else {
                Alert.error(response.message);
            }
        }
    });
})

// Submit otp to verify
$('#step-2-next').on('click', function () {

    let form = $('#otp-form');
    let url = form.attr('action');
    let storedEmail = $('#stored-email').val();
    let otp = $('#final-otp').val();

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            email: storedEmail,
            otp: otp
        },
        success: function (response) {
            if (response.success) {
                // move to step 3

                Alert.success(response.message);
            } else {
                Alert.error(response.message);
            }
        }
    });
});

// Submit reset new password
$('#step-3-next').on('click', function () {

    let form = $('#reset-password-form');
    let url = form.attr('action');
    let storedEmail = $('#stored-email').val();
    let password = $('#reset-new-password').val();
    let confirmPassword = $('#reset-confirm-password').val();

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            email: storedEmail,
            password: password,
            password_confirmation: confirmPassword
        },
        success: function (response) {
            if (response.success) {
                // move to step 3

                Alert.success(response.message);
            } else {
                Alert.error(response.message);
            }
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

function updateOtpValue() {
    let otp = '';
    $('.otp-input').each(function () {
        otp += $(this).val();
    });
    $('#final-otp').val(otp);
}