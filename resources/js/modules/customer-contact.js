let contactIndex = 0;
let editingCard = null;

$(document).ready(function () {
    const modal = $('#multi-step-modal');
    const form = modal.find('form');
    const contactsContainer = $('#extraContactsContainer');

    contactIndex = contactsContainer.find('.contact-item').length;

    initCustomerLoginAccess();

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

    function initCustomerLoginAccess() {
        const scope = $('[data-customer-login-scope]');
        if (!scope.length) {
            return;
        }

        const toggle = scope.find('[data-customer-login-toggle]');
        const fields = scope.find('[data-customer-login-fields]');
        const usernameInput = scope.find('[data-customer-username]');
        const passwordInput = scope.find('[data-customer-password]');
        const confirmationInput = scope.find('[data-customer-password-confirmation]');
        const confirmationStar = scope.find('[data-customer-password-confirmation-required]');
        const companyEmailInput = $('#email');
        const hasExistingPassword = scope.attr('data-has-existing-password') === '1';

        let usernameManuallyEdited = String(usernameInput.val() || '').trim() !== '';

        function syncCustomerLoginState(clearPasswords) {
            const enabled = toggle.is(':checked');

            fields.toggleClass('hidden', !enabled);
            fields.find('input').prop('disabled', !enabled);
            usernameInput.prop('required', enabled);

            if (enabled) {
                if (String(usernameInput.val() || '').trim() === '') {
                    const companyEmail = String(companyEmailInput.val() || '').trim().toLowerCase();
                    if (companyEmail !== '') {
                        usernameInput.val(companyEmail);
                    }
                }
                syncPasswordRequirements();
            } else {
                passwordInput.prop('required', false);
                confirmationInput.prop('required', false);

                if (clearPasswords) {
                    passwordInput.val('').attr('type', 'password');
                    confirmationInput.val('').attr('type', 'password');
                }
            }
        }

        function syncPasswordRequirements() {
            const enabled = toggle.is(':checked');
            if (!enabled) {
                passwordInput.prop('required', false);
                confirmationInput.prop('required', false);
                return;
            }

            const hasTypedPassword = String(passwordInput.val() || '') !== '';
            const requirePassword = !hasExistingPassword;
            const requireConfirmation = !hasExistingPassword || hasTypedPassword;

            passwordInput.prop('required', requirePassword);
            confirmationInput.prop('required', requireConfirmation);
            confirmationStar.toggleClass('hidden', !requireConfirmation);
        }

        toggle.on('change', function () {
            syncCustomerLoginState(!toggle.is(':checked'));
        });

        usernameInput.on('input', function () {
            usernameManuallyEdited = String(usernameInput.val() || '').trim() !== '';
        });

        companyEmailInput.on('input', function () {
            if (!toggle.is(':checked') || usernameManuallyEdited) {
                return;
            }

            usernameInput.val(String(companyEmailInput.val() || '').trim().toLowerCase());
        });

        passwordInput.on('input', function () {
            syncPasswordRequirements();
            confirmationInput[0]?.setCustomValidity('');
        });

        confirmationInput.on('input', function () {
            confirmationInput[0]?.setCustomValidity('');
        });

        scope.closest('form').on('submit', function (event) {
            if (!toggle.is(':checked')) {
                return;
            }

            const password = String(passwordInput.val() || '');
            const confirmation = String(confirmationInput.val() || '');

            if (password !== '' && password !== confirmation) {
                confirmationInput[0]?.setCustomValidity('Password confirmation does not match.');
                confirmationInput[0]?.reportValidity();
                event.preventDefault();
                return;
            }

            confirmationInput[0]?.setCustomValidity('');
        });

        $(document).on('click', '[data-password-toggle]', function () {
            const button = $(this);
            const input = document.getElementById(button.data('target'));

            if (!input) {
                return;
            }

            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.attr('aria-label', showPassword ? 'Hide password' : 'Show password');
            button.attr('title', showPassword ? 'Hide password' : 'Show password');
        });

        syncCustomerLoginState(false);
    }
});
