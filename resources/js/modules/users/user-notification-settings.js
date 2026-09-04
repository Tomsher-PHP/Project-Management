import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', () => {
    const bulkToggles = document.querySelectorAll('.bulk-notification-toggle');

    function updateBulkHeaderState(field) {
        const fieldBtns = document.querySelectorAll(`.switch-btn[data-field="${field}"]`);
        if (!fieldBtns.length) return;

        const allChecked = Array.from(fieldBtns).every(btn => btn.getAttribute('aria-checked') === 'true');
        const bulkToggle = document.querySelector(`.bulk-notification-toggle[data-field="${field}"]`);
        if (bulkToggle) {
            bulkToggle.checked = allChecked;
        }
    }

    document.querySelectorAll('.switch-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            let isActive = this.getAttribute('aria-checked') === 'true';
            let newState = !isActive;
            let field = this.dataset.field;

            this.setAttribute('aria-checked', newState ? 'true' : 'false');
            this.classList.toggle('active', newState);

            updateBulkHeaderState(field);

            fetch('/users/notification-settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    user_id: this.dataset.user,
                    action: this.dataset.action,
                    field: field,
                    value: newState ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Alert.success(data.message || 'Notification settings updated successfully');
                } else {
                    Alert.error(data.message || 'Something went wrong');
                }
            })
            .catch(error => {
                console.error(error);
                Alert.error('Server error occurred');
            });
        });
    });

    bulkToggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            let field = this.dataset.field;
            let userId = this.dataset.user;
            let newState = this.checked;

            const fieldBtns = document.querySelectorAll(`.switch-btn[data-field="${field}"]`);
            fieldBtns.forEach(btn => {
                btn.setAttribute('aria-checked', newState ? 'true' : 'false');
                btn.classList.toggle('active', newState);
            });

            fetch('/users/notification-settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    user_id: userId,
                    action: 'all',
                    field: field,
                    value: newState ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Alert.success(data.message || 'Notification settings updated successfully');
                } else {
                    Alert.error(data.message || 'Something went wrong');
                }
            })
            .catch(error => {
                console.error(error);
                Alert.error('Server error occurred');
            });
        });
    });
});