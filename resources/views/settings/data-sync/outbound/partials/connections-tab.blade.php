<div class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <p class="text-xs text-bgray-600 dark:text-bgray-400 -mt-2">
        Manage external application connections, base URLs, and authentication credentials for outbound data sync.
    </p>

    <!-- Table Content -->
    <div class="table-content w-full overflow-x-auto">
        <table class="w-full relative">
            <thead>
                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                    <th class="pr-6 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
                    </th>
                    <th class="px-6 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Connection Name</span>
                    </th>
                    <th class="px-6 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Base URL</span>
                    </th>
                    <th class="px-6 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Authentication</span>
                    </th>
                    <th class="px-6 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">APIs</span>
                    </th>
                    <th class="px-6 py-5 whitespace-nowrap text-left">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                    </th>
                    <th class="pl-6 py-5 whitespace-nowrap text-right">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-bgray-200 dark:divide-darkblack-400">
                @php
                    $startNumber = isset($connections) && method_exists($connections, 'currentPage') ? ($connections->currentPage() - 1) * $connections->perPage() : 0;
                @endphp
                @forelse ($connections ?? [] as $connection)
                    <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                        <td class="pr-6 py-5 whitespace-nowrap">
                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex flex-col gap-0.5">
                                <a href="{{ route('settings.data-sync.outbound.connections.edit', $connection) }}" class="text-base font-semibold text-bgray-900 transition hover:text-success-400 dark:text-white dark:hover:text-success-300">
                                    {{ $connection->name }}
                                </a>
                                <span class="text-xs text-bgray-600 dark:text-bgray-400">
                                    Created: {{ $connection->created_at?->format($globalDateFormat ?? 'Y-m-d') }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <span class="font-mono text-xs text-bgray-700 dark:text-bgray-300">
                                {{ $connection->base_url }}
                            </span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if ($connection->auth_type === 'bearer')
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                                    Bearer Token
                                </span>
                            @elseif ($connection->auth_type === 'api_key')
                                <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">
                                    API Key ({{ $connection->auth_config['header_name'] ?? 'X-API-Key' }})
                                </span>
                            @elseif ($connection->auth_type === 'basic')
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                                    Basic Auth
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-400">
                                    None
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <a href="{{ route('settings.data-sync.outbound.connections.edit', $connection) }}" class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-bold text-success-400 transition hover:bg-success-100 dark:bg-success-900/30 dark:text-success-300" title="Manage API configurations">
                                <span>{{ $connection->apis_count ?? $connection->apis()->count() }}</span>
                                <span>{{ ($connection->apis_count ?? $connection->apis()->count()) === 1 ? 'API' : 'APIs' }}</span>
                            </a>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <x-status-toggle :model="$connection" route="settings.data-sync.outbound.connections.toggle-status" :route-parameters="['connection' => $connection->id]" entity="Connection" permission="data_sync_settings.edit" />
                        </td>
                        <td class="pl-6 py-5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('data_sync_settings.edit')
                                    <a href="{{ route('settings.data-sync.outbound.connections.edit', $connection) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 text-bgray-600 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-success-300" title="Edit Connection & APIs">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                @endcan

                                @can('data_sync_settings.delete')
                                    <button type="button" class="delete-connection-btn inline-flex h-8 w-8 items-center justify-center rounded-lg border border-bgray-300 text-bgray-600 transition hover:border-error-300 hover:text-error-400 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-error-300" data-id="{{ $connection->id }}" data-name="{{ $connection->name }}" data-url="{{ route('settings.data-sync.outbound.connections.destroy', $connection) }}" title="Delete Connection">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-bgray-100 dark:bg-darkblack-500">
                                    <svg class="h-6 w-6 text-bgray-400 dark:text-bgray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h4 class="text-base font-semibold text-bgray-900 dark:text-white">No Outbound Connections</h4>
                                <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">Get started by creating your first external application connection.</p>
                                @can('data_sync_settings.create')
                                    <button type="button" onclick="$('#open-create-connection-btn').click()" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400">
                                        + Add Connection
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if (isset($connections) && method_exists($connections, 'hasPages') && $connections->hasPages())
        <div class="pt-4">
            {{ $connections->links() }}
        </div>
    @endif
</div>
