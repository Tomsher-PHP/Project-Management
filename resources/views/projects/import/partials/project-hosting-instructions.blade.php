@php
    $instructionsData = [
        [
            'name' => 'Project Code',
            'required' => 'Yes',
            'type' => 'Existing project code',
            'mapping' => 'projects.code → project_id',
            'rules' => 'Exact existing projects.code; no project IDs.',
        ],
        [
            'name' => 'Service Name',
            'required' => 'Yes',
            'type' => 'Text',
            'mapping' => 'project_hosting_records.name',
            'rules' => 'Text; maximum 255 characters.',
        ],
        [
            'name' => 'Provider',
            'required' => 'Yes',
            'type' => 'Existing provider code',
            'mapping' => 'hosting_providers.code → project_hosting_records.hosting_provider_id',
            'rules' => 'Enter exact existing hosting_providers.code (as text), not provider name or database ID.',
        ],
        [
            'name' => 'Service Type',
            'required' => 'Yes',
            'type' => 'Existing service type code',
            'mapping' => 'hosting_services.code → project_hosting_records.hosting_service_id',
            'rules' => 'Enter exact existing hosting_services.code (as text), not service name or database ID.',
        ],
        [
            'name' => 'Status',
            'required' => 'Default if blank',
            'type' => 'Select exact lookup name',
            'mapping' => 'project_hosting_records.hosting_service_status_id',
            'rules' => 'Default is Active in supplied SQL; resolve default in importer.',
        ],
        [
            'name' => 'Ownership Type',
            'required' => 'Default if blank',
            'type' => 'company / customer',
            'mapping' => 'project_hosting_records.ownership_type',
            'rules' => 'company or customer (lowercase); default company.',
        ],
        [
            'name' => 'Domain',
            'required' => 'No',
            'type' => 'Text',
            'mapping' => 'project_hosting_records.domain',
            'rules' => 'Text; maximum 255 characters.',
        ],
        [
            'name' => 'Environment',
            'required' => 'No',
            'type' => 'Select exact lookup name',
            'mapping' => 'project_hosting_records.hosting_environment_id',
            'rules' => 'Exact environment name, or leave blank.',
        ],
        [
            'name' => 'Start Date',
            'required' => 'No',
            'type' => 'Date',
            'mapping' => 'project_hosting_records.start_date',
            'rules' => 'Excel date; YYYY-MM-DD.',
        ],
        [
            'name' => 'Expiry Date',
            'required' => 'No',
            'type' => 'Date',
            'mapping' => 'project_hosting_records.expiry_date',
            'rules' => 'Excel date; YYYY-MM-DD.',
        ],
        [
            'name' => 'Next Renewal Date',
            'required' => 'No',
            'type' => 'Date',
            'mapping' => 'project_hosting_records.next_renewal_date',
            'rules' => 'Excel date; YYYY-MM-DD.',
        ],
        [
            'name' => 'Last Renewed Date',
            'required' => 'No',
            'type' => 'Date',
            'mapping' => 'project_hosting_records.last_renewed_date',
            'rules' => 'Excel date; YYYY-MM-DD.',
        ],
        [
            'name' => 'Billing Cycle',
            'required' => 'No',
            'type' => 'Select exact lookup name',
            'mapping' => 'project_hosting_records.hosting_billing_cycle_id',
            'rules' => 'Exact billing cycle name, or leave blank.',
        ],
        [
            'name' => 'Billing Amount',
            'required' => 'No',
            'type' => 'Numeric decimal',
            'mapping' => 'project_hosting_records.billing_amount',
            'rules' => 'Decimal(12,2); up to 10 integer digits and 2 decimals.',
        ],
        [
            'name' => 'Currency',
            'required' => 'No',
            'type' => 'Text',
            'mapping' => 'project_hosting_records.currency_code',
            'rules' => 'Three-letter currency code, e.g. AED or USD.',
        ],
        [
            'name' => 'Reference',
            'required' => 'No',
            'type' => 'Text',
            'mapping' => 'project_hosting_records.reference',
            'rules' => 'Text; maximum 255 characters.',
        ],
        [
            'name' => 'Description',
            'required' => 'No',
            'type' => 'Text',
            'mapping' => 'project_hosting_records.description',
            'rules' => 'Long text; optional.',
        ],
        [
            'name' => 'Notes',
            'required' => 'No',
            'type' => 'Text',
            'mapping' => 'project_hosting_records.notes',
            'rules' => 'Long text; optional.',
        ],
    ];
