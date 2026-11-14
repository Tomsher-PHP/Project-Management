<div class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 -mt-2">
        <p class="text-xs text-bgray-600 dark:text-bgray-400">
            View relationships between external system records and PMS customers or projects.
        </p>
        <span class="inline-flex items-center gap-1.5 text-xs text-bgray-500 dark:text-bgray-400">
            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
            <span>Read-Only Historical Mapping Registry</span>
        </span>
    </div>

    <!-- Filter and Search Bar -->
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        <form method="GET" action="{{ route('settings.data-sync.mappings') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <input type="hidden" name="per_page" value="{{ $perPage }}">

            <!-- API Client Filter Dropdown -->
            <div class="w-full sm:w-48">
                <select name="api_client_id" onchange="this.form.submit()" class="w-full rounded-lg border border-bgray-300 bg-white py-2 px-3 text-xs sm:text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    <option value="">All API Clients</option>
                    @foreach($apiClients as $client)
                        <option value="{{ $client->id }}" {{ (string)($filters['api_client_id'] ?? '') === (string)$client->id ? 'selected' : '' }}>
                            {{ $client->name }}{{ $client->trashed() ? ' (Deleted)' : (!$client->is_active ? ' (Inactive)' : '') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Entity Type Filter Dropdown -->
            <div class="w-full sm:w-40">
                <select name="entity_type" onchange="this.form.submit()" class="w-full rounded-lg border border-bgray-300 bg-white py-2 px-3 text-xs sm:text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    <option value="">All Entities</option>
                    @foreach($entityTypes as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" {{ ($filters['entity_type'] ?? '') === $typeKey ? 'selected' : '' }}>
                            {{ $typeLabel }}s
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Field -->
            <div class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search external code, ID, or PMS code..." class="w-full rounded-lg border border-bgray-300 bg-white py-2 pl-9 pr-4 text-xs sm:text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-bgray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Search Button -->
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-bgray-800 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm transition hover:bg-bgray-900 dark:bg-darkblack-400 dark:hover:bg-darkblack-300 shrink-0">
                Filter
            </button>

            <!-- Reset Filters -->
            @if(!empty($filters['api_client_id']) || !empty($filters['entity_type']) || !empty($filters['search']))
                <a href="{{ route('settings.data-sync.mappings') }}" class="inline-flex items-center text-xs font-semibold text-error-300 hover:underline">
                    Reset Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Mappings Table -->
    <div class="table-content w-full overflow-x-auto rounded-lg border border-bgray-200 dark:border-darkblack-400">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-bgray-200 bg-bgray-50 text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                    <th class="py-3.5 px-4 w-12 text-center">#</th>
                    <th class="py-3.5 px-4">API Client</th>
                    <th class="py-3.5 px-4">Entity Type</th>
                    <th class="py-3.5 px-4">External Code</th>
                    <th class="py-3.5 px-4">External ID</th>
                    <th class="py-3.5 px-4">PMS Record</th>
                    <th class="py-3.5 px-4 whitespace-nowrap">Mapped Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400 text-xs text-bgray-700 dark:text-bgray-200">
                @forelse($mappings as $mapping)
                    @php
                        $rowIndex = ($mappings->currentPage() - 1) * $mappings->perPage() + $loop->iteration;
                    @endphp
                    <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-600/50 transition">
                        <!-- # -->
                        <td class="py-4 px-4 text-center font-mono text-bgray-500 dark:text-bgray-400">
                            {{ $rowIndex }}
                        </td>

                        <!-- API Client -->
                        <td class="py-4 px-4 font-medium text-bgray-900 dark:text-white whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span>{{ $mapping->client_name }}</span>
                                @if($mapping->is_client_deleted)
                                    <span class="rounded bg-error-50 px-1.5 py-0.5 text-[10px] font-bold text-error-400 dark:bg-error-900/30">
                                        Deleted Client
                                    </span>
                                @elseif(!$mapping->is_client_active)
                                    <span class="rounded bg-bgray-100 px-1.5 py-0.5 text-[10px] font-bold text-bgray-500 dark:bg-darkblack-400 dark:text-bgray-300">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Entity Type -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if($mapping->entity_type === 'customer')
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    Customer
                                </span>
                            @elseif($mapping->entity_type === 'project')
                                <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-[11px] font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                    Project
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-[11px] font-semibold text-bgray-700 dark:bg-darkblack-400 dark:text-bgray-200">
                                    {{ ucfirst($mapping->entity_type) }}
                                </span>
                            @endif
                        </td>

                        <!-- External Code -->
                        <td class="py-4 px-4 font-mono font-medium text-bgray-900 dark:text-white">
                            {{ $mapping->external_code ?? '-' }}
                        </td>

                        <!-- External ID -->
                        <td class="py-4 px-4 font-mono text-bgray-600 dark:text-bgray-300">
                            {{ $mapping->external_id ?? '-' }}
                        </td>

                        <!-- PMS Record -->
                        <td class="py-4 px-4">
                            @if($mapping->is_pms_missing)
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-error-50 px-2 py-0.5 text-[10px] font-bold text-error-400 dark:bg-error-900/30">
                                        Missing Record
                                    </span>
                                    <span class="font-mono text-xs text-bgray-500 dark:text-bgray-400">
                                        PMS ID: {{ $mapping->local_id }}
                                    </span>
                                </div>
                            @else
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-bgray-900 dark:text-white">
                                            {{ $mapping->pms_code }}
                                        </span>
                                        @if($mapping->is_pms_deleted)
                                            <span class="rounded bg-amber-50 px-1.5 py-0.2 text-[10px] font-bold text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                                                Deleted
                                            </span>
                                        @endif
                                    </div>
                                    @if($mapping->pms_name)
                                        <span class="text-[11px] text-bgray-500 dark:text-bgray-400 truncate max-w-xs" title="{{ $mapping->pms_name }}">
                                            {{ $mapping->pms_name }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <!-- Mapped Date -->
                        <td class="py-4 px-4 text-xs text-bgray-600 dark:text-bgray-300 whitespace-nowrap font-mono">
                            {{ $mapping->created_at?->format('M d, Y H:i:s') ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <svg class="h-10 w-10 text-bgray-300 dark:text-darkblack-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                @if(!empty($filters['api_client_id']) || !empty($filters['entity_type']) || !empty($filters['search']))
                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                        No mappings match your search criteria.
                                    </p>
                                    <p class="text-xs text-bgray-500 dark:text-bgray-400">
                                        Try adjusting or resetting your filter criteria to find the record mapping.
                                    </p>
                                @else
                                    <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                        No record mappings found.
                                    </p>
                                    <p class="text-xs text-bgray-500 dark:text-bgray-400">
                                        External customer and project records synchronized via Data Sync APIs will automatically appear here.
                                    </p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($mappings->hasPages() || $mappings->total() > 5)
        <div class="mt-4">
            <x-pagination :paginator="$mappings" :perPage="$perPage" />
        </div>
    @endif
</div>
