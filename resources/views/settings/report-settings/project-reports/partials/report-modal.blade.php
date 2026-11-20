<div id="project-report-config-modal" class="modal fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="modal-overlay fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"></div>

    <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
        <div class="modal-content relative z-10 w-full max-w-3xl">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkblack-600">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-5 dark:border-darkblack-400 sm:px-7">
                    <div>
                        <h3 id="modal-report-title" class="text-xl font-bold text-bgray-900 dark:text-white">
                            Add Project Report
                        </h3>
                        <p class="text-xs text-bgray-600 dark:text-bgray-300 mt-0.5">
                            Configure schedule, project filters, and report columns.
                        </p>
                    </div>

                    <button type="button" class="modal-close inline-flex h-9 w-9 items-center justify-center rounded-lg bg-bgray-100 text-bgray-700 transition hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                        ✕
                    </button>
                </div>

                <!-- Modal Form -->
                <form id="project-report-config-form" action="{{ route('settings.project-reports.store') }}" method="POST" class="flex flex-col">
                    @csrf
                    <input type="hidden" name="_method" id="modal-form-method" value="POST">
                    <input type="hidden" name="id" id="modal-config-id" value="">

                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-7 space-y-6">

                        <!-- SECTION 1: GENERAL SETTINGS -->
                        <div>
                            <h4 class="mb-4 text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-300">
                                General Settings
                            </h4>

                            <div class="space-y-4">
                                <!-- Report Name -->
                                <div>
                                    <label for="modal-report-name" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                        Report Name <span class="text-error-300">*</span>
                                    </label>
                                    <input type="text" id="modal-report-name" name="report_name" required maxlength="255" placeholder="e.g., Executive Project Summary, Weekly Progress" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                    <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="report_name"></span>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Frequency -->
                                    <div>
                                        <label for="modal-report-frequency" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Frequency <span class="text-error-300">*</span>
                                        </label>
                                        <select id="modal-report-frequency" name="frequency" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="frequency"></span>
                                    </div>

                                    <!-- Delivery Time -->
                                    <div>
                                        <label for="modal-delivery-time" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Delivery Time <span class="text-error-300">*</span>
                                        </label>
                                        <input id="modal-delivery-time" type="text" name="delivery_time" required class="timepicker w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" autocomplete="off" placeholder="Select delivery time">
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="delivery_time"></span>
                                    </div>
                                </div>

                                <!-- Conditional Schedule Options -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Weekly Day (Show only for Weekly) -->
                                    <div id="modal-weekly-day-wrapper" class="hidden">
                                        <label for="modal-weekly-day" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Weekly Delivery Day <span class="text-error-300">*</span>
                                        </label>
                                        <select id="modal-weekly-day" name="weekly_day" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)
                                                <option value="{{ $day }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="weekly_day"></span>
                                    </div>

                                    <!-- Monthly Day (Show only for Monthly) -->
                                    <div id="modal-monthly-day-wrapper" class="hidden">
                                        <label for="modal-monthly-day" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Monthly Delivery Day <span class="text-error-300">*</span>
                                        </label>
                                        <select id="modal-monthly-day" name="monthly_day" class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            @for ($d = 1; $d <= 28; $d++)
                                                <option value="{{ $d }}">Day {{ $d }} of month</option>
                                            @endfor
                                            <option value="last">Last Day of Month</option>
                                        </select>
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="settings.schedule.monthly_day"></span>
                                    </div>

                                    <!-- Timezone -->
                                    <div>
                                        <label for="modal-report-timezone" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Timezone <span class="text-error-300">*</span>
                                        </label>
                                        <select id="modal-report-timezone" name="timezone" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            <option value="Asia/Dubai" selected>Asia/Dubai</option>
                                            <option value="UTC">UTC</option>
                                            <option value="Europe/London">Europe/London</option>
                                            <option value="America/New_York">America/New_York</option>
                                            <option value="Asia/Kolkata">Asia/Kolkata</option>
                                            <option value="Asia/Riyadh">Asia/Riyadh</option>
                                            <option value="Asia/Singapore">Asia/Singapore</option>
                                            <option value="Australia/Sydney">Australia/Sydney</option>
                                        </select>
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="timezone"></span>
                                    </div>
                                </div>

                                <!-- Enable Toggle -->
                                <div class="flex items-center justify-between rounded-lg border border-bgray-200 p-3.5 dark:border-darkblack-400">
                                    <div>
                                        <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                            Enable Report Schedule
                                        </span>
                                        <p class="text-xs text-bgray-600 dark:text-bgray-300 mt-0.5">
                                            Generate report automatically on the configured schedule.
                                        </p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input type="checkbox" id="modal-report-enabled" name="enabled" value="1" checked class="peer sr-only">
                                        <div class="peer h-6 w-11 rounded-full bg-bgray-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-success-300 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-darkblack-400"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: PROJECT FILTERS -->
                        <div class="border-t border-bgray-200 pt-5 dark:border-darkblack-400">
                            <h4 class="mb-4 text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-300">
                                Project Filters
                            </h4>

                            <div class="space-y-4">
                                <!-- Project Statuses -->
                                <div>
                                    <label for="modal-project-statuses" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                        Project Statuses <span class="text-error-300">*</span>
                                    </label>
                                    <select id="modal-project-statuses" name="project_status_ids[]" multiple required class="w-full rounded-lg border border-bgray-300 px-4 py-2 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white min-h-[90px]">
                                        @foreach ($projectStatuses as $status)
                                            <option value="{{ $status->id }}" selected>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="settings.filters.project_status_ids"></span>
                                    <span class="text-xs text-bgray-500 dark:text-bgray-300 mt-1 block">Hold Ctrl (Windows) or Cmd (Mac) to select multiple statuses.</span>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Reporting Period -->
                                    <div>
                                        <label for="modal-reporting-period" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Reporting Period <span class="text-error-300">*</span>
                                        </label>
                                        <select id="modal-reporting-period" name="reporting_period" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            <option value="all_time">All Time</option>
                                            <option value="previous_day">Previous Day</option>
                                            <option value="last_7_days">Last 7 Days</option>
                                            <option value="last_30_days">Last 30 Days</option>
                                            <option value="last_90_days">Last 90 Days</option>
                                            <option value="last_6_months">Last 6 Months</option>
                                            <option value="last_1_year">Last 1 Year</option>
                                        </select>
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="reporting_period"></span>
                                    </div>

                                    <!-- Date Filter -->
                                    <div>
                                        <label for="modal-date-filter" class="block text-sm font-semibold text-bgray-900 dark:text-white mb-1.5">
                                            Date Filter <span class="text-error-300">*</span>
                                        </label>
                                        <select id="modal-date-filter" name="date_filter" required class="w-full rounded-lg border border-bgray-300 px-4 py-2.5 text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                                            <option value="none">None (All Matching Projects)</option>
                                            <option value="start_date">Project Start Date within Period</option>
                                            <option value="end_date">Project End Date within Period</option>
                                            <option value="updated_at">Last Updated within Period</option>
                                        </select>
                                        <span class="text-xs text-error-300 hidden font-medium mt-1" data-error-for="settings.filters.date_filter"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: CUSTOMIZE COLUMNS -->
                        <div class="border-t border-bgray-200 pt-5 dark:border-darkblack-400">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-300">
                                        Customize Columns <span class="text-error-300">*</span>
                                    </h4>
                                    <p class="text-xs text-bgray-600 dark:text-bgray-300">
                                        Select the columns to include in the generated report.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="modal-columns-select-all" class="text-xs font-semibold text-success-300 hover:text-success-400">
                                        Select All
                                    </button>
                                    <span class="text-bgray-300 dark:text-darkblack-400">|</span>
                                    <button type="button" id="modal-columns-clear-all" class="text-xs font-semibold text-bgray-500 hover:text-bgray-700 dark:text-bgray-300 dark:hover:text-white">
                                        Clear All
                                    </button>
                                </div>
                            </div>

                            <span class="text-xs text-error-300 hidden font-medium mb-2 block" data-error-for="settings.columns"></span>

                            <div id="modal-columns-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                                @foreach ($availableColumns as $colKey => $colLabel)
                                    <label class="flex items-center gap-2.5 text-sm text-bgray-700 dark:text-bgray-300 cursor-pointer hover:text-bgray-900 dark:hover:text-white">
                                        <input type="checkbox" name="columns[]" value="{{ $colKey }}" checked class="modal-column-checkbox h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">
                                        <span>{{ $colLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="modal-close rounded-lg border border-bgray-300 bg-white px-5 py-2.5 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white">
                            Cancel
                        </button>
                        <button type="submit" id="modal-submit-btn" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                            <span id="modal-submit-text">Save Configuration</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

