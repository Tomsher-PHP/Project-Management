document.querySelectorAll('.general-setting').forEach(input => {
    input.addEventListener('change', function () {

        const value = this.value;
        const field = this.dataset.field;
        const authUser = this.dataset.loginUser;
        const userId = this.dataset.user;

        fetch('/users/general-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                user_id: userId,
                field: field,
                value: value
            })
        })
        .then(res => res.json())
        .then(data => {

            if (data.success) {

                Alert.success(data.message || 'Settings updated');

                // update local storage
                if (field === 'theme' && String(userId) === String(authUser)) {
                    localStorage.setItem('theme', value);
                     document.documentElement.classList.toggle('dark', value === 'dark');

                        // keep radio in sync
                        updateThemeInputs(value);

                    // if (value === 'dark') {
                    //     document.documentElement.classList.add('dark');
                    // } else {
                    //     document.documentElement.classList.remove('dark');
                    // }
                }

            } else {
                Alert.error('Something went wrong');
            }

        })
        .catch(() => {
            Alert.error('Server error occurred');
        });

    });
});

function updateThemeInputs(theme) {
    document.querySelectorAll('.general-setting[data-field="theme"]').forEach(input => {
        input.checked = input.value === theme;
    });
}