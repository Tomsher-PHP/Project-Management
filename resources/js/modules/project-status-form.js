document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('projectStatusForm');

    if (!form) {
        return;
    }

    const nameInput = form.querySelector('[data-project-status-name]');
    const codeInput = form.querySelector('[data-project-status-code]');

    if (!nameInput || !codeInput) {
        return;
    }

    const normalizeCode = (value) => value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

    const syncCodeFromName = () => {
        if (codeInput.dataset.manualCode === 'true') {
            return;
        }

        codeInput.value = normalizeCode(nameInput.value || '');
    };

    nameInput.addEventListener('input', syncCodeFromName);

    codeInput.addEventListener('input', function () {
        const normalizedValue = normalizeCode(this.value || '');
        const nameBasedValue = normalizeCode(nameInput.value || '');
        const isCreateMode = form.querySelector('.form-method')?.value === 'POST';

        this.value = normalizedValue;
        this.dataset.manualCode = normalizedValue !== '' && normalizedValue !== nameBasedValue ? 'true' : 'false';

        if (isCreateMode && normalizedValue === '') {
            this.dataset.manualCode = 'false';
            syncCodeFromName();
        }
    });

    const resetAutoCodeMode = () => {
        const isCreateMode = form.querySelector('.form-method')?.value === 'POST';
        codeInput.dataset.manualCode = isCreateMode ? 'false' : 'true';

        if (isCreateMode) {
            syncCodeFromName();
        }
    };

    document.addEventListener('click', function (event) {
        if (event.target.closest('.modal-open[data-target="#multi-step-modal"], .edit-record[data-modal="multi-step-modal"]')) {
            window.setTimeout(resetAutoCodeMode, 0);
        }
    });
});
