<div class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <p class="text-xs text-bgray-600 dark:text-bgray-400 -mt-2">
        Manage API credentials and permissions for connected external applications.
    </p>

    <!-- Filter and Action Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Search and Status Filter Form -->
        <form method="GET" action="{{ route('settings.data-sync.clients') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <input type="hidden" name="per_page" value="{{ $perPage }}">

            <!-- Search Field -->
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or Key ID..." class="w-full rounded-lg border border-bgray-300 bg-white py-2 pl-9 pr-4 text-xs sm:text-sm text-bgray-900 placeholder:text-bgray-400 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-bgray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Status Dropdown -->
            <div class="w-full sm:w-40">
                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border border-bgray-300 bg-white py-2 px-3 text-xs sm:text-sm text-bgray-900 focus:border-success-300 focus:outline-none focus:ring-1 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-white">
                    <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive / Revoked</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            @if(request('search') || request('status'))
                <a href="{{ route('settings.data-sync.clients') }}" class="inline-flex items-center text-xs font-semibold text-error-300 hover:underline">
                    Reset Filters
                </a>
            @endif
        </form>

        <!-- Generate Button -->
        @can('data_sync_settings.create')
            <button type="button" id="open-create-client-btn" class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-success-400 shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Generate API Key</span>
            </button>
        @endcan
    </div>

    <!-- Table Content -->
    <div class="table-content w-full overflow-x-auto rounded-lg border border-bgray-200 dark:border-darkblack-400">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-bgray-200 bg-bgray-50 text-xs font-semibold uppercase tracking-wider text-bgray-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                    <th class="py-3.5 px-4">#</th>
                    <th class="py-3.5 px-4">Application Name</th>
                    <th class="py-3.5 px-4">Key ID</th>
                    <th class="py-3.5 px-4">Permissions</th>
                    <th class="py-3.5 px-4">Created</th>
                    <th class="py-3.5 px-4">Last Used</th>
                    <th class="py-3.5 px-4">Expires</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-200 text-sm text-bgray-900 dark:divide-darkblack-400 dark:text-white">
                @php
                    $startNumber = ($clients->currentPage() - 1) * $clients->perPage();
                @endphp

                @forelse($clients as $client)
                    @php
                        $isExpired = $client->expires_at && $client->expires_at->isPast();
                        $statusLabel = 'Active';
                        $statusBadgeClass = 'bg-success-100 text-success-400 dark:bg-success-900/30 dark:text-success-300';

                        if (! $client->is_active) {
                            $statusLabel = 'Inactive';
                            $statusBadgeClass = 'bg-error-100 text-error-300 dark:bg-error-900/30 dark:text-error-300';
                        } elseif ($isExpired) {
                            $statusLabel = 'Expired';
                            $statusBadgeClass = 'bg-warning-100 text-warning-500 dark:bg-warning-900/30 dark:text-warning-300';
                        }
                    @endphp
                    <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30 transition duration-150" data-client-row="{{ $client->id }}">
                        <!-- Index -->
                        <td class="py-4 px-4 text-xs text-bgray-500 dark:text-bgray-400">
                            {{ $startNumber + $loop->iteration }}
                        </td>

                        <!-- Application Name -->
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-bgray-900 dark:text-white" data-client-name>
                                    {{ $client->name }}
                                </span>
                                <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    Inbound
                                </span>
                            </div>
                        </td>

                        <!-- Key ID -->
                        <td class="py-4 px-4 font-mono text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="rounded bg-bgray-100 px-2 py-1 font-mono text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-200">
                                    {{ $client->key_id }}
                                </span>
                                <button type="button" class="copy-key-id-btn text-bgray-400 hover:text-success-300 transition" data-key-id="{{ $client->key_id }}" title="Copy Key ID">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <!-- Permissions / Scopes -->
                        <td class="py-4 px-4">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @forelse($client->scopes ?? [] as $scope)
                                    @php
                                        $badgeColor = match($scope) {
                                            'settings.read' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
                                            'settings.write' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/20 dark:text-purple-300 dark:border-purple-800',
                                            'projects.write' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
                                            'meetings.read' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
                                            default => 'bg-bgray-100 text-bgray-700 border-bgray-200 dark:bg-darkblack-500 dark:text-bgray-300',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded border px-2 py-0.5 text-[11px] font-mono font-medium {{ $badgeColor }}">
                                        {{ $scope }}
                                    </span>
                                @empty
                                    <span class="text-xs text-bgray-400">None</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- Created -->
                        <td class="py-4 px-4 text-xs text-bgray-600 dark:text-bgray-300 whitespace-nowrap">
                            {{ $client->created_at ? $client->created_at->format('M d, Y') : '--' }}
                        </td>

                        <!-- Last Used -->
                        <td class="py-4 px-4 text-xs text-bgray-600 dark:text-bgray-300 whitespace-nowrap">
                            {{ $client->last_used_at ? $client->last_used_at->format('M d, Y H:i') : 'Never' }}
                        </td>

                        <!-- Expires -->
                        <td class="py-4 px-4 text-xs text-bgray-600 dark:text-bgray-300 whitespace-nowrap">
                            @if($client->expires_at)
                                <span class="{{ $isExpired ? 'text-error-300 font-semibold' : '' }}">
                                    {{ $client->expires_at->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-bgray-500 dark:text-bgray-400">Never</span>
                            @endif
                        </td>

                        <!-- Status Toggle & Badge -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusBadgeClass }}" data-status-badge>
                                    {{ $statusLabel }}
                                </span>
                                @can('data_sync_settings.edit')
                                    <x-status-toggle :model="$client" route="settings.data-sync.clients.toggle-status" :route-parameters="['client' => $client->id]" entity="API Client" permission="data_sync_settings.edit" />
                                @endcan
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Edit -->
                                @can('data_sync_settings.edit')
                                    <button type="button" class="edit-client-btn rounded-lg p-1.5 text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500" data-client-id="{{ $client->id }}" title="Edit API Client">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endcan

                                <!-- Rotate Secret -->
                                @can('data_sync_settings.edit')
                                    <button type="button" class="rotate-client-btn rounded-lg p-1.5 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-darkblack-500" data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}" data-url="{{ route('settings.data-sync.clients.rotate', $client->id) }}" title="Rotate Secret Key">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                @endcan

                                <!-- Revoke -->
                                @can('data_sync_settings.delete')
                                    @if($client->is_active)
                                        <button type="button" class="revoke-client-btn rounded-lg p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-darkblack-500" data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}" data-url="{{ route('settings.data-sync.clients.revoke', $client->id) }}" title="Revoke API Client">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <svg class="h-10 w-10 text-bgray-300 dark:text-darkblack-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                                <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                    No API Clients Found
                                </p>
                                <p class="text-xs text-bgray-500 dark:text-bgray-400">
                                    @if(request('search') || request('status'))
                                        No API clients match your search filters. Try adjusting your query.
                                    @else
                                        Generate an API key to allow external systems to synchronize data with PMS.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($clients->hasPages() || $clients->total() > 5)
        <div class="mt-4">
            <x-pagination :paginator="$clients" :perPage="$perPage" />
        </div>
    @endif
</div>

<!-- Include Modal Partials -->
@include('settings.data-sync.modals.create-client-modal')
@include('settings.data-sync.modals.edit-client-modal')
@include('settings.data-sync.modals.one-time-key-modal')
