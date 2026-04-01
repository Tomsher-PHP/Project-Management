import Alert from '../../alert';

window.projectForm = function () {
    return {
        dirty: false,
        markDirty() {
            this.dirty = true;
        },
    };
};

const initializeProjectSettings = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;

    const form = root.querySelector ? root.querySelector('#project-settings-form') : document.getElementById('project-settings-form');
    const button = root.querySelector ? root.querySelector('#update-project') : document.getElementById('update-project');

    let dirty = false;

    if (!button || !form || form.dataset.projectSettingsInitialized === 'true') {
        return;
    }

    const canEdit = form.dataset.canEdit === 'true';

    if (!canEdit) {
        form.querySelectorAll('select').forEach(el => {
            el.disabled = true;
            el.tomselect?.disable();
        });
        form.querySelectorAll('input').forEach(el => {
            el.disabled = true;
        });
        form.querySelectorAll('textarea').forEach(el => {
            el.disabled = true;
        });
    }

    function setDirty() {
        dirty = true;
        button.disabled = false;
        button.textContent = 'Update Project';
    }

    // Vanilla inputs/selects/textareas
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', setDirty);
        el.addEventListener('change', setDirty);
    });

    form.addEventListener('form-dirty', setDirty);

    button.addEventListener('click', function () {
        if (!dirty) return;

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

                    Alert.success(data.message);

                    form.querySelectorAll('.error-text').forEach(el => el.remove());

                    document.getElementById('project-header').innerHTML = data.project_header;

                    dirty = false;
                    button.disabled = true;
                    button.textContent = 'Updated';
                } else if (data.errors) {
                    button.disabled = false;
                    button.textContent = 'Update Project';

                    form.querySelectorAll('p.text-error-300').forEach(p => p.remove());

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

    form.dataset.projectSettingsInitialized = 'true';
};

document.addEventListener('DOMContentLoaded', function () {
    initializeProjectSettings();
});

document.addEventListener('project-tab:loaded', function (event) {
    if (event.detail?.tab !== 'settings') {
        return;
    }

    initializeProjectSettings(event.detail.panel);
});
