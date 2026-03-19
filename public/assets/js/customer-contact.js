let contactIndex = 0;

$(document).ready(function () {

    // OPEN CREATE
    $('.modal-open').on('click', function () {

        let modalId = $(this).data('target');
        let modal = $(modalId);

        let module = $(this).data('module');

        // Change title and button text
        modal.find('.modal-title').text(`Add ${module}`);
        modal.find('.submit-btn').text(`Add`);

        modal.removeClass('hidden');

        clearForms(modal.find('.modal-form'));
    });

    // CLOSE MODAL
    $(document).on('click', '.modal-close', function () {
        let modal = $(this).closest('.modal-form');

        modal.addClass('hidden');
        clearForms(modal.find('.modal-form'));
    });

    // SAVE CONTACT (frontend only)
    $(document).on('click', '#addDataBtn', function () {

        let modal = $('#multi-step-modal');
        let form = modal.find('form');

        const data = getFormData(form);

        if (!data.name) {
            showError(form, 'name', 'Name is required');
            return;
        }

        // If email is filled, check if it's valid
        if (data.email && !isValidEmail(data.email)) {
            showError(form, 'email', 'Please enter a valid email address');
            return;
        }

        // At least one contact field is required
        if (!data.email && !data.mobile && !data.landline && !data.whatsapp) {
            showError(form, 'email', 'At least one required (Email, Mobile, WhatsApp, Landline)');
            return;
        }

        const template = createContactTemplate(data, contactIndex);

        $('#extraContactsContainer').append(template);

        contactIndex++;

        clearForms(form);
        modal.addClass('hidden').removeClass('flex');
    });

    // REMOVE CONTACT
    $(document).on('click', '.remove-contact', function () {
        $(this).closest('.border').remove();
    });

    // Get form data
    const getFormData = (form) => {
        return {
            name: form.find('[name="name"]').val(),
            email: form.find('[name="email"]').val(),
            designation: form.find('[name="designation"]').val(),
            mobile: form.find('[name="mobile"]').val(),
            landline: form.find('[name="landline"]').val(),
            whatsapp: form.find('[name="whatsapp"]').val(),
        };
    }

    // Create template
    const createContactTemplate = (data, index) => {

        const template = $($('#contact-template').html());

        template.find('.contact-name').text(data.name);
        template.find('.contact-email').text(data.email ?? '--');
        template.find('.contact-designation').text(data.designation ?? '--');
        template.find('.contact-mobile').text(data.mobile ?? '--');
        template.find('.contact-landline').text(data.landline ?? '--');
        template.find('.contact-whatsapp').text(data.whatsapp ?? '--');

        Object.entries(data).forEach(([key, value]) => {
            template.find(`.contact-${key}-input`)
                .attr('name', `contacts[${index}][${key}]`)
                .val(value);
        });

        return template;
    }

    // Show validation error
    const showError = (form, fieldName, message) => {
        form.find('.error-text').remove();
        let fieldWrapper = form.find(`[name="${fieldName}"]`).closest('div');
        fieldWrapper.append(`<span class="text-red-500 error-text">${message}</span>`);
    }

    // Clear form elements and errors
    const clearForms = (form) => {
        form[0].reset();
        form.find('.error-text').remove();
        form.find('input').removeClass('border-red-500');
        form.find('select').removeClass('border-red-500');
        form.find('textarea').removeClass('border-red-500');
    }

    const isValidEmail = (email) => {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
});