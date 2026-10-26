<div class="hidden overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600"
     data-project-analytics-section="expired-hosting"
     data-url="{{ route('project.analytics.expired-hosting') }}">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-bgray-200 bg-bgray-50/80 px-5 py-3 dark:border-darkblack-400 dark:bg-darkblack-500/60">
        <div>
            <h4 class="text-sm font-bold text-bgray-900 dark:text-white">Expired Hosting</h4>
            <p class="text-xs text-bgray-700 dark:text-bgray-300">Hosting records whose expiry dates have passed</p>
        </div>
        <div class="text-xs font-semibold text-bgray-600 dark:text-bgray-300">
            Total Expired: <span data-expired-hosting-total-count class="font-bold text-bgray-900 dark:text-white">—</span>
        </div>
    </div>

    <div class="p-5">
        <!-- Loading State Skeleton -->
        <div data-loading-state class="space-y-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-center justify-between gap-4 py-2">
                    <div class="h-4 w-1/5 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
                    <div class="h-4 w-1/5 animate-pulse rounded bg-bgray-200 dark:bg-darkblack-500"></div>
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
                <img src="{{ asset(config('assets.images.chart_no_data')) }}" alt="No expired hosting records" class="mx-auto h-28 w-auto opacity-80">
            </div>
            <h4 class="text-sm font-semibold text-bgray-900 dark:text-white" data-empty-title>No expired hosting records found</h4>
            <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300" data-empty-subtitle>
                There are no expired server or hosting records.
            </p>
        </div>

        <!-- Table Content -->
        <div data-content class="hidden max-h-[460px] overflow-y-auto overflow-x-auto">
            <table class="w-full text-left text-xs text-bgray-600 dark:text-bgray-300">
                <thead class="sticky top-0 z-10 border-b border-bgray-200 bg-bgray-50 text-[11px] font-bold uppercase tracking-wider text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                    <tr>
                        <th scope="col" class="py-3 px-4 min-w-[180px]">Project Name</th>
                        <th scope="col" class="py-3 px-4 min-w-[170px]">Hosting / Service</th>
                        <th scope="col" class="py-3 px-4 min-w-[130px]">Provider</th>
                        <th scope="col" class="py-3 px-4 min-w-[120px]">Service Type</th>
                        <th scope="col" class="py-3 px-4 min-w-[140px]">Ownership</th>
                        <th scope="col" class="py-3 px-4 min-w-[120px]">Status</th>
                        <th scope="col" class="py-3 px-4 min-w-[140px]">Expiry</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400" data-expired-hosting-table-body>
                    <!-- Populated dynamically by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
