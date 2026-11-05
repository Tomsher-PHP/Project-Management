@php
    use App\Models\Bulletin;
@endphp

<div class="overflow-hidden rounded-[24px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
    <div class="overflow-x-auto">
        <table class="min-w-[1120px] w-full border-separate border-spacing-0">
            <thead class="bg-bgray-50/80 dark:bg-darkblack-500">
                <tr>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left text-sm font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-50">#</th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="title" label="Title" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="bulletin_category_id" label="Category" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="priority" label="Priority" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="audience_type" label="Audience" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="start_date" label="Start Date" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="end_date" label="End Date" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left dark:border-darkblack-400"><x-sorting.sortable-column column="status" label="Status" /></th>
                    <th class="border-b border-bgray-200 px-4 py-4 text-left text-base font-medium text-bgray-600 dark:border-darkblack-400 dark:text-bgray-50">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-darkblack-600">
                @forelse ($bulletins as $bulletin)
                    @php
                        $priorityClass = match ($bulletin->priority) {
                            Bulletin::PRIORITY_URGENT => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-300',
                            Bulletin::PRIORITY_IMPORTANT => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                            default => 'bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300',
                        };
                        $statusClass = match ($bulletin->status) {
                            Bulletin::STATUS_PUBLISHED => 'bg-success-50 text-success-500 dark:bg-success-300/15 dark:text-success-300',
                            Bulletin::STATUS_DRAFT => 'bg-bgray-100 text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300',
                            default => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
                        };
                    @endphp
                    <tr class="transition hover:bg-bgray-50/60 dark:hover:bg-darkblack-500/60">
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">{{ $bulletins->firstItem() + $loop->index }}</td>
                        <td class="max-w-[260px] border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            <button type="button" class="block max-w-full truncate text-left text-sm font-semibold text-bgray-900 hover:text-success-400 dark:text-white" data-bulletin-view data-url="{{ route('bulletins.show', $bulletin) }}" title="{{ $bulletin->title }}">
                                {{ $bulletin->title }}
                            </button>
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            <span class="inline-flex items-center gap-2 text-sm text-bgray-700 dark:text-bgray-300">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $bulletin->category?->color ?: '#94a3b8' }}"></span>
                                {{ $bulletin->category?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClass }}">{{ Bulletin::priorityOptions()[$bulletin->priority] ?? 'Unknown' }}</span></td>
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                            {{ Bulletin::audienceOptions()[$bulletin->audience_type] ?? 'Unknown' }}
                            @if ($bulletin->audience_type !== Bulletin::AUDIENCE_ALL)
                                <span class="block text-xs text-bgray-500 dark:text-bgray-400">{{ $bulletin->allocations_count }} recipient(s)</span>
                            @endif
                        </td>
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">{{ $bulletin->start_date?->format('d M Y') ?? 'Immediately' }}</td>
                        <td class="border-b border-bgray-100 px-4 py-4 text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">{{ $bulletin->end_date?->format('d M Y') ?? 'No expiry' }}</td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ Bulletin::statusOptions()[$bulletin->status] ?? 'Unknown' }}</span></td>
                        <td class="border-b border-bgray-100 px-4 py-4 dark:border-darkblack-400">
                            <div class="flex items-center gap-1.5">
                                <button type="button" data-bulletin-view data-url="{{ route('bulletins.show', $bulletin) }}" class="rounded-md p-2 text-bgray-600 transition hover:bg-bgray-100 hover:text-success-400 dark:text-bgray-300 dark:hover:bg-darkblack-400" title="View bulletin" aria-label="View bulletin">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                </button>
                                @can('bulletin.edit')
                                    <button type="button" data-bulletin-edit data-url="{{ route('bulletins.edit', $bulletin) }}" class="rounded-md p-2 text-bgray-600 transition hover:bg-bgray-100 hover:text-blue-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" title="Edit bulletin" aria-label="Edit bulletin">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 013 3L8 18l-4 1 1-4L16.5 3.5z"/></svg>
                                    </button>
                                @endcan
                                @can('bulletin.status_change')
                                    @if ($bulletin->status === Bulletin::STATUS_DRAFT)
                                        <button type="button" data-bulletin-status data-url="{{ route('bulletins.status', $bulletin) }}" data-status="{{ Bulletin::STATUS_PUBLISHED }}" data-action="publish" class="rounded-md p-2 text-bgray-600 transition hover:bg-success-50 hover:text-success-500 dark:text-bgray-300 dark:hover:bg-success-300/10" title="Publish bulletin" aria-label="Publish bulletin">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l4 4L19 6"/></svg>
                                        </button>
                                    @elseif ($bulletin->status === Bulletin::STATUS_PUBLISHED)
                                        <button type="button" data-bulletin-status data-url="{{ route('bulletins.status', $bulletin) }}" data-status="{{ Bulletin::STATUS_ARCHIVED }}" data-action="archive" class="rounded-md p-2 text-bgray-600 transition hover:bg-amber-50 hover:text-amber-600 dark:text-bgray-300 dark:hover:bg-amber-300/10" title="Archive bulletin" aria-label="Archive bulletin">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v13H4zM3 4h18v3H3zM9 11h6"/></svg>
                                        </button>
                                    @endif
                                @endcan
                                @can('bulletin.delete')
                                    <button type="button" data-bulletin-delete data-url="{{ route('bulletins.destroy', $bulletin) }}" data-title="{{ $bulletin->title }}" class="rounded-md p-2 text-bgray-600 transition hover:bg-red-50 hover:text-red-500 dark:text-bgray-300 dark:hover:bg-red-500/10" title="Delete bulletin" aria-label="Delete bulletin">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-no-data col-span="9" message="No bulletins found." sub-message="There are no bulletins to display for the current filters." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
