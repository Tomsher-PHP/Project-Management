document.addEventListener('DOMContentLoaded', function () {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;

    const form = document.getElementById('project-settings-form');
    const button = document.getElementById('update-project');

    const saveBtn = document.getElementById('saveNotes');

    const fileInput = document.getElementById('file-input');
    const fileUploadBox = document.getElementById('file-upload-box');
    const fileList = document.getElementById('file-list');

    let dirty = false;

    /* -------------------------- update project settings -------------------------- */
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

    // Listen for Alpine dispatch events
    form.addEventListener('form-dirty', setDirty);

    // update project settings
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

                    // Remove all previous error messages
                    form.querySelectorAll('.error-text').forEach(el => el.remove());

                    // update project header
                    document.getElementById('project-header').innerHTML = data.project_header;

                    dirty = false;
                    button.disabled = true;
                    button.textContent = 'Updated';
                } else if (data.errors) {
                    button.disabled = false;
                    button.textContent = 'Update Project';

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

    /* -------------------------- save notes -------------------------- */
    // Initialize Quill
    const projectNote = new Quill('#project-note', {
        theme: 'snow',
        readOnly: !window.ProjectApp.canEdit,
        placeholder: 'Write notes...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'header': [1, 2, 3, false] }],
                ['link'] // no image
            ]
        }
    });

    if (window.ProjectApp.notes) {
        projectNote.clipboard.dangerouslyPasteHTML(window.ProjectApp.notes);
    }

    saveBtn.addEventListener('click', () => {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        const notes = projectNote.root.innerHTML;

        fetch(`/projects/${projectId}/update-notes`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notes })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    dirty = false;
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Saved';
                    Alert.success(data.message);
                } else {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Notes';
                    Alert.error(data.message || 'Something went wrong.');
                }
            })
            .catch(err => {
                console.error(err);
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Notes';
                Alert.error('Something went wrong. Please try again.');
            });
    });

    /* -------------------------- file upload -------------------------- */

    fileUploadBox.addEventListener('click', () => fileInput.click());

    fileUploadBox.addEventListener('dragover', e => {
        e.preventDefault();
        fileUploadBox.classList.add('border-success-300');
    });

    fileUploadBox.addEventListener('dragleave', () => {
        fileUploadBox.classList.remove('border-success-300');
    });

    fileUploadBox.addEventListener('drop', e => {
        e.preventDefault();
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files);
    });

    function handleFiles(files) {
        [...files].forEach(file => {
            uploadFile(file);
            renderPreview(file);
        });
    }

    function uploadFile(file) {
        let formData = new FormData();
        formData.append('project_file', file);

        fetch(`/projects/${projectId}/files`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token
            },
            body: formData
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const list = document.getElementById('file-list');
                    list.insertAdjacentHTML('beforeend', res.html); // append rendered HTML
                    Alert.success(`Uploaded ${res.file.original_name}`);
                }
            })
            .catch(err => {
                console.error(err);
                Alert.error(`Failed to upload ${file.name}`);
            });
    }

    // Delete file
    document.addEventListener('click', async function (e) {
        if (e.target.classList.contains('delete-file')) {

            const id = e.target.dataset.id;

            // Wait for user confirmation
            const result = await Alert.confirm({
                title: 'Delete File',
                text: 'Are you sure you want to delete this file?',
                type: 'error'
            });

            // Only proceed if confirmed
            if (!result.isConfirmed) return;

            // Send delete request
            fetch(`/projects/${projectId}/files/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        e.target.closest('.file-item').remove();
                        Alert.success('File deleted successfully');
                    }
                })
                .catch(() => {
                    Alert.error('Failed to delete file');
                });
        }
    });
});
