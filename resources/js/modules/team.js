$(document).ready(function () {
    const members = [];
    const teamSelect = document.getElementById('team_member').tomselect;
    const roleSelect = document.getElementById('team_role').tomselect;
    const membersTable = $('#members-table');
    const emptyRow = membersTable.find('#empty-row');
    const template = document.querySelector('#member-row-template');

    const clearSelect = (ts) => ts?.clear();
    const showError = (msg) => Alert.error(msg);

    const applyRoleBadge = (badge, teamRole, teamRoleText) => {
        badge.textContent = teamRoleText;
        badge.className = 'member-role-badge absolute right-4 top-4 inline-block rounded-full px-3 py-1 text-xs';

        if (teamRole === 'team_leader') {
            badge.classList.add('bg-purple-100', 'text-purple-600');
            return;
        }

        badge.classList.add('bg-gray-200', 'text-gray-600');
    };

    const addMemberCard = (userId, userName, userEmail, userAvatar, teamRole, teamRoleText) => {
        const clone = template.content.cloneNode(true);
        clone.querySelector('.member-name').textContent = userName;
        clone.querySelector('.member-email').textContent = userEmail || '--';
        clone.querySelector('.member-avatar').src = userAvatar || clone.querySelector('.member-avatar').src;
        clone.querySelector('.member-avatar').alt = userName;
        applyRoleBadge(clone.querySelector('.member-role-badge'), teamRole, teamRoleText);

        clone.querySelector('.input-user-id').name = `members[${userId}][user_id]`;
        clone.querySelector('.input-user-id').value = userId;

        clone.querySelector('.input-team-role').name = `members[${userId}][team_role]`;
        clone.querySelector('.input-team-role').value = teamRole;

        emptyRow.hide();
        membersTable.append(clone);
    };

    membersTable.find('.team-member-card').each(function () {
        const row = $(this);
        const userId = String(row.find('.input-user-id').val() || '');
        const userName = row.find('.member-name').text().trim();
        const userEmail = row.find('.member-email').text().trim();
        const userAvatar = row.find('.member-avatar').attr('src') || '';
        const teamRole = String(row.find('.input-team-role').val() || '');

        if (!userId) {
            return;
        }

        members.push({ user_id: userId, team_role: teamRole, text: userName, email: userEmail, avatar: userAvatar });
    });

    const getDefaultRole = () => members.some((member) => member.team_role === 'team_leader')
        ? 'member'
        : 'team_leader';

    const handleRoleChange = (role) => {
        if (role === 'team_leader') {
            teamSelect.setMaxItems(1);

            const currentValue = teamSelect.getValue();
            if (Array.isArray(currentValue) && currentValue.length > 1) {
                teamSelect.setValue([currentValue[0]]);
            }

            return;
        }

        teamSelect.setMaxItems(null);
    };

    roleSelect.on('change', handleRoleChange);
    roleSelect.setValue(getDefaultRole(), true);
    handleRoleChange(roleSelect.getValue());

    $('#add-member-btn').click(() => {
        const teamRole = roleSelect.getValue();
        if (!teamRole) return showError('Please select a role.');

        const selectedUserIds = []
            .concat(teamSelect.getValue() || [])
            .map((value) => String(value))
            .filter(Boolean);

        if (!selectedUserIds.length) return showError('Please select a user.');
        if (teamRole === 'team_leader' && selectedUserIds.length > 1) return showError('Only one member can be selected as team leader.');
        if (teamRole === 'team_leader' && members.some(m => m.team_role === 'team_leader')) return showError('A team leader already exists.');

        const duplicateUser = selectedUserIds.find((userId) => members.some(m => m.user_id == userId));
        if (duplicateUser) return showError('User already added.');

        const teamRoleText = roleSelect.getItem(teamRole)?.textContent || '';

        selectedUserIds.forEach((userId) => {
            const option = teamSelect.options[userId];
            if (!option) {
                return;
            }

            const userName = option.text;
            const userEmail = option.email || '';
            const userAvatar = option.profile_image_url || '';

            members.push({ user_id: userId, team_role: teamRole, text: userName, email: userEmail, avatar: userAvatar });
            teamSelect.removeOption(userId);
            addMemberCard(userId, userName, userEmail, userAvatar, teamRole, teamRoleText);
        });

        clearSelect(teamSelect);
        const nextRole = getDefaultRole();
        roleSelect.setValue(nextRole, true);
        handleRoleChange(nextRole);
        teamSelect.refreshOptions(false);
    });

    $(document).on('click', '.remove-member', function () {
        const row = $(this).closest('.team-member-card');
        const id = String(row.find('.input-user-id').val() || '');
        const name = row.find('.member-name').text();
        const member = members.find(m => m.user_id == id);

        // Remove from members array
        const index = members.findIndex(m => m.user_id == id);
        if (index > -1) members.splice(index, 1);

        // Add option back
        if (!teamSelect.options[id] && member) {
            teamSelect.addOption({
                value: id,
                text: name,
                email: member.email || '',
                profile_image_url: member.avatar || '',
            });
            teamSelect.refreshOptions(false);
        }

        row.remove();
        if (!membersTable.find('.team-member-card').length) emptyRow.show();

        const nextRole = getDefaultRole();
        roleSelect.setValue(nextRole, true);
        handleRoleChange(nextRole);
    });
});
