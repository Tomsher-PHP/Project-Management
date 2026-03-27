document.addEventListener('DOMContentLoaded', function () {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;
    const fileInput = document.getElementById('file-input');
    const fileUploadBox = document.getElementById('file-upload-box');

    // Important: file upload box is not present in all pages
    if (!fileUploadBox) return;

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
            // renderPreview(file);
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

    // function renderPreview(file) {
    //     const list = document.getElementById('file-list');
    //     if (!list) return;

    //     const item = document.createElement('div');
    //     item.className = 'file-item p-2 border rounded mb-2';
    //     item.textContent = file.name;
    //     list.appendChild(item);
    // }

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