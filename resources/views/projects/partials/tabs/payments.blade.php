<div class="space-y-4 p-4 sm:p-6">
    @if ($payments->isEmpty())
        <div class="rounded-xl border border-dashed border-bgray-300 bg-white px-6 py-12 text-center dark:border-darkblack-400 dark:bg-darkblack-600">
            <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <h3 class="mt-4 text-lg font-medium text-bgray-900 dark:text-white">No Payments Recorded</h3>
            <p class="mt-2 text-sm text-bgray-700 dark:text-bgray-300">There are no payment records for this project yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($payments as $index => $payment)
                <div class="rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h4 class="text-lg font-semibold text-bgray-900 dark:text-white">
                                    {{ $payment->amount ? number_format($payment->amount, 2) : 'Amount not specified' }}
                                </h4>
                                @if ($index === 0)
                                    <span class="text-xs font-bold text-success-300 uppercase tracking-wider">
                                        Latest
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-bgray-600 dark:text-bgray-300">
                                <div class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-bgray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Paid: {{ $payment->paid_date ? $payment->paid_date->format($globalDateFormat ?? 'Y-m-d') : '--' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-bgray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Coverage: {{ $payment->coverage_start_date ? $payment->coverage_start_date->format($globalDateFormat ?? 'Y-m-d') : '--' }} to {{ $payment->coverage_end_date ? $payment->coverage_end_date->format($globalDateFormat ?? 'Y-m-d') : '--' }}</span>
                                </div>
                            </div>

                            @if ($payment->notes)
                                <p class="mt-3 text-sm text-bgray-700 dark:text-bgray-400">{{ $payment->notes }}</p>
                            @endif
                        </div>

                        @if ($index === 0 && ! $project->trashed() && auth()->user()->can('project.add_payment_status'))
                            <div class="sm:self-start">
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-bgray-200 bg-white px-3 py-1.5 text-sm font-medium text-bgray-700 shadow-sm transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-600" data-project-payment-edit data-url="{{ route('projects.updateProjectPaymentStatus', ['project' => $project, 'payment' => $payment]) }}" data-amount="{{ $payment->amount }}" data-paid-date="{{ $payment->paid_date ? $payment->paid_date->format('Y-m-d') : '' }}"
                                    data-coverage-start-date="{{ $payment->coverage_start_date ? $payment->coverage_start_date->format('Y-m-d') : '' }}" data-coverage-end-date="{{ $payment->coverage_end_date ? $payment->coverage_end_date->format('Y-m-d') : '' }}" data-notes="{{ $payment->notes }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Edit Latest
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
