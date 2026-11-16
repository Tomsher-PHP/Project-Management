@forelse ($items as $item)
    @php
        $count = (int) ($item['count'] ?? 0);
        $percentage = $totalProjects > 0 ? (int) round(($count / $totalProjects) * 100) : 0;
        $barWidth = min(100, $percentage);
        $color = $item['color'] ?? '#3B82F6';
    @endphp
    <div class="space-y-1.5" data-service-type-id="{{ $item['id'] ?? 'none' }}">
        <div class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 min-w-0">
                <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: {{ $color }};"></span>
                <span class="font-semibold text-bgray-900 dark:text-white truncate">{{ $item['name'] }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="font-bold text-bgray-900 dark:text-white">{{ number_format($count) }}</span>
                <span class="text-[11px] text-bgray-700 dark:text-bgray-300">({{ $percentage }}%)</span>
            </div>
        </div>
        <div class="h-2 w-full rounded-full bg-bgray-100 dark:bg-darkblack-500 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700 ease-out" style="width: {{ $barWidth }}%; background-color: {{ $color }};"></div>
        </div>
    </div>
@empty
@endforelse
