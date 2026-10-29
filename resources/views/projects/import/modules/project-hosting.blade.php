<div class="space-y-6">
    <!-- Active / Recent Queued Import Status Tracker -->
    @if (!empty($activeImport))
        <div id="import-status-card" class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8" data-import-id="{{ $activeImport->id }}" data-module-key="{{ $module['key'] }}" data-initial-status="{{ $activeImport->status }}" data-status-url="{{ route('projects.import.status', ['module' => $module['key'], 'import' => $activeImport->id]) }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-bgray-200 pb-5 dark:border-darkblack-400">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                            Queued Import Job #{{ $activeImport->id }}
                        </span>
                        <span class="text-xs text-bgray-400">&bull;</span>
                        <span class="text-xs font-mono text-bgray-600 dark:text-bgray-300">{{ $activeImport->file_name }}</span>
                    </div>
                    <h4 class="mt-1 text-lg font-bold text-bgray-900 dark:text-white flex items-center gap-2">
                        <span>Import Processing Status</span>
                    </h4>
                </div>

                <div class="flex items-center gap-3">
                    <span id="import-status-badge">
                        @if ($activeImport->status === \App\Models\ProjectImport::STATUS_PENDING)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Pending in Queue
                            </span>
                        @elseif ($activeImport->status === \App\Models\ProjectImport::STATUS_PROCESSING)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-darkblack-500 dark:text-blue-400">
                                <svg class="animate-spin h-3.5 w-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Processing Rows...
                            </span>
                        @elseif ($activeImport->status === \App\Models\ProjectImport::STATUS_COMPLETED)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-600 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Completed Successfully
                            </span>
                        @elseif ($activeImport->status === \App\Models\ProjectImport::STATUS_COMPLETED_WITH_ERRORS)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400">
                                Completed with Row Errors
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                Failed
                            </span>
                        @endif
                    </span>

                    <button type="button" id="btn-refresh-import-status" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 transition" title="Refresh status">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Metrics -->
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-bgray-200 bg-bgray-50/60 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Total Rows</span>
                    <p id="metric-total-rows" class="mt-1 text-2xl font-bold text-bgray-900 dark:text-white">
                        {{ $activeImport->total_rows ?? '-' }}
                    </p>
                </div>
                <div class="rounded-lg border border-bgray-200 bg-bgray-50/60 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Processed</span>
                    <p id="metric-processed-rows" class="mt-1 text-2xl font-bold text-bgray-900 dark:text-white">
                        {{ $activeImport->processed_rows ?? 0 }}
                    </p>
                </div>
                <div class="rounded-lg border border-success-200 bg-success-50/40 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <span class="text-xs font-medium text-success-600 dark:text-success-400">Created Records</span>
                    <p id="metric-successful-rows" class="mt-1 text-2xl font-bold text-success-500 dark:text-success-300">
                        {{ $activeImport->successful_rows ?? 0 }}
                    </p>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50/40 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <span class="text-xs font-medium text-red-600 dark:text-red-400">Failed Rows</span>
                    <p id="metric-failed-rows" class="mt-1 text-2xl font-bold text-red-500 dark:text-red-400">
                        {{ $activeImport->failed_rows ?? 0 }}
                    </p>
                </div>
            </div>

            <!-- Error message if any -->
            <div id="import-error-container" class="{{ !empty($activeImport->error_message) ? '' : 'hidden' }} mt-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-darkblack-500">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-xs text-red-700 dark:text-red-300" id="import-error-text">
                        {{ $activeImport->error_message }}
                    </div>
                </div>
            </div>

            <!-- Row Level Results Table if available -->
            @if (!empty($activeImport->row_results))
                <div class="mt-6 pt-5 border-t border-bgray-200 dark:border-darkblack-400">
                    <h5 class="text-sm font-bold text-bgray-900 dark:text-white mb-3">
                        Row Execution Outcomes
                    </h5>
                    <div class="overflow-x-auto max-h-72 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-bgray-200 text-[11px] uppercase tracking-wider text-bgray-500 dark:border-darkblack-400 dark:text-bgray-400 sticky top-0 bg-white dark:bg-darkblack-600">
                                <tr>
                                    <th class="py-2.5 px-3">Excel Row</th>
                                    <th class="py-2.5 px-3">Project Code</th>
                                    <th class="py-2.5 px-3">Service Name</th>
                                    <th class="py-2.5 px-3">Outcome</th>
                                    <th class="py-2.5 px-3">Message</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400" id="import-row-results-body">
                                @foreach ($activeImport->row_results as $outcome)
                                    <tr>
                                        <td class="py-2 px-3 font-mono font-bold">Row {{ $outcome['excel_row'] ?? '-' }}</td>
                                        <td class="py-2 px-3 font-mono">{{ $outcome['project_code'] ?? '-' }}</td>
                                        <td class="py-2 px-3">{{ $outcome['service_name'] ?? '-' }}</td>
                                        <td class="py-2 px-3">
                                            @if (($outcome['status'] ?? '') === 'Success')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-success-50 text-success-600 dark:bg-darkblack-500 dark:text-success-400">Success</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-red-600 dark:bg-darkblack-500 dark:text-red-400">Failed</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 {{ ($outcome['status'] ?? '') === 'Success' ? 'text-success-600 dark:text-success-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $outcome['message'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Module Overview & Template Card -->
    <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">
        <!-- Module Header -->
        <div class="border-b border-bgray-200 pb-6 dark:border-darkblack-400">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-400 dark:bg-darkblack-500">
                            Active Module
                        </span>
                        <span class="text-xs text-bgray-500 dark:text-bgray-400">Key: project_hosting</span>
                    </div>
                    <h3 class="mt-2 text-xl font-bold text-bgray-900 dark:text-white sm:text-2xl">
                        {{ $module['label'] ?? 'Project Hosting Records' }}
                    </h3>
                    <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">
                        {{ $module['description'] ?? 'Import hosting records associated with multiple projects.' }}
                    </p>
                </div>

                <!-- Module Template Download Action -->
                <div class="flex-shrink-0">
                    @if (!empty($module['has_template']))
                        <a href="{{ route('projects.import.template', $module['key']) }}" class="inline-flex items-center gap-2 rounded-lg border border-success-300 bg-success-300 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:border-success-400 hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 dark:border-success-400 dark:bg-success-400 dark:hover:bg-success-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                            </svg>
                            <span>Download Excel Template</span>
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-bgray-300 bg-bgray-100 px-3 py-1.5 text-xs font-medium text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-400">
                            No template available
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Workflow Guidelines -->
        <div class="mt-8">
            <h4 class="text-base font-semibold text-bgray-900 dark:text-white">
                Import Workflow &amp; Guidelines
            </h4>
            <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-400">
                Follow these steps to prepare, validate, and verify your project hosting spreadsheet before importing:
            </p>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Step 1 -->
                <div class="relative flex flex-col justify-between rounded-lg border border-bgray-200 bg-bgray-50/60 p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-success-50 text-sm font-bold text-success-400 ring-2 ring-success-200 dark:bg-darkblack-600 dark:text-success-300 dark:ring-darkblack-400">
                                1
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Step 1</span>
                        </div>
                        <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                            Download Template
                        </h5>
                        <p class="mt-1 text-xs leading-relaxed text-bgray-600 dark:text-bgray-400">
                            Get the preconfigured Excel template with all 18 standard columns, instructions, examples, and validation rules.
                        </p>
                    </div>

                    @if (!empty($module['has_template']))
                        <div class="mt-4 pt-3 border-t border-bgray-200 dark:border-darkblack-400">
                            <a href="{{ route('projects.import.template', $module['key']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-success-500 hover:text-success-600 dark:text-success-400 dark:hover:text-success-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                                </svg>
                                <span>Download (.xlsx) &rarr;</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Step 2 -->
                <div class="relative flex flex-col justify-between rounded-lg border border-bgray-200 bg-bgray-50/60 p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-success-50 text-sm font-bold text-success-400 ring-2 ring-success-200 dark:bg-darkblack-600 dark:text-success-300 dark:ring-darkblack-400">
                                2
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Step 2</span>
                        </div>
                        <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                            Fill in Details
                        </h5>
                        <p class="mt-1 text-xs leading-relaxed text-bgray-600 dark:text-bgray-400">
                            Fill in the "Hosting Upload" worksheet with Project Codes, service types, providers, dates, environments, and billing info.
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative flex flex-col justify-between rounded-lg border border-bgray-200 bg-bgray-50/60 p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-success-50 text-sm font-bold text-success-400 ring-2 ring-success-200 dark:bg-darkblack-600 dark:text-success-300 dark:ring-darkblack-400">
                                3
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Step 3</span>
                        </div>
                        <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                            Validate Workbook
                        </h5>
                        <p class="mt-1 text-xs leading-relaxed text-bgray-600 dark:text-bgray-400">
                            Upload your spreadsheet below to run non-destructive row validation against projects, permissions, and master tables.
                        </p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="relative flex flex-col justify-between rounded-lg border border-bgray-200 bg-bgray-50/60 p-5 dark:border-darkblack-400 dark:bg-darkblack-500">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-success-50 text-sm font-bold text-success-400 ring-2 ring-success-200 dark:bg-darkblack-600 dark:text-success-300 dark:ring-darkblack-400">
                                4
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">Step 4</span>
                        </div>
                        <h5 class="mt-3 text-sm font-semibold text-bgray-900 dark:text-white">
                            Review Results
                        </h5>
                        <p class="mt-1 text-xs leading-relaxed text-bgray-600 dark:text-bgray-400">
                            Inspect the summary table and fix any reported errors before performing the eventual database import.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload & Validate Card -->
    <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">
        <div class="border-b border-bgray-200 pb-5 dark:border-darkblack-400">
            <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                Upload &amp; Validate Spreadsheet
            </h4>
            <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">
                Select your completed <span class="font-mono text-xs font-semibold text-bgray-800 dark:text-bgray-200">.xlsx</span> workbook to validate structure, master data lookups, and project permissions.
                <span class="font-semibold text-success-500 dark:text-success-400">Validation is strictly read-only; no database records will be created, updated, or deleted.</span>
            </p>
        </div>

        <form action="{{ route('projects.import.validate', $module['key']) }}" method="POST" enctype="multipart/form-data" class="mt-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <label for="excel-file-input" class="block text-xs font-semibold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">
                        Choose Excel Workbook (.xlsx)
                    </label>

                    <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-3">
                        <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-bgray-300 bg-bgray-50 px-4 py-2.5 text-sm font-semibold text-bgray-700 transition hover:bg-bgray-100 hover:border-success-300 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-200 dark:hover:bg-darkblack-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>Browse File...</span>
                            <input id="excel-file-input" type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" onchange="document.getElementById('file-chosen-name').textContent = this.files[0] ? this.files[0].name : 'No file selected';" required />
                        </label>

                        <span id="file-chosen-name" class="truncate text-xs font-medium text-bgray-500 dark:text-bgray-400">
                            No file selected
                        </span>
                    </div>

                    @error('file')
                        <p class="mt-2 text-xs font-medium text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                    <p class="mt-2 text-xs text-bgray-500 dark:text-bgray-400">
                        Supported format: Microsoft Excel (.xlsx), maximum 10 MB. Only the "Hosting Upload" sheet is processed.
                    </p>
                </div>

                <div class="flex items-end justify-start lg:justify-end">
                    <button type="submit" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg bg-success-300 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-success-400 dark:hover:bg-success-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Validate Excel File</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Validation Results Section -->
    @if (!empty($validationResult))
        <div class="space-y-6">
            <!-- Structure Error Alert -->
            @if (!$validationResult['is_valid_structure'])
                <div class="rounded-lg border border-red-300 bg-red-50 p-6 dark:border-red-900/50 dark:bg-darkblack-500">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 text-red-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-base font-bold text-red-800 dark:text-red-400">
                                Workbook Structure Error
                            </h5>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                {{ $validationResult['structure_error'] }}
                            </p>
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                Please download and use the official template without altering worksheet names or header columns.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- No Data Rows Alert -->
            @elseif ($validationResult['total_rows'] === 0)
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-6 dark:border-amber-900/40 dark:bg-darkblack-500">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 text-amber-500">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-base font-bold text-amber-800 dark:text-amber-400">
                                No Data Rows Found
                            </h5>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                The uploaded workbook <span class="font-semibold">{{ $validationResult['filename'] }}</span> contains the correct structure, but no non-blank data rows were found in the "Hosting Upload" worksheet.
                            </p>
                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                Please populate data starting from Row 2 and upload again.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Row Validation Summary & Detailed Results -->
            @else
                <!-- Summary Metrics Card -->
                <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-bgray-200 pb-5 dark:border-darkblack-400">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                                Validation Report
                            </span>
                            <h4 class="mt-1 text-lg font-bold text-bgray-900 dark:text-white">
                                File: {{ $validationResult['filename'] }}
                            </h4>
                        </div>

                        <div>
                            @if ($validationResult['is_all_valid'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-500 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    All Rows Valid (Ready for Import)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Validation Issues Detected ({{ $validationResult['invalid_rows_count'] }} Failed)
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Metrics Tiles -->
                    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50/60 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Total Rows Examined</span>
                            <p class="mt-1 text-2xl font-bold text-bgray-900 dark:text-white">
                                {{ $validationResult['total_rows'] }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-success-200 bg-success-50/40 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <span class="text-xs font-medium text-success-600 dark:text-success-400">Valid Rows</span>
                            <p class="mt-1 text-2xl font-bold text-success-500 dark:text-success-300">
                                {{ $validationResult['valid_rows_count'] }}
                            </p>
                        </div>

                        <div class="rounded-lg border {{ $validationResult['invalid_rows_count'] > 0 ? 'border-red-200 bg-red-50/40' : 'border-bgray-200 bg-bgray-50/60' }} p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <span class="text-xs font-medium {{ $validationResult['invalid_rows_count'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-bgray-500 dark:text-bgray-400' }}">
                                Invalid Rows
                            </span>
                            <p class="mt-1 text-2xl font-bold {{ $validationResult['invalid_rows_count'] > 0 ? 'text-red-500 dark:text-red-400' : 'text-bgray-900 dark:text-white' }}">
                                {{ $validationResult['invalid_rows_count'] }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-bgray-200 bg-bgray-50/60 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">Database Writes</span>
                            <p class="mt-1 text-2xl font-bold text-bgray-700 dark:text-bgray-300">
                                0
                            </p>
                            <span class="text-[10px] text-bgray-400">Read-only preview</span>
                        </div>
                    </div>
                </div>

                @if ($validationResult['is_all_valid'])
                    <!-- Confirm & Start Queued Import Action Card -->
                    <div class="rounded-lg border border-success-300 bg-success-50/50 p-6 dark:border-success-800 dark:bg-darkblack-500">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-bold text-success-700 dark:bg-darkblack-600 dark:text-success-400">
                                        Validation Passed
                                    </span>
                                    <span class="text-xs text-success-700 dark:text-success-400 font-medium">Ready for Queued Processing</span>
                                </div>
                                <h5 class="mt-2 text-base font-bold text-bgray-900 dark:text-white">
                                    Queue Asynchronous Database Import
                                </h5>
                                <p class="mt-1 text-xs text-bgray-600 dark:text-bgray-300 max-w-xl">
                                    All {{ $validationResult['valid_rows_count'] }} non-blank rows are valid. Confirm by uploading the file below to dispatch the asynchronous import job.
                                </p>
                            </div>

                            <form action="{{ route('projects.import.process', $module['key']) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                @csrf
                                <div class="relative">
                                    <input type="file" name="file" accept=".xlsx" required class="block w-full text-xs text-bgray-600 dark:text-bgray-300 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-white dark:file:bg-darkblack-600 file:text-bgray-700 dark:file:text-bgray-200 file:border file:border-bgray-300 dark:file:border-darkblack-400 hover:file:bg-bgray-50 cursor-pointer">
                                </div>
                                <button type="submit" onclick="return confirm('Are you sure you want to queue this file for database import?');" class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-300 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-success-400 dark:hover:bg-success-500 whitespace-nowrap transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>Confirm &amp; Import Records</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Row-by-Row Results Table -->
                <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8">
                    <div class="flex items-center justify-between pb-4">
                        <h5 class="text-base font-bold text-bgray-900 dark:text-white">
                            Row-Level Validation Results
                        </h5>
                        <span class="text-xs text-bgray-500 dark:text-bgray-400">
                            Showing {{ count($validationResult['rows']) }} non-blank rows
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-bgray-200 text-xs uppercase tracking-wider text-bgray-500 dark:border-darkblack-400 dark:text-bgray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-4 font-semibold">Excel Row</th>
                                    <th scope="col" class="py-3 px-4 font-semibold">Project Code</th>
                                    <th scope="col" class="py-3 px-4 font-semibold">Service Name</th>
                                    <th scope="col" class="py-3 px-4 font-semibold">Status</th>
                                    <th scope="col" class="py-3 px-4 font-semibold">Validation Messages</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400">
                                @foreach ($validationResult['rows'] as $row)
                                    <tr class="hover:bg-bgray-50 dark:hover:bg-darkblack-500 transition-colors">
                                        <td class="py-3.5 px-4 font-mono font-bold text-xs text-bgray-700 dark:text-bgray-300">
                                            Row {{ $row['row_number'] }}
                                        </td>
                                        <td class="py-3.5 px-4 font-mono text-xs text-bgray-900 dark:text-white font-medium">
                                            {{ $row['project_code'] }}
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-bgray-900 dark:text-white font-medium">
                                            {{ $row['service_name'] }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            @if ($row['status'] === 'Valid')
                                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2 py-0.5 text-xs font-semibold text-success-500 dark:bg-darkblack-400 dark:text-success-300">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-success-400"></span>
                                                    Valid
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-500 dark:bg-darkblack-400 dark:text-red-400">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                                                    Invalid
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-xs">
                                            @if (empty($row['errors']))
                                                <span class="text-success-500 dark:text-success-400 font-medium flex items-center gap-1">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    Ready for import
                                                </span>
                                            @else
                                                <ul class="space-y-1 text-red-600 dark:text-red-400">
                                                    @foreach ($row['errors'] as $error)
                                                        <li class="flex items-start gap-1.5">
                                                            <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-400"></span>
                                                            <span>{{ $error }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @push('scripts')
        @vite('resources/js/modules/projects/imports/project-imports.js')
    @endpush
</div>
