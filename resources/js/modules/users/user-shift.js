document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('user-shift-calendar');
    if (!calendarEl) {
        return;
    }

    const url = calendarEl.dataset.url;
    const tabBtn = document.querySelector('[data-tab="shiftCalendarTab"]');
    const loadingEl = document.getElementById('shift-calendar-loading');
    
    let isInitialized = false;
    let calendarInstance = null;
    let abortController = null;

    function fetchEventsForMonth(date) {
        const year = date.getFullYear();
        const month = date.getMonth() + 1; // 0-indexed to 1-indexed

        // Abort any pending requests
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        // Show loading state
        if (loadingEl) {
            loadingEl.classList.remove('hidden');
        }
        calendarEl.classList.add('hidden');

        const fetchUrl = `${url}?year=${year}&month=${month}`;

        fetch(fetchUrl, {
            signal: abortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(events => {
            // Hide loading state
            if (loadingEl) {
                loadingEl.classList.add('hidden');
            }
            calendarEl.classList.remove('hidden');

            if (calendarInstance) {
                calendarInstance.removeAllEvents();
                if (events && events.length > 0) {
                    calendarInstance.addEventSource(events);
                }
            }
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                return; // Suppress abort error
            }
            console.error('Error fetching shift assignments:', error);
            
            // Hide loading state and show calendar on error
            if (loadingEl) {
                loadingEl.classList.add('hidden');
            }
            calendarEl.classList.remove('hidden');
        });
    }

    // Helper to initialize calendar when tab becomes active
    function checkAndInit() {
        if (isInitialized) return;
        
        // Check if tab is currently active
        const isActive = tabBtn && (tabBtn.classList.contains('active') || localStorage.getItem('activeTab') === 'shiftCalendarTab');
        if (!isActive) return;

        isInitialized = true;

        if (window.initCalendar) {
            calendarInstance = window.initCalendar(calendarEl, {
                events: [],
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                editable: false,
                selectable: false,
                datesSet: function (dateInfo) {
                    fetchEventsForMonth(dateInfo.view.currentStart);
                }
            });
        } else {
            console.error("initCalendar function not found on window object.");
        }
    }

    // 1. Listen for click on the Shift Calendar tab button
    tabBtn?.addEventListener('click', () => {
        setTimeout(checkAndInit, 50);
    });

    // 2. Also check on DOMContentLoaded in case active tab is stored
    setTimeout(checkAndInit, 100);
});
