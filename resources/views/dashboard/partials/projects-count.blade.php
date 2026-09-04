<!-- Projects Count Card -->
<div class="rounded-xl border border-bgray-100 bg-white p-6 shadow-sm dark:border-darkblack-500 dark:bg-darkblack-600" data-projects-count-section data-projects-count-url="{{ route('dashboard.projects-count') }}">
    <!-- Card Header -->
    <div class="mb-5 space-y-4 border-b border-bgray-100 pb-4 dark:border-darkblack-500">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Projects Bar Chart</h3>
        </div>

        <!-- 5 Filter Elements Grid with Labels -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 items-end">
            <!-- 1. Project Flow Filter -->
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="projects-count-flow-filter" class="text-xs font-bold text-bgray-700 dark:text-bgray-200">
                    Project Flow
                </label>
                <select id="projects-count-flow-filter" class="tom-select-no-search w-full" data-placeholder="All Flow">
                    <option value="">All Flow</option>
                    <option value="agile">Agile</option>
                    <option value="linear">Linear</option>
                </select>
            </div>

            <!-- 2. Project Category Multi-Select Filter -->
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="projects-count-category-filter" class="text-xs font-bold text-bgray-700 dark:text-bgray-200">
                    Project Category
                </label>
                <select id="projects-count-category-filter" class="tom-select-multiple w-full" multiple data-placeholder="All Categories" data-sort="0">
                    @foreach ($projectCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Customer Filter -->
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="projects-count-customer-filter" class="text-xs font-bold text-bgray-700 dark:text-bgray-200">
                    Customer
                </label>
                <select id="projects-count-customer-filter" class="tom-select w-full" data-placeholder="All Customers">
                    <option value="">All Customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 4. Month Filter -->
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="projects-count-month-filter" class="text-xs font-bold text-bgray-700 dark:text-bgray-200">
                    Month
                </label>
                <select id="projects-count-month-filter" class="tom-select-no-search w-full" data-placeholder="All Months">
                    <option value="">All Months</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>

            <!-- 5. Project Status Multi-Select Filter -->
            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="projects-count-status-filter" class="text-xs font-bold text-bgray-700 dark:text-bgray-200">
                    Project Status
                </label>
                <select id="projects-count-status-filter" class="tom-select-multiple w-full" multiple data-placeholder="All Statuses" data-sort="0">
                    @foreach ($projectStatuses as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Chart Body Container -->
    <div class="relative min-h-[320px] w-full">
        <!-- Loading Overlay -->
        <div data-projects-count-loading class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-darkblack-600/70 transition-opacity">
            <span class="inline-block animate-pulse text-sm font-semibold text-bgray-500 dark:text-bgray-300">Loading chart...</span>
        </div>

        <!-- Chart Canvas Container -->
        <div data-projects-count-chart-wrapper class="h-[320px] w-full">
            <canvas data-projects-count-chart aria-label="Projects count by year chart"></canvas>
        </div>

        <!-- Empty State -->
        <div data-projects-count-empty-state class="hidden flex h-[320px] items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
            No projects found matching the selected criteria.
        </div>
    </div>
</div>
