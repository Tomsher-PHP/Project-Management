import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

/**
 * Standardize PMS Calendar styling & design system matching Attendance Calendar layout
 */
function injectCalendarStyles() {
    if (document.getElementById('pms-calendar-styles')) return;

    const style = document.createElement('style');
    style.id = 'pms-calendar-styles';
    style.textContent = `
        /* FullCalendar Base & Layout Container */
        .fc {
            --fc-border-color: #e5e7eb;
            --fc-page-bg-color: transparent;
            --fc-neutral-bg-color: #f9fafb;
            --fc-today-bg-color: rgba(34, 197, 94, 0.08);
            font-family: inherit;
        }
        .dark .fc {
            --fc-border-color: #374151;
            --fc-neutral-bg-color: #1f2937;
            --fc-today-bg-color: rgba(34, 197, 94, 0.12);
        }

        /* Toolbar Header & Buttons */
        .fc .fc-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .fc .fc-toolbar-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
        }
        .dark .fc .fc-toolbar-title {
            color: #ffffff;
        }
        .fc .fc-button-primary {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            color: #374151;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: none;
            transition: all 0.15s ease-in-out;
        }
        .dark .fc .fc-button-primary {
            background-color: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }
        .fc .fc-button-primary:hover:not(:disabled) {
            background-color: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }
        .dark .fc .fc-button-primary:hover:not(:disabled) {
            background-color: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }
        .fc .fc-button-primary:focus,
        .fc .fc-button-primary:active {
            box-shadow: none !important;
            outline: none !important;
        }
        .fc .fc-button-primary.fc-button-active {
            background-color: #111827;
            border-color: #111827;
            color: #ffffff;
        }
        .dark .fc .fc-button-primary.fc-button-active {
            background-color: #374151;
            border-color: #4b5563;
            color: #ffffff;
        }

        /* Column Headers (Monday..Sunday) */
        .fc .fc-col-header-cell {
            background-color: #f9fafb;
            padding: 0.625rem 0.5rem;
            border-color: #e5e7eb;
        }
        .dark .fc .fc-col-header-cell {
            background-color: #1f2937;
            border-color: #374151;
        }
        .fc .fc-col-header-cell-cushion {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #6b7280;
            text-decoration: none !important;
        }
        .dark .fc .fc-col-header-cell-cushion {
            color: #9ca3af;
        }

        /* Day Grid Cells & Out-of-Month Days */
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #e5e7eb;
        }
        .dark .fc-theme-standard td, .dark .fc-theme-standard th {
            border-color: #374151;
        }
        .fc .fc-daygrid-day {
            min-height: 120px;
            transition: background-color 0.15s ease;
        }
        .fc .fc-daygrid-day:hover {
            background-color: rgba(249, 250, 251, 0.6);
        }
        .dark .fc .fc-daygrid-day:hover {
            background-color: rgba(31, 41, 55, 0.4);
        }
        .fc .fc-day-other {
            background-color: rgba(249, 250, 251, 0.7);
        }
        .dark .fc .fc-day-other {
            background-color: rgba(31, 41, 55, 0.5);
        }

        /* Today Date Circle Accent */
        .fc .fc-daygrid-day-number {
            font-size: 0.75rem;
            font-weight: 600;
            color: #374151;
            padding: 0.375rem 0.5rem;
            text-decoration: none !important;
        }
        .dark .fc .fc-daygrid-day-number {
            color: #d1d5db;
        }
        .fc .fc-day-today .fc-daygrid-day-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 9999px;
            background-color: #22c55e;
            color: #ffffff !important;
            font-weight: 700;
            margin: 0.25rem;
        }

        /* Events & Badges */
        .fc-daygrid-event {
            border-radius: 0.375rem;
            margin-top: 2px;
            margin-bottom: 2px;
            padding: 2px 4px;
            font-size: 0.75rem;
            font-weight: 500;
            border: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .fc-event-day-off,
        .fc-daygrid-event.fc-event-day-off {
            background-color: #fef3c7 !important;
            border: 1px dashed #d97706 !important;
            color: #b45309 !important;
            font-weight: 600 !important;
        }
        .dark .fc-event-day-off,
        .dark .fc-daygrid-event.fc-event-day-off {
            background-color: #451a03 !important;
            border: 1px dashed #b45309 !important;
            color: #fde68a !important;
        }

        /* More Link (+ X More) */
        .fc .fc-daygrid-more-link {
            font-size: 0.75rem;
            font-weight: 600;
            color: #16a34a;
            text-decoration: none !important;
            padding: 2px 4px;
        }
        .dark .fc .fc-daygrid-more-link {
            color: #4ade80;
        }
        .fc .fc-more-popover {
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .dark .fc .fc-more-popover {
            background-color: #1f2937;
            border-color: #374151;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Single Reusable Calendar Abstraction Wrapper
 * Standardized with Attendance Calendar layout & presentation rules.
 */
window.initCalendar = function (calendarEl, options = {}) {
    if (!calendarEl) return null;

    injectCalendarStyles();

    const defaultOptions = {
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin
        ],
        initialView: 'dayGridMonth',
        firstDay: 1, // Monday start (matching Attendance Calendar)
        dayMaxEvents: 3, // Show max 3 events per day before "+ X more"
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: false,
        selectable: false,
        events: [],

        // Standardized Attendance-style Event Renderer
        eventContent: function (arg) {
            const title = arg.event.title;
            const color = arg.event.backgroundColor || arg.event.borderColor || '#3B82F6';
            const isDayOff = arg.event.classNames.includes('fc-event-day-off');

            const container = document.createElement('div');
            container.className = 'flex items-center gap-1.5 overflow-hidden w-full text-xs font-medium px-1.5 py-0.5 rounded';

            if (!isDayOff && color && color !== 'transparent') {
                const dot = document.createElement('span');
                dot.className = 'h-2 w-2 rounded-full flex-shrink-0';
                dot.style.backgroundColor = color;
                container.appendChild(dot);
            }

            const titleEl = document.createElement('span');
            titleEl.className = 'truncate text-xs font-medium text-bgray-900 dark:text-white';
            titleEl.textContent = title;
            container.appendChild(titleEl);

            return { domNodes: [container] };
        }
    };

    // Merge options allowing callers to customize or override
    const mergedOptions = {
        ...defaultOptions,
        ...options
    };

    const calendar = new Calendar(calendarEl, mergedOptions);
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