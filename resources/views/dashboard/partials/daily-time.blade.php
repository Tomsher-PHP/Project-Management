<!-- Daily Time Card -->
<div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600" data-worked-time-section data-worked-time-url="{{ route('dashboard.worked-time') }}">
    <!-- Card Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-bgray-100 pb-4 dark:border-darkblack-500">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Daily Time</h3>
            <a href="{{ route('reports.daily_time', ['from_date' => today()->toDateString(), 'to_date' => today()->toDateString()]) }}" id="view-all-daily-time" data-base-url="{{ route('reports.daily_time') }}" class="text-sm font-semibold text-success-300 hover:text-success-400 hover:underline transition-colors">
                View All
            </a>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-2">
            <div class="flex rounded-lg border border-bgray-300 bg-white p-0.5 dark:border-darkblack-400 dark:bg-darkblack-500" data-worked-time-filter-group>
                <style>
                    [data-worked-time-filter].active {
                        background-color: rgb(34 197 94 / 0.1) !important;
                        color: rgb(22 163 74) !important;
                    }

                    .dark [data-worked-time-filter].active {
                        background-color: rgb(34 197 94 / 0.2) !important;
                        color: rgb(74 222 128) !important;
                    }
                </style>
                <button type="button" class="active rounded-md px-3 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-worked-time-filter="today" aria-pressed="true">Today</button>
                <button type="button" class="rounded-md px-3 py-1 text-[11px] font-bold transition-all text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-worked-time-filter="yesterday" aria-pressed="false">Yesterday</button>
            </div>

            <!-- Custom Datepicker Input (Always visible) -->
            <div class="relative" id="custom-datepicker-container">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-bgray-500 dark:text-bgray-400" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <input type="text" id="worked-time-datepicker" class="rounded-lg border border-bgray-150 pl-9 pr-3 py-1.5 text-xs font-bold text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white w-32" placeholder="Select Date" data-format="Y-m-d" value="{{ today()->toDateString() }}">
            </div>
        </div>
    </div>

    <!-- Worked Time Table -->
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">User</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Date</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Start</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">End</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Worked Hours</th>
                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Shift Hours</th>
                </tr>
            </thead>
            <tbody id="worked-time-table-body" class="divide-y divide-bgray-100 dark:divide-darkblack-500">
                @forelse($workedTimeData as $row)
                    <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                        <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                            <div class="flex items-center gap-2">
                                <x-user-avatar :user="$row['user']" size="sm" />
                                <span>{{ $row['user_name'] }}</span>
                            </div>
                        </td>
                        <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">{{ $row['date'] }}</td>
                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $row['start_time'] }}</td>
                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">
                            @if ($row['end_time'] === 'Running')
                                <span class="text-success-300 font-semibold">Running</span>
                            @else
                                {{ $row['end_time'] }}
                            @endif
                        </td>
                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $row['total_worked_time'] }}</td>
                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">
                            @if ($row['shift_working_hour'] === 'Day Off')
                                <span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>
                            @else
                                {{ $row['shift_working_hour'] }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for today.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
