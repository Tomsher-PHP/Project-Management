document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('userEditForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // clear old errors first
        document.querySelectorAll('.error-text').forEach(el => el.remove());

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            // HANDLE VALIDATION ERROR
            if (!response.ok) {

                if (response.status === 422 && data.errors) {
                    showValidationErrors(data.errors);
                    return;
                }

                Alert.error(data.message || 'Something went wrong');
                return;
            }

            // SUCCESS
            Alert.success(data.message || 'User updated successfully');
            window.dispatchEvent(new CustomEvent('close-edit-modal'));
            window.location.reload();


        } catch (err) {
            console.error(err);
            Alert.error('Something went wrong');
        }
    });

    function showValidationErrors(errors) {
        Object.keys(errors).forEach(field => {

            // Special case for profile image
            if (field === 'profile_image') {
                const container = document.getElementById('drop-area')?.parentElement;

                if (container) {
                    const error = document.createElement('p');
                    error.classList.add('text-red-500', 'text-sm', 'mt-2', 'error-text');
                    error.innerText = errors[field][0];

                    container.appendChild(error);
                }
                return;
            }

            // Normal fields
            const input = document.querySelector(`[name="${field}"]`);

            if (input) {
                const wrapper = input.closest('.flex.flex-col') || input.parentElement;

                const error = document.createElement('p');
                error.classList.add('text-red-500', 'text-sm', 'mt-1', 'error-text');
                error.innerText = errors[field][0];

                wrapper.appendChild(error);
            }
        });
    }

});