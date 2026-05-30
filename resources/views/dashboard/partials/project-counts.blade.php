<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Projects Overview</h2>
    </div>
    <!-- KPI cards grid: custom-scroll flex items-center gap-3 overflow-x-auto py-1 -->
    <div class="custom-scroll flex items-center gap-3 overflow-x-auto py-1">
        <!-- Total Projects Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Total Projects</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="total_projects">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-50">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-bgray-400"></div>
        </div>

        <!-- Open Projects Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Open</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="open_projects">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-50">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gray-400"></div>
        </div>

        <!-- In Progress Projects Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">In Progress</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="in_progress_projects">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-950/30 dark:text-blue-400">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500"></div>
        </div>

        <!-- Archived Projects Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Archived</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="archived_projects">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-50 text-orange dark:bg-amber-950/30 dark:text-orange">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-orange"></div>
        </div>

        <!-- Completed Projects Card -->
        <div class="group relative min-w-[160px] flex-1 shrink-0 overflow-hidden rounded-xl border border-bgray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md dark:border-darkblack-500 dark:bg-darkblack-600">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-semibold text-bgray-600 dark:text-bgray-50 uppercase tracking-wider">Completed</span>
                    <h3 class="text-3xl font-extrabold text-bgray-900 dark:text-white" data-dashboard-count="completed_projects">
                        <span class="inline-block animate-pulse bg-bgray-200 dark:bg-darkblack-500 h-8 w-12 rounded"></span>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 text-success-300 dark:bg-emerald-950/30 dark:text-success-300">
                    <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-success-300"></div>
        </div>
    </div>
</div>
