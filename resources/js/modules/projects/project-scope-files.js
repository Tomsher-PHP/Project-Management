document.addEventListener('DOMContentLoaded', function () {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;
    const fileInput = document.getElementById('file-input');
    const fileUploadBox = document.getElementById('file-upload-box');

    // Important: file upload box is not present in all pages
    if (!fileUploadBox) return;

    fileUploadBox.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files);
    });

    fileUploadBox.addEventListener('drop', e => {
        e.preventDefault();
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        if (!files) return;

        const fileArray = Array.from(files);
        if (!fileArray.length) return;

        uploadFiles(fileArray);
    }

    function uploadFiles(files) {
        let formData = new FormData();

        [...files].forEach(file => {
            formData.append('project_files[]', file);
        });

        fetch(`/projects/${projectId}/scope-files`, {
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

                    res.html.forEach(html => {
                        list.insertAdjacentHTML('beforeend', html);
                    });

                    Alert.success(res.message);
                }
            })
            .catch(err => {
                console.error(err);
                Alert.error('Failed to upload files');
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
            fetch(`/projects/${projectId}/scope-files/${id}`, {
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