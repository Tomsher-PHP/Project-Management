import Alert from "../../alert";

function ajaxFormSubmit(formSelector) {

    // $(formSelector).on('submit', function (e) {
    //     e.preventDefault();

    //     let form = $(this);
    //     let url = form.attr('action');
    //     let btn = form.find('button[type="submit"]');

    //     let formData = new FormData(this);

    //     setButtonLoading(btn, true);

    //     $.ajax({
    //         url: url,
    //         type: "POST",
    //         data: formData,
    //         processData: false,
    //         contentType: false,

    //         success: function (response) {
    //             Alert.success(response.message || 'Success');

    //             form[0].reset();
    //         },

    //         error: function (xhr) {
    //             let res = xhr.responseJSON;

    //             if (res?.errors) {
    //                 Object.values(res.errors).forEach(err => {
    //                     Alert.errorModal(err[0], 'Error');
    //                 });
    //             } else {
    //                 Alert.errorModal(res?.message || 'Something went wrong', 'Error');
    //             }
    //         },

    //         complete: function () {
    //             setButtonLoading(btn, false);
    //         }
    //     });
    // });
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