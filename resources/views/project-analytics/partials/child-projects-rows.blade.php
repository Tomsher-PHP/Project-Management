@forelse ($children as $child)
    <tr class="child-project-row bg-bgray-50/60 transition-colors hover:bg-bgray-100/70 dark:bg-darkblack-500/30 dark:hover:bg-darkblack-500/60" data-parent-id="{{ $parentProject->id }}">
        <!-- 1. Child Project Name -->
        <td class="py-3 px-4 pl-10">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-bgray-400 dark:text-bgray-500">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </span>
                <div>
                    <a href="{{ $child['url'] }}" class="font-medium text-bgray-800 transition hover:text-success-400 hover:underline dark:text-bgray-100 dark:hover:text-success-300">
                        {{ $child['name'] }}
                    </a>
                    @if (!empty($child['project_code']))
                        <span class="ml-1 text-[11px] text-bgray-700 dark:text-bgray-300 font-normal">({{ $child['project_code'] }})</span>
                    @endif
                </div>
            </div>
        </td>

        <!-- 2. Service -->
        <td class="py-3 px-4">
            @if (!empty($child['service_types']))
                <div class="flex flex-wrap gap-1">
                    @foreach ($child['service_types'] as $st)
                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium border"
                              style="border-color: {{ $st['color'] }}40; color: {{ $st['color'] }}; background-color: {{ $st['color'] }}15;">
                            {{ $st['name'] }}
                        </span>
                    @endforeach
                </div>
            @else
                <span class="text-bgray-400 text-xs">—</span>
            @endif
        </td>

        <!-- 3. Estimate -->
        <td class="py-3 px-4 font-semibold text-bgray-700 dark:text-bgray-200">
            {{ $child['estimate_formatted'] }}
        </td>

        <!-- 4. Spent -->
        <td class="py-3 px-4 font-semibold {{ $child['spent_seconds'] > $child['estimate_seconds'] && $child['estimate_seconds'] > 0 ? 'text-red-500' : 'text-bgray-700 dark:text-bgray-200' }}">
            {{ $child['spent_formatted'] }}
        </td>

        <!-- 5. Child projects column placeholder -->
        <td class="py-3 px-4 text-center text-bgray-400">
            <span class="text-xs">—</span>
        </td>
    </tr>
@empty
    <tr class="child-project-row bg-bgray-50/40 dark:bg-darkblack-500/20" data-parent-id="{{ $parentProject->id }}">
        <td colspan="5" class="py-3 px-4 pl-10 text-xs italic text-bgray-700 dark:text-bgray-300">
            No child projects found for this parent project.
        </td>
    </tr>
@endforelse
