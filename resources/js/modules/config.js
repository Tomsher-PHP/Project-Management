const previewImage = (input, previewId, placeholderId, removeBtnId, removeInputId) => {
    if (!input?.files || !input.files[0]) {
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);
        const removeBtn = document.getElementById(removeBtnId);
        const removeInput = document.getElementById(removeInputId);

        if (!preview || !placeholder || !removeBtn || !removeInput) {
            return;
        }

        preview.src = event.target.result;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
        removeInput.value = '0';
    };

    reader.readAsDataURL(input.files[0]);
};

const bindImagePreview = (inputId, options) => {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    input.addEventListener('change', function () {
        previewImage(this, options.previewId, options.placeholderId, options.removeBtnId, options.removeInputId);
    });
};

const bindImageRemoval = (buttonId, options) => {
    const button = document.getElementById(buttonId);

    if (!button) {
        return;
    }

    button.addEventListener('click', function () {
        const preview = document.getElementById(options.previewId);
        const placeholder = document.getElementById(options.placeholderId);
        const input = document.getElementById(options.inputId);
        const removeInput = document.getElementById(options.removeInputId);

        if (!preview || !placeholder || !input || !removeInput) {
            return;
        }

        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        this.classList.add('hidden');
        input.value = '';
        removeInput.value = '1';
    });
};

document.addEventListener('DOMContentLoaded', function () {
    bindImagePreview('logo', {
        previewId: 'preview-logo',
        placeholderId: 'placeholder-logo',
        removeBtnId: 'remove-btn-logo',
        removeInputId: 'remove_logo',
    });

    bindImagePreview('favicon', {
        previewId: 'preview-favicon',
        placeholderId: 'placeholder-favicon',
        removeBtnId: 'remove-btn-favicon',
        removeInputId: 'remove_favicon',
    });

    bindImageRemoval('remove-btn-logo', {
        previewId: 'preview-logo',
        placeholderId: 'placeholder-logo',
        inputId: 'logo',
        removeInputId: 'remove_logo',
    });

    bindImageRemoval('remove-btn-favicon', {
        previewId: 'preview-favicon',
        placeholderId: 'placeholder-favicon',
        inputId: 'favicon',
        removeInputId: 'remove_favicon',
    });
});
