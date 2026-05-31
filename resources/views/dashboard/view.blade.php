@extends('layouts.master')
@section('main-class', 'w-full px-6 pb-6 pt-[80px] sm:pt-[70px] xl:px-8 xl:pb-8')

@section('page-content')


    <!-- Main Outer Wrapper: space-y-6 -->
    <div class="space-y-4" data-dashboard-summary-section data-dashboard-summary-url="{{ route('dashboard.summary') }}">

        <!-- 1. PROJECTS OVERVIEW KPI SECTION -->
        @include('dashboard.partials.project-counts')

        <!-- 2. TASKS OVERVIEW KPI SECTION -->
        @include('dashboard.partials.task-counts')

        <!-- Columns container: flex flex-col xl:flex-row gap-6 -->
        <div class="flex flex-col xl:flex-row gap-6">

            <!-- Left/Main content (Charts): flex-1 xl:flex-[3.2] space-y-6 -->
            <div class="flex-1 xl:flex-[3.2] space-y-6">

                <!-- Users Task Worked Time Card -->
                <div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600" data-worked-time-section data-worked-time-url="{{ route('dashboard.worked-time') }}">
                    <!-- Card Header -->
                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                        <div>
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Users Task Worked Time</h3>
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
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">User Name</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Date</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Start</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">End</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Worked Hour</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Shift Hour</th>
                                </tr>
                            </thead>
                            <tbody id="worked-time-table-body" class="divide-y divide-bgray-100 dark:divide-darkblack-500">
                                @forelse($workedTimeData as $row)
                                    <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                        <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">{{ $row['user_name'] }}</td>
                                        <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">{{ $row['date'] }}</td>
                                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $row['start_time'] }}</td>
                                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">
                                            @if($row['end_time'] === 'Running')
                                                <span class="text-success-300 font-semibold">Running</span>
                                            @else
                                                {{ $row['end_time'] }}
                                            @endif
                                        </td>
                                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $row['total_worked_time'] }}</td>
                                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">
                                            @if($row['shift_working_hour'] === 'Day Off')
                                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">Day Off</span>
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

                <!-- Running Tasks Card -->
                <div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
                    <!-- Card Header -->
                    <div class="mb-6 border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                        <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Running Tasks</h3>
                    </div>

                    <!-- Running Tasks Table -->
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">User Name</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Task Name</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Estimated Time</th>
                                    <th class="pb-3 text-sm font-bold text-bgray-600 dark:text-bgray-50">Worked Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-500">
                                @forelse($runningTasksData as $row)
                                    <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                        <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">{{ $row['user_name'] }}</td>
                                        <td class="py-3.5 text-sm font-semibold text-success-300 hover:text-success-400 transition-colors">
                                            <a href="{{ route('tasks.edit', $row['task_id']) }}">
                                                {{ $row['task_name'] }}
                                            </a>
                                        </td>
                                        <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $row['estimated_time'] }}</td>
                                        <td class="py-3.5 text-sm {{ $row['color_class'] }}">{{ $row['worked_time'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-400 dark:bg-darkblack-500/50 dark:text-bgray-300 mb-3">
                                                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-sm font-bold text-bgray-900 dark:text-white mb-1">No Running Tasks</h4>
                                                <p class="text-xs text-bgray-500 dark:text-bgray-400">There are currently no tasks in progress.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar: w-full xl:w-auto xl:flex-[1] shrink-0 -->
            <div class="w-full xl:w-auto xl:flex-[1] shrink-0">

                <!-- Sticky Notification Card -->
                <div class="rounded-xl border border-bgray-100 bg-white p-6 xl:p-4 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">

                    <!-- Sidebar Header -->
                    <div class="mb-6 flex items-center justify-between border-b border-bgray-100 pb-4 dark:border-darkblack-500">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Request Notifications</h3>
                        </div>
                        <span class="inline-flex h-5 items-center justify-center rounded-full bg-rose-50 px-2 text-xs font-bold text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                            {{ $total_request_count }} New
                        </span>
                    </div>

                    <!-- Scrollable Notifications List Feed (UI Only) -->
                    <div class="max-h-[500px] overflow-y-auto pr-1 space-y-4">

                        <!-- Notification 1: Task Approvals -->
                        @if ($task_request_count > 0)
                            <a href="{{ route('tasks.requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-purple-200 bg-slate-50/50 hover:bg-purple-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-purple-900/50 dark:bg-darkblack-500/20 dark:hover:bg-purple-950/10">
                                <div class="flex space-x-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-purple-500 dark:bg-purple-950/40 dark:text-purple-400">
                                        <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">Task Approvals</span>
                                            <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                        </div>
                                        <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">{{ $task_request_count }} new pending task requests</p>
                                        <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">Just now</span>
                                    </div>
                                </div>
                            </a>
                        @endif

                        <!-- Notification: Time Log Approvals -->
                        @if ($task_log_time_request_count > 0)
                            <a href="{{ route('tasks.time-log-change-requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-amber-200 bg-slate-50/50 hover:bg-amber-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-amber-900/50 dark:bg-darkblack-500/20 dark:hover:bg-amber-950/10">
                                <div class="flex space-x-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-950/40 dark:text-amber-400">
                                        <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">Time Log Approvals</span>
                                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                        </div>
                                        <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">{{ $task_log_time_request_count }} pending time log requests</p>
                                        <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">Just now</span>
                                    </div>
                                </div>
                            </a>
                        @endif

                        <!-- Notification 2: Handoff -->
                        @if ($handoff_request_count > 0)
                            <a href="{{ route('handoff_requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-blue-200 bg-slate-50/50 hover:bg-blue-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-blue-900/50 dark:bg-darkblack-500/20 dark:hover:bg-blue-950/10">
                                <div class="flex space-x-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-500 dark:bg-blue-950/40 dark:text-blue-400">
                                        <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Handoff</span>
                                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                        </div>
                                        <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">{{ $handoff_request_count }} pending handoff requests</p>
                                        <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">2 hours ago</span>
                                    </div>
                                </div>
                            </a>
                        @endif

                        <!-- Notification 3: Breaks -->
                        @if ($break_request_count > 0)
                            <a href="{{ route('break-requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-rose-200 bg-slate-50/50 hover:bg-rose-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-rose-900/50 dark:bg-darkblack-500/20 dark:hover:bg-rose-950/10">
                                <div class="flex space-x-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500 dark:bg-rose-950/40 dark:text-rose-400">
                                        <svg class="h-4.5 w-4.5 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m11.314 11.314l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-bgray-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400">Breaks</span>
                                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                        </div>
                                        <p class="text-xs font-semibold text-bgray-600 dark:text-bgray-50">{{ $break_request_count }} break approvals pending</p>
                                        <span class="block text-[10px] text-bgray-600 dark:text-bgray-50">Yesterday</span>
                                    </div>
                                </div>
                            </a>
                        @endif

                        @if ($total_request_count === 0)
                            <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success-50 text-success-300 dark:bg-success-950/30 dark:text-success-300 mb-3">
                                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-bgray-900 dark:text-white mb-1">All Caught Up!</h4>
                                <p class="text-xs text-bgray-500 dark:text-bgray-400">No pending requests require your approval.</p>
                            </div>
                        @endif

                    </div>
                </div>

            </div>

        </div>

    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
