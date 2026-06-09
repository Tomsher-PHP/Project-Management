<div id="shiftCalendarTab" class="tab-pane">
    <h3 class="mb-5 mt-10 text-3xl font-bold text-bgray-900 dark:text-white">
        Shift Calendar
    </h3>

    <!-- Calendar Container -->
    <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-darkblack-600 border border-gray-100 dark:border-darkblack-400">
        <!-- Spinner / Loading State -->
        <div id="shift-calendar-loading" class="flex items-center justify-center py-20">
            <svg class="animate-spin h-10 w-10 text-success-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- The actual calendar el -->
        <div id="user-shift-calendar" data-url="{{ route('users.shift-calendar', $user->id) }}" class="hidden"></div>
    </div>
</div>

@push('styles')
    <style>
        .fc-event.fc-event-day-off,
        .fc-daygrid-event.fc-event-day-off {
            background-color: #fef3c7 !important;
            border: 1px dashed #d97706 !important;
            border-radius: 4px !important;
            opacity: 0.6;
        }

        .fc-event.fc-event-day-off,
        .fc-event.fc-event-day-off .fc-event-title,
        .fc-event.fc-event-day-off .fc-event-main,
        .fc-daygrid-event.fc-event-day-off,
        .fc-daygrid-event.fc-event-day-off .fc-event-title,
        .fc-daygrid-event.fc-event-day-off .fc-event-main {
            color: #b45309 !important;
            font-weight: 600 !important;
        }

        .dark .fc-event.fc-event-day-off,
        .dark .fc-daygrid-event.fc-event-day-off {
            background-color: #451a03 !important;
            border: 1px dashed #b45309 !important;
        }

        .dark .fc-event.fc-event-day-off,
        .dark .fc-event.fc-event-day-off .fc-event-title,
        .dark .fc-event.fc-event-day-off .fc-event-main,
        .dark .fc-daygrid-event.fc-event-day-off,
        .dark .fc-daygrid-event.fc-event-day-off .fc-event-title,
        .dark .fc-daygrid-event.fc-event-day-off .fc-event-main {
            color: #fef3c7 !important;
        }
    </style>
@endpush
