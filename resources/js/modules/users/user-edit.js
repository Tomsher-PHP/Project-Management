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

            // ❗ HANDLE VALIDATION ERROR
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

            // optional DOM update
            if (data.html) {
                document.getElementById('userProfileSection').innerHTML = data.html;
            }

        } catch (err) {
            console.error(err);
            Alert.error('Something went wrong');
        }
    });

    function showValidationErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);

            if (input) {
                const error = document.createElement('p');
                error.classList.add('text-red-500', 'text-sm', 'mt-1', 'error-text');
                error.innerText = errors[field][0];

                input.closest('.flex, .grid, .col-span-1, .flex-col')?.appendChild(error);
            }
        });
    }

});