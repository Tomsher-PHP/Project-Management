@forelse ($projects as $item)
    @php
        $serviceTypes = $item['service_types'] ?? [];
        $isSpentExceeded = !empty($item['estimate_seconds']) && $item['estimate_seconds'] > 0 && ($item['spent_seconds'] ?? 0) > $item['estimate_seconds'];
        $spentColorClass = $isSpentExceeded ? 'text-red-500 font-semibold' : 'text-bgray-900 dark:text-white font-semibold';
        $childCount = (int) ($item['child_projects_count'] ?? 0);
    @endphp
    <tr class="parent-project-row transition-colors hover:bg-bgray-50/70 dark:hover:bg-darkblack-500/40" data-parent-row-id="{{ $item['id'] }}">
        <!-- 1. Parent Project Name & Expand Toggle -->
        <td class="py-3 px-4">
            <div class="flex items-center gap-2">
                <button type="button"
                        class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md text-bgray-500 hover:bg-bgray-200 hover:text-bgray-900 dark:text-bgray-400 dark:hover:bg-darkblack-500 dark:hover:text-white transition-all duration-200"
                        data-parent-project-toggle="{{ $item['id'] }}"
                        aria-expanded="false"
                        title="Toggle child projects">
                    <svg class="h-4 w-4 transform transition-transform duration-200" data-toggle-chevron viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div>
                    <a href="{{ $item['url'] ?? '#' }}" class="font-semibold text-bgray-900 transition hover:text-success-400 hover:underline dark:text-white dark:hover:text-success-300">
                        {{ $item['name'] }}
                    </a>
                    @if (!empty($item['project_code']))
                        <span class="ml-1 text-[11px] text-bgray-700 dark:text-bgray-300">({{ $item['project_code'] }})</span>
                    @endif
                </div>
            </div>
        </td>

        <!-- 2. Service -->
        <td class="py-3 px-4">
            <div class="flex flex-wrap gap-1">
                @if (!empty($serviceTypes) && count($serviceTypes) > 0)
                    @foreach ($serviceTypes as $st)
                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium border" style="border-color: {{ $st['color'] ?? '#3B82F6' }}40; color: {{ $st['color'] ?? '#3B82F6' }}; background-color: {{ $st['color'] ?? '#3B82F6' }}15;">
                            {{ $st['name'] }}
                        </span>
                    @endforeach
                @elseif (!empty($item['service_type_names']) && $item['service_type_names'] !== '--')
                    <span class="text-xs text-bgray-700 dark:text-bgray-300">{{ $item['service_type_names'] }}</span>
                @else
                    <span class="text-bgray-400 text-xs">—</span>
                @endif
            </div>
        </td>

        <!-- 3. Estimate -->
        <td class="py-3 px-4 font-semibold text-bgray-900 dark:text-white">
            {{ $item['estimate_formatted'] }}
        </td>

        <!-- 4. Spent -->
        <td class="py-3 px-4 {{ $spentColorClass }}">
            {{ $item['spent_formatted'] }}
        </td>

        <!-- 5. Child projects count -->
        <td class="py-3 px-4 text-center">
            <button type="button"
                    class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600 hover:bg-blue-100 transition dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                    data-parent-project-toggle="{{ $item['id'] }}"
                    title="Click to view child projects">
                <span>{{ $childCount }}</span>
                <span class="text-[11px] font-normal text-blue-500/80 dark:text-blue-400/80">{{ $childCount === 1 ? 'child' : 'children' }}</span>
            </button>
        </td>
    </tr>
@empty
@endforelse
