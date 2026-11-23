<!-- HISTORY TAB -->
<section id="report-panel-history" data-report-panel="history" class="report-panel hidden p-5">
    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach (['queued', 'sent', 'failed', 'partial'] as $status)
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm capitalize text-bgray-600 dark:text-bgray-300">
                    {{ $status }} deliveries
                </p>
                <p class="mt-2 text-2xl font-semibold text-bgray-900 dark:text-white">
                    {{ $deliveryCounts[$status] ?? 0 }}
                </p>
            </div>
        @endforeach
    </div>

    <h2 class="mb-3 font-semibold text-bgray-900 dark:text-white">
        Recent {{ $currentReport['label'] ?? 'Project Report' }} History
    </h2>

    <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex flex-col space-y-5">
            <div class="table-content w-full overflow-x-auto">
                <table class="w-full text-left" id="project-report-history-table">
                    <thead>
                        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                            <th class="px-4 py-4 whitespace-nowrap min-w-[200px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Configuration</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[130px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Report date</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[100px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Projects</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[120px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[160px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Generated at</span></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                        @forelse ($reports as $report)
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-100 dark:hover:bg-darkblack-500">
                                <td class="px-4 py-4">
                                    <span class="block text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $report->reportSetting?->report_name ?? ($report->report_data['metadata']['report_name'] ?? 'Project Report') }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                    {{ $report->report_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                    {{ $report->report_data['metadata']['total_projects'] ?? (isset($report->report_data['projects']) ? count($report->report_data['projects']) : '—') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @php
                                        $status = strtolower($report->status ?? 'pending');
                                        $badgeClass = match ($status) {
                                            'sent' => 'bg-success-50 text-success-500 border border-success-200 dark:bg-darkblack-500 dark:text-success-400',
                                            'queued', 'pending' => 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-darkblack-500 dark:text-blue-400',
                                            'failed' => 'bg-red-50 text-red-500 border border-red-200 dark:bg-darkblack-500 dark:text-red-400',
                                            'partial' => 'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-darkblack-500 dark:text-amber-400',
                                            'skipped' => 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-darkblack-500 dark:text-gray-400',
                                            default => 'bg-gray-50 text-gray-600 border border-gray-200 dark:bg-darkblack-500 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                    @if ($report->last_error)
                                        <span class="mt-1 block max-w-xs truncate text-xs text-bgray-500 dark:text-bgray-400" title="{{ $report->last_error }}">
                                            {{ $report->last_error }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                    {{ $report->generated_at?->format('d M Y H:i') ?? ($report->created_at?->format('d M Y H:i') ?? '—') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
                                    No reports have been generated yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="mt-4 text-sm text-bgray-600 dark:text-bgray-300">
        Manual generation, test sending, and retry actions must be connected to their dedicated endpoints before use.
    </p>
</section>
