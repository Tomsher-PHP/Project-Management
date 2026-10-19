@php
    $today = now()->startOfDay();
    $in30Days = now()->addDays(30)->endOfDay();
    $isFiltering = request()->filled('search')
        || request()->filled('hosting_provider_id')
        || request()->filled('hosting_service_id')
        || request()->filled('hosting_service_status_id')
        || request()->filled('ownership_type')
        || request()->filled('expiry_status');
@endphp

<div>
    @if ($hostingRecords->isEmpty())
        <div class="p-8 text-center" data-project-hosting-empty-state>
            @if ($isFiltering)
                <!-- No Search Results Found -->
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-bgray-100 text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                    No matching hosting records
                </h5>
                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">
                    We couldn't find any hosting records matching your active filters or search query.
                </p>
                <div class="mt-4">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white"
                        data-project-hosting-reset
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filters</span>
                    </button>
                </div>
            @else
                <!-- Zero Records in Project -->
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-50 text-success-400 dark:bg-darkblack-500 dark:text-success-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                </div>
                <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                    No hosting records yet
                </h5>
                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300">
                    Keep track of servers, domains, SSL, database instances, and hosting services for this project.
                </p>
                @can('project_hosting.create')
                    <div class="mt-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-success-400"
                            data-project-hosting-modal-open
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add First Hosting Record</span>
                        </button>
                    </div>
                @endcan
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px]">
                <thead>
                    <tr class="border-b border-bgray-200 dark:border-darkblack-400">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Hosting / Service
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Provider
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Service Type
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Ownership
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Status
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Expiry
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Billing
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                    @foreach ($hostingRecords as $record)
                        @php
                            $ownershipLabel = \App\Models\ProjectHostingRecord::OWNERSHIP_TYPES[$record->ownership_type] ?? ucfirst($record->ownership_type);
                            $isCompany = $record->ownership_type === \App\Models\ProjectHostingRecord::OWNERSHIP_COMPANY;

                            // Expiry computation
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
                        @endphp
                        <tr class="transition hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
                            <!-- Hosting / Service -->
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-bgray-900 dark:text-white">
                                    {{ $record->name }}
                                </div>
                                @if ($record->domain)
                                    <div class="mt-0.5 text-xs text-bgray-600 dark:text-bgray-300">
                                        <a href="{{ str_starts_with($record->domain, 'http') ? $record->domain : 'https://' . $record->domain }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 hover:text-success-400">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                            <span>{{ $record->domain }}</span>
                                        </a>
                                    </div>
                                @endif
                                @if ($record->hostingEnvironment)
                                    <div class="mt-0.5 text-[11px] text-bgray-500 dark:text-bgray-400">
                                        Env: {{ $record->hostingEnvironment->name }}
                                    </div>
                                @endif
                            </td>

                            <!-- Provider -->
                            <td class="px-5 py-4">
                                <span class="text-sm font-medium text-bgray-800 dark:text-bgray-300">
                                    {{ $record->hostingProvider?->name ?? '--' }}
                                </span>
                            </td>

                            <!-- Service Type -->
                            <td class="px-5 py-4">
                                <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                    {{ $record->hostingService?->name ?? '--' }}
                                </span>
                            </td>

                            <!-- Ownership -->
                            <td class="px-5 py-4">
                                @if ($isCompany)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-bgray-800 dark:text-bgray-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                                        {{ $ownershipLabel }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-bgray-800 dark:text-bgray-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>
                                        {{ $ownershipLabel }}
                                    </span>
                                @endif
                            </td>

                            <!-- Actual Status -->
                            <td class="px-5 py-4">
                                @if ($record->hostingServiceStatus)
                                    <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-xs font-medium text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-300">
                                        {{ $record->hostingServiceStatus->name }}
                                    </span>
                                @else
                                    <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                @endif
                            </td>

                            <!-- Expiry Date & Separate Derived Indicator -->
                            <td class="px-5 py-4">
                                <div class="text-sm text-bgray-900 dark:text-white">
                                    @if ($record->expiry_date)
                                        <div>@appDate($record->expiry_date)</div>
                                    @else
                                        <span class="text-bgray-500 dark:text-bgray-400">--</span>
                                    @endif
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] {{ $expiryBadgeClass }}">
                                        {{ $expiryBadgeText }}
                                    </span>
                                </div>
                            </td>

                            <!-- Billing -->
                            <td class="px-5 py-4">
                                @if ($record->billing_amount !== null)
                                    <div class="text-sm font-semibold text-bgray-900 dark:text-white">
                                        {{ $record->currency_code ? $record->currency_code . ' ' : '' }}{{ number_format((float) $record->billing_amount, 2) }}
                                    </div>
                                    @if ($record->hostingBillingCycle)
                                        <div class="text-xs text-bgray-600 dark:text-bgray-300">
                                            / {{ $record->hostingBillingCycle->name }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @can('project_hosting.view')
                                        <x-view-button :action="route('projects.hosting.show', [$project, $record])" data-project-hosting-view data-hosting-id="{{ $record->id }}" title="View Details" />
                                    @endcan

                                    @can('project_hosting.edit')
                                        <x-edit-button :action="route('projects.hosting.show', [$project, $record]) . '?mode=edit'" data-project-hosting-edit data-hosting-id="{{ $record->id }}" title="Edit Record" />
                                    @endcan

                                    @can('project_hosting.delete')
                                        <x-delete-form :action="route('projects.hosting.destroy', [$project, $record])" :id="$record->id" ajax="true" renderTarget="#project-hosting-history" renderMode="replace_inner" formClass="delete-form project-hosting-delete-form" confirmTitle="Delete Hosting Record" confirmMessage="Are you sure you want to delete this hosting record?" data-project-hosting-delete title="Delete Record" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- AJAX Pagination footer --}}
        @if ($hostingRecords instanceof \Illuminate\Pagination\LengthAwarePaginator)
            @include('projects.partials.tabs.hosting.pagination', ['paginator' => $hostingRecords])
        @endif
    @endif
</div>
