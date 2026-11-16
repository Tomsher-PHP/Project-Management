@php
    $depth = $depth ?? 1;
    // Calculate indentation: depth 1 = pl-10 (2.5rem), depth 2 = pl-16 (4rem), depth 3 = pl-22, etc.
    $paddingLeftPx = 16 + ($depth * 24);
@endphp

@forelse ($children as $child)
    @php
        $hasSubChildren = !empty($child['child_projects_count']) && $child['child_projects_count'] > 0;
    @endphp
    <tr class="child-project-row bg-bgray-50/60 transition-colors hover:bg-bgray-100/70 dark:bg-darkblack-500/30 dark:hover:bg-darkblack-500/60"
        data-child-row-id="{{ $child['id'] }}"
        data-parent-id="{{ $parentProject->id }}"
        data-depth="{{ $depth }}">
        <!-- 1. Child Project Name -->
        <td class="py-3 px-4" style="padding-left: {{ $paddingLeftPx }}px;">
            <div class="flex items-center gap-2">
                @if ($hasSubChildren)
                    <button type="button"
                            class="inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-md text-bgray-500 hover:bg-bgray-200 hover:text-bgray-900 dark:text-bgray-400 dark:hover:bg-darkblack-500 dark:hover:text-white transition-all duration-200"
                            data-child-project-toggle="{{ $child['id'] }}"
                            data-depth="{{ $depth + 1 }}"
                            aria-expanded="false"
                            title="Toggle sub-child projects">
                        <svg class="h-3.5 w-3.5 transform transition-transform duration-200" data-toggle-chevron viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @else
                    <span class="inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-bgray-400 dark:text-bgray-500">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif
                <div>
                    <a href="{{ $child['url'] }}" class="font-medium text-bgray-800 transition hover:text-success-400 hover:underline dark:text-bgray-300 dark:hover:text-success-300">
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
                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium border" style="border-color: {{ $st['color'] }}40; color: {{ $st['color'] }}; background-color: {{ $st['color'] }}15;">
                            {{ $st['name'] }}
                        </span>
                    @endforeach
                </div>
            @else
                <span class="text-bgray-400 text-xs">—</span>
            @endif
        </td>

        <!-- 3. Estimate -->
        <td class="py-3 px-4 font-semibold text-bgray-700 dark:text-bgray-300">
            {{ $child['estimate_formatted'] }}
        </td>

        <!-- 4. Spent -->
        <td class="py-3 px-4 font-semibold {{ $child['spent_seconds'] > $child['estimate_seconds'] && $child['estimate_seconds'] > 0 ? 'text-red-500' : 'text-bgray-700 dark:text-bgray-300' }}">
            {{ $child['spent_formatted'] }}
        </td>

        <!-- 5. Child projects column -->
        <td class="py-3 px-4 text-center">
            @if ($hasSubChildren)
                <button type="button"
                        class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-600 hover:bg-blue-100 transition dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                        data-child-project-toggle="{{ $child['id'] }}"
                        data-depth="{{ $depth + 1 }}"
                        title="Click to view sub-child projects">
                    <span>{{ $child['child_projects_count'] }}</span>
                    <span class="text-[10px] font-normal text-blue-500/80 dark:text-blue-400/80">{{ $child['child_projects_count'] === 1 ? 'child' : 'children' }}</span>
                </button>
            @else
                <span class="text-xs text-bgray-400">—</span>
            @endif
        </td>
    </tr>
@empty
    <tr class="child-project-row bg-bgray-50/40 dark:bg-darkblack-500/20" data-parent-id="{{ $parentProject->id }}" data-depth="{{ $depth }}">
        <td colspan="5" class="py-3 px-4 text-xs italic text-bgray-700 dark:text-bgray-300" style="padding-left: {{ $paddingLeftPx }}px;">
            No child projects found for this project.
        </td>
    </tr>
@endforelse
