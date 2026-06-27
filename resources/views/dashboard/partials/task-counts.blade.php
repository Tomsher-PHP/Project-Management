<div class="space-y-2">
    <div class="flex items-center justify-between">
        {{-- <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Tasks Overview</h2> --}}
    </div>
    <!-- KPI cards grid: custom-scroll flex items-center gap-3 overflow-x-auto py-1 -->
    <div class="custom-scroll flex items-center gap-3 overflow-x-auto py-1">
        <!-- Total Tasks Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600 cursor-pointer" data-dashboard-tile="total_tasks">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Total Tasks</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="total_tasks">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-50">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-bgray-400"></div>
        </div>

        <!-- Pending Tasks Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600 cursor-pointer" data-dashboard-tile="pending_tasks">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Pending Tasks</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="pending_tasks">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-50">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gray-400"></div>
        </div>

        <!-- Active Tasks Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600 cursor-pointer" data-dashboard-tile="active_tasks">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Active Tasks</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="active_tasks">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-950/30 dark:text-blue-400">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500"></div>
        </div>

        <!-- Archived Tasks Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600 cursor-pointer" data-dashboard-tile="archived_tasks">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Archived Tasks</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="archived_tasks">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-50 text-orange dark:bg-amber-950/30 dark:text-orange">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-orange"></div>
        </div>

        <!-- Completed Tasks Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600 cursor-pointer" data-dashboard-tile="completed_tasks">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Completed Tasks</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="completed_tasks">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 text-success-300 dark:bg-emerald-950/30 dark:text-success-300">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-success-300"></div>
        </div>
    </div>
</div>
