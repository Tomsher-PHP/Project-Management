import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

window.initCalendar = function (calendarEl, options = {}) {
    if (!calendarEl) return null;

    const defaultOptions = {
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin
        ],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        selectable: true,
        events: []
    };

    const calendar = new Calendar(calendarEl, {
        ...defaultOptions,
        ...options
    });

    calendar.render();
    return calendar;
};

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    if (!calendarEl) {
        return;
    }

    window.initCalendar(calendarEl, {
        events: [
            {
                title: 'Sample Task',
                start: '2026-06-08'
            }
        ]
    });
});