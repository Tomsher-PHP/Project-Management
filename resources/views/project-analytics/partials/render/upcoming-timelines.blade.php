@forelse ($timelines as $item)
    @php
        $daysLeft = (int) ($item['days_remaining'] ?? 0);
        $isSoon = !empty($item['is_expiring_soon']);
        $serviceTypes = $item['service_types'] ?? [];
        $status = $item['status'] ?? 'active';
        $statusLabel = $item['status_label'] ?? 'Active';
        $statusColor = $status === 'active' ? 'bg-success-50 text-success-500 dark:bg-success-500/10' : 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300';
    @endphp
    <tr class="transition-colors hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
        <td class="py-3 px-4">
            <div class="font-semibold text-bgray-900 dark:text-white">{{ $item['project_name'] }}</div>
            <div class="text-[11px] text-bgray-700 dark:text-bgray-300">{{ $item['project_code'] ?: $item['customer_name'] ?? '' }}</div>
        </td>
        <td class="py-3 px-4">
            @if (!empty($serviceTypes) && count($serviceTypes) > 0)
                <div class="flex flex-wrap gap-1">
                    @foreach ($serviceTypes as $st)
                        <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-medium border" style="border-color: {{ $st['color'] ?? '#3B82F6' }}40; color: {{ $st['color'] ?? '#3B82F6' }}; background-color: {{ $st['color'] ?? '#3B82F6' }}10;">
                            {{ $st['name'] }}
                        </span>
                    @endforeach
                </div>
            @elseif (!empty($item['service_type_names']) && $item['service_type_names'] !== '--')
                <span class="text-xs text-bgray-700 dark:text-bgray-300">{{ $item['service_type_names'] }}</span>
            @else
                <span class="text-bgray-400">—</span>
            @endif
        </td>
        <td class="py-3 px-4 font-medium text-bgray-700 dark:text-bgray-300">
            {{ $item['formatted_start_date'] ?? ($item['start_date'] ?? '—') }}
        </td>
        <td class="py-3 px-4 font-semibold text-bgray-900 dark:text-white">
            {{ $item['formatted_end_date'] ?? ($item['end_date'] ?? ($item['formatted_customer_end_date'] ?? ($item['customer_end_date'] ?? '—'))) }}
        </td>
        <td class="py-3 px-4">
            @if ($isSoon)
                <span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-[11px] font-bold text-error-300 dark:bg-error-500/10">{{ $daysLeft }} days left</span>
            @else
                <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">{{ $daysLeft }} days left</span>
            @endif
        </td>
        <td class="py-3 px-4">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusColor }}">
                {{ $statusLabel }}
            </span>
        </td>
    </tr>
@empty
@endforelse
