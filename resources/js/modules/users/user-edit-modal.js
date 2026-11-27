document.addEventListener('DOMContentLoaded', () => {
    function initModalContactRepeater({
        containerId,
        addButtonId,
        fieldName,
        idPrefix,
        numberPlaceholder
    }) {
        const container = document.getElementById(containerId);
        const addButton = document.getElementById(addButtonId);
        const template = document.getElementById('modal-contact-row-template');
        if (!container || !addButton || !template) return;

        function reindex() {
            const rows = container.querySelectorAll('.modal-contact-row');
            rows.forEach((row, idx) => {
                row.dataset.index = idx;

                const nameLabel = row.querySelector('.col-contact-name label');
                const nameInput = row.querySelector('.col-contact-name input');
                if (nameLabel && nameInput) {
                    const id = `${idPrefix}_name_${idx}`;
                    nameLabel.setAttribute('for', id);
                    nameInput.id = id;
                    nameInput.name = `${fieldName}[${idx}][name]`;
                }

                const numLabel = row.querySelector('.col-contact-number label');
                const numInput = row.querySelector('.col-contact-number input');
                if (numLabel && numInput) {
                    const id = `${idPrefix}_number_${idx}`;
                    numLabel.setAttribute('for', id);
                    numInput.id = id;
                    numInput.name = `${fieldName}[${idx}][number]`;
                }

                const relLabel = row.querySelector('.col-contact-relation label');
                const relInput = row.querySelector('.col-contact-relation input');
                if (relLabel && relInput) {
                    const id = `${idPrefix}_relation_${idx}`;
                    relLabel.setAttribute('for', id);
                    relInput.id = id;
                    relInput.name = `${fieldName}[${idx}][relation]`;
                }
            });
        }

        function createRow(index) {
            const row = template.content.firstElementChild.cloneNode(true);
            row.dataset.index = index;

            const numInput = row.querySelector('.col-contact-number input');
            if (numInput && numberPlaceholder) {
                numInput.placeholder = numberPlaceholder;
            }

            return row;
        }

        addButton.addEventListener('click', () => {
            const rows = container.querySelectorAll('.modal-contact-row');
            const nextIndex = rows.length;
            const newRow = createRow(nextIndex);
            container.appendChild(newRow);
            reindex();
        });

        container.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.btn-remove-contact');
            if (!removeBtn) return;
            const row = removeBtn.closest('.modal-contact-row');
            if (!row) return;

            const rows = container.querySelectorAll('.modal-contact-row');
            if (rows.length <= 1) {
                row.querySelectorAll('input').forEach(input => input.value = '');
            } else {
                row.remove();
                reindex();
            }
        });
    }

    initModalContactRepeater({
        containerId: 'modal-emergency-contacts-container',
        addButtonId: 'modal-btn-add-emergency-contact',
        fieldName: 'emergency_contacts',
        idPrefix: 'modal_emergency_contact',
        numberPlaceholder: '+971501234567',
    });

    initModalContactRepeater({
        containerId: 'modal-native-contacts-container',
        addButtonId: 'modal-btn-add-native-contact',
        fieldName: 'native_contacts',
        idPrefix: 'modal_native_contact',
        numberPlaceholder: '+919876543210',
    });
});
