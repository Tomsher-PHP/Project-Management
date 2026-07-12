document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('multi-step-modal');
    const form = document.getElementById('appraisalCategoryForm');
    const builder = modal?.querySelector('[data-appraisal-question-builder]');
    const list = builder?.querySelector('[data-appraisal-question-list]');
    const template = document.getElementById('appraisal-question-template');

    if (!modal || !form || !builder || !list || !template) {
        return;
    }

    let draggedQuestionItem = null;
    let dragHandleItem = null;

    const refreshNumbers = () => {
        list.querySelectorAll('[data-appraisal-question-item]').forEach((item, index) => {
            const number = item.querySelector('[data-appraisal-question-number]');
            const removeButton = item.querySelector('[data-appraisal-question-remove]');

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

    const resetDraggedItemState = () => {
        if (draggedQuestionItem) {
            draggedQuestionItem.classList.remove('opacity-60', 'scale-[0.99]');
            draggedQuestionItem.setAttribute('draggable', 'false');
            draggedQuestionItem.style.boxShadow = '';
        }

        if (dragHandleItem) {
            dragHandleItem.setAttribute('draggable', 'false');
        }

        draggedQuestionItem = null;
        dragHandleItem = null;
    };

    const parseQuestions = (value) => {
        if (Array.isArray(value)) {
            return value.filter((question) => String(question?.question || question || '').trim() !== '');
        }

        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);

                return Array.isArray(parsed)
                    ? parsed.filter((question) => String(question?.question || question || '').trim() !== '')
                    : [];
            } catch (error) {
                return [];
            }
        }

        return [];
    };

    const toBoolean = (value, fallback = true) => {
        if (typeof value === 'boolean') {
            return value;
        }

        if (value === undefined || value === null || value === '') {
            return fallback;
        }

        return ['1', 'true', 'on', 'yes'].includes(String(value).toLowerCase());
    };

    const normalizeQuestion = (question = '') => {
        if (typeof question === 'object' && question !== null) {
            return {
                id: question.id || '',
                question: question.question || '',
                questionType: question.question_type || question.questionType || 'rating',
                isActive: toBoolean(question.is_active ?? question.isActive, true),
            };
        }

        return {
            id: '',
            question,
            questionType: 'rating',
            isActive: true,
        };
    };

    const setQuestionActiveState = (item, isActive = true) => {
        const activeInput = item?.querySelector('[data-appraisal-question-active-input]');
        const activeToggle = item?.querySelector('[data-appraisal-question-active-toggle]');
        const activeLabel = item?.querySelector('[data-appraisal-question-active-label]');

        if (!item) {
            return;
        }

        if (activeInput) {
            activeInput.value = isActive ? '1' : '0';
        }

        if (activeToggle) {
            activeToggle.classList.toggle('active', isActive);
            activeToggle.setAttribute('aria-checked', isActive ? 'true' : 'false');
        }

        if (activeLabel) {
            activeLabel.textContent = isActive ? 'Enabled' : 'Disabled';
            activeLabel.classList.toggle('text-success-400', isActive);
            activeLabel.classList.toggle('dark:text-success-300', isActive);
            activeLabel.classList.toggle('text-bgray-500', !isActive);
            activeLabel.classList.toggle('dark:text-bgray-300', !isActive);
        }

        item.classList.toggle('opacity-70', !isActive);
    };

    const setDefaultStatusState = (isDefault = false) => {
        const defaultInput = modal?.querySelector('[data-appraisal-category-default-input]');
        const defaultToggle = modal?.querySelector('[data-appraisal-category-default-toggle]');
        const defaultLabel = modal?.querySelector('[data-appraisal-category-default-label]');

        if (defaultInput) {
            defaultInput.value = isDefault ? '1' : '0';
        }

        if (defaultToggle) {
            defaultToggle.classList.toggle('active', isDefault);
            defaultToggle.setAttribute('aria-checked', isDefault ? 'true' : 'false');
        }

        if (defaultLabel) {
            defaultLabel.textContent = isDefault ? 'Enabled' : 'Disabled';
            defaultLabel.classList.toggle('text-success-400', isDefault);
            defaultLabel.classList.toggle('dark:text-success-300', isDefault);
            defaultLabel.classList.toggle('text-bgray-500', !isDefault);
            defaultLabel.classList.toggle('dark:text-bgray-300', !isDefault);
        }
    };

    const createQuestionRow = (question = '') => {
        const normalizedQuestion = normalizeQuestion(question);
        const fragment = template.content.cloneNode(true);
        const item = fragment.querySelector('[data-appraisal-question-item]');
        const idInput = item?.querySelector('[data-appraisal-question-id]');
        const input = item?.querySelector('input[name="questions[]"]');
        const typeSelect = item?.querySelector('[data-appraisal-question-type]');

        if (idInput) {
            idInput.value = normalizedQuestion.id;
        }

        if (input) {
            input.value = normalizedQuestion.question;
        }

        if (typeSelect) {
            typeSelect.value = normalizedQuestion.questionType;
        }

        setQuestionActiveState(item, normalizedQuestion.isActive);

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
        const addTrigger = event.target.closest('[data-appraisal-question-add]');

        if (addTrigger) {
            event.preventDefault();
            addQuestion();
            return;
        }

        const activeToggle = event.target.closest('[data-appraisal-question-active-toggle]');

        if (activeToggle) {
            event.preventDefault();

            const item = activeToggle.closest('[data-appraisal-question-item]');
            const isActive = activeToggle.getAttribute('aria-checked') !== 'true';

            setQuestionActiveState(item, isActive);
            return;
        }

        const defaultToggle = event.target.closest('[data-appraisal-category-default-toggle]');

        if (defaultToggle) {
            event.preventDefault();
            const isDefault = defaultToggle.getAttribute('aria-checked') !== 'true';
            setDefaultStatusState(isDefault);
            return;
        }

        const removeTrigger = event.target.closest('[data-appraisal-question-remove]');

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

            removeTrigger.closest('[data-appraisal-question-item]')?.remove();
            refreshNumbers();
            return;
        }

        const createTrigger = event.target.closest('.modal-open[data-target="#multi-step-modal"]');

        if (createTrigger) {
            window.setTimeout(() => {
                modal.querySelector('.modal-title').textContent = 'Create Appraisal Category';
                modal.querySelector('.submit-btn').textContent = 'Save';
                setQuestions(['']);
                setDefaultStatusState(false);
            }, 0);
            return;
        }

        const editTrigger = event.target.closest('.edit-record[data-modal="multi-step-modal"]');

        if (editTrigger) {
            window.setTimeout(() => {
                modal.querySelector('.modal-title').textContent = 'Edit Appraisal Category';
                modal.querySelector('.submit-btn').textContent = 'Update';
                setQuestions(parseQuestions(editTrigger.dataset.questions));
                setDefaultStatusState(toBoolean(editTrigger.dataset.isDefault, false));
            }, 0);
            return;
        }

        if (event.target.closest('#multi-step-modal .modal-close')) {
            window.setTimeout(() => {
                setQuestions(['']);
                setDefaultStatusState(false);
            }, 0);
        }
    });

    document.addEventListener('ajax-form:rendered', () => {
        setQuestions(['']);
        setDefaultStatusState(false);
    });

    list.addEventListener('mousedown', function (event) {
        const handle = event.target.closest('[data-appraisal-question-handle]');

        if (!handle) {
            return;
        }

        const item = handle.closest('[data-appraisal-question-item]');

        if (!item) {
            return;
        }

        dragHandleItem = item;
        item.setAttribute('draggable', 'true');
    });

    list.addEventListener('mouseup', function () {
        if (!draggedQuestionItem && dragHandleItem) {
            dragHandleItem.setAttribute('draggable', 'false');
            dragHandleItem = null;
        }
    });

    list.addEventListener('mouseleave', function () {
        if (!draggedQuestionItem && dragHandleItem) {
            dragHandleItem.setAttribute('draggable', 'false');
            dragHandleItem = null;
        }
    });

    list.addEventListener('dragstart', function (event) {
        const item = event.target.closest('[data-appraisal-question-item]');

        if (!item || item !== dragHandleItem) {
            event.preventDefault();
            return;
        }

        draggedQuestionItem = item;
        item.classList.add('opacity-60', 'scale-[0.99]');
        item.style.boxShadow = '0 18px 35px -18px rgba(15, 23, 42, 0.35)';

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.querySelector('[data-appraisal-question-id]')?.value || '');
        }
    });

    list.addEventListener('dragover', function (event) {
        if (draggedQuestionItem) {
            event.preventDefault();
        }

        const targetItem = event.target.closest('[data-appraisal-question-item]');

        if (!draggedQuestionItem || !targetItem || targetItem === draggedQuestionItem) {
            return;
        }

        const targetBounds = targetItem.getBoundingClientRect();
        const insertAfterTarget = event.clientY > targetBounds.top + (targetBounds.height / 2);

        if (insertAfterTarget) {
            list.insertBefore(draggedQuestionItem, targetItem.nextElementSibling);
            return;
        }

        list.insertBefore(draggedQuestionItem, targetItem);
    });

    list.addEventListener('drop', function (event) {
        if (!draggedQuestionItem) {
            return;
        }

        event.preventDefault();
        refreshNumbers();
        resetDraggedItemState();
    });

    list.addEventListener('dragend', function () {
        refreshNumbers();
        resetDraggedItemState();
    });

    setQuestions(['']);
});

