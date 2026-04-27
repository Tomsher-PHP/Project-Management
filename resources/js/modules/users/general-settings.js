document.querySelectorAll('.general-setting').forEach(input => {
    input.addEventListener('change', function () {
        fetch('/users/general-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                user_id: this.dataset.user,
                field: this.dataset.field,
                value: this.value
            })
        })
        .then(res => res.json())
        .then(data => {

            if (data.success) {
                Alert.success(data.message || 'Settings updated');
            } else {
                Alert.error('Something went wrong');
            }

        })
        .catch(() => {
            Alert.error('Server error occurred');
        });

    });

});

