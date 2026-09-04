<div class="bg-white border border-slate-200/80 rounded-xl p-3.5 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600">
    <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-100 dark:border-darkblack-500 cursor-pointer rounded-lg px-1.5 py-0.5 transition-colors" data-dashboard-tile="total_projects">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span class="text-xs font-semibold text-slate-500 dark:text-bgray-300 uppercase tracking-wider">Total Projects</span>
        </div>
        <span class="text-lg font-bold text-bgray-900 dark:text-white" data-dashboard-count="total_projects">
            <span class="inline-block animate-pulse h-5 w-10 rounded"></span>
        </span>
    </div>
    <div class="grid grid-cols-4 gap-2 text-center">
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors" data-dashboard-tile="open_projects">
            <div class="text-[11px] font-medium text-slate-400 dark:text-bgray-300">Open</div>
            <div class="text-sm font-semibold text-slate-700 dark:text-bgray-300" data-dashboard-count="open_projects">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors" data-dashboard-tile="in_progress_projects">
            <div class="text-[11px] font-medium text-blue-900 dark:text-blue-400">Progress</div>
            <div class="text-sm font-semibold text-blue-700 dark:text-blue-300" data-dashboard-count="in_progress_projects">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors" data-dashboard-tile="archived_projects">
            <div class="text-[11px] font-medium text-amber-500 dark:text-amber-400">Archived</div>
            <div class="text-sm font-semibold text-amber-700 dark:text-amber-300" data-dashboard-count="archived_projects">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
        <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors" data-dashboard-tile="completed_projects">
            <div class="text-[11px] font-medium text-emerald-500 dark:text-emerald-400">Completed</div>
            <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-300" data-dashboard-count="completed_projects">
                <span class="inline-block animate-pulse h-4 w-8 rounded"></span>
            </div>
        </div>
    </div>
</div>
