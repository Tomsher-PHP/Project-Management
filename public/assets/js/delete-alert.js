$(document).on('submit', '.delete-form', function (e) {

    e.preventDefault();
    let form = this;

    Alert.confirm({
        title: 'Delete Record?',
        // text: 'This action cannot be undone.',
        confirmText: 'Yes, delete it'
    }).then(result => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});