document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('project-module-modal');

    if (!modal) {
        return;
    }

    const librarySelect = modal.querySelector('#library_module_id');
    const nameInput = modal.querySelector('[name="name"]');
    const colorInput = modal.querySelector('[name="color"]');
    const descriptionInput = modal.querySelector('[name="description"]');

    if (!librarySelect || !nameInput || !colorInput || !descriptionInput) {
        return;
    }

    const fillFromLibraryOption = () => {
        const selectedOption = librarySelect.options[librarySelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            return;
        }

        nameInput.value = selectedOption.dataset.name || '';
        colorInput.value = selectedOption.dataset.color || '#000000';
        descriptionInput.value = selectedOption.dataset.description || '';
    };

    librarySelect.addEventListener('change', fillFromLibraryOption);

    document.addEventListener('click', function (event) {
        const createButton = event.target.closest('.modal-open[data-module-context="project-module"]');
        const editButton = event.target.closest('.edit-record[data-module-context="project-module"]');

        if (createButton) {
            setTimeout(() => {
                librarySelect.value = '';
            }, 0);
        }

        if (editButton) {
            setTimeout(() => {
                librarySelect.value = '';
            }, 0);
        }
    });
});
