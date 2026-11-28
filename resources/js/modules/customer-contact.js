let contactIndex = 0;
let editingCard = null;

$(document).ready(function () {
    const modal = $('#multi-step-modal');
    const form = modal.find('form');
    const contactsContainer = $('#extraContactsContainer');

    contactIndex = contactsContainer.find('.contact-item').length;

    $(document).on('click', '.modal-open[data-target="#multi-step-modal"]', function () {
        editingCard = null;
        resetModalForm();

        modal.find('.modal-title').text(`Add ${$(this).data('module') || 'Contact'}`);
        modal.find('#addDataBtn, .submit-btn').text('Add');
        modal.removeClass('hidden').addClass('block');
    });

    $(document).on('click', '.edit-contact', function (event) {
        event.preventDefault();

        editingCard = $(this).closest('.contact-item');

        ['name', 'email', 'designation', 'mobile', 'landline', 'whatsapp'].forEach(function (field) {
            form.find(`[name="${field}"]`).val(
                editingCard.find(`.contact-${field}-input`).val() || '',
            );
        });

        modal.find('.modal-title').text('Edit Extra Contact');
        modal.find('#addDataBtn, .submit-btn').text('Update');
        modal.removeClass('hidden').addClass('block');
    });

    $(document).on('click', '#multi-step-modal .modal-close', function (event) {
        event.preventDefault();
        modal.addClass('hidden').removeClass('block');
        resetModalForm();
        editingCard = null;
    });

    $(document).on('click', '#addDataBtn', function (event) {
        event.preventDefault();

        const data = getFormData();
        clearErrors();

        if (!data.name) {
            showError('name', 'Name is required.');
            return;
        }

        if (data.email && !isValidEmail(data.email)) {
            showError('email', 'Please enter a valid email address.');
            return;
        }

        if (!data.email && !data.mobile && !data.whatsapp && !data.landline) {
            showError(
                'email',
                'Please provide at least one contact method (Email, Mobile, WhatsApp, or Landline).',
            );
            return;
        }

        if (editingCard) {
            updateContactCard(editingCard, data);
        } else {
            contactsContainer.append(createContactCard(data, contactIndex));
            contactIndex++;
        }

        reindexContacts();
        modal.addClass('hidden').removeClass('block');
        resetModalForm();
        editingCard = null;
    });

    $(document).on('click', '.remove-contact', function (event) {
        event.preventDefault();
        $(this).closest('.contact-item').remove();
        reindexContacts();
    });

    function getFormData() {
        return {
            name: valueOf('name'),
            email: valueOf('email').toLowerCase(),
            designation: valueOf('designation'),
            mobile: valueOf('mobile'),
            landline: valueOf('landline'),
            whatsapp: valueOf('whatsapp'),
        };
    }

    function valueOf(field) {
        return String(form.find(`[name="${field}"]`).val() || '').trim();
    }

    function createContactCard(data, index) {
        const templateHtml = $('#contact-template').html();

        if (!templateHtml) {
            throw new Error('The customer contact card template was not found.');
        }

        const card = $(templateHtml);
        updateContactCard(card, data);
        setContactInputNames(card, index);

        return card;
    }

    function updateContactCard(card, data) {
        card.find('.contact-name').text(data.name);
        card.find('.contact-email').text(data.email || '--');
        card.find('.contact-designation').text(data.designation || '--');
        card.find('.contact-mobile').text(data.mobile || '--');
        card.find('.contact-landline').text(data.landline || '--');
        card.find('.contact-whatsapp').text(data.whatsapp || '--');

        ['name', 'email', 'designation', 'mobile', 'landline', 'whatsapp'].forEach(function (field) {
            card.find(`.contact-${field}-input`).val(data[field] || '');
        });
    }

    function setContactInputNames(card, index) {
        const fieldClasses = {
            name: 'contact-name-input',
            email: 'contact-email-input',
            designation: 'contact-designation-input',
            mobile: 'contact-mobile-input',
            landline: 'contact-landline-input',
            whatsapp: 'contact-whatsapp-input',
        };

        Object.entries(fieldClasses).forEach(function ([field, className]) {
            card.find(`.${className}`).attr('name', `contacts[${index}][${field}]`);
        });

        card.find('.edit-contact').attr('data-index', index);
    }

    function reindexContacts() {
        contactsContainer.find('.contact-item').each(function (index) {
            const card = $(this);

            card.find('.edit-contact').attr('data-index', index);
            card.find('input[name]').each(function () {
                const input = $(this);
                input.attr(
                    'name',
                    input.attr('name').replace(/^contacts\[\d+\]/, `contacts[${index}]`),
                );
            });
        });

        contactIndex = contactsContainer.find('.contact-item').length;
    }

    function resetModalForm() {
        if (form[0]) {
            form[0].reset();
        }

        clearErrors();
    }

    function showError(fieldName, message) {
        const field = form.find(`[name="${fieldName}"]`).first();

        if (!field.length) {
            return;
        }

        field.addClass('border-red-500');
        $('<p>')
            .addClass('error-text mt-1 text-sm text-error-300')
            .text(message)
            .appendTo(field.closest('.flex.flex-col'));
    }

    function clearErrors() {
        form.find('.error-text').remove();
        form.find('.border-red-500').removeClass('border-red-500');
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
