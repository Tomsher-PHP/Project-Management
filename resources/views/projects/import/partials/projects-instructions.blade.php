@php
    $instructionsData = [
        [
            'name' => 'Project Code',
            'create_req' => 'Blank (Auto)',
            'update_req' => 'Required',
            'type' => 'Text',
            'rules' => 'Leave blank to create a new project (auto-generated). Enter an existing Project Code to update that project. Unknown codes fail validation.',
            'example' => 'PRJ00001 (or blank for new)',
        ],
        [
            'name' => 'Project Name',
            'create_req' => 'Yes',
            'update_req' => 'Optional',
            'type' => 'Text',
            'rules' => 'Project title (max 255 characters). Blank preserves the existing Project Name on update.',
            'example' => 'New Corporate Website',
        ],
        [
            'name' => 'Customer Code',
            'create_req' => 'Yes',
            'update_req' => 'Optional',
            'type' => 'Existing Customer Code',
            'rules' => 'Exact existing customer code (customers.customer_code), not customer name. Blank preserves existing on update.',
            'example' => 'CUST001',
        ],
        [
            'name' => 'Project Flow',
            'create_req' => 'Yes',
            'update_req' => 'Must be blank',
            'type' => 'agile / linear',
            'rules' => 'Required on create (agile or linear). Must be left blank on update; existing Project Flow cannot be changed.',
            'example' => 'agile',
        ],
        [
            'name' => 'Priority',
            'create_req' => 'Yes',
            'update_req' => 'Optional',
            'type' => 'urgent / high / medium / low',
            'rules' => 'Project priority level. Blank preserves existing priority on update.',
            'example' => 'high',
        ],
        [
            'name' => 'Project Status Code',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Existing Status Code',
            'rules' => 'Exact existing PMS project status code (not display name). Defaults to system default on create; blank preserves existing on update.',
            'example' => '100001',
        ],
        [
            'name' => 'Project Stage Code',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Existing Stage Code',
            'rules' => 'Exact existing PMS project stage code (not display name). Defaults to system default on create; blank preserves existing on update.',
            'example' => '100001',
        ],
        [
            'name' => 'Service Type Codes',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Comma-separated Codes',
            'rules' => 'Comma-separated existing PMS service type codes. Blank preserves existing values on update.',
            'example' => '100001, 100002',
        ],
        [
            'name' => 'Category Codes',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Comma-separated Codes',
            'rules' => 'Comma-separated existing PMS project category codes. Blank preserves existing values on update.',
            'example' => '100001, 100002',
        ],
        [
            'name' => 'Timeline Name',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Text',
            'rules' => 'Original timeline name (defaults to "Original Timeline" on create). Applies to the original timeline only; blank preserves existing on update.',
            'example' => 'Original Timeline',
        ],
        [
            'name' => 'Start Date',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Date (YYYY-MM-DD)',
            'rules' => 'Original timeline start date in YYYY-MM-DD format. Defaults to today on create; blank preserves existing on update.',
            'example' => '2026-11-15',
        ],
        [
            'name' => 'End Date',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Date (YYYY-MM-DD)',
            'rules' => 'Original timeline end date in YYYY-MM-DD format. Must be on or after Start Date. Blank preserves existing on update.',
            'example' => '2027-02-15',
        ],
        [
            'name' => 'Customer End Date',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Date (YYYY-MM-DD)',
            'rules' => 'Customer deadline in YYYY-MM-DD format. Must be on or after End Date. Blank preserves existing on update.',
            'example' => '2027-02-20',
        ],
        [
            'name' => 'Estimated Hours',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Numeric Hours',
            'rules' => 'Non-negative numeric hours for the original timeline estimate. Blank preserves existing on update.',
            'example' => '120',
        ],
        [
            'name' => 'Customer Estimated Hours',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Numeric Hours',
            'rules' => 'Non-negative numeric hours for the customer estimate. Blank preserves existing on update.',
            'example' => '145',
        ],
        [
            'name' => 'Technologies',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Comma-separated Names',
            'rules' => 'Comma-separated existing technology names in PMS. Blank preserves existing values on update.',
            'example' => 'Laravel, React',
        ],
        [
            'name' => 'Sales Person Email',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'User Email',
            'rules' => 'Resolve using an existing PMS user email address (names are not accepted). Blank preserves existing on update.',
            'example' => 'sales@example.com',
        ],
        [
            'name' => 'Domain',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Text',
            'rules' => 'Project domain name. Blank preserves existing domain on update.',
            'example' => 'example.com',
        ],
        [
            'name' => 'Default Billable',
            'create_req' => 'Optional',
            'update_req' => 'Optional',
            'type' => 'Yes / No',
            'rules' => 'Enter Yes or No. Defaults to Yes on create; blank preserves existing value on update.',
            'example' => 'Yes',
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
                        19 Columns
                    </span>
                </div>
                <p class="text-xs text-bgray-700 dark:text-bgray-300 mt-0.5">
                    Review column rules for creating and updating projects, required fields, master codes, and examples before uploading.
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
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Required for Create</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Required for Update</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Data Type</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px]">Instructions</th>
                        <th class="py-3 px-4 font-bold uppercase tracking-wider text-[11px] whitespace-nowrap">Example</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400 text-bgray-700 dark:text-bgray-300">
                    @foreach ($instructionsData as $row)
                        <tr class="hover:bg-bgray-50/60 dark:hover:bg-darkblack-500/40">
                            <td class="py-3 px-4 font-semibold text-bgray-900 dark:text-white whitespace-nowrap">
                                {{ $row['name'] }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if ($row['create_req'] === 'Yes')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                        Yes
                                    </span>
                                @elseif ($row['create_req'] === 'Blank (Auto)')
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-700/20 dark:bg-darkblack-500 dark:text-amber-400">
                                        Blank (Auto)
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-bgray-100 px-2 py-0.5 text-[11px] font-medium text-bgray-600 ring-1 ring-inset ring-bgray-300 dark:bg-darkblack-500 dark:text-bgray-400">
                                        Optional
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if ($row['update_req'] === 'Required')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                        Required
                                    </span>
                                @elseif ($row['update_req'] === 'Must be blank')
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-700/20 dark:bg-darkblack-500 dark:text-amber-400">
                                        Must be blank
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-bgray-100 px-2 py-0.5 text-[11px] font-medium text-bgray-600 ring-1 ring-inset ring-bgray-300 dark:bg-darkblack-500 dark:text-bgray-400">
                                        Optional
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-bgray-800 dark:text-bgray-300">
                                {{ $row['type'] }}
                            </td>
                            <td class="py-3 px-4 text-bgray-600 dark:text-bgray-300">
                                {{ $row['rules'] }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-bgray-700 dark:text-bgray-300 whitespace-nowrap">
                                <span class="rounded bg-bgray-100 px-1.5 py-0.5 dark:bg-darkblack-500">{{ $row['example'] }}</span>
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

