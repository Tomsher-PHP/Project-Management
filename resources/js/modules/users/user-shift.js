document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('user-shift-calendar');
    if (!calendarEl) {
        return;
    }

    const url = calendarEl.dataset.url;
    const tabBtn = document.querySelector('[data-tab="shiftCalendarTab"]');
    const loadingEl = document.getElementById('shift-calendar-loading');
    
    let isInitialized = false;

    // Helper to initialize calendar when tab becomes active (either on click, or on load if active)
    function checkAndInit() {
        if (isInitialized) return;
        
        // Check if tab is currently active
        const isActive = tabBtn && (tabBtn.classList.contains('active') || localStorage.getItem('activeTab') === 'shiftCalendarTab');
        if (!isActive) return;

        isInitialized = true;
        
        // Show loading state
        if (loadingEl) loadingEl.classList.remove('hidden');
        calendarEl.classList.add('hidden');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(events => {
            // Hide loading state
            if (loadingEl) loadingEl.classList.add('hidden');
            calendarEl.classList.remove('hidden');

            // Render calendar with events
            if (window.initCalendar) {
                window.initCalendar(calendarEl, {
                    events: events,
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth'
                    },
                    editable: false,
                    selectable: false
                });
            } else {
                console.error("initCalendar function not found on window object.");
            }
        })
        .catch(error => {
            console.error('Error fetching shift assignments:', error);
            if (loadingEl) loadingEl.classList.add('hidden');
            calendarEl.classList.remove('hidden');
        });
    }

    // 1. Listen for click on the Shift Calendar tab button
    tabBtn?.addEventListener('click', () => {
        // We use setTimeout to ensure active tab classes are updated first by main.js
        setTimeout(checkAndInit, 50);
    });

    // 2. Also check on DOMContentLoaded in case the active tab stored in localStorage is shiftCalendarTab
    setTimeout(checkAndInit, 100);
});
