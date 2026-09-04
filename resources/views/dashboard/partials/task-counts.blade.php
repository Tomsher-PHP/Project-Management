<div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
    <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-100 dark:border-darkblack-500 cursor-pointer rounded-lg px-1.5 py-0.5 transition-colors duration-200 hover:bg-slate-300/40 dark:hover:bg-darkblack-100" data-dashboard-tile="total_tasks">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
            <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-300 uppercase tracking-wider">Total Tasks</span>
        </div>
        <span class="text-lg font-bold text-bgray-900 dark:text-white" data-dashboard-count="total_tasks">
            <span class="inline-block animate-pulse h-5 w-10 rounded"></span>
        </span>
    </div>
    <div class="grid grid-cols-4 gap-2 text-center">
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-slate-500/40 dark:hover:bg-darkblack-100" data-dashboard-tile="pending_tasks">
            <div class="text-[11px] font-medium text-bgray-600 dark:text-bgray-300">Pending</div>
            <div class="text-sm font-semibold text-bgray-900 dark:text-white" data-dashboard-count="pending_tasks">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-indigo-50 dark:hover:bg-indigo-100" data-dashboard-tile="active_tasks">
            <div class="text-[11px] font-medium text-indigo-500 dark:text-indigo-400">Active</div>
            <div class="text-sm font-bold text-indigo-500 dark:text-indigo-400" data-dashboard-count="active_tasks">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-amber-50/70 dark:hover:bg-amber-100" data-dashboard-tile="archived_tasks">
            <div class="text-[11px] font-medium text-amber-500 dark:text-amber-400">Archived</div>
            <div class="text-sm font-bold text-amber-500 dark:text-amber-400" data-dashboard-count="archived_tasks">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-emerald-100 dark:hover:bg-emerald-50" data-dashboard-tile="completed_tasks">
            <div class="text-[11px] font-medium text-emerald-500 dark:text-emerald-400">Completed</div>
            <div class="text-sm font-bold text-emerald-500 dark:text-emerald-400" data-dashboard-count="completed_tasks">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
    </div>
</div>
