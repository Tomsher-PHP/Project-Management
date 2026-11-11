@if ($hasMultipleTimelines)
    <tr data-search-details-for="{{ $project->id }}">
        <td colspan="{{ $visibleColumnCount }}" class="p-0">
            <div id="project-timelines-{{ $project->id }}" data-timeline-panel="{{ $project->id }}" class="grid grid-rows-[0fr] opacity-0 transition-all duration-200 ease-in-out" aria-hidden="true">
                <div class="min-h-0 overflow-hidden">
                    <div class="mx-4 mb-4 ml-16 mt-3 overflow-hidden rounded-lg border border-blue-200 bg-white shadow-sm dark:border-darkblack-300 dark:bg-darkblack-600">
                        <div class="overflow-x-auto">
                            <table class="w-full {{ $canViewCustomerColumns ? 'min-w-[1380px]' : 'min-w-[1000px]' }}">
                                <thead class="bg-blue-50/80 dark:bg-darkblack-500">
                                    <tr class="border-b border-blue-200 dark:border-darkblack-400">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Timeline Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Start Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">End Date</th>
                                        @if ($canViewCustomerColumns)
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Cust End Date</th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Estimated</th>
                                        @if ($canViewCustomerColumns)
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Cust Estimate</th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Actual</th>
                                        <th class="min-w-[260px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Progress</th>
                                        @if ($canViewCustomerColumns)
                                            <th class="min-w-[260px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-bgray-700 dark:text-bgray-300">Cust Progress</th>
                                        @endif
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-blue-100 dark:divide-darkblack-400">
                                    @foreach ($eligibleTimelines as $timeline)
                                        @php
                                            $timelineEstimatedSeconds = (int) $timeline->report_estimated_seconds;
                                            $timelineCustomerEstimatedSeconds = (int) ($timeline->report_customer_estimated_seconds ?? 0);
                                            $timelineActualSeconds = (int) $timeline->report_actual_seconds;
                                            $timelineProgress = (float) $timeline->report_progress_percentage;
                                            $timelineCustomerProgress = (float) ($timeline->report_customer_progress_percentage ?? 0);
                                            $timelineProgressBarWidth = min($timelineProgress, 100);
                                            $timelineCustomerProgressBarWidth = min($timelineCustomerProgress, 100);
                                            $timelineActualClasses = $timelineActualSeconds <= $timelineEstimatedSeconds ? 'text-success-400 dark:text-success-300' : 'text-red-500 dark:text-red-400';
                                            [$timelineProgressBarClass, $timelineProgressTextClass] = $resolveProgressClasses($timelineEstimatedSeconds, $timelineProgress);
                                            [$timelineCustomerProgressBarClass, $timelineCustomerProgressTextClass] = $resolveProgressClasses($timelineCustomerEstimatedSeconds, $timelineCustomerProgress);
                                            $isActiveTimeline = $timeline->status === \App\Models\ProjectTimeline::STATUS_ACTIVE;
                                        @endphp

                                        <tr class="hover:bg-gray-50 dark:hover:bg-darkblack-500" {!! $isActiveTimeline ? 'style="border-left-width: 4px; border-left-color: rgb(34, 197, 94);"' : '' !!}>
                                            <td class="px-4 py-3 text-sm font-semibold text-bgray-900 dark:text-bgray-300">{{ $timeline->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $isActiveTimeline ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200' }}">
                                                    {{ $timeline->report_status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-300">{{ $timeline->start_date?->format($globalDateFormat) ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-300">{{ $timeline->end_date?->format($globalDateFormat) ?? '-' }}</td>
                                            @if ($canViewCustomerColumns)
                                                <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-300">{{ $timeline->customer_end_date?->format($globalDateFormat) ?? '-' }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-300">{{ formatSecondsToHoursMinutes($timelineEstimatedSeconds) }}</td>
                                            @if ($canViewCustomerColumns)
                                                <td class="px-4 py-3 text-sm whitespace-nowrap text-bgray-700 dark:text-bgray-300">{{ formatSecondsToHoursMinutes($timelineCustomerEstimatedSeconds) }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap {{ $timelineActualClasses }}">{{ formatSecondsToHoursMinutes($timelineActualSeconds) }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-400">
                                                        <div class="h-2.5 rounded-full transition-all duration-300 {{ $timelineProgressBarClass }}" style="width: {{ $timelineProgressBarWidth }}%"></div>
                                                    </div>
                                                    <span class="min-w-[56px] text-xs font-semibold {{ $timelineProgressTextClass }}">
                                                        {{ rtrim(rtrim(number_format($timelineProgress, 2, '.', ''), '0'), '.') }}%
                                                    </span>
                                                </div>
                                            </td>
                                            @if ($canViewCustomerColumns)
                                                <td class="px-4 py-3 text-sm">
                                                    <div class="flex items-center gap-3">
                                                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-darkblack-400">
                                                            <div class="h-2.5 rounded-full transition-all duration-300 {{ $timelineCustomerProgressBarClass }}" style="width: {{ $timelineCustomerProgressBarWidth }}%"></div>
                                                        </div>
                                                        <span class="min-w-[56px] text-xs font-semibold {{ $timelineCustomerProgressTextClass }}">
                                                            {{ rtrim(rtrim(number_format($timelineCustomerProgress, 2, '.', ''), '0'), '.') }}%
                                                        </span>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@endif
