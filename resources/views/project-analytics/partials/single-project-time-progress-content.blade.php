@php
    $formatDuration = $formatDuration ?? function (?int $seconds): string {
        $seconds = max(0, (int) $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%02dh : %02dm', $hours, $minutes);
    };
@endphp

@foreach ($timelineProgressbars as $bar)
    @php
        $workedSeconds = (int) $bar->get('worked_seconds', 0);
        $estimatedSeconds = (int) $bar->get('estimated_seconds', 0);
        $customerEstimateSeconds = (int) $bar->get('customer_estimate_seconds', 0);
        $workedPercent = (float) $bar->get('worked_percent', 0);
        $estimatedPercent = (float) $bar->get('estimated_percent', 0);
        $customerEstimatePercent = (float) $bar->get('customer_estimate_percent', 0);
        $differencePercentage = $bar->get('difference_percentage');
        $isExceeded = (bool) $bar->get('is_exceeded', false);
        $isActive = (bool) $bar->get('is_active', false);
        $timelineName = $bar->get('timeline_name', 'Task Progress');
    @endphp

    <div class="flex flex-col gap-5 rounded-[8px] border border-bgray-200 p-4 dark:border-darkblack-400 lg:flex-row lg:items-center lg:gap-8 {{ $isActive ? 'bg-white dark:bg-black' : '' }}" {!! $isActive ? 'style="border-left-width: 4px; border-left-color: rgb(34, 197, 94);"' : '' !!}>

        <!-- Left: Task Title / Subtitle -->
        <div class="min-w-[140px] shrink-0">
            <h5 class="flex items-center gap-2 text-sm font-bold text-bgray-900 dark:text-white">
                {{ $timelineName }}
                @if ($isActive)
                    <span class="rounded bg-primary-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-success-400">Active</span>
                @endif
            </h5>
        </div>

        <!-- Center & Right: 3 Slim Progress Bars & Guide Lines -->
        <div class="w-full flex-1">

            <!-- Bars Container -->
            <div class="relative space-y-1.5 py-1">

                <!-- Dynamic Threshold Reference Markers -->
                @if (isset($estimatedPercent) && $estimatedPercent < 100)
                    <div class="pointer-events-none absolute top-0 bottom-0 z-10 w-px border-r border-dashed border-sky-400/70" style="left: {{ min($estimatedPercent, 100) }}%;" title="Estimated limit"></div>
                @endif
                @if (isset($customerEstimatePercent) && $customerEstimatePercent < 100)
                    <div class="pointer-events-none absolute top-0 bottom-0 z-10 w-px bg-purple-400/80" style="left: {{ min($customerEstimatePercent, 100) }}%;" title="Customer limit"></div>
                @endif

                <!-- Bar 1: Spent Hours -->
                <div class="relative h-2 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                    @php
                        // Spent Color Logic based on limits
                        $spentGradient = 'bg-emerald-500';
                        if (isset($customerEstimateSeconds) && $customerEstimateSeconds > 0 && $workedSeconds > $customerEstimateSeconds) {
                            $spentGradient = 'bg-gradient-to-r from-red-500 via-rose-500 to-amber-500';
                        } elseif ($workedSeconds > $estimatedSeconds) {
                            $spentGradient = 'bg-gradient-to-r from-amber-500 to-orange-500';
                        }
                    @endphp
                    <div class="h-full rounded-full transition-all duration-500 {{ $spentGradient }}" style="width: {{ min($workedPercent, 100) }}%;"></div>
                </div>

                <!-- Bar 2: Internal Estimated Hours -->
                <div class="relative h-2 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                    <div class="h-full rounded-full bg-sky-500 transition-all duration-500" style="width: {{ min($estimatedPercent, 100) }}%;"></div>
                </div>

                <!-- Bar 3: Customer Estimated Hours -->
                @if ($customerEstimateSeconds > 0 && auth()->user()?->can('project.customer_end_date'))
                    <div class="relative h-2 w-full overflow-hidden rounded-full bg-bgray-100 dark:bg-darkblack-500">
                        <div class="h-full rounded-full bg-purple-500 transition-all duration-500" style="width: {{ min($customerEstimatePercent, 100) }}%;"></div>
                    </div>
                @endif
            </div>

            <!-- Bottom Metadata Legend with Dots -->
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-bgray-600 dark:text-darkblack-200">
                <!-- Spent Legend -->
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $workedSeconds > $estimatedSeconds ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                    <span class="text-bgray-700 dark:text-bgray-300">{{ __('label.project.spent') }}:</span>
                    <span class="font-semibold text-bgray-900 dark:text-white">{{ $formatDuration($workedSeconds) }}</span>
                </div>

                <!-- Estimated Legend -->
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    <span class="text-bgray-700 dark:text-bgray-300">{{ __('label.project.estimated') }}:</span>
                    <span class="font-semibold text-bgray-900 dark:text-white">{{ $formatDuration($estimatedSeconds) }}</span>
                </div>

                <!-- Customer Estimated Legend -->
                @if ($customerEstimateSeconds > 0 && auth()->user()?->can('project.customer_end_date'))
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                        <span class="text-bgray-700 dark:text-bgray-300">{{ __('label.project.customer_estimated') }}:</span>
                        <span class="font-semibold text-bgray-900 dark:text-white">{{ $formatDuration($customerEstimateSeconds) }}</span>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endforeach
