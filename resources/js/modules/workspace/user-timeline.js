import { initDatepicker } from '../../components/datepicker';

let activeTimelineRequest = null;

const ROOT_SELECTOR = '[data-user-timeline-root]';
const PICKER_SELECTOR = '[data-user-timeline-picker]';
const PICKER_BUTTON_SELECTOR = '[data-user-timeline-picker-button]';

const padNumber = (value) => String(value).padStart(2, '0');

const formatDate = (date) => `${date.getFullYear()}-${padNumber(date.getMonth() + 1)}-${padNumber(date.getDate())}`;

const parseDate = (value) => {
    if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const offsetDate = (value, days) => {
    const baseDate = parseDate(value);
    if (!baseDate) {
        return value;
    }

    baseDate.setDate(baseDate.getDate() + days);
    return formatDate(baseDate);
};

const getControls = (root) => root.querySelectorAll('button, input');

const setLoadingState = (root, isLoading) => {
    root.dataset.timelineLoading = isLoading ? 'true' : 'false';
    root.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    root.classList.toggle('opacity-70', isLoading);
    root.classList.toggle('pointer-events-none', isLoading);

    getControls(root).forEach((control) => {
        control.disabled = isLoading;
    });
};

const showError = (root, message) => {
    const errorNode = root.querySelector('[data-user-timeline-error]');
    if (!errorNode) {
        return;
    }

    if (!message) {
        errorNode.textContent = '';
        errorNode.classList.add('hidden');
        return;
    }

    errorNode.textContent = message;
    errorNode.classList.remove('hidden');
};

const updateBrowserUrl = (date) => {
    const url = new URL(window.location.href);
    url.searchParams.set('date', date);
    window.history.replaceState({}, '', url);
};

const buildTimelineUrl = (root, date) => {
    const url = new URL(root.dataset.userTimelineUrl || window.location.pathname, window.location.origin);
    const params = new URLSearchParams(window.location.search);
    params.set('date', date);
    url.search = params.toString();

    return url;
};

const replaceTimelineRoot = (currentRoot, html) => {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    const nextRoot = template.content.firstElementChild;
    if (!nextRoot) {
        throw new Error('Timeline response did not contain any markup.');
    }

    currentRoot.replaceWith(nextRoot);
    bindTimeline(nextRoot);
};

const requestTimeline = async (root, date) => {
    const normalizedDate = formatDate(parseDate(date) || new Date());

    if (activeTimelineRequest) {
        activeTimelineRequest.abort();
    }

    activeTimelineRequest = new AbortController();
    setLoadingState(root, true);
    showError(root, '');

    try {
        const response = await fetch(buildTimelineUrl(root, normalizedDate), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            signal: activeTimelineRequest.signal,
        });

        if (!response.ok) {
            throw new Error('Failed to load the selected day.');
        }

        const data = await response.json();
        replaceTimelineRoot(root, data.html || '');
        updateBrowserUrl(normalizedDate);
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        showError(root, 'Could not update the daily timeline. Please try again.');
        console.error(error);
        setLoadingState(root, false);
    } finally {
        activeTimelineRequest = null;
    }
};

const bindPicker = (root) => {
    const picker = root.querySelector(PICKER_SELECTOR);
    if (!picker) {
        return;
    }

    const handlePickerChange = (_selectedDates, dateStr) => {
        if (dateStr) {
            requestTimeline(root, dateStr);
        }
    };

    if (picker._flatpickr) {
        picker._flatpickr.config.onChange.push(handlePickerChange);
    } else {
        initDatepicker(PICKER_SELECTOR, {
            onChange: handlePickerChange,
        }, root);
    }

    root.querySelector(PICKER_BUTTON_SELECTOR)?.addEventListener('click', () => {
        picker._flatpickr?.open();
    });
};

function bindTimeline(root) {
    if (!root) {
        return;
    }

    bindPicker(root);

    root.querySelector('[data-user-timeline-prev]')?.addEventListener('click', () => {
        requestTimeline(root, offsetDate(root.dataset.userTimelineSelectedDate, -1));
    });

    root.querySelector('[data-user-timeline-next]')?.addEventListener('click', () => {
        requestTimeline(root, offsetDate(root.dataset.userTimelineSelectedDate, 1));
    });

    root.querySelector('[data-user-timeline-today]')?.addEventListener('click', () => {
        requestTimeline(root, root.dataset.userTimelineToday);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindTimeline(document.querySelector(ROOT_SELECTOR));
});
