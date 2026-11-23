<div class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <p class="text-xs text-bgray-600 dark:text-bgray-400 -mt-2">
        View relationships between external system records and PMS customers or projects.
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
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">API Client</span>
                    </div>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Entity Type</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">External Code</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">External ID</span>
                </td>
                <td class="px-6 py-5 whitespace-nowrap">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">PMS Record</span>
                </td>
                <td class="pl-6 py-5 whitespace-nowrap text-right">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Mapped Date</span>
                </td>
            </tr>
            @php
                $startNumber = ($mappings->currentPage() - 1) * $mappings->perPage();
            @endphp
            @forelse($mappings as $mapping)
                <tr class="border-b border-bgray-300 dark:border-darkblack-400 {{ config('assets.classes.table_row_hover') }}">
                    <td class="pr-6 py-5 whitespace-nowrap">
                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="text-base font-semibold text-bgray-900 dark:text-white">{{ $mapping->client_name }}</span>
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
                    <td class="px-6 py-5 whitespace-nowrap">
                        @if($mapping->entity_type === 'customer')
                            <span class="inline-flex items-center text-[12px] font-semibold text-blue-700 dark:text-blue-500">
                                Customer
                            </span>
                        @elseif($mapping->entity_type === 'project')
                            <span class="inline-flex items-center text-[12px] font-semibold text-purple-700 dark:text-purple-500">
                                Project
                            </span>
                        @else
                            <span class="inline-flex items-center text-[12px] font-semibold text-bgray-800 dark:text-bgray-300">
                                {{ ucfirst($mapping->entity_type) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap font-mono text-sm font-medium text-bgray-900 dark:text-white">
                        {{ $mapping->external_code ?? '-' }}
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap font-mono text-sm text-bgray-600 dark:text-bgray-300">
                        {{ $mapping->external_id ?? '-' }}
                    </td>
                    <td class="px-6 py-5 whitespace-nowrap">
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
                                    <span class="font-mono text-sm font-bold text-bgray-900 dark:text-white">
                                        {{ $mapping->pms_code }}
                                    </span>
                                    @if($mapping->is_pms_deleted)
                                        <span class="rounded bg-amber-50 px-1.5 py-0.2 text-[10px] font-bold text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                                            Deleted
                                        </span>
                                    @endif
                                </div>
                                @if($mapping->pms_name)
                                    <span class="text-xs text-bgray-500 dark:text-bgray-400 truncate max-w-xs" title="{{ $mapping->pms_name }}">
                                        {{ $mapping->pms_name }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="pl-6 py-5 whitespace-nowrap text-right font-mono text-xs text-bgray-600 dark:text-bgray-300">
                        @appDateTime($mapping->created_at)
                    </td>
                </tr>
            @empty
                <x-table-no-data :col-span="7" message="No record mappings found." sub-message="View relationships between external system records and PMS customers or projects." />
            @endforelse
        </table>
    </div>

    <x-pagination :paginator="$mappings" :per-page="$perPage" />
</div>
