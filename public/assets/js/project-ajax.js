document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('project-settings-form');
    const button = document.getElementById('update-project');
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    button.addEventListener('click', function () {
        const projectId = this.dataset.projectId;
        const formData = new FormData(form);

        // Optional: disable button while processing
        button.disabled = true;
        button.textContent = 'Updating...';

        fetch(`/projects/${projectId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.textContent = 'Update Project';

                if (data.success) {

                    // Show success message (Alpine Toast or simple alert)
                    Alert.success(data.message);

                    // Remove all previous error messages
                    form.querySelectorAll('.error-text').forEach(el => el.remove());

                    // update project header
                    document.getElementById('project-header').innerHTML = data.project_header;
                } else if (data.errors) {
                    // Clear previous errors
                    form.querySelectorAll('p.text-error-300').forEach(p => p.remove());

                    // Show validation errors
                    Object.keys(data.errors).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            const errorEl = document.createElement('p');
                            errorEl.classList.add('mt-1', 'text-sm', 'text-error-300', 'error-text');
                            errorEl.textContent = data.errors[key][0];
                            input.parentNode.appendChild(errorEl);
                        }
                    });
                }
            })
            .catch(err => {
                button.disabled = false;
                button.textContent = 'Update Project';
                console.error(err);
                Alert.error('Something went wrong. Please try again.');
            });
    });
});