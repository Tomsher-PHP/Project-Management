document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('multi-step-modal');
    const form = document.getElementById('checklistForm');
    const builder = modal?.querySelector('[data-checklist-question-builder]');
    const list = builder?.querySelector('[data-checklist-question-list]');
    const template = document.getElementById('checklist-question-template');

    if (!modal || !form || !builder || !list || !template) {
        return;
    }

    const parseQuestions = (value) => {
        if (Array.isArray(value)) {
            return value.filter((question) => String(question || '').trim() !== '');
        }

        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                return Array.isArray(parsed)
                    ? parsed.filter((question) => String(question || '').trim() !== '')
                    : [];
            } catch (error) {
                return [];
            }
        }

        return [];
    };

    const refreshNumbers = () => {
        list.querySelectorAll('[data-checklist-question-item]').forEach((item, index) => {
            const number = item.querySelector('[data-checklist-question-number]');
            const removeButton = item.querySelector('[data-checklist-question-remove]');

            if (number) {
                number.textContent = String(index + 1);
            }

            if (removeButton) {
                removeButton.disabled = list.children.length === 1;
                removeButton.classList.toggle('opacity-50', list.children.length === 1);
                removeButton.classList.toggle('cursor-not-allowed', list.children.length === 1);
            }
        });
    };

    const createQuestionRow = (value = '') => {
        const fragment = template.content.cloneNode(true);
        const item = fragment.querySelector('[data-checklist-question-item]');
        const input = item?.querySelector('input[name="questions[]"]');

        if (input) {
            input.value = value;
        }

        return item;
    };

    const setQuestions = (questions = ['']) => {
        const normalized = questions.length ? questions : [''];

        list.innerHTML = '';
        normalized.forEach((question) => {
            const item = createQuestionRow(question);
            if (item) {
                list.appendChild(item);
            }
        });

        refreshNumbers();
    };

    const addQuestion = (value = '') => {
        const item = createQuestionRow(value);

        if (!item) {
            return;
        }

        list.appendChild(item);
        refreshNumbers();
        item.querySelector('input[name="questions[]"]')?.focus();
    };

    document.addEventListener('click', (event) => {
        const addTrigger = event.target.closest('[data-checklist-question-add]');

        if (addTrigger) {
            event.preventDefault();
            addQuestion();
            return;
        }

        const removeTrigger = event.target.closest('[data-checklist-question-remove]');

        if (removeTrigger) {
            event.preventDefault();

            if (list.children.length === 1) {
                const input = list.querySelector('input[name="questions[]"]');
                if (input) {
                    input.value = '';
                    input.focus();
                }
                return;
            }

            removeTrigger.closest('[data-checklist-question-item]')?.remove();
            refreshNumbers();
            return;
        }

        const createTrigger = event.target.closest('.modal-open[data-target="#multi-step-modal"]');

        if (createTrigger) {
            window.setTimeout(() => setQuestions(['']), 0);
            return;
        }

        const editTrigger = event.target.closest('.edit-record[data-modal="multi-step-modal"]');

        if (editTrigger) {
            window.setTimeout(() => {
                setQuestions(parseQuestions(editTrigger.dataset.questions));
            }, 0);
            return;
        }

        if (event.target.closest('#multi-step-modal .modal-close')) {
            window.setTimeout(() => setQuestions(['']), 0);
        }
    });

    document.addEventListener('ajax-form:rendered', () => {
        setQuestions(['']);
    });

    setQuestions(['']);
});
