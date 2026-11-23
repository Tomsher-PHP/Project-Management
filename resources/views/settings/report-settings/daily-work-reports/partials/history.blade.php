<!-- HISTORY TAB -->
<section id="report-panel-history" data-report-panel="history" class="report-panel hidden px-4 py-2">
    @php
        $deliveryCards = [
            [
                'label' => 'Queued deliveries',
                'count' => $deliveryCounts['queued'] ?? 0,
                'key' => 'queued',
                'color' => 'text-blue-500 dark:text-blue-400',
                'badge' => 'bg-blue-50 text-blue-500 dark:bg-darkblack-500 dark:text-blue-400',
                'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ],
            [
                'label' => 'Sent deliveries',
                'count' => $deliveryCounts['sent'] ?? 0,
                'key' => 'sent',
                'color' => 'text-success-400',
                'badge' => 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300',
                'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ],
            [
                'label' => 'Failed deliveries',
                'count' => $deliveryCounts['failed'] ?? 0,
                'key' => 'failed',
                'color' => 'text-error-300',
                'badge' => 'bg-error-50 text-error-300 dark:bg-darkblack-500 dark:text-error-300',
                'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ],
            [
                'label' => 'Partial deliveries',
                'count' => $deliveryCounts['partial'] ?? 0,
                'key' => 'partial',
                'color' => 'text-amber-500',
                'badge' => 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400',
                'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
            ],
        ];
    @endphp

    <div class="mb-4 grid grid-cols-4 gap-3">
        @foreach ($deliveryCards as $card)
            <div class="flex items-center justify-between rounded-[8px] border border-bgray-200 bg-white px-3.5 py-2 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                <div class="min-w-0 pr-2">
                    <p class="truncate text-[11px] font-medium tracking-wide text-bgray-700 dark:text-bgray-300">
                        {{ $card['label'] }}
                    </p>
                    <div class="mt-0.5 flex items-baseline">
                        <span class="text-lg font-bold leading-none {{ $card['color'] }}" data-summary-count="{{ $card['key'] }}">
                            {{ $card['count'] }}
                        </span>
                    </div>
                </div>
                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $card['badge'] }}">
                    {!! $card['icon'] !!}
                </span>
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
