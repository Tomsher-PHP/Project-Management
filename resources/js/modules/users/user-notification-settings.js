document.querySelectorAll('.switch-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        let isActive = this.getAttribute('aria-checked') === 'true';
        let newState = !isActive;

        this.setAttribute('aria-checked', newState);
        this.classList.toggle('bg-green-500');

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
                field: this.dataset.field,
                value: newState ? 1 : 0
            })
        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {
                Alert.success(data.message || 'Notification settings updated successfully');
            } else {
                Alert.error('Something went wrong');
            }
        })
        .catch(error => {
            console.error(error);
            Alert.error('Server error occurred');
        });

    });
});