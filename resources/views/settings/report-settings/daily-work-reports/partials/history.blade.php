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
        Recent {{ $currentReport['label'] ?? 'Report' }} History
    </h2>

    <div class="w-full rounded-lg border border-bgray-200 bg-white px-[24px] py-[20px] shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex flex-col space-y-5">
            <div class="table-content w-full overflow-x-auto">
                <table class="w-full text-left" id="daily-work-report-history-table">
                    <thead>
                        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                            <th class="px-4 py-4 whitespace-nowrap min-w-[200px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Employee</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[130px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Report date</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[120px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span></th>
                            <th class="px-4 py-4 whitespace-nowrap min-w-[160px]"><span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Generated at</span></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                        @forelse ($reports as $report)
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400 hover:bg-bgray-100 dark:hover:bg-darkblack-500">
                                <td class="px-4 py-4">
                                    <span class="block text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $report->user?->name ?? 'Deleted employee' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                    {{ $report->report_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-bgray-700 dark:text-bgray-300 font-medium whitespace-nowrap">
                                    @appDateTime($report->generated_at)
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-bgray-600 dark:text-bgray-300">
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

