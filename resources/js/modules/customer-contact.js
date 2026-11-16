let contactIndex = 0;
let editingIndex = null;
let editingCard = null;

$(document).ready(function () {
    const modal = $('#multi-step-modal');
    const form = modal.find('form');
    const contactsContainer = $('#extraContactsContainer');

    contactIndex = contactsContainer.find('.contact-item').length;

    // --------------------------------------------------
    // OPEN CREATE CONTACT MODAL
    // --------------------------------------------------
    $(document).on('click', '.modal-open[data-target="#multi-step-modal"]', function () {
        editingIndex = null;
        editingCard = null;

        const module = $(this).data('module') || 'Contact';

        modal.find('.modal-title').text(`Add ${module}`);
        modal.find('#addDataBtn').text('Add');
        modal.find('.submit-btn').text('Add');

        clearForms(form);

        modal.removeClass('hidden').addClass('flex');
    });

    // --------------------------------------------------
    // EDIT CONTACT
    // --------------------------------------------------
    $(document).on('click', '.edit-contact', function (e) {
        e.preventDefault();

        editingCard = $(this).closest('.contact-item');
        editingIndex = $(this).data('index');

        form.find('[name="name"]').val(
            editingCard.find('.contact-name-input').val() || ''
        );

        form.find('[name="email"]').val(
            editingCard.find('.contact-email-input').val() || ''
        );

        form.find('[name="designation"]').val(
            editingCard.find('.contact-designation-input').val() || ''
        );

        form.find('[name="mobile"]').val(
            editingCard.find('.contact-mobile-input').val() || ''
        );

        form.find('[name="landline"]').val(
            editingCard.find('.contact-landline-input').val() || ''
        );

        form.find('[name="whatsapp"]').val(
            editingCard.find('.contact-whatsapp-input').val() || ''
        );

        // Never prefill a password into the modal.
        form.find('[name="password"]').val('');
        form.find('[name="password_confirmation"]').val('');

        const needsLoginInput = editingCard.find('.contact-needs-login');
        const needsLogin = needsLoginInput.length
            ? needsLoginInput.is(':checked')
            : Number(editingCard.find('.contact-needs-login-input').val()) === 1;

        form.find('[name="needs_login"]')
            .prop('checked', needsLogin)
            .trigger('change');

        modal.find('.modal-title').text('Edit Contact');
        modal.find('#addDataBtn').text('Update');
        modal.find('.submit-btn').text('Update');

        modal.removeClass('hidden').addClass('flex');
    });

    // --------------------------------------------------
    // CLOSE MODAL
    // --------------------------------------------------
    $(document).on('click', '.modal-close', function (e) {
        e.preventDefault();

        const currentModal = $(this).closest('.modal');

        currentModal.addClass('hidden').removeClass('flex');

        clearForms(currentModal.find('form'));

        editingCard = null;
        editingIndex = null;
    });

    // --------------------------------------------------
    // SAVE CONTACT
    // Contact data is transferred to the main customer form.
    // --------------------------------------------------
    $(document).on('click', '#addDataBtn', function (e) {
        e.preventDefault();

        const currentModal = $('#multi-step-modal');
        const currentForm = currentModal.find('form');
        const data = getFormData(currentForm);

        clearErrors(currentForm);

        // Validate name.
        if (!data.name.trim()) {
            showError(currentForm, 'name', 'Name is required');
            return;
        }

        // Validate email format.
        if (data.email && !isValidEmail(data.email)) {
            showError(currentForm, 'email', 'Please enter a valid email address');
            return;
        }

        // At least one contact method is required.
        if (!data.email && !data.mobile && !data.landline && !data.whatsapp) {
            showError(
                currentForm,
                'email',
                'At least one contact field is required: Email, Mobile, WhatsApp or Landline'
            );
            return;
        }

        // Find any password already held by this contact card.
        // This allows editing a newly added contact without re-entering its password.
        const previousPassword = editingCard
            ? (editingCard.find('.contact-password-input').val() || '')
            : '';

        const previousConfirmation = editingCard
            ? (editingCard.find('.contact-password-confirmation-input').val() || '')
            : '';

        // Is this an existing contact already saved in the database?
        const existingContactId = editingCard
            ? (
                editingCard.find('.contact-id-input').val() ||
                editingCard.find('input[name$="[id]"]').val() ||
                editingCard.data('contact-id') ||
                ''
            )
            : '';

        if (data.needs_login) {
            if (!data.email) {
                showError(currentForm, 'email', 'Email is required for login access');
                return;
            }

            // For a new contact, a password is required.
            // For an existing database contact, a blank password means unchanged.
            const passwordProvided = data.password.length > 0;
            const passwordAlreadyAvailable = previousPassword.length > 0;

            if (!passwordProvided && !passwordAlreadyAvailable && !existingContactId) {
                showError(currentForm, 'password', 'Password is required');
                return;
            }

            if (passwordProvided && data.password.length < 8) {
                showError(
                    currentForm,
                    'password',
                    'Password must be at least 8 characters'
                );
                return;
            }

            if (passwordProvided && !data.password_confirmation) {
                showError(
                    currentForm,
                    'password_confirmation',
                    'Please confirm the password'
                );
                return;
            }

            if (
                passwordProvided &&
                data.password !== data.password_confirmation
            ) {
                showError(
                    currentForm,
                    'password_confirmation',
                    'Password confirmation does not match'
                );
                return;
            }

            // Preserve the password for an existing card when no replacement
            // password was entered in the modal.
            if (!passwordProvided && passwordAlreadyAvailable) {
                data.password = previousPassword;
                data.password_confirmation = previousConfirmation;
            }
        } else {
            // Login disabled: do not submit credentials for this contact.
            data.needs_login = 0;
            data.password = '';
            data.password_confirmation = '';
        }

        // EDIT EXISTING CONTACT CARD
        if (editingCard !== null) {
            editingCard.find('.contact-name').text(data.name);
            editingCard.find('.contact-email').text(data.email || '--');
            editingCard.find('.contact-designation').text(data.designation || '--');
            editingCard.find('.contact-mobile').text(data.mobile || '--');
            editingCard.find('.contact-landline').text(data.landline || '--');
            editingCard.find('.contact-whatsapp').text(data.whatsapp || '--');

            // Update ordinary contact inputs.
            const contactFields = [
                'name',
                'email',
                'designation',
                'mobile',
                'landline',
                'whatsapp'
            ];

            contactFields.forEach(function (key) {
                editingCard.find(`.contact-${key}-input`).val(data[key] || '');
            });

            // Update the login checkbox.
            editingCard.find('.contact-needs-login')
                .prop('checked', Number(data.needs_login) === 1);

            // Update the hidden login-access value if the template has one.
            editingCard.find('.contact-needs-login-input')
                .val(Number(data.needs_login) === 1 ? '1' : '0');

            // Keep credentials available for the main customer form submission.
            // If login remains enabled and no new password was entered, preserve
            // any existing card value rather than overwriting it with an empty one.
            const passwordInput = editingCard.find('.contact-password-input');
            const confirmationInput = editingCard.find(
                '.contact-password-confirmation-input'
            );

            if (!data.needs_login) {
                passwordInput.val('');
                confirmationInput.val('');
            } else if (data.password) {
                passwordInput.val(data.password);
                confirmationInput.val(data.password_confirmation || '');
            }

            updateContactLoginFields(editingCard);
        } else {
            // CREATE NEW CONTACT CARD
            const template = createContactTemplate(data, contactIndex);

            contactsContainer.append(template);

            contactIndex++;
        }

        // Reindex cards to ensure submitted contacts use sequential indexes.
        reindexContacts();

        // Close and reset modal.
        currentModal.addClass('hidden').removeClass('flex');

        clearForms(currentForm);

        editingCard = null;
        editingIndex = null;
    });

    // --------------------------------------------------
    // REMOVE CONTACT
    // --------------------------------------------------
    $(document).on('click', '.remove-contact', function (e) {
        e.preventDefault();

        $(this).closest('.contact-item').remove();

        reindexContacts();
    });

    // --------------------------------------------------
// TOGGLE LOGIN FIELDS
// Supports primary contact, create modal and edit modal.
// --------------------------------------------------
$(document).on('change', '[data-login-toggle]', function () {
    const checkbox = $(this);
    const targetId = checkbox.data('login-target');

    if (!targetId) {
        return;
    }

    const loginFields = $('#' + targetId);
    const enabled = checkbox.is(':checked');

    loginFields.toggleClass('hidden', !enabled);
    loginFields.find('input').prop('disabled', !enabled);
});

// Retain support for existing contact cards using the old class.
$(document).on('change', '.contact-needs-login', function () {
    const contact = $(this).closest('.contact-item, .contact-person-item');

    updateContactLoginFields(contact);
});

    // --------------------------------------------------
    // GET MODAL FORM DATA
    // Modal names remain: needs_login, password,
    // password_confirmation.
    // --------------------------------------------------
    function getFormData(formElement) {
        return {
            name: String(formElement.find('[name="name"]').val() || '').trim(),
            email: String(formElement.find('[name="email"]').val() || '').trim(),
            designation: String(
                formElement.find('[name="designation"]').val() || ''
            ).trim(),
            mobile: String(formElement.find('[name="mobile"]').val() || '').trim(),
            landline: String(
                formElement.find('[name="landline"]').val() || ''
            ).trim(),
            whatsapp: String(
                formElement.find('[name="whatsapp"]').val() || ''
            ).trim(),

            needs_login: formElement.find('[name="needs_login"]').is(':checked')
                ? 1
                : 0,

            password: String(formElement.find('[name="password"]').val() || ''),
            password_confirmation: String(
                formElement.find('[name="password_confirmation"]').val() || ''
            )
        };
    }

    // --------------------------------------------------
    // CREATE CONTACT CARD
    // Every field uses contacts[index][field] so Laravel
    // receives the values inside request('contacts').
    // --------------------------------------------------
    function createContactTemplate(data, index) {
        const templateHtml = $('#contact-template').html();

        if (!templateHtml) {
            console.error('The #contact-template element was not found.');
            return $('<div class="contact-item"></div>');
        }

        const template = $(templateHtml);

        template.find('.edit-contact').attr('data-index', index);

        template.find('.contact-name').text(data.name);
        template.find('.contact-email').text(data.email || '--');
        template.find('.contact-designation').text(data.designation || '--');
        template.find('.contact-mobile').text(data.mobile || '--');
        template.find('.contact-landline').text(data.landline || '--');
        template.find('.contact-whatsapp').text(data.whatsapp || '--');

        // Contact details.
        const contactFields = [
            'name',
            'email',
            'designation',
            'mobile',
            'landline',
            'whatsapp'
        ];

        contactFields.forEach(function (key) {
            template.find(`.contact-${key}-input`)
                .attr('name', `contacts[${index}][${key}]`)
                .val(data[key] || '');
        });

        // Login access.
        const needsLoginCheckbox = template.find('.contact-needs-login');

        needsLoginCheckbox
            .attr('name', `contacts[${index}][needs_login]`)
            .val('1')
            .prop('checked', Number(data.needs_login) === 1);

        // If the template has a hidden login-access input instead of a checkbox,
        // update it as well. Avoid creating duplicate fields automatically.
        template.find('.contact-needs-login-input')
            .attr('name', `contacts[${index}][needs_login]`)
            .val(Number(data.needs_login) === 1 ? '1' : '0');

        // Password and confirmation must remain in the submitted form while
        // login is enabled. Do not clear them immediately after assigning them.
        template.find('.contact-password-input')
            .attr('name', `contacts[${index}][password]`)
            .val(Number(data.needs_login) === 1 ? data.password : '');

        template.find('.contact-password-confirmation-input')
            .attr('name', `contacts[${index}][password_confirmation]`)
            .val(
                Number(data.needs_login) === 1
                    ? data.password_confirmation
                    : ''
            );

        updateContactLoginFields(template);

        return template;
    }

    // --------------------------------------------------
    // UPDATE LOGIN FIELD VISIBILITY AND SUBMISSION STATE
    // --------------------------------------------------
    function updateContactLoginFields(contact) {
        const checkbox = contact.find('.contact-needs-login, [name="needs_login"]')
            .first();

        const enabled = checkbox.length && checkbox.is(':checked');

        contact.find('.contact-login-fields').each(function () {
            const fieldsContainer = $(this);

            fieldsContainer.toggle(Boolean(enabled));

            fieldsContainer.find('input').each(function () {
                const input = $(this);
                const isPasswordField =
                    input.is('[name="password"], [name="password_confirmation"]') ||
                    input.hasClass('contact-password-input') ||
                    input.hasClass('contact-password-confirmation-input');

                input.prop('disabled', !enabled);

                // Passwords are required for new contacts in the modal.
                // Card inputs are validated before being added to the card.
                if (isPasswordField && contact.hasClass('contact-person-item')) {
                    input.prop('required', Boolean(enabled));
                } else {
                    input.prop('required', false);
                }
            });
        });
    }

    // --------------------------------------------------
    // REINDEX CONTACT CARDS
    // Fixes the broken regex in the previous version.
    // --------------------------------------------------
    function reindexContacts() {
        contactsContainer.find('.contact-item').each(function (index) {
            const card = $(this);

            card.find('.edit-contact').attr('data-index', index);

            card.find('input[name], select[name], textarea[name]').each(function () {
                const input = $(this);
                const oldName = input.attr('name');

                if (!oldName) {
                    return;
                }

                const newName = oldName.replace(
                    /^contacts\[\d+\]/,
                    `contacts[${index}]`
                );

                input.attr('name', newName);
            });
        });

        contactIndex = contactsContainer.find('.contact-item').length;
    }

    // --------------------------------------------------
    // VALIDATION ERROR
    // --------------------------------------------------
    function showError(formElement, fieldName, message) {
        clearErrors(formElement);

        const field = formElement.find(`[name="${fieldName}"]`).first();

        if (!field.length) {
            return;
        }

        field.addClass('border-red-500');

        const wrapper = field.closest('div');

        $('<span>')
            .addClass('text-red-500 text-sm error-text')
            .text(message)
            .appendTo(wrapper);
    }

    function clearErrors(formElement) {
        formElement.find('.error-text').remove();

        formElement.find('.border-red-500')
            .removeClass('border-red-500');
    }

    // --------------------------------------------------
    // RESET MODAL FORM
    // --------------------------------------------------
    function clearForms(formElement) {
    if (!formElement.length) {
        return;
    }

    if (formElement[0]) {
        formElement[0].reset();
    }

    clearErrors(formElement);

    formElement.find('[data-login-toggle]').each(function () {
        const checkbox = $(this);
        const targetId = checkbox.data('login-target');

        if (targetId) {
            const loginFields = $('#' + targetId);

            loginFields.addClass('hidden');

            loginFields.find('input')
                .prop('disabled', true)
                .prop('required', false);
        }
    });

    formElement.find('.contact-login-fields')
    .addClass('hidden')
    .find('input')
    .prop('disabled', true)
    .prop('required', false);
}

    // --------------------------------------------------
    // EMAIL VALIDATION
    // --------------------------------------------------
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }






// Password visibility toggle
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-password-toggle]');

    if (!button) {
        return;
    }

    const input = document.getElementById(button.dataset.target);

    if (!input) {
        return;
    }

    const isPassword = input.type === 'password';

    input.type = isPassword ? 'text' : 'password';

    button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    button.setAttribute('title', isPassword ? 'Hide password' : 'Show password');

    button.innerHTML = isPassword
        ? `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58a2 2 0 002.84 2.84M9.88 5.09A10.94 10.94 0 0112 4.75c6.25 0 9.75 7.25 9.75 7.25s-3.5 7.25-9.75 7.25c-1.2 0-2.3-.25-3.3-.7" />
            </svg>`
        : `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>`;
});







});
