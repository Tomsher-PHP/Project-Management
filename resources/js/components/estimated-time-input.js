function initializeEstimatedTimeInput(wrapper) {
    if (!wrapper || wrapper.dataset.estimatedTimeInitialized === 'true') {
        return;
    }

    const totalMinutesInput = wrapper.querySelector('[data-estimated-total-minutes]');
    const hoursInput = wrapper.querySelector('[data-estimated-hours]');
    const minutesInput = wrapper.querySelector('[data-estimated-extra-minutes]');

    if (!totalMinutesInput || !hoursInput || !minutesInput) {
        return;
    }

    const syncFromTotalMinutes = () => {
        const totalMinutes = Math.max(0, Number.parseInt(totalMinutesInput.value || '0', 10) || 0);
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        hoursInput.value = String(hours);
        minutesInput.value = String(minutes);
    };

    const syncToTotalMinutes = () => {
        let hours = Math.max(0, Number.parseInt(hoursInput.value || '0', 10) || 0);
        let minutes = Math.max(0, Number.parseInt(minutesInput.value || '0', 10) || 0);

        if (minutes >= 60) {
            hours += Math.floor(minutes / 60);
            minutes = minutes % 60;
        }

        hoursInput.value = String(hours);
        minutesInput.value = String(minutes);
        totalMinutesInput.value = String((hours * 60) + minutes);
        totalMinutesInput.dispatchEvent(new Event('input', { bubbles: true }));
        totalMinutesInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    hoursInput.addEventListener('input', syncToTotalMinutes);
    minutesInput.addEventListener('input', syncToTotalMinutes);
    wrapper.addEventListener('estimated-time:refresh', syncFromTotalMinutes);

    wrapper.dataset.estimatedTimeInitialized = 'true';
    syncFromTotalMinutes();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-estimated-time]').forEach(initializeEstimatedTimeInput);
});

export { initializeEstimatedTimeInput };
