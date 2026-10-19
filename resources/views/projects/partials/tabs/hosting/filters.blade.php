@php
    $ownershipTypes = \App\Models\ProjectHostingRecord::OWNERSHIP_TYPES;
    $providers = $filterOptions['providers'] ?? [];
    $services = $filterOptions['services'] ?? [];
    $statuses = $filterOptions['statuses'] ?? [];
    $expiryOptions = [
        '' => 'All Expiry Dates',
        'expiring_soon' => 'Expiring Soon (Next 30 Days)',
        'expired' => 'Expired',
        'no_expiry' => 'No Expiry Date',
    ];
@endphp

<div class="border-b border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-600" data-project-hosting-filter-bar>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <!-- Search bar -->
        <div class="relative w-full lg:max-w-xs">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-bgray-500 dark:text-bgray-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search service, domain, reference..."
                class="w-full rounded-lg border border-bgray-300 bg-white py-2 pl-9 pr-3 text-xs text-bgray-900 outline-none transition focus:border-success-300 focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white"
                data-project-hosting-filter="search"
                autocomplete="off"
            >
        </div>

        <!-- Filter Dropdowns & Reset -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Provider Filter -->
            <div class="w-full sm:w-44">
                <select name="hosting_provider_id" class="tom-select-no-search w-full text-xs" data-project-hosting-filter="hosting_provider_id">
                    <option value="">All Providers</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}" @selected((string)request('hosting_provider_id') === (string)$provider->id)>{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Service Type Filter -->
            <div class="w-full sm:w-44">
                <select name="hosting_service_id" class="tom-select-no-search w-full text-xs" data-project-hosting-filter="hosting_service_id">
                    <option value="">All Service Types</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected((string)request('hosting_service_id') === (string)$service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actual Status Filter -->
            <div class="w-full sm:w-36">
                <select name="hosting_service_status_id" class="tom-select-no-search w-full text-xs" data-project-hosting-filter="hosting_service_status_id">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected((string)request('hosting_service_status_id') === (string)$status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Ownership Filter -->
            <div class="w-full sm:w-40">
                <select name="ownership_type" class="tom-select-no-search w-full text-xs" data-project-hosting-filter="ownership_type">
                    <option value="">All Ownership</option>
                    @foreach ($ownershipTypes as $key => $label)
                        <option value="{{ $key }}" @selected(request('ownership_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Expiry Filter -->
            <div class="w-full sm:w-44">
                <select name="expiry_status" class="tom-select-no-search w-full text-xs" data-project-hosting-filter="expiry_status">
                    @foreach ($expiryOptions as $val => $txt)
                        <option value="{{ $val }}" @selected(request('expiry_status') === $val)>{{ $txt }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Reset Button -->
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-2 text-xs font-semibold text-bgray-700 transition hover:bg-bgray-50 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400 dark:hover:text-white"
                data-project-hosting-reset
                title="Reset all filters"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Reset</span>
            </button>
        </div>
    </div>
</div>
