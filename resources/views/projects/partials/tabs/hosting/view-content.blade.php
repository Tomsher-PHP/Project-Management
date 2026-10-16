@php
    $ownershipLabel = \App\Models\ProjectHostingRecord::OWNERSHIP_TYPES[$record->ownership_type] ?? ucfirst($record->ownership_type);
    $isCompany = $record->ownership_type === \App\Models\ProjectHostingRecord::OWNERSHIP_COMPANY;
    $dateFormat = config('constants.date_format', 'd-m-Y');
@endphp

<div class="space-y-6">
    <!-- Basic Info Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Hosting / Service</div>
            <div class="mt-1 text-base font-semibold text-bgray-900 dark:text-white">{{ $record->name }}</div>
            @if ($record->domain)
                <div class="mt-1 text-xs text-success-400">
                    <a href="{{ str_starts_with($record->domain, 'http') ? $record->domain : 'https://' . $record->domain }}" target="_blank" rel="noopener noreferrer" class="hover:underline inline-flex items-center gap-1">
                        {{ $record->domain }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Provider & Service Type</div>
            <div class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                {{ $record->hostingProvider?->name ?? '--' }}
            </div>
            <div class="mt-0.5 text-xs text-bgray-600 dark:text-bgray-300">
                {{ $record->hostingService?->name ?? 'Standard' }}
            </div>
        </div>

        <div class="rounded-lg border border-bgray-200 bg-bgray-50/50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500/30">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Ownership & Status</div>
            <div class="mt-2 flex items-center gap-2">
                @if ($isCompany)
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ $ownershipLabel }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                        {{ $ownershipLabel }}
                    </span>
                @endif

                @if ($record->hostingServiceStatus)
                    <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-xs font-medium text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-300">
                        {{ $record->hostingServiceStatus->name }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Secondary Meta Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Environment</div>
            <div class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                {{ $record->hostingEnvironment?->name ?? '--' }}
            </div>
        </div>

        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Reference / Account ID</div>
            <div class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                {{ $record->reference ?? '--' }}
            </div>
        </div>

        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Billing Information</div>
            <div class="mt-1 text-sm font-semibold text-bgray-900 dark:text-white">
                @if ($record->billing_amount !== null)
                    {{ $record->currency_code ? $record->currency_code . ' ' : '' }}{{ number_format((float) $record->billing_amount, 2) }}
                    @if ($record->hostingBillingCycle)
                        <span class="text-xs font-normal text-bgray-500 dark:text-bgray-400">/ {{ $record->hostingBillingCycle->name }}</span>
                    @endif
                @else
                    --
                @endif
            </div>
        </div>
    </div>

    <!-- Service Dates Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Start Date</div>
            <div class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                {{ $record->start_date ? $record->start_date->format($dateFormat) : '--' }}
            </div>
        </div>

        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Expiry Date</div>
            <div class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                {{ $record->expiry_date ? $record->expiry_date->format($dateFormat) : '--' }}
            </div>
        </div>

        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Next Renewal Date</div>
            <div class="mt-1 text-sm font-medium text-bgray-900 dark:text-white">
                {{ $record->next_renewal_date ? $record->next_renewal_date->format($dateFormat) : '--' }}
            </div>
        </div>
    </div>

    <!-- Description & Notes -->
    @if ($record->description)
        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Description</div>
            <div class="mt-2 whitespace-pre-line text-sm text-bgray-800 dark:text-bgray-200">
                {{ $record->description }}
            </div>
        </div>
    @endif

    @if ($record->notes)
        <div class="rounded-lg border border-bgray-200 p-4 dark:border-darkblack-400">
            <div class="text-xs font-medium uppercase text-bgray-500 dark:text-bgray-400">Internal Notes</div>
            <div class="mt-2 whitespace-pre-line text-sm text-bgray-800 dark:text-bgray-200">
                {{ $record->notes }}
            </div>
        </div>
    @endif

    <!-- Audit Meta -->
    <div class="border-t border-bgray-200 pt-3 text-xs text-bgray-500 dark:border-darkblack-400 dark:text-bgray-400">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                Created: {{ $record->created_at?->format('d M Y, h:i A') }}
                @if ($record->creator)
                    by <span class="font-medium text-bgray-700 dark:text-bgray-300">{{ $record->creator->name }}</span>
                @endif
            </div>
            @if ($record->updated_at && $record->updated_at != $record->created_at)
                <div>
                    Updated: {{ $record->updated_at->format('d M Y, h:i A') }}
                    @if ($record->updater)
                        by <span class="font-medium text-bgray-700 dark:text-bgray-300">{{ $record->updater->name }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
