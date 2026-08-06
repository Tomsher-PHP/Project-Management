import { initTomSelect } from '../../components/tom-select';

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
    let validationErrorSequence = 0;
    let modalOpenRequest = 0;
    const targetQuestionType = builder.dataset.appraisalTargetQuestionType;
    const questionUnitsUrl = builder.dataset.appraisalQuestionUnitsUrl;

    form.noValidate = true;

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
                measurementType: question.measurement_type ?? question.measurementType ?? '',
                targetValue: question.target_value ?? question.targetValue ?? '',
                unit: question.unit ?? '',
                isActive: toBoolean(question.is_active ?? question.isActive, true),
            };
        }

        return {
            id: '',
            question,
            questionType: 'rating',
            measurementType: '',
            targetValue: '',
            unit: '',
            isActive: true,
        };
    };

    const setTargetFieldsVisibility = (item) => {
        const typeSelect = item?.querySelector('[data-appraisal-question-type]');
        const targetFields = item?.querySelector('[data-appraisal-target-fields]');
        const isTarget = typeSelect?.value === targetQuestionType;

        if (!targetFields) {
            return;
        }

        targetFields.classList.toggle('hidden', !isTarget);
        targetFields.classList.toggle('flex', isTarget);
        targetFields.querySelectorAll('input, select').forEach((field) => {
            if (isTarget) {
                field.setAttribute('required', 'required');
                return;
            }

            field.removeAttribute('required');
        });
    };

    const clearTargetValidationError = (field) => {
        if (!field) {
            return;
        }

        const validationId = field.dataset.appraisalValidationId;

        if (validationId) {
            document.getElementById(validationId)?.remove();
            delete field.dataset.appraisalValidationId;
        }

        field.classList.remove('border-red-500');
        field.tomselect?.control?.classList.remove('border-red-500');
    };

    const showTargetValidationError = (field, message) => {
        clearTargetValidationError(field);

        validationErrorSequence += 1;
        const validationId = `appraisal-target-error-${validationErrorSequence}`;
        const error = document.createElement('span');
        const anchor = field.tomselect?.wrapper || field;

        error.id = validationId;
        error.className = 'mt-1 block text-xs text-red-500 error-text';
        error.textContent = message;
        anchor.insertAdjacentElement('afterend', error);
        field.dataset.appraisalValidationId = validationId;

        if (field.tomselect) {
            field.tomselect.control.classList.add('border-red-500');
            return;
        }

        field.classList.add('border-red-500');
    };

    const validateTargetQuestions = () => {
        let firstInvalidField = null;

        list.querySelectorAll('[data-appraisal-question-item]').forEach((item) => {
            const typeSelect = item.querySelector('[data-appraisal-question-type]');
            const measurementType = item.querySelector('[data-appraisal-measurement-type]');
            const targetValue = item.querySelector('[data-appraisal-target-value]');
            const unit = item.querySelector('[data-appraisal-unit]');

            [measurementType, targetValue, unit].forEach(clearTargetValidationError);

            if (typeSelect?.value !== targetQuestionType) {
                return;
            }

            const requiredFields = [
                [measurementType, 'Please select a measurement type.'],
                [targetValue, 'Please enter a target value.'],
                [unit, 'Please select a unit.'],
            ];

            requiredFields.forEach(([field, message]) => {
                const value = field?.tomselect?.getValue() ?? field?.value ?? '';

                if (String(value).trim() !== '') {
                    return;
                }

                showTargetValidationError(field, message);
                firstInvalidField ??= field;
            });
        });

        if (!firstInvalidField) {
            return true;
        }

        if (firstInvalidField.tomselect) {
            firstInvalidField.tomselect.focus();
        } else {
            firstInvalidField.focus();
        }

        return false;
    };

    const destroyQuestionRowSelects = (item) => {
        item?.querySelectorAll('select.tom-select, select.tom-select-no-search, select.tom-select-add').forEach((select) => {
            select.tomselect?.destroy();
        });
    };

    const refreshUnitSelectOptions = (select, units, selectedValue = '') => {
        if (!select) {
            return;
        }

        const options = units.map((unit) => ({
            value: String(unit.value ?? unit.name ?? ''),
            text: String(unit.text ?? unit.name ?? ''),
        })).filter((unit) => unit.value !== '');
        const selectedUnit = String(selectedValue || '');

        if (selectedUnit && !options.some((unit) => unit.value === selectedUnit)) {
            options.push({ value: selectedUnit, text: selectedUnit });
        }

        if (select.tomselect) {
            select.tomselect.clear(true);
            select.tomselect.clearOptions();
            select.tomselect.addOptions(options);
            select.tomselect.refreshOptions(false);

            if (selectedUnit) {
                select.tomselect.setValue(selectedUnit, true);
            }

            return;
        }

        const placeholder = new Option('Select unit', '');
        select.replaceChildren(
            placeholder,
            ...options.map((unit) => new Option(unit.text, unit.value)),
        );
        select.value = selectedUnit;
    };

    const refreshQuestionUnits = async () => {
        if (!questionUnitsUrl) {
            return;
        }

        const response = await fetch(questionUnitsUrl, {
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Unable to load appraisal question units.');
        }

        const payload = await response.json();
        const units = Array.isArray(payload.data) ? payload.data : [];
        const templateUnitSelect = template.content.querySelector('[data-appraisal-unit]');

        refreshUnitSelectOptions(templateUnitSelect, units);
        list.querySelectorAll('[data-appraisal-unit]').forEach((select) => {
            const selectedValue = select.tomselect?.getValue() ?? select.value;
            refreshUnitSelectOptions(select, units, selectedValue);
        });
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
        const measurementTypeSelect = item?.querySelector('[data-appraisal-measurement-type]');
        const targetValueInput = item?.querySelector('[data-appraisal-target-value]');
        const unitSelect = item?.querySelector('[data-appraisal-unit]');

        if (idInput) {
            idInput.value = normalizedQuestion.id;
        }

        if (input) {
            input.value = normalizedQuestion.question;
        }

        if (typeSelect) {
            typeSelect.value = normalizedQuestion.questionType;
        }

        if (measurementTypeSelect) {
            measurementTypeSelect.value = normalizedQuestion.measurementType;
        }

        if (targetValueInput) {
            targetValueInput.value = normalizedQuestion.targetValue;
        }

        if (unitSelect) {
            if (
                normalizedQuestion.unit
                && !Array.from(unitSelect.options).some((option) => option.value === normalizedQuestion.unit)
            ) {
                unitSelect.add(new Option(normalizedQuestion.unit, normalizedQuestion.unit));
            }

            unitSelect.value = normalizedQuestion.unit;
        }

        setQuestionActiveState(item, normalizedQuestion.isActive);
        setTargetFieldsVisibility(item);

        return item;
    };

    const setQuestions = (questions = ['']) => {
        const parsed = Array.isArray(questions) ? questions : [];
        const normalized = parsed.length > 0 ? parsed : [''];

        list.querySelectorAll('[data-appraisal-question-item]').forEach(destroyQuestionRowSelects);
        list.innerHTML = '';
        normalized.forEach((question) => {
            const item = createQuestionRow(question);

            if (item) {
                list.appendChild(item);
                initTomSelect(item);
                setTargetFieldsVisibility(item);
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
        initTomSelect(item);
        setTargetFieldsVisibility(item);
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
                const item = list.children[0];
                const input = item.querySelector('input[name="questions[]"]');
                const idInput = item.querySelector('[data-appraisal-question-id]');
                if (input) input.value = '';
                if (idInput) idInput.value = '';
                item.querySelectorAll('[data-appraisal-target-value]').forEach(el => { el.value = ''; });
                return;
            }

            const item = removeTrigger.closest('[data-appraisal-question-item]');

            destroyQuestionRowSelects(item);
            item?.remove();
            refreshNumbers();
            return;
        }

        const createTrigger = event.target.closest('.modal-open[data-target="#multi-step-modal"]');

        if (createTrigger) {
            const requestId = ++modalOpenRequest;

            window.setTimeout(async () => {
                try {
                    await refreshQuestionUnits();
                } catch (error) {
                    // Keep the currently rendered options available if refreshing fails.
                }

                if (requestId !== modalOpenRequest) {
                    return;
                }

                modal.querySelector('.modal-title').textContent = 'Create Appraisal Category';
                modal.querySelector('.submit-btn').textContent = 'Save';
                setQuestions(['']);
                setDefaultStatusState(false);
            }, 0);
            return;
        }

        const editTrigger = event.target.closest('.edit-record[data-modal="multi-step-modal"]');

        if (editTrigger) {
            const requestId = ++modalOpenRequest;

            window.setTimeout(async () => {
                try {
                    await refreshQuestionUnits();
                } catch (error) {
                    // Keep the currently rendered options available if refreshing fails.
                }

                if (requestId !== modalOpenRequest) {
                    return;
                }

                modal.querySelector('.modal-title').textContent = 'Edit Appraisal Category';
                modal.querySelector('.submit-btn').textContent = 'Update';
                setQuestions(parseQuestions(editTrigger.dataset.questions));
                setDefaultStatusState(toBoolean(editTrigger.dataset.isDefault, false));
            }, 0);
            return;
        }

        if (event.target.closest('#multi-step-modal .modal-close')) {
            modalOpenRequest += 1;
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

    list.addEventListener('change', (event) => {
        if (event.target.matches('[data-appraisal-measurement-type], [data-appraisal-unit]')) {
            clearTargetValidationError(event.target);
        }

        if (!event.target.matches('[data-appraisal-question-type]')) {
            return;
        }

        const item = event.target.closest('[data-appraisal-question-item]');

        setTargetFieldsVisibility(item);
        item.querySelectorAll('[data-appraisal-measurement-type], [data-appraisal-target-value], [data-appraisal-unit]')
            .forEach(clearTargetValidationError);
    });

    list.addEventListener('input', (event) => {
        if (event.target.matches('[data-appraisal-target-value]')) {
            clearTargetValidationError(event.target);
        }
    });

    form.addEventListener('submit', (event) => {
        if (!validateTargetQuestions()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        const questionsArray = [];
        list.querySelectorAll('[data-appraisal-question-item]').forEach((item) => {
            const idInput = item.querySelector('[data-appraisal-question-id]');
            const questionInput = item.querySelector('input[name="questions[]"]');
            const typeSelect = item.querySelector('[data-appraisal-question-type]');
            const measurementSelect = item.querySelector('[data-appraisal-measurement-type]');
            const targetInput = item.querySelector('[data-appraisal-target-value]');
            const unitSelect = item.querySelector('[data-appraisal-unit]');
            const activeInput = item.querySelector('[data-appraisal-question-active-input]');

            const unitValue = unitSelect?.tomselect?.getValue() ?? unitSelect?.value ?? '';

            questionsArray.push({
                id: idInput?.value || null,
                question: questionInput?.value || '',
                question_type: typeSelect?.value || 'rating',
                measurement_type: measurementSelect?.value || null,
                target_value: targetInput?.value || null,
                unit: unitValue || null,
                is_active: activeInput ? activeInput.value === '1' : true,
            });
        });

        console.log('Selected questions count:', questionsArray.length);
        console.log('Question IDs before sending:', questionsArray.map((q) => q.id).filter(Boolean));

        let jsonInput = form.querySelector('input[name="questions_json"]');
        if (!jsonInput) {
            jsonInput = document.createElement('input');
            jsonInput.type = 'hidden';
            jsonInput.name = 'questions_json';
            form.appendChild(jsonInput);
        }
        jsonInput.value = JSON.stringify(questionsArray);

        const rowInputs = list.querySelectorAll('input, select');
        rowInputs.forEach((el) => {
            el.disabled = true;
        });

        setTimeout(() => {
            rowInputs.forEach((el) => {
                el.disabled = false;
            });
        }, 0);
    }, true);

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
