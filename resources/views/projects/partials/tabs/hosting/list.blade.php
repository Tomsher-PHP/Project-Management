<div class="overflow-x-auto">
    @if ($hostingRecords->isEmpty())
        <div class="p-5">
            <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                No hosting records available yet.
            </div>
        </div>
    @else
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
                        Ownership
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                        Status
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                        Environment
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-bgray-900 dark:text-white">
                        Date
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
                    @endphp
                    <tr class="transition hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/30">
                        <td class="px-5 py-4">
                            <div class="text-sm font-semibold text-bgray-900 dark:text-white">
                                {{ $record->name }}
                            </div>
                            @if ($record->domain)
                                <div class="mt-0.5 text-xs text-bgray-700 dark:text-bgray-300">
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        {{ $record->domain }}
                                    </span>
                                </div>
                            @endif
                            @if ($record->hostingService)
                                <div class="mt-0.5 text-xs text-bgray-700 dark:text-bgray-300">
                                    {{ $record->hostingService->name }}
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-sm font-medium text-bgray-800 dark:text-bgray-300">
                                {{ $record->hostingProvider?->name ?? '--' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            @if ($isCompany)
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                                    {{ $ownershipLabel }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-purple-700 dark:text-purple-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>
                                    {{ $ownershipLabel }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if ($record->hostingServiceStatus)
                                <span class="inline-flex items-center rounded-full bg-bgray-100 px-2.5 py-0.5 text-xs font-medium text-bgray-800 dark:bg-darkblack-500 dark:text-bgray-300">
                                    {{ $record->hostingServiceStatus->name }}
                                </span>
                            @else
                                <span class="text-sm text-bgray-700 dark:text-bgray-300">--</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-sm text-bgray-700 dark:text-bgray-300">
                                {{ $record->hostingEnvironment?->name ?? '--' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="text-sm text-bgray-900 dark:text-white">
                                @if ($record->start_date)
                                    <div><span class="text-xs text-bgray-700 dark:text-bgray-300">Start:</span> @appDate($record->start_date)</div>
                                @endif
                                @if ($record->expiry_date)
                                    <div><span class="text-xs text-bgray-700 dark:text-bgray-300">Exp:</span> @appDate($record->expiry_date)</div>
                                @endif
                                @if ($record->next_renewal_date)
                                    <div class="text-xs text-bgray-700 dark:text-bgray-300">
                                        Ren: @appDate($record->next_renewal_date)
                                    </div>
                                @endif
                                @if (!$record->start_date && !$record->expiry_date && !$record->next_renewal_date)
                                    <span class="text-bgray-700 dark:text-bgray-300">--</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @if ($record->billing_amount !== null)
                                <div class="text-sm font-semibold text-bgray-900 dark:text-white">
                                    {{ $record->currency_code ? $record->currency_code . ' ' : '' }}{{ number_format((float) $record->billing_amount, 2) }}
                                </div>
                                @if ($record->hostingBillingCycle)
                                    <div class="text-xs text-bgray-700 dark:text-bgray-300">
                                        / {{ $record->hostingBillingCycle->name }}
                                    </div>
                                @endif
                            @else
                                <span class="text-sm text-bgray-700 dark:text-bgray-300">--</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('project_hosting.view')
                                    <x-view-button :action="route('projects.hosting.show', [$project, $record])" data-project-hosting-view data-hosting-id="{{ $record->id }}" title="View" />
                                @endcan

                                @can('project_hosting.edit')
                                    <x-edit-button :action="route('projects.hosting.show', [$project, $record]) . '?mode=edit'" data-project-hosting-edit data-hosting-id="{{ $record->id }}" title="Edit" />
                                @endcan

                                @can('project_hosting.delete')
                                    <x-delete-form :action="route('projects.hosting.destroy', [$project, $record])" :id="$record->id" ajax="true" renderTarget="#project-hosting-history" renderMode="replace_inner" formClass="delete-form project-hosting-delete-form" confirmTitle="Delete Hosting Record" confirmMessage="Are you sure you want to delete this hosting record?" data-project-hosting-delete title="Delete" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
