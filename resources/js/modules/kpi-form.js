document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('kpiForm');

    if (!form || !window.Quill) {
        return;
    }

    const modal = document.getElementById('multi-step-modal');
    const descriptionField = form.querySelector('[name="description"]');
    const editorElement = document.getElementById('kpi-description-editor');

    if (!modal || !descriptionField || !editorElement) {
        return;
    }

    const quill = editorElement.dataset.quillInitialized === 'true'
        ? null
        : new window.Quill(editorElement, {
            theme: 'snow',
            placeholder: 'Write a short description for this KPI...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ header: [1, 2, 3, false] }],
                    ['link'],
                ],
            },
        });

    if (!quill) {
        return;
    }

    editorElement.dataset.quillInitialized = 'true';

    const normalizeHtml = (value) => {
        const plainText = String(value || '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/&nbsp;/g, ' ')
            .trim();

        return plainText === '' ? '' : String(value);
    };

    const syncEditorFromField = () => {
        const value = normalizeHtml(descriptionField.value);

        if (!value) {
            quill.setContents([]);
            return;
        }

        quill.clipboard.dangerouslyPasteHTML(value);
    };

    const syncFieldFromEditor = () => {
        const html = normalizeHtml(quill.root.innerHTML);
        descriptionField.value = html;
    };

    quill.on('text-change', syncFieldFromEditor);

    form.addEventListener('submit', syncFieldFromEditor);

    document.addEventListener('click', function (event) {
        if (event.target.closest('.modal-open[data-target="#multi-step-modal"], .edit-record[data-modal="multi-step-modal"]')) {
            window.setTimeout(syncEditorFromField, 0);
        }

        if (event.target.closest('#multi-step-modal .modal-close')) {
            window.setTimeout(syncEditorFromField, 0);
        }
    });

    document.addEventListener('ajax-form:rendered', function () {
        syncEditorFromField();
    });

    syncEditorFromField();
});
