function addShift() {
    const wrapper = document.getElementById('shifts-wrapper');
    const blockCard = document.getElementById('add-shift-card');
    const html = blockCard.innerHTML;

    wrapper.insertAdjacentHTML('beforeend', html);
}

document.addEventListener('click', function (e) {

    if (e.target.classList.contains('remove-shift')) {

        const shiftItem = e.target.closest('.shift-item');

        if (shiftItem) {
            shiftItem.remove();
        }
    }

});