<div data-analytics-task-counts-wrapper class="w-full">
    <!-- Single Project View: Combined Row (Header 60% Left, Summary Counts 40% Right) -->
    <div data-single-project-combined-row class="hidden w-full">
        <div data-single-project-header-slot>
            <!-- Header section (60%) mounted here dynamically -->
        </div>
        <div data-single-project-counts-slot>
            <!-- Task counts (40%) mounted here dynamically -->
        </div>
    </div>

    <!-- Standalone Container for Overall / All Projects Mode -->
    <div data-task-counts-standalone-slot class="w-full h-full">
        <div data-task-counts-card class="h-full flex flex-col justify-between bg-white border border-bgray-200 rounded-xl p-4 sm:p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
            <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-bgray-100 dark:border-darkblack-400 cursor-pointer rounded-lg px-1.5 py-0.5 transition-colors duration-200 hover:bg-slate-300/40 dark:hover:bg-darkblack-100" data-dashboard-tile="total_tasks">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span class="text-xs font-semibold text-bgray-600 dark:text-bgray-300 uppercase tracking-wider">Total Tasks</span>
                </div>
                <span class="text-lg font-bold text-bgray-900 dark:text-white" data-dashboard-count="total_tasks" data-project-analytics-summary-count="total_tasks">
                    <span class="inline-block animate-pulse h-5 w-10 rounded bg-bgray-200 dark:bg-darkblack-400"></span>
                </span>
            </div>
            <div class="grid grid-cols-4 gap-2 text-center pt-1">
                <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-slate-500/40 dark:hover:bg-darkblack-100" data-dashboard-tile="pending_tasks">
                    <div class="text-[12px] font-medium text-bgray-600 dark:text-bgray-300">Pending</div>
                    <div class="text-[15px] font-semibold text-bgray-900 dark:text-white" data-dashboard-count="pending_tasks" data-project-analytics-summary-count="pending_tasks">
                        <span class="inline-block animate-pulse h-4 w-8 rounded bg-bgray-200 dark:bg-darkblack-400"></span>
                    </div>
                </div>
                <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-indigo-50 dark:hover:bg-indigo-100" data-dashboard-tile="active_tasks">
                    <div class="text-[12px] font-medium text-indigo-500 dark:text-indigo-400">Active</div>
                    <div class="text-[15px] font-bold text-indigo-500 dark:text-indigo-400" data-dashboard-count="active_tasks" data-project-analytics-summary-count="active_tasks">
                        <span class="inline-block animate-pulse h-4 w-8 rounded bg-bgray-200 dark:bg-darkblack-400"></span>
                    </div>
                </div>
                <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-amber-50/70 dark:hover:bg-amber-100" data-dashboard-tile="archived_tasks">
                    <div class="text-[12px] font-medium text-amber-500 dark:text-amber-400">Archived</div>
                    <div class="text-[15px] font-bold text-amber-500 dark:text-amber-400" data-dashboard-count="archived_tasks" data-project-analytics-summary-count="archived_tasks">
                        <span class="inline-block animate-pulse h-4 w-8 rounded bg-bgray-200 dark:bg-darkblack-400"></span>
                    </div>
                </div>
                <div class="rounded-lg py-1.5 px-2 cursor-pointer transition-colors duration-200 shadow shadow-md hover:bg-emerald-100 dark:hover:bg-emerald-50" data-dashboard-tile="completed_tasks">
                    <div class="text-[12px] font-medium text-emerald-500 dark:text-emerald-400">Completed</div>
                    <div class="text-[15px] font-bold text-emerald-500 dark:text-emerald-400" data-dashboard-count="completed_tasks" data-project-analytics-summary-count="completed_tasks">
                        <span class="inline-block animate-pulse h-4 w-8 rounded bg-bgray-200 dark:bg-darkblack-400"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media (min-width: 768px) {
        [data-single-project-combined-row]:not(.hidden) {
            display: flex !important;
            flex-direction: row !important;
            gap: 1rem !important;
            align-items: stretch !important;
            width: 100% !important;
        }
        [data-single-project-header-slot] {
            width: 60% !important;
            flex: 0 0 calc(60% - 0.5rem) !important;
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }
        [data-single-project-counts-slot] {
            width: 40% !important;
            flex: 0 0 calc(40% - 0.5rem) !important;
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }
    }
    @media (max-width: 767px) {
        [data-single-project-combined-row]:not(.hidden) {
            display: flex !important;
            flex-direction: column !important;
            gap: 1rem !important;
            width: 100% !important;
        }
        [data-single-project-header-slot],
        [data-single-project-counts-slot] {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
        }
    }
</style>

<script>
    (function() {
        function syncSingleProjectRow() {
            const singleView = document.querySelector('[data-single-project-dashboard-view]');
            const isSingleProject = singleView && !singleView.classList.contains('hidden');

            const combinedRow = document.querySelector('[data-single-project-combined-row]');
            const headerSlot = document.querySelector('[data-single-project-header-slot]');
            const countsSlot = document.querySelector('[data-single-project-counts-slot]');
            const standaloneSlot = document.querySelector('[data-task-counts-standalone-slot]');
            const countsCard = document.querySelector('[data-task-counts-card]');
            const summaryCard = document.querySelector('[data-single-project-summary-section]');
            const summaryHost = document.querySelector('[data-single-project-summary-host]');

            if (!combinedRow || !headerSlot || !countsSlot || !countsCard) return;

            if (isSingleProject && summaryCard) {
                if (summaryCard.parentElement !== headerSlot) {
                    headerSlot.appendChild(summaryCard);
                }
                if (countsCard.parentElement !== countsSlot) {
                    countsSlot.appendChild(countsCard);
                }
                combinedRow.classList.remove('hidden');
                if (standaloneSlot) standaloneSlot.classList.add('hidden');
            } else {
                if (summaryHost && summaryCard && summaryCard.parentElement !== summaryHost) {
                    summaryHost.appendChild(summaryCard);
                }
                if (standaloneSlot && countsCard.parentElement !== standaloneSlot) {
                    standaloneSlot.appendChild(countsCard);
                }
                combinedRow.classList.add('hidden');
                if (standaloneSlot) standaloneSlot.classList.remove('hidden');
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', syncSingleProjectRow);
        } else {
            syncSingleProjectRow();
        }

        const observer = new MutationObserver(function() {
            syncSingleProjectRow();
        });

        function setupObserver() {
            const singleView = document.querySelector('[data-single-project-dashboard-view]');
            if (singleView) {
                observer.observe(singleView, { attributes: true, attributeFilter: ['class'] });
                syncSingleProjectRow();
            } else {
                setTimeout(setupObserver, 50);
            }
        }
        setupObserver();

        document.addEventListener('project-analytics:summary-loaded', syncSingleProjectRow);
    })();
</script>
