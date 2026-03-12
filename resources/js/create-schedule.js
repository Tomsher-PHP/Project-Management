const form = document.getElementById("schedule-form");

form.addEventListener("submit", function (e) {
    e.preventDefault();

    previewSchedule().then((hasConflicts) => {

        if (!hasConflicts) {
            form.submit();
            return;
        }

        openPreviewModal();
    });
});

function previewSchedule() {

    const users = [...document.querySelector("#user-select").selectedOptions]
        .map(o => o.value)
        .filter(v => v);

    const shiftId = document.getElementById("shift_id").value;
    const dateFrom = document.getElementById("date_from").value;
    const dateTo = document.getElementById("date_to").value;

    if (!users.length || !dateFrom) {
        return Promise.resolve(false);
    }

    return fetch("/schedule-shift/preview", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            users: users,
            shift_id: shiftId,
            date_from: dateFrom,
            date_to: dateTo
        })
    })
        .then(res => res.json())
        .then(data => {

            const content = document.getElementById("preview-modal-content");
            content.innerHTML = data.html;

            return content.innerHTML.trim() !== "";
        });
}

function openPreviewModal() {
    document.getElementById("preview-modal").classList.remove("hidden");
}

function closePreviewModal() {
    document.getElementById("preview-modal").classList.add("hidden");
}

document.getElementById("continue-schedule").addEventListener("click", function () {
    const removedUsers = [...document.querySelectorAll(".remove-user-checkbox:checked")]
        .map(el => el.value);

    if (removedUsers.length) {
        const select = document.getElementById("user-select");

        removedUsers.forEach(id => {
            const option = select.querySelector(`option[value="${id}"]`);
            if (option) option.selected = false;
        });
    }

    closePreviewModal();
    form.submit();
});

//make global accessible
window.openPreviewModal = openPreviewModal;
window.closePreviewModal = closePreviewModal;

document.addEventListener('DOMContentLoaded', function () {
    const selectedUsers = JSON.parse(sessionStorage.getItem('preSelectedUsers') || '[]');

    const select = document.getElementById('user-select');

    // Make sure TomSelect is initialized first
    if (select.tomselect) {
        selectedUsers.forEach(val => {
            select.tomselect.addItem(val);
        });
    }

    // optionally clear sessionStorage
    sessionStorage.removeItem('preSelectedUsers');
});