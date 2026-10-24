@php
    $today = now()->startOfDay();
    $in30Days = now()->addDays(30)->endOfDay();
    $isFiltering = request()->filled('search') || request()->filled('hosting_provider_id') || request()->filled('hosting_service_id') || request()->filled('hosting_service_status_id') || request()->filled('ownership_type') || request()->filled('expiry_status');
    $serviceStatuses = $formOptions['statuses'] ?? \App\Models\HostingServiceStatus::active()->orderBy('sort_order')->orderBy('name')->get();
    $canEditHosting = auth()->user()?->can('project_hosting.edit') && auth()->user()?->can('update', $project);
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
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-hosting-reset>
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
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-2 py-1 text-xs font-semibold text-white shadow-sm transition hover:bg-success-400" data-project-hosting-modal-open>
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
                                    <div class="mt-0.5 text-[11px] text-bgray-700 dark:text-bgray-400">
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
                                @php
                                    $currentStatus = $record->hostingServiceStatus;
                                    $currentStatusName = $currentStatus?->name ?? '--';
                                    $statusColor = $currentStatus?->color ?: '#6B7280';
                                    $updateStatusUrl = route('projects.hosting.status', [$project, $record]);
                                @endphp

                                @if ($canEditHosting && $serviceStatuses->isNotEmpty())
                                    <div class="relative inline-block" data-project-hosting-status-dropdown>
                                        <button type="button" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm transition hover:opacity-90" data-project-hosting-status-trigger aria-expanded="false" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                            <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
                                            <span data-project-hosting-status-label class="truncate">{{ $currentStatusName }}</span>
                                            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="transition-transform duration-200">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div class="hidden min-w-[150px] overflow-hidden rounded-xl border border-bgray-200 bg-white p-1 shadow-lg ring-1 ring-black/5 dark:border-darkblack-400 dark:bg-darkblack-500 dark:ring-white/10 z-20" data-project-hosting-status-menu>
                                            <ul class="max-h-56 overflow-y-auto py-1">
                                                @foreach ($serviceStatuses as $statusOption)
                                                    @php
                                                        $isCurrent = (int) ($record->hosting_service_status_id ?? 0) === (int) $statusOption->id;
                                                        $optColor = $statusOption->color ?: '#6B7280';
                                                    @endphp
                                                    <li>
                                                        <button type="button" class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs font-medium transition hover:bg-bgray-100 dark:hover:bg-darkblack-400 {{ $isCurrent ? 'bg-bgray-100 font-semibold text-bgray-900 dark:bg-darkblack-400 dark:text-white' : 'text-bgray-700 dark:text-bgray-300' }}" data-project-hosting-status-option data-status-id="{{ $statusOption->id }}" data-current-status-id="{{ $record->hosting_service_status_id ?? '' }}" data-update-url="{{ $updateStatusUrl }}">
                                                            <span class="flex items-center gap-2">
                                                                <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $optColor }};"></span>
                                                                <span>{{ $statusOption->name }}</span>
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
                                    @if ($record->hostingServiceStatus)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                            <span class="inline-flex h-2 w-2 rounded-full" style="background-color: {{ $statusColor }};"></span>
                                            {{ $currentStatusName }}
                                        </span>
                                    @else
                                        <span class="text-sm text-bgray-500 dark:text-bgray-400">--</span>
                                    @endif
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
                                @if (auth()->user()?->can('project_hosting.view') || auth()->user()?->can('project_hosting.edit') || auth()->user()?->can('project_hosting.delete'))
                                    <div class="relative inline-flex" data-project-hosting-row-dropdown>
                                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-bgray-200 bg-white text-bgray-700 transition hover:border-success-200 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-success-900/40 dark:hover:text-success-300" data-project-hosting-row-menu-trigger aria-expanded="false" aria-label="Hosting actions">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 3.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.75a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                            </svg>
                                        </button>

                                        <div class="hidden min-w-[155px] overflow-hidden rounded-xl border border-bgray-200 bg-white py-1 shadow-lg dark:border-darkblack-400 dark:bg-darkblack-500 z-1" data-project-hosting-row-menu>
                                            @can('project_hosting.view')
                                                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-hosting-view data-action="{{ route('projects.hosting.show', [$project, $record]) }}" data-hosting-id="{{ $record->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span>View Details</span>
                                                </button>
                                            @endcan

                                            @can('project_hosting.edit')
                                                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-hosting-edit data-action="{{ route('projects.hosting.show', [$project, $record]) }}?mode=edit" data-hosting-id="{{ $record->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 112 0v3a4 4 0 01-4 4H5a4 4 0 01-4-4V7a4 4 0 014-4h3a1 1 0 110 2H5z" />
                                                    </svg>
                                                    <span>Edit Record</span>
                                                </button>

                                                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white" data-project-hosting-renew data-action="{{ route('projects.hosting.renew', [$project, $record]) }}"
                                                    data-record-json="{{ json_encode([
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
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    <span>Renew</span>
                                                </button>
                                            @endcan

                                            @can('project_hosting.delete')
                                                <div class="my-1 border-t border-bgray-100 dark:border-darkblack-400"></div>

                                                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-error-300 transition hover:bg-red-50 hover:text-red-600 dark:text-red-300 dark:hover:bg-darkblack-400 dark:hover:text-red-200" data-project-hosting-delete-trigger data-target-form="project-hosting-delete-form-{{ $record->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M 6.496094 1 C 5.675781 1 5 1.675781 5 2.496094 L 5 3 L 2 3 L 2 4 L 3 4 L 3 12.5 C 3 13.328125 3.671875 14 4.5 14 L 10.5 14 C 11.328125 14 12 13.328125 12 12.5 L 12 4 L 13 4 L 13 3 L 10 3 L 10 2.496094 C 10 1.675781 9.324219 1 8.503906 1 Z M 6.496094 2 L 8.503906 2 C 8.785156 2 9 2.214844 9 2.496094 L 9 3 L 6 3 L 6 2.496094 C 6 2.214844 6.214844 2 6.496094 2 Z M 5 5 L 6 5 L 6 12 L 5 12 Z M 7 5 L 8 5 L 8 12 L 7 12 Z M 9 5 L 10 5 L 10 12 L 9 12 Z"></path>
                                                    </svg>
                                                    <span>Delete Record</span>
                                                </button>

                                                <form id="project-hosting-delete-form-{{ $record->id }}" action="{{ route('projects.hosting.destroy', [$project, $record]) }}" method="POST" class="delete-form project-hosting-delete-form hidden" data-id="{{ $record->id }}" data-ajax-delete="true" data-render-target="#project-hosting-history" data-render-mode="replace_inner" data-confirm-title="Delete Hosting Record" data-confirm-message="Are you sure you want to delete this hosting record?">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                @endif
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
