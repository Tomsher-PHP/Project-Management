const projectScopeFilesState = {
    listenersBound: false,
};

const initializeProjectScopeFiles = (root = document) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const projectId = window.ProjectApp?.id;
    const fileInput = root.querySelector ? root.querySelector('#file-input') : document.getElementById('file-input');
    const fileUploadBox = root.querySelector ? root.querySelector('#file-upload-box') : document.getElementById('file-upload-box');

    if (fileUploadBox && fileInput && fileUploadBox.dataset.projectScopeInitialized !== 'true') {
        fileUploadBox.addEventListener('click', (e) => {
            if (fileUploadBox.classList.contains('pointer-events-none')) return;
            if (e.target !== fileInput) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });

        fileUploadBox.addEventListener('drop', e => {
            e.preventDefault();
            if (fileUploadBox.classList.contains('pointer-events-none')) return;
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
        if (!fileUploadBox) return;

        let formData = new FormData();
        [...files].forEach(file => {
            formData.append('project_files[]', file);
        });

        const defaultPrompt = fileUploadBox.querySelector('p.text-bgray-700');
        let loader = fileUploadBox.querySelector('#file-upload-loader');

        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'file-upload-loader';
            loader.className = 'flex items-center justify-center gap-3 py-1';
            loader.innerHTML = `
                <svg class="h-6 w-6 animate-spin text-success-300" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold text-bgray-900 dark:text-white">Uploading file(s)... Please wait</span>
            `;
            fileUploadBox.appendChild(loader);
        }

        if (defaultPrompt) defaultPrompt.classList.add('hidden');
        loader.classList.remove('hidden');
        fileUploadBox.classList.add('pointer-events-none', 'opacity-75');

        const list = document.getElementById('file-list');
        document.getElementById('file-empty-state')?.remove();

        const skeletonId = 'file-upload-skeleton';
        if (list && !document.getElementById(skeletonId)) {
            const skeleton = document.createElement('div');
            skeleton.id = skeletonId;
            skeleton.className = 'file-item flex h-24 w-24 flex-col items-center justify-center rounded-xl border border-dashed border-success-300 bg-success-50/40 p-2 dark:bg-darkblack-500 lg:h-44 lg:w-44 animate-pulse';
            skeleton.innerHTML = `
                <svg class="h-8 w-8 animate-spin text-success-400 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-medium text-success-500 text-center">Uploading...</span>
            `;
            list.appendChild(skeleton);
        }

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
                    if (list) {
                        res.html.forEach(html => {
                            list.insertAdjacentHTML('beforeend', html);
                        });
                    }
                    Alert.success(res.message);
                } else {
                    Alert.error(res.message || 'Failed to upload files');
                }
            })
            .catch(err => {
                console.error(err);
                Alert.error('Failed to upload files');
            })
            .finally(() => {
                document.getElementById(skeletonId)?.remove();
                if (fileUploadBox) {
                    fileUploadBox.classList.remove('pointer-events-none', 'opacity-75');
                    if (loader) loader.classList.add('hidden');
                    if (defaultPrompt) defaultPrompt.classList.remove('hidden');
                }
                if (fileInput) {
                    fileInput.value = '';
                }
                ensureEmptyState();
            });
    }

    if (!projectScopeFilesState.listenersBound) {
        document.addEventListener('click', async function (e) {
            const deleteBtn = e.target.closest('.delete-file');
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                const fileItem = deleteBtn.closest('.file-item');

                const result = await Alert.confirm({
                    title: 'Delete File',
                    text: 'Are you sure you want to delete this file?',
                    type: 'error'
                });

                if (!result.isConfirmed) return;

                const originalBtnHtml = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = `
                    <span class="inline-flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Deleting...
                    </span>
                `;
                if (fileItem) {
                    fileItem.classList.add('opacity-50', 'pointer-events-none', 'animate-pulse');
                }

                fetch(`/projects/${projectId}/scope-files/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            fileItem?.remove();
                            ensureEmptyState();
                            Alert.success('File deleted successfully');
                        } else {
                            throw new Error(res.message || 'Failed to delete file');
                        }
                    })
                    .catch((err) => {
                        if (fileItem) {
                            fileItem.classList.remove('opacity-50', 'pointer-events-none', 'animate-pulse');
                        }
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalBtnHtml;
                        Alert.error(err.message || 'Failed to delete file');
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
