$(document).ready(function () {
    const members = [];
    const teamSelect = document.getElementById('team_member').tomselect;
    const roleSelect = document.getElementById('team_role').tomselect;
    const membersTable = $('#members-table');
    const emptyRow = membersTable.find('#empty-row');
    const template = document.querySelector('#member-row-template');

    const clearSelect = (ts) => ts?.clear();
    const showError = (msg) => Alert.error(msg);

    const addMemberRow = (userId, userName, teamRole, teamRoleText) => {
        const clone = template.content.cloneNode(true);
        clone.querySelector('.member-name').textContent = userName;
        clone.querySelector('.member-role').textContent = teamRoleText;

        clone.querySelector('.input-user-id').name = `members[${userId}][user_id]`;
        clone.querySelector('.input-user-id').value = userId;

        clone.querySelector('.input-team-role').name = `members[${userId}][team_role]`;
        clone.querySelector('.input-team-role').value = teamRole;

        emptyRow.hide();
        membersTable.append(clone);
    };

    $('#add-member-btn').click(() => {
        const userId = teamSelect.getValue();
        if (!userId) return showError('Please select a user.');

        const option = teamSelect.options[userId]; // get original option
        const userName = option.text;
        const userType = option.subtype; // store subtype
        const teamRole = roleSelect.getValue();
        const teamRoleText = roleSelect.getItem(teamRole)?.textContent || '';

        if (!teamRole) return showError('Please select a role.');
        if (members.some(m => m.user_id == userId)) return showError('User already added.');
        if (teamRole === 'owner' && members.some(m => m.team_role === 'owner')) return showError('An owner already exists.');

        members.push({ user_id: userId, team_role: teamRole, subtype: userType });

        // Remove option and clear selects
        teamSelect.removeOption(userId);
        clearSelect(teamSelect);
        clearSelect(roleSelect);

        addMemberRow(userId, userName, teamRole, teamRoleText);
    });

    $(document).on('click', '.remove-member', function () {
        const row = $(this).closest('tr');
        const id = row.find('.input-user-id').val();
        const name = row.find('.member-name').text();
        const member = members.find(m => m.user_id == id);

        // Remove from members array
        const index = members.findIndex(m => m.user_id == id);
        if (index > -1) members.splice(index, 1);

        // Add option back with subtype
        if (!teamSelect.options[id] && member) {
            teamSelect.addOption({ value: id, text: name, subtype: member.subtype });
            teamSelect.refreshOptions(false);
        }

        row.remove();
        if (!membersTable.find('.team-member-row').length) emptyRow.show();
    });
});