@php
    $ownershipLabel = \App\Models\ProjectHostingRecord::OWNERSHIP_TYPES[$record->ownership_type] ?? ucfirst($record->ownership_type);
    $isCompany = $record->ownership_type === \App\Models\ProjectHostingRecord::OWNERSHIP_COMPANY;

    $today = now()->startOfDay();
    $in30Days = now()->addDays(30)->endOfDay();

    // Derived Expiry computation
    $expiryDate = $record->expiry_date;
    $expiryCategory = 'not_set';
    $expiryBadgeText = 'Not set';
    $expiryBadgeClass = 'bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-400';

    if ($expiryDate) {
        $expDay = $expiryDate->copy()->startOfDay();
        if ($expDay->lt($today)) {
            $expiryCategory = 'expired';
            $expiryBadgeText = 'Expired';
            $expiryBadgeClass = 'bg-error-50 text-error-300 dark:bg-darkblack-500 dark:text-error-300 font-semibold';
        } elseif ($expDay->equalTo($today)) {
            $expiryCategory = 'expiring_soon';
            $expiryBadgeText = 'Expires today';
            $expiryBadgeClass = 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400 font-semibold';
        } elseif ($expDay->lte($in30Days)) {
            $daysRemaining = (int) $today->diffInDays($expDay, false);
            $expiryCategory = 'expiring_soon';
            $expiryBadgeText = 'Expires in ' . $daysRemaining . ' ' . \Illuminate\Support\Str::plural('day', $daysRemaining);
            $expiryBadgeClass = 'bg-amber-50 text-amber-600 dark:bg-darkblack-500 dark:text-amber-400 font-medium';
        } else {
            $expiryCategory = 'valid';
            $expiryBadgeText = 'Valid';
            $expiryBadgeClass = 'bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300';
        }
    }

    $editActionUrl = route('projects.hosting.show', [$project, $record]) . '?mode=edit';
@endphp

