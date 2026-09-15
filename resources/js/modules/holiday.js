document.addEventListener('DOMContentLoaded', function () {
    const appliedTo = document.getElementById('applied_to');

    const usersWrapper = document.getElementById(
        'holiday-users-wrapper'
    );

    const shiftsWrapper = document.getElementById(
        'holiday-shifts-wrapper'
    );

    const info = document.getElementById(
        'holiday-application-info'
    );

    const infoText = document.getElementById(
        'holiday-application-info-text'
    );

    if (!appliedTo) {
        return;
    }

    function updateApplicationFields() {
        const value = appliedTo.value;

        usersWrapper?.classList.toggle(
            'hidden',
            value !== 'user'
        );

        shiftsWrapper?.classList.toggle(
            'hidden',
            value !== 'shift'
        );

        if (!info || !infoText) {
            return;
        }

        if (value === 'all_users') {
            infoText.textContent =
                'This holiday will be applied to all active users.';
        } else if (value === 'user') {
            infoText.textContent =
                'This holiday will be applied only to the selected users.';
        } else if (value === 'shift') {
            infoText.textContent =
                'This holiday will be applied to users who are assigned to the selected shift(s) on the holiday date.';
        }
    }

    appliedTo.addEventListener(
        'change',
        updateApplicationFields
    );

    updateApplicationFields();
});
