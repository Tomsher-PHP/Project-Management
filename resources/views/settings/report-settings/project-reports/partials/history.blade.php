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

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-bgray-600 dark:bg-gray-800 dark:text-bgray-300">
                <tr>
                    <th class="px-4 py-3">Configuration</th>
                    <th class="px-4 py-3">Report date</th>
                    <th class="px-4 py-3">Projects</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Generated at</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($reports as $report)
                    <tr>
                        <td class="px-4 py-3 text-bgray-900 dark:text-white">
                            <span class="font-medium">
                                {{ $report->reportSetting?->report_name ?? $report->report_data['metadata']['report_name'] ?? 'Project Report' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-bgray-900 dark:text-white">
                            {{ $report->report_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-bgray-900 dark:text-white">
                            {{ $report->report_data['metadata']['total_projects'] ?? (isset($report->report_data['projects']) ? count($report->report_data['projects']) : '—') }}
                        </td>
                        <td class="px-4 py-3 text-bgray-900 dark:text-white">
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
                        <td class="px-4 py-3 text-bgray-900 dark:text-white">
                            {{ $report->generated_at?->format('d M Y H:i') ?? $report->created_at?->format('d M Y H:i') ?? '—' }}
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

    <p class="mt-4 text-sm text-bgray-600 dark:text-bgray-300">
        Manual generation, test sending, and retry actions must be connected to their dedicated endpoints before use.
    </p>
</section>

