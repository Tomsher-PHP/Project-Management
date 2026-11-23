<!-- GENERAL SETTINGS TAB -->
<section id="report-panel-general" data-report-panel="general" class="report-panel p-5">
    <form method="POST" action="{{ route('settings.daily-work-reports.settings.update') }}">
        @csrf

        <input type="hidden" name="report_key" value="{{ $reportKey }}">

        <div class="mb-6 flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div>
                <h2 class="font-semibold text-bgray-900 dark:text-white">
                    Enable automatic reports
                </h2>
                <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">
                    Reports are generated only when the scheduler runs and the configured time is due.
                </p>
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" id="daily-work-reports-enabled" name="enabled" value="1" @checked($enabledValue) class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

                <span id="daily-work-reports-enabled-label" class="text-sm font-medium text-bgray-900 dark:text-white">
                    {{ $enabledValue ? 'Enabled' : 'Disabled' }}
                </span>
            </label>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <label for="report-frequency" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                    Report frequency
                </label>
                <select id="report-frequency" name="frequency" required class="tom-select-no-search w-full">
                    <option value="daily" @selected(old('frequency', $settings->frequency) === 'daily')>
                        Daily
                    </option>
                    <option value="weekdays" @selected(old('frequency', $settings->frequency) === 'weekdays')>
                        Weekdays only
                    </option>
                    <option value="weekly" @selected(old('frequency', $settings->frequency) === 'weekly')>
                        Weekly
                    </option>
                </select>
            </div>

            <div>
                <label for="delivery-time" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                    Delivery time
                </label>
                <input id="delivery-time" type="text" name="delivery_time" value="{{ old('delivery_time', $settings->delivery_time ? \Carbon\Carbon::parse($settings->delivery_time)->format($globalTimeFormat ?? config('constants.time_format', 'H:i')) : '') }}" required class="timepicker w-full rounded-lg border border-bgray-200 bg-white p-3 text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white" autocomplete="off">
            </div>

            <div>
                <label for="report-timezone" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                    Timezone
                </label>
                <select id="report-timezone" name="timezone" required class="tom-select-lazy w-full" data-route="{{ route('timezones.search') }}" data-placeholder="Start typing to search timezone...">
                    <option value="">Select Timezone</option>
                    @if (old('timezone', $settings->timezone ?? false))
                        <option value="{{ old('timezone', $settings->timezone) }}" selected>
                            {{ old('timezone', $settings->timezone) }}
                        </option>
                    @endif
                </select>
            </div>

            <div id="weekly-day-wrapper" class="{{ old('frequency', $settings->frequency) === 'weekly' ? '' : 'hidden' }}">
                <label for="weekly-day" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                    Weekly delivery day
                </label>

                <select id="weekly-day" name="weekly_day" class="tom-select-no-search w-full">
                    @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)
                        <option value="{{ $day }}" @selected((int) old('weekly_day', $settings->weekly_day) === $day)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        @if ($reportKey === 'daily_work')
            <div class="mt-6 space-y-4">
                <div>
                    <label for="reporting-period" class="mb-2 block text-sm font-medium text-bgray-900 dark:text-white">
                        Reporting period
                    </label>

                    <select id="reporting-period" name="reporting_period" required class="tom-select-no-search w-full">
                        <option value="current_day" @selected(old('reporting_period', $settings->reporting_period) === 'current_day')>
                            Current day
                        </option>

                        <option value="previous_working_day" @selected(old('reporting_period', $settings->reporting_period) === 'previous_working_day')>
                            Previous working day
                        </option>
                    </select>
                </div>
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="send_zero_activity" value="1" @checked((bool) old('send_zero_activity', $settings->send_zero_activity)) class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

                    <span>
                        <span class="block text-sm font-medium text-bgray-900 dark:text-white">
                            Send reports for employees with no activity
                        </span>

                        <span class="block text-xs text-bgray-600 dark:text-bgray-300">
                            Send a report even when no reportable activity is found.
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-3">
                    <input type="checkbox" name="include_inactive_users" value="1" @checked((bool) old('include_inactive_users', $settings->include_inactive_users)) class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

                    <span>
                        <span class="block text-sm font-medium text-bgray-900 dark:text-white">
                            Include inactive employees
                        </span>

                        <span class="block text-xs text-bgray-600 dark:text-bgray-300">
                            Include inactive employees when generating reports.
                        </span>
                    </span>
                </label>
            </div>
        @endif

        @if (!empty($currentReport['sections']))
            @php
                $sections = $currentReport['sections'];

                $selectedSections = old('report_sections', $settings->report_sections ?? []);

                if (is_string($selectedSections)) {
                    $selectedSections = json_decode($selectedSections, true) ?: [];
                }

                if (!is_array($selectedSections)) {
                    $selectedSections = [];
                }
            @endphp

            <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700">
                <h3 class="mb-3 font-semibold text-bgray-900 dark:text-white">
                    Report sections
                </h3>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sections as $key => $label)
                        <label class="flex items-center gap-2 text-sm font-medium text-bgray-900 dark:text-white">
                            <input type="checkbox" name="report_sections[]" value="{{ $key }}" @checked(in_array($key, $selectedSections, true)) class="h-5 w-5 cursor-pointer rounded border border-bgray-400 text-success-300 focus:outline-none focus:ring-0 dark:border-darkblack-400 dark:bg-darkblack-600">

                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        @can($reportPermission)
            <div class="mt-6 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                    Save Settings
                </button>
            </div>
        @endcan
    </form>
</section>