<div class="flex flex-col h-full bg-white dark:bg-darkblack-600">

    <!-- Drawer Header -->
    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 shrink-0">
        <div class="min-w-0 flex-1 pr-4">
            <h3 id="project-hosting-drawer-title" class="text-lg font-bold text-bgray-900 dark:text-white truncate" title="{{ $record->name }}">
                {{ $record->name }}
            </h3>
            @if ($record->domain)
                <div class="mt-0.5 text-xs text-success-400">
                    <a href="{{ str_starts_with($record->domain, 'http') ? $record->domain : 'https://' . $record->domain }}" target="_blank" rel="noopener noreferrer" class="hover:underline inline-flex items-center gap-1">
                        {{ $record->domain }}
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            @elseif ($record->hostingService)
                <p class="mt-0.5 text-xs text-bgray-600 dark:text-bgray-300">
                    {{ $record->hostingService->name }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <!-- Renew Action -->
            @can('project_hosting.edit')
                <button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-success-300 bg-success-50 px-2.5 text-xs font-semibold text-success-400 shadow-sm transition hover:bg-success-100 hover:text-success-500 dark:border-success-400 dark:bg-darkblack-500 dark:text-success-300 dark:hover:bg-darkblack-400" data-project-hosting-renew data-action="{{ route('projects.hosting.renew', [$project, $record]) }}" data-record-json="{{ json_encode([
                    'id' => $record->id,
                    'name' => $record->name,
                    'domain' => $record->domain,
                    'expiry_date' => $record->expiry_date?->format('Y-m-d'),
                    'expiry_date_formatted' => $record->expiry_date ? $record->expiry_date->format('d M Y') : '—',
                    'start_date' => $record->start_date?->format('Y-m-d'),
                    'hosting_billing_cycle_id' => $record->hosting_billing_cycle_id,
                    'billing_amount' => $record->billing_amount,
                    'currency_code' => $record->currency_code,
                    'reference' => $record->reference,
                    'renew_url' => route('projects.hosting.renew', [$project, $record]),
                ]) }}" title="Renew Hosting Record">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Renew</span>
                </button>
            @endcan

            <!-- Edit Action -->
            @can('project_hosting.edit')
                <button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-2.5 text-xs font-semibold text-bgray-700 shadow-sm transition hover:border-success-300 hover:bg-success-50 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" data-project-hosting-edit data-action="{{ $editActionUrl }}" data-hosting-id="{{ $record->id }}" title="Edit Hosting Record">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                </button>
            @endcan

            <!-- Close Drawer -->
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" data-project-hosting-drawer-close aria-label="Close details">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Drawer Content Scroll Area -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6">

        <!-- Status & Ownership Badges Bar -->
        <div class="flex flex-wrap items-center gap-2">
            @if ($record->hostingServiceStatus)
                <span class="inline-flex items-center rounded-full bg-bgray-100 px-3 py-1 text-xs font-semibold text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-200">
                    Status: {{ $record->hostingServiceStatus->name }}
                </span>
            @endif

            @if ($isCompany)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-darkblack-500 dark:text-blue-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                    {{ $ownershipLabel }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-darkblack-500 dark:text-purple-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>
                    {{ $ownershipLabel }}
                </span>
            @endif

            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $expiryBadgeClass }}">
                Expiry: {{ $expiryBadgeText }}
            </span>
        </div>

        <!-- Section A: Service Information -->
        <div class="rounded-xl border border-bgray-200 p-5 dark:border-darkblack-400 bg-white dark:bg-darkblack-600">
            <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-400 mb-4">
                Service Information
            </h4>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Service Name</span>
                    <p class="mt-0.5 text-sm font-semibold text-bgray-900 dark:text-white">{{ $record->name }}</p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Provider</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-800 dark:text-bgray-200">{{ $record->hostingProvider?->name ?? '—' }}</p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Service Type</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-800 dark:text-bgray-200">{{ $record->hostingService?->name ?? '—' }}</p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Environment</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-800 dark:text-bgray-200">{{ $record->hostingEnvironment?->name ?? '—' }}</p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Domain / URL</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-800 dark:text-bgray-200">
                        @if ($record->domain)
                            <a href="{{ str_starts_with($record->domain, 'http') ? $record->domain : 'https://' . $record->domain }}" target="_blank" rel="noopener noreferrer" class="text-success-400 hover:underline">
                                {{ $record->domain }}
                            </a>
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Reference / Account ID</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-800 dark:text-bgray-200">{{ $record->reference ?? '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Section B: Dates & Billing -->
        <div class="rounded-xl border border-bgray-200 p-5 dark:border-darkblack-400 bg-white dark:bg-darkblack-600">
            <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-400 mb-4">
                Dates & Billing
            </h4>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Start Date</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-900 dark:text-white">
                        @if ($record->start_date)
                            @appDate($record->start_date)
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Expiry Date</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-900 dark:text-white">
                        @if ($record->expiry_date)
                            @appDate($record->expiry_date)
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Next Renewal Date</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-900 dark:text-white">
                        @if ($record->next_renewal_date)
                            @appDate($record->next_renewal_date)
                        @else
                            —
                        @endif
                    </p>
                </div>

                @if ($record->last_renewed_date)
                    <div>
                        <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Last Renewed Date</span>
                        <p class="mt-0.5 text-sm font-medium text-bgray-900 dark:text-white">
                            @appDate($record->last_renewed_date)
                        </p>
                    </div>
                @endif

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Billing Amount</span>
                    <p class="mt-0.5 text-sm font-semibold text-bgray-900 dark:text-white">
                        @if ($record->billing_amount !== null)
                            {{ $record->currency_code ? $record->currency_code . ' ' : '' }}{{ number_format((float) $record->billing_amount, 2) }}
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Billing Cycle</span>
                    <p class="mt-0.5 text-sm font-medium text-bgray-800 dark:text-bgray-200">
                        {{ $record->hostingBillingCycle?->name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Section C: Description & Notes -->
        @if ($record->description)
            <div class="rounded-xl border border-bgray-200 p-5 dark:border-darkblack-400 bg-white dark:bg-darkblack-600">
                <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-400 mb-2">
                    Description
                </h4>
                <div class="whitespace-pre-line text-sm text-bgray-800 dark:text-bgray-300">
                    {{ $record->description }}
                </div>
            </div>
        @endif

        @if ($record->notes)
            <div class="rounded-xl border border-bgray-200 p-5 dark:border-darkblack-400 bg-white dark:bg-darkblack-600">
                <h4 class="text-xs font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-400 mb-2">
                    Internal Notes
                </h4>
                <div class="whitespace-pre-line text-sm text-bgray-800 dark:text-bgray-300">
                    {{ $record->notes }}
                </div>
            </div>
        @endif

        <!-- Audit Meta -->
        <div class="border-t border-bgray-200 pt-4 text-xs text-bgray-600 dark:border-darkblack-400 dark:text-bgray-400">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    Created: {{ $record->created_at?->format('d M Y, h:i A') }}
                    @if ($record->creator)
                        by <span class="font-medium text-bgray-800 dark:text-bgray-200">{{ $record->creator->name }}</span>
                    @endif
                </div>
                @if ($record->updated_at && $record->updated_at != $record->created_at)
                    <div>
                        Updated: {{ $record->updated_at->format('d M Y, h:i A') }}
                        @if ($record->updater)
                            by <span class="font-medium text-bgray-800 dark:text-bgray-200">{{ $record->updater->name }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Drawer Footer -->
    <div class="flex items-center justify-between gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 shrink-0 bg-bgray-50/50 dark:bg-darkblack-500/30">
        <!-- History Buttons -->
        <div class="flex items-center gap-2">
            @can('project_hosting.view')
                <button type="button" class="rounded-lg border border-bgray-300 bg-white px-3.5 py-2 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-renewal-history data-hosting-id="{{ $record->id }}" data-hosting-name="{{ $record->name }}" data-action="{{ route('projects.hosting.renewal-history', [$project, $record]) }}">
                    Renewal History
                </button>
                <button type="button" class="rounded-lg border border-bgray-300 bg-white px-3.5 py-2 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-status-history data-hosting-id="{{ $record->id }}" data-hosting-name="{{ $record->name }}" data-action="{{ route('projects.hosting.status-history', [$project, $record]) }}">
                    Status History
                </button>
            @endcan
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5">
            <button type="button" class="rounded-lg border border-bgray-300 bg-white px-4 py-2 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-hosting-drawer-close>
                Close
            </button>
            @can('project_hosting.edit')
                <button type="button" class="rounded-lg border border-success-300 bg-success-50 px-4 py-2 text-xs font-semibold text-success-400 transition hover:bg-success-100 hover:text-success-500 dark:border-success-400 dark:bg-darkblack-500 dark:text-success-300 dark:hover:bg-darkblack-400" data-project-hosting-renew data-action="{{ route('projects.hosting.renew', [$project, $record]) }}" data-record-json="{{ json_encode([
                    'id' => $record->id,
                    'name' => $record->name,
                    'domain' => $record->domain,
                    'expiry_date' => $record->expiry_date?->format('Y-m-d'),
                    'expiry_date_formatted' => $record->expiry_date ? $record->expiry_date->format('d M Y') : '—',
                    'start_date' => $record->start_date?->format('Y-m-d'),
                    'hosting_billing_cycle_id' => $record->hosting_billing_cycle_id,
                    'billing_amount' => $record->billing_amount,
                    'currency_code' => $record->currency_code,
                    'reference' => $record->reference,
                    'renew_url' => route('projects.hosting.renew', [$project, $record]),
                ]) }}">
                    Renew Service
                </button>
                <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-success-400" data-project-hosting-edit data-action="{{ $editActionUrl }}" data-hosting-id="{{ $record->id }}">
                    Edit Record
                </button>
            @endcan
        </div>
    </div>

</div>
