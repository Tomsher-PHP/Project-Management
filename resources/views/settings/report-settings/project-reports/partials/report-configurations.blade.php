<!-- GENERAL SETTINGS / CONFIGURATIONS LIST TAB -->
<section id="report-panel-general" data-report-panel="general" class="report-panel p-5">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-bgray-900 dark:text-white">
                Project Report Configurations
            </h2>
            <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">
                Manage automated project performance, progress, and milestone reports.
            </p>
        </div>

        @can($reportPermission)
            <button type="button" id="btn-add-project-report" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-success-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Report
            </button>
        @endcan
    </div>

    <!-- CONFIGURATIONS TABLE -->
    <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex flex-col space-y-5">
            <div class="table-content w-full overflow-x-auto">
                <table class="w-full text-left" id="project-reports-table">
                    <thead>
                        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                            <th class="px-4 py-4 whitespace-nowrap min-w-[200px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Report Name</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[130px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Frequency</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[120px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Delivery Time</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[140px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reporting Period</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[180px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Statuses</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[90px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span></th>
                            @can($reportPermission)
                                <th class="px-4 py-4 whitespace-nowrap min-w-[120px] text-right"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                        @php
                            $periodLabels = config('report-settings.project_report.reporting_periods', []);
                            $statusMap = isset($projectStatuses) ? $projectStatuses->keyBy('id') : collect();
                        @endphp
                        @forelse ($configurations as $config)
                            @php
                                $statusIds = (array) ($config->settings['filters']['project_status_ids']
                                    ?? $config->settings['project_statuses']
                                    ?? $config->settings['project_status_ids']
                                    ?? []);
                                $rowStatuses = collect($statusIds)->map(function ($id) use ($statusMap) {
                                    $found = $statusMap->get((int) $id);
                                    return [
                                        'id' => $id,
                                        'name' => $found ? $found->name : 'Status #' . $id,
                                        'color' => $found ? ($found->color ?: '#94A3B8') : '#94A3B8',
                                    ];
                                });
                                $periodLabel = $periodLabels[$config->reporting_period]
                                    ?? ucwords(str_replace('_', ' ', $config->reporting_period ?: 'all_time'));
                            @endphp
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-100 dark:hover:bg-darkblack-500" data-config-row="{{ $config->id }}">
                                <td class="px-4 py-4">
                                    <span class="block text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $config->report_name ?: 'Project Report (#' . $config->id . ')' }}
                                    </span>
                                    <span class="block text-xs font-mono text-bgray-600 dark:text-bgray-300">
                                        {{ $config->setting_key }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ ucfirst($config->frequency) }}
                                    </span>
                                    @if ($config->frequency === 'weekly' && $config->weekly_day)
                                        <span class="block text-xs text-bgray-500 dark:text-bgray-300 mt-0.5">
                                            Every {{ [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'][$config->weekly_day] ?? 'Day ' . $config->weekly_day }}
                                        </span>
                                    @elseif ($config->frequency === 'monthly' && isset($config->settings['schedule']['monthly_day']))
                                        <span class="block text-xs text-bgray-500 dark:text-bgray-300 mt-0.5">
                                            {{ $config->settings['schedule']['monthly_day'] === 'last' ? 'Last Day of Month' : 'Day ' . $config->settings['schedule']['monthly_day'] . ' of month' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                    {{ $config->delivery_time ? \Carbon\Carbon::parse($config->delivery_time)->format($globalTimeFormat ?? config('constants.time_format', 'H:i')) : '—' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ $periodLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    @if ($rowStatuses->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 max-w-[280px]">
                                            @foreach ($rowStatuses as $st)
                                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                                    <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" style="background-color: {{ $st['color'] }};"></span>
                                                    <span>{{ $st['name'] }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-bgray-500 dark:text-bgray-400">All</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @can($reportPermission)
                                        <button type="button" class="project-report-status-toggle switch-btn {{ $config->enabled ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-id="{{ $config->id }}" data-url="{{ route('settings.project-reports.toggle', $config->id) }}" role="switch" aria-checked="{{ $config->enabled ? 'true' : 'false' }}" title="{{ $config->enabled ? 'Click to disable' : 'Click to enable' }}">
                                            <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $config->enabled ? 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300' }}">
                                            {{ $config->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    @endcan
                                </td>
                                @can($reportPermission)
                                    <td class="px-4 py-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" class="btn-edit-project-report inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:bg-darkblack-400 dark:hover:text-success-300" data-id="{{ $config->id }}" title="Edit Configuration">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button type="button" class="btn-delete-project-report inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-300 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-800 dark:hover:bg-darkblack-400 dark:hover:text-red-400" data-id="{{ $config->id }}" data-name="{{ $config->report_name ?: 'Project Report (#' . $config->id . ')' }}" data-url="{{ route('settings.project-reports.destroy', $config->id) }}" title="Delete Configuration">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="@can($reportPermission) 7 @else 6 @endcan" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                    No project report configurations found. Click "+ Add Report" to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

