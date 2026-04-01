const projectScopeFilesState = {
    listenersBound: false,
};

const initializeProjectScopeFiles = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;
    const fileInput = root.querySelector ? root.querySelector('#file-input') : document.getElementById('file-input');
    const fileUploadBox = root.querySelector ? root.querySelector('#file-upload-box') : document.getElementById('file-upload-box');

    if (fileUploadBox && fileInput && fileUploadBox.dataset.projectScopeInitialized !== 'true') {
        fileUploadBox.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });

        fileUploadBox.addEventListener('drop', e => {
            e.preventDefault();
            handleFiles(e.dataTransfer.files);
        });

        fileUploadBox.dataset.projectScopeInitialized = 'true';
    }

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

                    document.getElementById('file-empty-state')?.remove();

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

    if (!projectScopeFilesState.listenersBound) {
        document.addEventListener('click', async function (e) {
            if (e.target.classList.contains('delete-file')) {

                const id = e.target.dataset.id;

                const result = await Alert.confirm({
                    title: 'Delete File',
                    text: 'Are you sure you want to delete this file?',
                    type: 'error'
                });

                if (!result.isConfirmed) return;

                fetch(`/projects/${projectId}/scope-files/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            e.target.closest('.file-item')?.remove();
                            ensureEmptyState();
                            Alert.success('File deleted successfully');
                        }
                    })
                    .catch(() => {
                        Alert.error('Failed to delete file');
                    });
            }
        });

        projectScopeFilesState.listenersBound = true;
    }

    function ensureEmptyState() {
        const list = document.getElementById('file-list');

        if (!list || list.children.length) {
            return;
        }

        if (!document.getElementById('file-empty-state')) {
            const emptyState = document.createElement('p');
            emptyState.id = 'file-empty-state';
            emptyState.className = 'text-gray-400 text-sm';
            emptyState.textContent = 'No scope files uploaded yet.';
            list.appendChild(emptyState);
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    initializeProjectScopeFiles();
});

document.addEventListener('project-tab:loaded', function (event) {
    if (event.detail?.tab !== 'scope') {
        return;
    }

    initializeProjectScopeFiles(event.detail.panel);
});
