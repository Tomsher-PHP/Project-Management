<div class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <p class="text-xs text-bgray-600 dark:text-bgray-400 -mt-2">
        Manage API credentials and permissions for connected external applications. Manage API clients, explore endpoints, and view external record mappings.
    </p>

    <!-- Table Content -->
    <div class="table-content w-full overflow-x-auto">
        <table class="w-full relative">
            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                <td class="pr-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex w-full items-center space-x-2.5">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Application Name</span>
                    </div>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Key ID</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Scopes</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Expires</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                </td>
                <td class="pl-6 py-5 whitespace-nowrap text-right">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                </td>
            </tr>
            @php
                $startNumber = ($clients->currentPage() - 1) * $clients->perPage();
            @endphp
            @forelse ($clients as $client)
                @php
                    $isExpired = $client->expires_at && $client->expires_at->isPast();
                @endphp
                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                    <td class="pr-6 py-5 whitespace-nowrap">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-semibold text-bgray-900 dark:text-white">
                                    {{ $client->name }}
                                </span>
                                @if ($isExpired)
                                    <span class="inline-flex rounded-full bg-error-50 px-2 py-0.5 text-[10px] font-bold text-error-400 dark:bg-error-900/30">
                                        Expired
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-bgray-600 dark:text-bgray-400">Created: {{ $client->created_at->format($globalDateFormat) }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <div class="flex items-center gap-1.5 font-mono text-sm text-bgray-900 dark:text-white">
                            <span>{{ $client->key_id }}</span>
                            <button type="button" class="copy-key-id-btn p-1 text-bgray-400 hover:text-success-300 transition" data-key-id="{{ $client->key_id }}" title="Copy Key ID">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <div class="flex flex-wrap gap-1 max-w-xs">
                            @forelse($client->scopes ?? [] as $scope)
                                <span class="inline-flex items-center rounded bg-bgray-100 px-2 py-0.5 font-mono text-[11px] font-medium text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-300">
                                    {{ $scope }}
                                </span>
                            @empty
                                <span class="text-xs text-bgray-400">No scopes</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        @if ($client->expires_at)
                            <span class="text-sm {{ $isExpired ? 'text-error-400 font-semibold' : 'text-bgray-600 dark:text-bgray-300' }}">
                                {{ $client->expires_at->format($globalDateFormat) }}
                            </span>
                        @else
                            <span class="text-sm text-bgray-600 dark:text-bgray-300">Never</span>
                        @endif
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <div class="flex w-full items-center">
                            <x-status-toggle :model="$client" route="settings.data-sync.clients.toggle-status" :route-parameters="['client' => $client->id]" entity="API Client" permission="data_sync_settings.edit" />
                        </div>
                    </td>
                    <td class="pl-6 py-5 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            @can('data_sync_settings.edit')
                                <x-edit-button action="javascript:void(0)" class="edit-client-btn" data-client-id="{{ $client->id }}" title="Edit API Client" />

                                <a href="javascript:void(0)" class="rotate-client-btn inline-flex h-9 w-9 items-center justify-center rounded-lg border border-bgray-400 bg-white text-bgray-700 shadow-sm transition duration-200 hover:border-amber-400 hover:bg-amber-50 hover:text-amber-600 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-amber-400 dark:hover:bg-darkblack-400 dark:hover:text-amber-400 group" data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}" data-url="{{ route('settings.data-sync.clients.rotate', $client->id) }}" title="Rotate Secret Key">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </a>
                            @endcan

                            @can('data_sync_settings.delete')
                                @if ($client->is_active)
                                    <x-delete-form :action="route('settings.data-sync.clients.revoke', $client->id)" confirm-title="Revoke API Client?" confirm-message="Revoking this API key will immediately disable authentication for “{{ $client->name }}”. Existing record mappings will be preserved. Are you sure you want to revoke this client?" title="Revoke API Client" />
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <x-table-no-data :col-span="7" message="No API clients found." sub-message="Generate an API key to allow external systems to synchronize data with PMS." />
            @endforelse
        </table>
    </div>

    <x-pagination :paginator="$clients" :per-page="$perPage" />
</div>

<!-- Include Modal Partials -->
@include('settings.data-sync.modals.create-client-modal')
@include('settings.data-sync.modals.edit-client-modal')
@include('settings.data-sync.modals.one-time-key-modal')
