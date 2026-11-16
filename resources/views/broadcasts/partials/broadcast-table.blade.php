@php
    use App\Models\Broadcast;
@endphp

<div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="overflow-x-auto">
        <table class="min-w-[1040px] w-full border-separate border-spacing-0">
            <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                <tr>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left text-sm font-medium text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">#</th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="title" label="Title" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="broadcast_category_id" label="Category" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="priority" label="Priority" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="audience_type" label="Audience" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="start_date" label="Date" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="status" label="Status" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left text-base font-medium text-bgray-700 dark:border-darkblack-400 dark:text-bgray-50">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-darkblack-600">
                @forelse ($broadcasts as $broadcast)
                    @php
                        $priorityClass = match ($broadcast->priority) {
                            Broadcast::PRIORITY_URGENT => 'bg-error-50 text-error-300',
                            Broadcast::PRIORITY_IMPORTANT => 'bg-yellow-50 text-warning-600',
                            default => 'bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300',
                        };

                        $dateFormat = $globalDateFormat ?? config('constants.date_format', 'd M Y');
                        $timeFormat = $globalTimeFormat ?? config('constants.time_format', 'h:i A');
                        $timezone = $globalTimezone ?? config('app.timezone');
                        $dateDisplay = '—';
                        if ($broadcast->start_date && $broadcast->end_date) {
                            $dateDisplay = $broadcast->start_date->format('Y-m-d') === $broadcast->end_date->format('Y-m-d') ? $broadcast->start_date->format($dateFormat) : $broadcast->start_date->format($dateFormat) . ' - ' . $broadcast->end_date->format($dateFormat);
                        } elseif ($broadcast->start_date) {
                            $dateDisplay = $broadcast->start_date->format($dateFormat);
                        } elseif ($broadcast->end_date) {
                            $dateDisplay = $broadcast->end_date->format($dateFormat);
                        }

                        $statusColors = Broadcast::statusColors();
                        $currentStatusName = Broadcast::statusOptions()[$broadcast->status] ?? 'Unknown';
                        $statusColor = $statusColors[$broadcast->status] ?? '#64748B';
                        $updateStatusUrl = route('broadcasts.status', $broadcast);
                    @endphp
                    <tr class="transition hover:bg-bgray-100 dark:hover:bg-darkblack-500">
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">{{ $broadcasts->firstItem() + $loop->index }}</td>
                        <td class="max-w-[260px] border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            <button type="button" class="block max-w-full truncate text-left text-sm font-semibold text-bgray-900 hover:text-success-400 dark:text-white" data-broadcast-view data-url="{{ route('broadcasts.show', $broadcast) }}" title="{{ $broadcast->title }}">
                                {{ $broadcast->title }}
                            </button>
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            <span class="inline-flex items-center gap-2 text-sm text-bgray-700 dark:text-bgray-300">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $broadcast->category?->color ?: '#94a3b8' }}"></span>
                                {{ $broadcast->category?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClass }}">{{ Broadcast::priorityOptions()[$broadcast->priority] ?? 'Unknown' }}</span></td>
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                            {{ Broadcast::audienceOptions()[$broadcast->audience_type] ?? 'Unknown' }}
                            @if ($broadcast->audience_type !== Broadcast::AUDIENCE_ALL)
                                <span class="block text-xs text-bgray-700 dark:text-bgray-300">{{ $broadcast->allocations_count }} recipient(s)</span>
                            @endif
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">{{ $dateDisplay }}</td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            @can('broadcast.status_change')
                                <div class="relative inline-block" data-broadcast-status-dropdown>
                                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm transition hover:opacity-90" data-broadcast-status-trigger aria-expanded="false" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                        <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
                                        <span data-broadcast-status-label class="truncate">{{ $currentStatusName }}</span>
                                        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="transition-transform duration-200">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <div class="hidden min-w-[150px] overflow-hidden rounded-xl border border-bgray-200 bg-white p-1 shadow-lg ring-1 ring-black/5 dark:border-darkblack-400 dark:bg-darkblack-500 dark:ring-white/10 z-20" data-broadcast-status-menu>
                                        <ul class="max-h-56 overflow-y-auto py-1">
                                            @foreach ($broadcast->allowedStatusOptions() as $statusOptionId => $statusOptionName)
                                                @php
                                                    $isCurrent = (int) $broadcast->status === (int) $statusOptionId;
                                                    $optColor = $statusColors[$statusOptionId] ?? '#64748B';
                                                @endphp
                                                <li>
                                                    <button type="button" class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs font-medium transition hover:bg-bgray-100 dark:hover:bg-darkblack-400 {{ $isCurrent ? 'bg-bgray-100 font-semibold text-bgray-900 dark:bg-darkblack-400 dark:text-white' : 'text-bgray-700 dark:text-bgray-300' }}" data-broadcast-status-option data-status-id="{{ $statusOptionId }}" data-status-name="{{ $statusOptionName }}" data-current-status-id="{{ $broadcast->status }}" data-update-url="{{ $updateStatusUrl }}" data-broadcast-title="{{ $broadcast->title }}">
                                                        <span class="flex items-center gap-2">
                                                            <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $optColor }};"></span>
                                                            <span>{{ $statusOptionName }}</span>
                                                        </span>
                                                        @if ($isCurrent)
                                                            <svg class="h-3.5 w-3.5 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                    <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
                                    {{ $currentStatusName }}
                                </span>
                            @endcan

                            @if ($broadcast->status === Broadcast::STATUS_PUBLISHED && $broadcast->published_at)
                                <span class="mt-1 block text-xs text-bgray-700 dark:text-bgray-300 whitespace-nowrap">
                                    @appDateTime($broadcast->published_at)
                                </span>
                            @endif
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            <div class="flex items-center gap-2">
                                <x-view-button action="javascript:void(0)" data-broadcast-view data-url="{{ route('broadcasts.show', $broadcast) }}" title="View broadcast" aria-label="View broadcast" />
                                @can('broadcast.edit')
                                    <x-edit-button action="javascript:void(0)" data-broadcast-edit data-url="{{ route('broadcasts.edit', $broadcast) }}" title="Edit broadcast" aria-label="Edit broadcast" />
                                @endcan
                                @can('broadcast.delete')
                                    <x-delete-form :action="route('broadcasts.destroy', $broadcast)" confirm-title="Delete Broadcast" confirm-message="The broadcast “{{ $broadcast->title }}” will be permanently deleted." title="Delete broadcast" aria-label="Delete broadcast" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-no-data col-span="8" message="No broadcasts found." sub-message="There are no broadcasts to display for the current filters." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
