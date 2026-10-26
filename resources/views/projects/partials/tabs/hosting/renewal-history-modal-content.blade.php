@if ($renewals->isEmpty())
    <div class="py-12 text-center" data-project-hosting-renewal-history-empty>
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
            No renewal history available.
        </h5>
        <p class="mt-1 text-xs text-bgray-700 dark:text-bgray-300">
            No renewal transactions have been recorded for this hosting record yet.
        </p>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-xs">
            <thead>
                <tr class="border-b border-bgray-200 bg-bgray-50/70 text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500/50 dark:text-bgray-300">
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Renewal Date</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Previous Period</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">New Period</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Billing</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Reference / Notes</th>
                    <th scope="col" class="py-3 px-4 font-semibold uppercase tracking-wider">Renewed By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400">
                @foreach ($renewals as $renewal)
                    <tr class="transition hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
                        <!-- Renewal Date & Created Date -->
                        <td class="py-3.5 px-4 align-top">
                            <span class="font-semibold text-bgray-900 dark:text-white">
                                {{ $renewal->renewal_date?->format('d M Y') ?? '—' }}
                            </span>
                            @if ($renewal->created_at)
                                <div class="mt-0.5 text-[11px] text-bgray-700 dark:text-bgray-300">
                                    Logged: {{ $renewal->created_at->format('d M Y, h:i A') }}
                                </div>
                            @endif
                        </td>

                        <!-- Previous Period -->
                        <td class="py-3.5 px-4 align-top text-bgray-700 dark:text-bgray-300">
                            <div>
                                <span class="text-bgray-700 dark:text-bgray-300">Start:</span>
                                {{ $renewal->previous_start_date?->format('d M Y') ?? '—' }}
                            </div>
                            <div class="mt-0.5">
                                <span class="text-bgray-700 dark:text-bgray-300">Expiry:</span>
                                <span class="font-medium text-bgray-800 dark:text-bgray-300">
                                    {{ $renewal->previous_expiry_date?->format('d M Y') ?? '—' }}
                                </span>
                            </div>
                        </td>

                        <!-- New Period -->
                        <td class="py-3.5 px-4 align-top text-bgray-700 dark:text-bgray-300">
                            <div>
                                <span class="text-bgray-700 dark:text-bgray-300">Start:</span>
                                {{ $renewal->new_start_date?->format('d M Y') ?? '—' }}
                            </div>
                            <div class="mt-0.5">
                                <span class="text-bgray-700 dark:text-bgray-300">Expiry:</span>
                                <span class="font-semibold text-success-500 dark:text-success-400">
                                    {{ $renewal->new_expiry_date?->format('d M Y') ?? '—' }}
                                </span>
                            </div>
                        </td>

                        <!-- Billing -->
                        <td class="py-3.5 px-4 align-top text-bgray-800 dark:text-bgray-300">
                            <div class="font-semibold text-bgray-900 dark:text-white">
                                @if ($renewal->billing_amount !== null)
                                    {{ $renewal->currency_code ? $renewal->currency_code . ' ' : '' }}{{ number_format((float) $renewal->billing_amount, 2) }}
                                @else
                                    —
                                @endif
                            </div>
                            @if ($renewal->billingCycle)
                                <div class="mt-0.5 text-[11px] text-bgray-700 dark:text-bgray-300">
                                    {{ $renewal->billingCycle->name }}
                                </div>
                            @endif
                        </td>

                        <!-- Reference / Notes -->
                        <td class="py-3.5 px-4 align-top max-w-[220px]">
                            @if ($renewal->reference)
                                <div class="inline-block rounded bg-bgray-100 px-2 py-0.5 text-[11px] font-mono font-medium text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-300">
                                    {{ $renewal->reference }}
                                </div>
                            @endif
                            @if ($renewal->notes)
                                <div class="mt-1 line-clamp-3 text-xs text-bgray-600 dark:text-bgray-300" title="{{ $renewal->notes }}">
                                    {{ $renewal->notes }}
                                </div>
                            @endif
                            @if (!$renewal->reference && !$renewal->notes)
                                <span class="text-bgray-400">—</span>
                            @endif
                        </td>

                        <!-- Renewed By -->
                        <td class="py-3.5 px-4 align-top text-bgray-700 dark:text-bgray-300">
                            @if ($renewal->renewedBy)
                                <span class="font-medium text-bgray-900 dark:text-white">
                                    {{ $renewal->renewedBy->name }}
                                </span>
                            @else
                                <span class="text-bgray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
