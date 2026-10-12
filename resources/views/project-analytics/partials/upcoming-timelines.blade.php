<div class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600" data-project-analytics-section="upcoming-timelines" data-url="{{ route('project.analytics.upcoming-timelines') }}">
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Upcoming Timeline Expirations</h4>
            <p class="text-xs text-bgray-700 dark:text-bgray-400">Timelines ending soonest</p>
        </div>
        <div class="text-xs font-semibold text-bgray-600 dark:text-bgray-300">
            Total Upcoming: <span data-timelines-total-count data-contracts-total-count class="font-bold text-bgray-900 dark:text-white">—</span>
        </div>
    </div>

    <div class="p-5">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="space-y-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-center justify-between gap-4 py-2">
                    <div class="h-4 w-1/4 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/6 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/6 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/6 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/12 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                </div>
            @endfor
        </div>

        <!-- Empty State -->
        <div data-empty-state class="hidden flex h-full flex-col items-center justify-center text-center py-8">
            <div class="mb-3">
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No timelines" class="mx-auto h-28 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white" data-empty-title>No upcoming timeline expirations</h4>
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-400" data-empty-subtitle>
                There are no active timelines with upcoming expiration dates.
            </p>
        </div>

        <!-- Table Content -->
        <div data-content class="hidden overflow-x-auto">
            <table class="w-full text-left text-xs text-bgray-600 dark:text-bgray-300">
                <thead class="border-b border-bgray-200 bg-bgray-50/50 text-[11px] font-bold uppercase tracking-wider text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500/30 dark:text-bgray-300">
                    <tr>
                        <th scope="col" class="py-3 px-4">Project</th>
                        <th scope="col" class="py-3 px-4">Service Type</th>
                        <th scope="col" class="py-3 px-4">Timeline Start</th>
                        <th scope="col" class="py-3 px-4">End Date</th>
                        <th scope="col" class="py-3 px-4">Remaining</th>
                        <th scope="col" class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400" data-timelines-table-body data-contracts-table-body>
                    <!-- Populated dynamically by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
