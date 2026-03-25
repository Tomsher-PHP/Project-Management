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

    function renderUploadedFile(file) {
        const list = document.getElementById('file-list');

        const ext = file.original_name.split('.').pop().toLowerCase();

        const div = document.createElement('div');
        div.className = 'file-item flex h-24 w-24 flex-col items-center lg:h-44 lg:w-44';

        div.innerHTML = `
        <div class="flex w-full justify-center">
            ${['jpg', 'jpeg', 'png'].includes(ext)
                ? `<img src="/storage/${file.file_path}" class="h-16 w-16 object-cover rounded" />`
                : `<svg width="47" height="66" viewBox="0 0 67 86" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.4032 0H46.9892L67 19.8123V80.625C67 83.5946 64.5796 86 61.5968 86H5.4032C2.42052 86 0 83.5946 0 80.625V5.37496C0 2.40536 2.4208 0 5.4032 0Z" fill="white"/>
                        <path d="M5.4032 0.5H46.7835L66.5 20.0208V80.625C66.5 83.3158 64.306 85.5 61.5968 85.5H5.4032C2.69405 85.5 0.5 83.3158 0.5 80.625V5.37496C0.5 2.68413 2.6943 0.5 5.4032 0.5Z" stroke="#E8E9EB"/>
                        <path d="M65.9198 20.4252H51.2864C48.6265 20.4252 46.468 18.2802 46.468 15.6368V1.0752" stroke="#E8E9EB"/>
                        <path d="M34.7022 51.4466V48.8359H37.3266V46.2252H34.7022V43.6145H37.3266V41.0038H34.7022V38.3931H37.3266V35.7823H34.7022V33.1716H37.3266V30.5609H34.7022V27.9502H32.0777V30.5609H29.4533V33.1716H32.0777V35.7823H29.4533V38.3931H32.0777V41.0038H29.4533V43.6145H32.0777V46.2252H29.4533V48.8359H32.0777V51.4466H26.8289V57.9734C26.8289 61.5723 29.7722 64.5002 33.3899 64.5002C37.0077 64.5002 39.951 61.5723 39.951 57.9734V51.4466H34.7022ZM37.3266 57.9734C37.3266 60.1325 35.5603 61.8895 33.3899 61.8895C31.2195 61.8895 29.4533 60.1325 29.4533 57.9734V54.0573H37.3266V57.9734Z" fill="#8A9099"/>
                        <path d="M32.0778 59.2787H34.7023C35.4266 59.2787 36.0145 58.6952 36.0145 57.9733C36.0145 57.2515 35.4266 56.668 34.7023 56.668H32.0778C31.3535 56.668 30.7656 57.2515 30.7656 57.9733C30.7656 58.6952 31.3535 59.2787 32.0778 59.2787Z" fill="#8A9099"/>
                    </svg>`
            }
        </div>
        <h4 class="mt-2 text-bgray-600 text-sm font-semibold dark:text-white md:text-base truncate w-full text-center">${file.original_name}</h4>
        <span class="text-xs text-bgray-500">${(file.file_size / 1024).toFixed(1)} KB</span>
        <div class="flex gap-2 mt-1">
            <a href="/storage/${file.file_path}" target="_blank" class="text-xs text-blue-500 hover:underline">View</a>
            <button type="button" class="text-xs text-red-500 delete-file" data-id="${file.id}">Delete</button>
        </div>
    `;

        list.appendChild(div);
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
                    renderUploadedFile(res.file);
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
