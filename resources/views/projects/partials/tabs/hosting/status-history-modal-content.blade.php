@if ($statuses->isEmpty())
    <div class="py-12 text-center" data-project-hosting-status-history-empty>
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
            No status changes recorded.
        </h5>
        <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">
            No status transitions have been recorded for this hosting record yet.
        </p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[680px] text-left text-xs">
            <thead>
                <tr class="border-b border-bgray-200 bg-bgray-50/70 text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500/50 dark:text-bgray-300">
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Date & Time</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Previous Status</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">New Status</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Changed By</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Reason / Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400">
                @foreach ($statuses as $history)
                    <tr class="transition hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
                        <!-- Change Date and Time -->
                        <td class="py-3.5 px-4 align-top">
                            <span class="font-semibold text-bgray-900 dark:text-white">
                                {{ $history->changed_at?->format('d M Y') ?? '—' }}
                            </span>
                            <div class="mt-0.5 text-[11px] text-bgray-700 dark:text-bgray-300">
                                {{ $history->changed_at?->format('h:i A') ?? '' }}
                            </div>
                        </td>

                        <!-- Previous Status -->
                        <td class="py-3.5 px-4 align-top">
                            @if ($history->fromStatus)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" style="background-color: {{ $history->fromStatus->color ? $history->fromStatus->color . '20' : '#f3f4f6' }}; color: {{ $history->fromStatus->color ?? '#374151' }};">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $history->fromStatus->color ?? '#6b7280' }};"></span>
                                    {{ $history->fromStatus->name }}
                                </span>
                            @else
                                <span class="text-xs text-bgray-600 italic">None (Initial)</span>
                            @endif
                        </td>

                        <!-- New Status -->
                        <td class="py-3.5 px-4 align-top">
                            @if ($history->toStatus)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" style="background-color: {{ $history->toStatus->color ? $history->toStatus->color . '20' : '#f3f4f6' }}; color: {{ $history->toStatus->color ?? '#374151' }};">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $history->toStatus->color ?? '#6b7280' }};"></span>
                                    {{ $history->toStatus->name }}
                                </span>
                            @else
                                <span class="text-bgray-400">—</span>
                            @endif
                        </td>

                        <!-- Changed By -->
                        <td class="py-3.5 px-4 align-top text-bgray-700 dark:text-bgray-300">
                            @if ($history->changedBy)
                                <span class="font-medium text-bgray-900 dark:text-white">
                                    {{ $history->changedBy->name }}
                                </span>
                            @else
                                <span class="text-bgray-600">System</span>
                            @endif
                        </td>

                        <!-- Reason / Notes -->
                        <td class="py-3.5 px-4 align-top max-w-[240px]">
                            @if ($history->reason)
                                <div class="font-medium text-bgray-800 dark:text-bgray-300">
                                    {{ $history->reason }}
                                </div>
                            @endif
                            @if ($history->notes)
                                <div class="mt-0.5 text-xs text-bgray-600 dark:text-bgray-300 line-clamp-3" title="{{ $history->notes }}">
                                    {{ $history->notes }}
                                </div>
                            @endif
                            @if (!$history->reason && !$history->notes)
                                <span class="text-bgray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