$(document).on('click', '.default-toggle', function () {
    let btn = $(this);

    if (btn.data('processing')) return;
    btn.data('processing', true);

    let id = btn.data('id');
    let url = btn.data('url');
    let entity = btn.data('entity');

    let isDefault = btn.attr('aria-checked') === 'true';
    let actionText = isDefault ? 'deactivate' : 'activate';

    window.Alert.confirm({
        title: 'Are you sure?',
        text: `You are about to ${actionText} this ${entity}.`,
        confirmText: `Yes, ${actionText} it`
    }).then(result => {

        if (!result.isConfirmed) {
            btn.data('processing', false);
            btn.toggleClass('active', isDefault);
            return;
        }

        $.ajax({
            url: url,
            type: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id
            },

            success: function (response) {
                if (response.success) {
                    let newStatus = response.is_default == 1;

                    // Update switch UI
                    btn.attr('aria-checked', newStatus);
                    btn.toggleClass('active', newStatus);

                    let capitalizedEntity = entity.charAt(0).toUpperCase() + entity.slice(1);
                    window.Alert.success(`${capitalizedEntity} updated successfully.`);
                }
                else {
                    window.Alert.error('Update failed.');
                }
            },

            error: function () {
                window.Alert.error('Something went wrong.');
            },

            complete: function () {
                btn.data('processing', false);
            }
        });
    });
});
