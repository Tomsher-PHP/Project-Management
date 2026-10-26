@forelse ($records as $item)
    @php
        $isCompany = !empty($item['is_company']);
        $statusColor = $item['status_color'] ?: '#6B7280';
        $statusName = $item['status_name'] ?? '—';
    @endphp
    <tr class="transition-colors hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
        <!-- 1. Project name (with code as sub title) -->
        <td class="py-3 px-4">
            @if (!empty($item['project_id']))
                <a href="{{ $item['project_url'] ?? '#' }}" class="font-semibold text-bgray-900 transition hover:text-success-400 hover:underline dark:text-white dark:hover:text-success-300">
                    {{ $item['project_name'] }}
                </a>
            @else
                <div class="font-semibold text-bgray-900 dark:text-white">{{ $item['project_name'] }}</div>
            @endif
            @if (!empty($item['project_code']))
                <div class="text-[11px] text-bgray-700 dark:text-bgray-300">{{ $item['project_code'] }}</div>
            @endif
        </td>

        <!-- 2. Hosting / Service -->
        <td class="py-3 px-4">
            <div class="text-xs font-semibold text-bgray-900 dark:text-white">
                {{ $item['name'] }}
            </div>
            @if (!empty($item['domain']))
                <div class="mt-0.5 text-[11px] text-bgray-600 dark:text-bgray-300">
                    <a href="{{ str_starts_with($item['domain'], 'http') ? $item['domain'] : 'https://' . $item['domain'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 hover:text-success-400">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span>{{ $item['domain'] }}</span>
                    </a>
                </div>
            @endif
        </td>

        <!-- 3. Provider -->
        <td class="py-3 px-4 font-medium text-bgray-800 dark:text-bgray-300">
            {{ $item['provider_name'] ?? '—' }}
        </td>

        <!-- 4. Service Type -->
        <td class="py-3 px-4 text-bgray-700 dark:text-bgray-300">
            {{ $item['service_type_name'] ?? '—' }}
        </td>

        <!-- 5. Ownership -->
        <td class="py-3 px-4">
            @if ($isCompany)
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-bgray-800 dark:text-bgray-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                    {{ $item['ownership_label'] }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-bgray-800 dark:text-bgray-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>
                    {{ $item['ownership_label'] }}
                </span>
            @endif
        </td>

        <!-- 6. Status -->
        <td class="py-3 px-4">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
                <span>{{ $statusName }}</span>
            </span>
        </td>

        <!-- 7. Expiry -->
        <td class="py-3 px-4">
            <div class="text-xs font-semibold text-bgray-900 dark:text-white">
                {{ $item['formatted_expiry_date'] ?? ($item['expiry_date'] ?? '—') }}
            </div>
            <div class="mt-1">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] {{ $item['expiry_badge_class'] }}">
                    {{ $item['expiry_badge_text'] }}
                </span>
            </div>
        </td>
    </tr>
@empty
@endforelse
