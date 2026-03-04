$(document).ready(function () {

    let members = [];

    $('#add-member-btn').click(function () {

        let userId = $('#team_member').val();
        let userName = $('#team_member option:selected').text();
        let teamRole = $('#team_role').val();
        let teamRoleText = $('#team_role option:selected').text();

        if (!userId || !teamRole) {
            Alert.error('Please select both user and role.');
            return;
        }

        // Prevent duplicate users
        if (members.some(member => member.user_id == userId)) {
            Alert.error('User already added.');
            return;
        }

        // Prevent duplicate owner
        if (teamRole === 'owner' && members.some(member => member.team_role === 'owner')) {
            Alert.error('An owner already exists for this team.');
            return;
        }

        let member = {
            user_id: userId,
            team_role: teamRole
        };

        members.push(member);

        // Remove selected option from dropdown
        $('#team_member option[value="' + userId + '"]').remove();

        // Clone template
        let template = document.querySelector('#member-row-template');
        let clone = template.content.cloneNode(true);

        // Fill values
        clone.querySelector('.member-name').textContent = userName;
        clone.querySelector('.member-role').textContent = teamRoleText;

        clone.querySelector('.input-user-id').name = `members[${userId}][user_id]`;
        clone.querySelector('.input-user-id').value = userId;

        clone.querySelector('.input-team-role').name = `members[${userId}][team_role]`;
        clone.querySelector('.input-team-role').value = teamRole;

        $('#members-table').find('#empty-row').hide();
        $('#members-table').append(clone);

        $('#team_member').val('');
        $('#team_role').val('');
    });

    // Remove Member
    $(document).on('click', '.remove-member', function () {

        let row = $(this).closest('tr');
        let id = row.data('id');
        let name = row.find('.member-name').text();

        members = members.filter(member => member.user_id != id);

        // Add back to dropdown
        $('#team_member').append(
            `<option value="${id}">${name}</option>`
        );

        row.remove();

        if ($('#members-table').find('.team-member-row').length === 0) {
            $('#members-table').find('#empty-row').show();
        }
    });

});