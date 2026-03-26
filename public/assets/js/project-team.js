document.addEventListener('DOMContentLoaded', function () {

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectId = window.ProjectApp.id;

    const form = document.getElementById('project-team-form');
    const button = document.getElementById('add-member-btn');

    const membersContainer = document.getElementById('members-container');

    let loading = false;

    const showError = (msg) => Alert.error(msg);
    const showSuccess = (msg) => Alert.success(msg);

    /* -------------------------- ADD MEMBER -------------------------- */

    button.addEventListener('click', async function () {
        if (loading) return;

        const formData = new FormData(form);

        try {
            const response = await fetch(`/projects/${projectId}/members`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const res = await response.json();
            if (!response.ok) {
                throw new Error(res.message || 'Something went wrong');
            }
            showSuccess(res.message);
            document.getElementById('empty-row')?.remove();
            membersContainer.insertAdjacentHTML('beforeend', res.member_cards);

            // Reset User Select (MULTIPLE)
            const userSelect = document.getElementById('user_id')?.tomselect;
            userSelect?.clear();

            // Reset Role to "member"
            const roleSelect = document.getElementById('project_role')?.tomselect;
            roleSelect?.setValue('member');

        } catch (error) {
            showError(error.message);
        } finally {
            loading = false;
        }
    });

    /* -------------------------- REMOVE MEMBER -------------------------- */

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.remove-member');
        if (!btn) return; // exit if not clicking a remove button

        const userId = btn.dataset.id;

        // Confirm with user
        const result = await Alert.confirm({
            title: 'Remove Member',
            text: 'Are you sure you want to remove this member?',
            type: 'warning'
        });

        if (!result.isConfirmed) return;

        // Disable button while processing
        btn.disabled = true;
        btn.textContent = 'Removing...';

        try {
            const response = await fetch(`/projects/${projectId}/members/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            });

            const res = await response.json();

            if (!response.ok) {
                throw new Error(res.message || 'Failed to remove member');
            }

            showSuccess(res.message);

            // Remove the card from DOM
            const card = btn.closest('.team-member-card');
            card?.remove();

            // Add back to dropdown (if using TomSelect)
            // const name = card?.querySelector('.member-name')?.textContent || '';
            // if (teamSelect) {
            //     teamSelect.addOption({
            //         value: userId,
            //         text: name
            //     });
            //     teamSelect.refreshOptions(false);
            // }

            // Check if no cards remain
            if (membersContainer && !membersContainer.querySelector('.team-member-card')) {
                emptyRow();
            }

        } catch (error) {
            showError(error.message);
            btn.disabled = false;
            btn.textContent = 'Remove';
        }
    });

    // Show empty row when no members are added
    const emptyRow = () => {
        let emptyRow = document.getElementById('empty-row');
        if (!emptyRow) {
            emptyRow = document.createElement('div');
            emptyRow.id = 'empty-row';
            emptyRow.className = 'col-span-full text-center text-gray-400 py-10';
            emptyRow.textContent = 'No members added yet.';
            membersContainer.appendChild(emptyRow);
        } else {
            emptyRow.style.display = '';
        }
    }

});

// TomSelect role change handler
document.addEventListener('tomselect:ready', () => {

    const roleSelect = document.getElementById('project_role');
    const userSelect = document.getElementById('user_id').tomselect;

    function handleRoleChange(role) {
        if (!userSelect) return;

        if (role === 'team_leader' || role === 'coordinator') {
            userSelect.setMaxItems(1);

            const values = userSelect.getValue();
            if (Array.isArray(values) && values.length > 1) {
                userSelect.setValue([values[0]]);
            }

        } else {
            userSelect.setMaxItems(null);
        }
    }

    roleSelect.addEventListener('change', (e) => {
        handleRoleChange(e.target.value);
    });

    // Initial state
    handleRoleChange(roleSelect.value);
});