<!-- Sticky Notification Card -->
<div class="rounded-xl border border-bgray-100 bg-white p-6 xl:p-4 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">

    <!-- Sidebar Header -->
    <div class="mb-6 flex items-center justify-between border-b border-bgray-100 pb-4 dark:border-darkblack-500">
        <div class="flex items-center space-x-2">
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Request Notifications</h3>
        </div>
        <span class="inline-flex h-5 items-center justify-center text-xs font-bold text-rose-600 dark:text-rose-300">
            {{ $total_request_count }} New
        </span>
    </div>

    <!-- Scrollable Notifications List Feed (UI Only) -->
    <div class="max-h-[500px] overflow-y-auto pr-1 space-y-4">

        <!-- Notification 1: Task Approvals -->
        @if ($task_request_count > 0)
            <a href="{{ route('tasks.requests.index') }}" class="group block rounded-lg p-3.5 xl:p-2.5 border border-slate-50 hover:border-purple-200 bg-slate-50/50 hover:bg-purple-50/30 transition-all duration-300 dark:border-darkblack-500 dark:hover:border-purple-900/50 dark:bg-darkblack-500/20 dark:hover:bg-purple-950/10">
                <div class="flex space-x-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-purple-500 dark:text-purple-400">
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
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-amber-500 dark:text-amber-400">
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
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-blue-500 dark:text-blue-400">
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
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-rose-500 dark:text-rose-400">
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