@endphp

<!-- Instructions & Column Specifications Accordion (Default Collapsed) -->
<div class="mt-5 overflow-hidden rounded-xl border border-bgray-200 bg-white shadow-2xs dark:border-darkblack-400 dark:bg-darkblack-600" id="import-instructions-accordion">
    <!-- Accordion Header Button -->
    <button type="button" id="import-instructions-toggle" onclick="toggleInstructionsAccordion()" class="flex w-full items-center justify-between p-4 text-left transition hover:bg-bgray-50 dark:hover:bg-darkblack-500 focus:outline-none" aria-expanded="false" aria-controls="import-instructions-content">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center text-bgray-900 dark:text-bgray-300 shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold text-bgray-900 dark:text-white">
                        Column Instructions &amp; Format Examples
                    </h4>
                    <span class="rounded-full bg-bgray-100 px-2 py-0.5 text-[11px] font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                        18 Columns
                    </span>
                </div>
                <p class="text-xs text-bgray-700 dark:text-bgray-300 mt-0.5">
                    Review column specifications, required fields, and data formatting guidelines before uploading.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 shadow-2xs transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400">
                <span id="import-instructions-toggle-text">Expand</span>
                <svg id="import-instructions-toggle-icon" class="h-3.5 w-3.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </div>
    </button>

    <!-- Collapsible Table Content -->
    <div id="import-instructions-content" class="hidden border-t border-bgray-200 bg-white dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-bgray-200 bg-bgray-50/80 dark:border-darkblack-400 dark:bg-darkblack-500/60 text-bgray-900 dark:text-white">
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Column Name</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Required?</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Data Type</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Database Mapping</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px]">Instructions &amp; Examples</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400 text-bgray-700 dark:text-bgray-300">
                    @foreach ($instructionsData as $row)
                        <tr class="hover:bg-bgray-50/60 dark:hover:bg-darkblack-500/40">
                            <td class="py-3 px-4 font-semibold text-bgray-900 dark:text-white whitespace-nowrap">
                                {{ $row['name'] }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if ($row['required'] === 'Yes')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                        Yes
                                    </span>
                                @elseif ($row['required'] === 'Default if blank')
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-700/20 dark:bg-darkblack-500 dark:text-amber-400">
                                        Default if blank
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-bgray-100 px-2 py-0.5 text-[11px] font-medium text-bgray-600 ring-1 ring-inset ring-bgray-300 dark:bg-darkblack-500 dark:text-bgray-400">
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-bgray-800 dark:text-bgray-300">
                                {{ $row['type'] }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-bgray-700 dark:text-bgray-300 whitespace-nowrap">
                                <span class="rounded bg-bgray-100 px-1.5 py-0.5 dark:bg-darkblack-500">{{ $row['mapping'] }}</span>
                            </td>
                            <td class="py-3 px-4 text-bgray-600 dark:text-bgray-300">
                                {{ $row['rules'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function toggleInstructionsAccordion() {
            const content = document.getElementById('import-instructions-content');
            const toggleBtn = document.getElementById('import-instructions-toggle');
            const toggleText = document.getElementById('import-instructions-toggle-text');
            const toggleIcon = document.getElementById('import-instructions-toggle-icon');

            if (content) {
                const isHidden = content.classList.contains('hidden');
                if (isHidden) {
                    content.classList.remove('hidden');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                    if (toggleText) toggleText.textContent = 'Collapse';
                    if (toggleIcon) toggleIcon.classList.add('rotate-180');
                } else {
                    content.classList.add('hidden');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                    if (toggleText) toggleText.textContent = 'Expand';
                    if (toggleIcon) toggleIcon.classList.remove('rotate-180');
                }
            }
        }
    </script>
@endpush
