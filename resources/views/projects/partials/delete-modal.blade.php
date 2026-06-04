<div class="text-left font-sans text-sm">
    <p class="mb-4 text-bgray-600 dark:text-bgray-300">
        Are you sure you want to delete this project? Review the linked resources below:
    </p>

    <div class="grid grid-cols-2 gap-3 mb-4 p-4 bg-gray-50 dark:bg-darkblack-500 border border-gray-200 dark:border-darkblack-400 rounded-lg">
        @if ($summary['is_agile'])
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <span class="text-bgray-700 dark:text-bgray-300 font-medium">{{ $summary['milestones_count'] }} Milestones</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <span class="text-bgray-700 dark:text-bgray-300 font-medium">{{ $summary['sprints_count'] }} Sprints</span>
            </div>
        @endif
        <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
            <span class="text-bgray-700 dark:text-bgray-300 font-medium">{{ $summary['tasks_count'] }} Tasks ({{ $summary['active_tasks_count'] }} active)</span>
        </div>
        <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
            <span class="text-bgray-700 dark:text-bgray-300 font-medium">{{ $summary['sub_tasks_count'] }} Sub-tasks</span>
        </div>
        <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
            <span class="text-bgray-700 dark:text-bgray-300 font-medium">{{ $summary['pending_requests_count'] }} Pending requests</span>
        </div>
        <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
            <span class="text-bgray-700 dark:text-bgray-300 font-medium">{{ $summary['scope_files_count'] }} Scope files</span>
        </div>
    </div>

    @if (($summary['running_timers_count'] ?? 0) > 0)
        <div class="p-3 border rounded-lg text-error-300 text-sm mb-4 bg-white dark:bg-darkblack-600" style="border-color: #dd3333;">
            <strong class="font-bold flex items-center gap-1.5 mb-1 text-error-300">
                <svg class="w-4.5 h-4.5 text-error-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Deletion Blocked
            </strong>
            This project has <strong class="font-bold">{{ $summary['running_timers_count'] }} active running timer(s)</strong>. Stop all active task timers to proceed.
        </div>
    @else
        <div class="p-3 border rounded-lg text-warning-300 text-sm mb-4 bg-white dark:bg-darkblack-600" style="border-color: #eab308;">
            <strong class="font-bold flex items-center gap-1.5 mb-1 text-warning-300">
                <svg class="w-4.5 h-4.5 text-warning-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Important Note
            </strong>
            Deleting this project will hide the project, tasks, sprints, milestones, and modules. Time logs and activity history will be kept.
        </div>
    @endif
</div>
