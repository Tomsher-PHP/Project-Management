import Alert from "../../alert";

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

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let form = this;
    let formData = new FormData(form);

    // clear only error messages (NOT labels)
    document.querySelectorAll('.error').forEach(el => el.innerText = '');
    document.querySelectorAll('#changePasswordForm input').forEach(i => {
        i.classList.remove('border-red-500');
    });

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async res => {

        let data;

        try {
            data = await res.json();
        } catch (e) {
            console.error("Invalid JSON response", e);
            return;
        }

        // clear old errors always
        document.querySelectorAll('.error').forEach(el => el.innerText = '');

        if (!res.ok) {

            if (data.errors) {
                Object.keys(data.errors).forEach(field => {

                    let message = data.errors[field][0];

                    // show for exact field
                    let errorEl = document.querySelector(`[data-error="${field}"]`);

                    if (errorEl) {
                        errorEl.innerText = message;
                    }

                    // special case for confirmed validation
                    if (field === 'new_password') {
                        let confirmEl = document.querySelector(`[data-error="new_password_confirmation"]`);
                        if (confirmEl) {
                            confirmEl.innerText = message;
                        }
                    }
                });
            } else {
                console.error(data);
            }

            return;
        }

        // success
        Alert.success('Password changed successfully');
        form.reset();

        // clear errors on success
        document.querySelectorAll('.error').forEach(el => el.innerText = '');
    })
    .catch(err => console.error(err));
});

document.querySelectorAll('#changePasswordForm input').forEach(input => {
    input.addEventListener('input', function () {
        let errorEl = document.querySelector(`[data-error="${this.name}"]`);
        if (errorEl) {
            errorEl.innerText = '';
        }

        this.classList.remove('border-red-500');
    });
});
