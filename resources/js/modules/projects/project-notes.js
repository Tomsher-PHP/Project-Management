document.addEventListener('DOMContentLoaded', function () {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;
    const saveBtn = document.getElementById('saveNotes');

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

    // Important: file upload box is not present in all pages
    if (!saveBtn) return;

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

});