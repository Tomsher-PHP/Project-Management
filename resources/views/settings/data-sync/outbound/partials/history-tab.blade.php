<div class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <p class="text-xs text-bgray-600 dark:text-bgray-400 -mt-2">
        Monitor outbound sync execution logs, payloads, HTTP responses, and delivery status.
    </p>

    <!-- Table Content -->
    <div class="table-content w-full overflow-x-auto">
        <table class="w-full relative">
            <thead>
                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                    <th class="pr-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                    </th>
                    <th class="px-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Connection</span>
                    </th>
                    <th class="px-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Module</span>
                    </th>
                    <th class="px-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Operation</span>
                    </th>
                    <th class="px-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">HTTP Status</span>
                    </th>
                    <th class="px-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                    </th>
                    <th class="px-4 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Date</span>
                    </th>
                    <th class="pl-4 py-5 whitespace-nowrap text-right">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                @php
                    $startNumber = isset($histories) && method_exists($histories, 'currentPage') ? ($histories->currentPage() - 1) * $histories->perPage() : 0;
                @endphp
                @forelse ($histories ?? [] as $history)
                    @php
                        $api = $history->outboundApi;
                        $connection = $api?->connection;
                        $moduleName = $api ? ($modules[$api->module] ?? ucfirst($api->module)) : '—';
                        $operationName = $api ? ($operations[$api->operation] ?? ucfirst($api->operation)) : '—';
                    @endphp
                    <tr data-history-id="{{ $history->id }}" class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                        <td class="pr-4 py-5 whitespace-nowrap">
                            <span class="text-sm font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                        </td>
                        <td class="px-4 py-5 whitespace-nowrap">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-sm font-semibold text-bgray-900 dark:text-white">
                                    {{ $connection?->name ?? '—' }}
                                </span>
                                @if ($api && $api->endpoint)
                                    <span class="text-xs font-mono text-bgray-500 dark:text-bgray-400 truncate max-w-xs">
                                        {{ $api->method }} {{ $api->endpoint }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-5 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-md bg-bgray-100 px-2.5 py-1 text-xs font-semibold text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-200">
                                {{ $moduleName }}
                            </span>
                        </td>
                        <td class="px-4 py-5 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ $operationName }}
                            </span>
                        </td>
                        <td class="px-4 py-5 whitespace-nowrap">
                            @if ($history->response_code !== null)
                                @php
                                    $code = $history->response_code;
                                    $codeBadgeClass = 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-400';
                                    if ($code >= 200 && $code < 300) {
                                        $codeBadgeClass = 'bg-success-50 text-success-400 dark:bg-success-900/30 dark:text-success-300';
                                    } elseif ($code >= 400) {
                                        $codeBadgeClass = 'bg-error-50 text-error-400 dark:bg-error-900/30 dark:text-error-300';
                                    }
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold font-mono {{ $codeBadgeClass }}">
                                    {{ $code }}
                                </span>
                            @else
                                <span class="text-xs text-bgray-500 dark:text-bgray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 whitespace-nowrap">
                            @if ($history->status === 'success')
                                <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-bold text-success-400 dark:bg-success-900/30 dark:text-success-300">
                                    Success
                                </span>
                            @elseif ($history->status === 'failed')
                                <span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-bold text-error-400 dark:bg-error-900/30 dark:text-error-300">
                                    Failed
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-500 dark:bg-amber-900/30 dark:text-amber-400">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-5 whitespace-nowrap">
                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-300">
                                {{ $history->created_at?->format('M d, Y H:i:s') ?? '—' }}
                            </span>
                        </td>
                        <td class="pl-4 py-5 whitespace-nowrap text-right">
                            <button type="button" class="view-history-btn inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 text-bgray-600 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-success-300" data-id="{{ $history->id }}" data-url="{{ route('settings.data-sync.outbound.history.show', $history) }}" title="View History Details">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-bgray-100 dark:bg-darkblack-500">
                                    <svg class="h-6 w-6 text-bgray-400 dark:text-bgray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-base font-semibold text-bgray-900 dark:text-white">No Outbound History Found</h4>
                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                                    @if (request()->hasAny(['connection_id', 'module', 'operation', 'status', 'date_from', 'date_to', 'start_date', 'end_date', 'search']))
                                        No history records match the selected filter criteria.
                                    @else
                                        Outbound sync execution logs, payloads, and delivery status will appear here once executed.
                                    @endif
                                </p>
                                @if (request()->hasAny(['connection_id', 'module', 'operation', 'status', 'date_from', 'date_to', 'start_date', 'end_date', 'search']))
                                    <a href="{{ route('settings.data-sync.outbound.history') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-bgray-300 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200">
                                        Clear Filters
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if (isset($histories) && method_exists($histories, 'hasPages') && $histories->hasPages())
        <div class="pt-4">
            {{ $histories->links() }}
        </div>
    @endif
</div>
