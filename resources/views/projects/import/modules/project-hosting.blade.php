<div class="space-y-6">

    <!-- Module Overview & Template Card -->
    <div class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-bold text-bgray-900 dark:text-white">
                    {{ $module['label'] ?? 'Project Hosting Records' }}
                </h3>

                <!-- Minimal Single-Row Import Steps -->
                <div class="mt-2.5 flex items-center gap-3 text-xs text-bgray-600 dark:text-bgray-400 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-[11px] font-bold text-success-500 ring-1 ring-inset ring-success-500/20 dark:bg-darkblack-500 dark:text-success-400">1</span>
                        Download Template
                    </span>
                    <svg class="h-3 w-3 text-bgray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-[11px] font-bold text-success-500 ring-1 ring-inset ring-success-500/20 dark:bg-darkblack-500 dark:text-success-400">2</span>
                        Fill in Details
                    </span>
                    <svg class="h-3 w-3 text-bgray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-50 text-[11px] font-bold text-success-500 ring-1 ring-inset ring-success-500/20 dark:bg-darkblack-500 dark:text-success-400">3</span>
                        Upload &amp; Import
                    </span>
                </div>
            </div>

            <!-- Template Download Action -->
            <div class="flex-shrink-0">
                @if (!empty($module['has_template']))
                    <a href="{{ route('projects.import.template', $module['key']) }}" class="inline-flex items-center gap-2 rounded-lg border border-success-300 bg-success-300 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:border-success-400 hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 dark:border-success-400 dark:bg-success-400 dark:hover:bg-success-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                        </svg>
                        <span>Template</span>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-md border border-bgray-300 bg-bgray-100 px-3 py-1.5 text-xs font-medium text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-400">
                        No template available
                    </span>
                @endif
            </div>
        </div>

        <form id="import-form" action="{{ route('projects.import.process', $module['key']) }}" method="POST" enctype="multipart/form-data" class="mt-6">
            @csrf

            <!-- Upload Box (Drag and Drop) -->
            <div id="file-upload-box" class="border-2 border-dashed border-bgray-300 rounded-xl p-6 text-center cursor-pointer hover:border-success-300 transition dark:border-darkblack-400">
                <p id="file-upload-prompt" class="text-bgray-700 dark:text-bgray-300">
                    Attach your Excel file <span class="text-success-300 font-semibold">click to upload</span>
                </p>
                <div id="file-chosen-container" class="hidden items-center justify-center gap-2">
                    <svg class="h-5 w-5 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span id="file-chosen-name" class="font-semibold text-bgray-900 dark:text-white"></span>
                    <span class="text-xs text-success-500 ml-1 font-normal">(click to change)</span>
                </div>
                <input id="excel-file-input" type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" required />
            </div>

            <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-error-300 text-sm">Files includes xlsx and max file size is 20MB</p>

                <button type="submit" id="btn-start-import" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg bg-success-300 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition duration-200 hover:bg-success-400 focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-success-400 dark:hover:bg-success-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg id="btn-import-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <svg id="btn-import-spinner" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="btn-import-label">Import Records</span>
                </button>
            </div>

            @error('file')
                <p class="mt-2 text-xs font-medium text-red-500">
                    {{ $message }}
                </p>
            @enderror
        </form>
    </div>

    <!-- Active / Recent Queued Import Status & Results Tracker -->
    @if (!empty($activeImport))
        @php
            $isFinished = in_array($activeImport->status, [\App\Services\ProjectImportTrackingManager::STATUS_COMPLETED, \App\Services\ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS, \App\Services\ProjectImportTrackingManager::STATUS_FAILED, \App\Services\ProjectImportTrackingManager::STATUS_INTERRUPTED], true);
            $total = (int) ($activeImport->total_rows ?? 0);
            $processed = (int) ($activeImport->processed_rows ?? 0);
            $percent = $total > 0 ? min(100, round(($processed / $total) * 100)) : ($isFinished ? 100 : 0);
            $failedCount = (int) ($activeImport->failed_rows ?? 0);
            $successCount = (int) ($activeImport->successful_rows ?? 0);
        @endphp

        <div id="import-status-card" class="rounded-lg border border-bgray-200 bg-white p-6 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-8" data-import-id="{{ $activeImport->id }}" data-module-key="{{ $module['key'] }}" data-initial-status="{{ $activeImport->status }}" data-status-url="{{ route('projects.import.status', ['module' => $module['key'], 'import' => $activeImport->id]) }}">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-bgray-200 pb-5 dark:border-darkblack-400">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-bgray-500 dark:text-bgray-400">
                            Import Task
                        </span>
                        <span class="text-xs text-bgray-400">&bull;</span>
                        <span class="text-xs font-mono text-bgray-600 dark:text-bgray-300">{{ $activeImport->file_name }}</span>
                    </div>
                    <h4 class="mt-1 text-lg font-bold text-bgray-900 dark:text-white flex items-center gap-2">
                        <span id="import-title-text">
                            @if ($isFinished)
                                Import Results
                            @else
                                Import Processing Progress
                            @endif
                        </span>
                    </h4>
                </div>

                <div class="flex items-center gap-3">
                    <span id="import-status-badge">
                        @if ($activeImport->status === \App\Services\ProjectImportTrackingManager::STATUS_PENDING)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Pending in Queue
                            </span>
                        @elseif ($activeImport->status === \App\Services\ProjectImportTrackingManager::STATUS_PROCESSING)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-darkblack-500 dark:text-blue-400">
                                <svg class="animate-spin h-3.5 w-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Processing Rows...
                            </span>
                        @elseif ($activeImport->status === \App\Services\ProjectImportTrackingManager::STATUS_COMPLETED)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-600 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Completed Successfully
                            </span>
                        @elseif ($activeImport->status === \App\Services\ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400">
                                Completed with Row Errors
                            </span>
                        @elseif ($activeImport->status === \App\Services\ProjectImportTrackingManager::STATUS_INTERRUPTED)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                Processing Interrupted
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

            <!-- Progress Bar Section -->
            <div id="import-progress-container" class="mt-5 {{ $isFinished ? 'hidden' : '' }}">
                <div class="flex items-center justify-between text-xs font-semibold text-bgray-600 dark:text-bgray-300 mb-1.5">
                    <span>Import Progress</span>
                    <span id="import-progress-percent">{{ $percent }}%</span>
                </div>
                <div class="w-full bg-bgray-200 rounded-full h-2.5 dark:bg-darkblack-500 overflow-hidden">
                    <div id="import-progress-bar" class="bg-success-400 h-2.5 rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <!-- Summary Text Banner (shown when finished) -->
            <div id="import-summary-banner" class="mt-5 {{ $isFinished ? '' : 'hidden' }} rounded-lg border border-bgray-200 bg-bgray-50/80 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm text-bgray-900 dark:text-white" id="import-summary-text">
                            @if ($activeImport->status === \App\Services\ProjectImportTrackingManager::STATUS_INTERRUPTED)
                                Import interrupted: {{ $processed }} rows processed, {{ $successCount }} successful, {{ $failedCount }} failed.
                            @elseif ($isFinished)
                                Import completed: {{ $processed }} rows processed, {{ $successCount }} successful, {{ $failedCount }} failed.
                            @endif
                        </span>
                    </div>

                    <!-- Optional Excel Downloads -->
                    <div class="flex items-center gap-2.5 flex-wrap" id="import-download-actions">
                        <a id="btn-download-failed" href="{{ route('projects.import.download.failed', ['module' => $module['key'], 'import' => $activeImport->id]) }}" class="{{ $failedCount > 0 ? 'inline-flex' : 'hidden' }} items-center gap-1.5 rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-darkblack-500 dark:text-red-400 transition" title="Download only failed rows to correct and re-upload">
                            <svg class="h-3.5 w-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Download Failed Rows</span>
                        </a>

                        <a id="btn-download-results" href="{{ route('projects.import.download.results', ['module' => $module['key'], 'import' => $activeImport->id]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-bgray-300 bg-white px-3 py-1.5 text-xs font-semibold text-bgray-700 hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 transition" title="Download complete audit log of all processed rows">
                            <svg class="h-3.5 w-3.5 text-bgray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Download All Results</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metrics Tiles -->
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
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

            <!-- Error message if file-level issue -->
            <div id="import-error-container" class="{{ !empty($activeImport->error_message) ? '' : 'hidden' }} mt-5 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-darkblack-500">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-xs text-red-700 dark:text-red-300" id="import-error-text">
                        {{ $activeImport->error_message }}
                    </div>
                </div>
            </div>

            <!-- Results Table Section -->
            <div id="import-results-section" class="mt-6 pt-5 border-t border-bgray-200 dark:border-darkblack-400 {{ !empty($activeImport->row_results) ? '' : 'hidden' }}">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <h5 class="text-sm font-bold text-bgray-900 dark:text-white">
                        Processed Row Outcomes
                    </h5>

                    <!-- Filters -->
                    <div class="flex items-center gap-1.5 bg-bgray-100 p-1 rounded-lg dark:bg-darkblack-500 text-xs" id="results-filter-bar">
                        <button type="button" class="filter-btn active rounded-md px-2.5 py-1 font-semibold text-bgray-900 bg-white shadow-sm dark:bg-darkblack-600 dark:text-white" data-filter="all">
                            All (<span id="filter-count-all">{{ count($activeImport->row_results ?? []) }}</span>)
                        </button>
                        <button type="button" class="filter-btn rounded-md px-2.5 py-1 font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-filter="failed">
                            Failed (<span id="filter-count-failed">{{ $failedCount }}</span>)
                        </button>
                        <button type="button" class="filter-btn rounded-md px-2.5 py-1 font-semibold text-bgray-600 hover:text-bgray-900 dark:text-bgray-400 dark:hover:text-white" data-filter="success">
                            Success (<span id="filter-count-success">{{ $successCount }}</span>)
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-bgray-200 dark:border-darkblack-400">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-bgray-200 bg-bgray-50 text-[11px] uppercase tracking-wider text-bgray-500 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-400">
                            <tr>
                                <th class="py-2.5 px-4 font-semibold w-24">Excel Row</th>
                                <th class="py-2.5 px-4 font-semibold w-36">Project Code</th>
                                <th class="py-2.5 px-4 font-semibold w-48">Service Name</th>
                                <th class="py-2.5 px-4 font-semibold w-28">Result</th>
                                <th class="py-2.5 px-4 font-semibold">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-bgray-100 dark:divide-darkblack-400 bg-white dark:bg-darkblack-600" id="import-row-results-body">
                            @if (!empty($activeImport->row_results))
                                @foreach ($activeImport->row_results as $outcome)
                                    @php
                                        $rowStatus = strtolower($outcome['status'] ?? 'unknown');
                                    @endphp
                                    <tr class="result-row hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/50" data-row-status="{{ $rowStatus }}">
                                        <td class="py-2.5 px-4 font-mono font-bold text-bgray-800 dark:text-bgray-200">
                                            Row {{ $outcome['excel_row'] ?? '-' }}
                                        </td>
                                        <td class="py-2.5 px-4 font-mono text-bgray-900 dark:text-white">
                                            {{ $outcome['project_code'] ?? '-' }}
                                        </td>
                                        <td class="py-2.5 px-4 text-bgray-900 dark:text-white font-medium">
                                            {{ $outcome['service_name'] ?? '-' }}
                                        </td>
                                        <td class="py-2.5 px-4">
                                            @if ($rowStatus === 'success')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
                                                    Success
                                                </span>
                                            @elseif ($rowStatus === 'unprocessed')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-bgray-100 text-bgray-700 ring-1 ring-inset ring-bgray-300 dark:bg-darkblack-500 dark:text-bgray-300">
                                                    Unprocessed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                    Failed
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-4 {{ $rowStatus === 'success' ? 'text-success-600 dark:text-success-400' : ($rowStatus === 'unprocessed' ? 'text-bgray-500 dark:text-bgray-400' : 'text-red-600 dark:text-red-400') }}">
                                            {{ $outcome['message'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar for Results -->
                <div id="results-pagination-container" class="mt-3 flex items-center justify-between text-xs text-bgray-500 dark:text-bgray-400">
                    <span id="pagination-info">Showing all results</span>
                    <div class="flex items-center gap-2">
                        <button type="button" id="pagination-prev" class="rounded border border-bgray-300 bg-white px-2 py-1 text-xs font-semibold text-bgray-700 disabled:opacity-40 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            Previous
                        </button>
                        <span id="pagination-current-page" class="font-bold text-bgray-800 dark:text-white">Page 1</span>
                        <button type="button" id="pagination-next" class="rounded border border-bgray-300 bg-white px-2 py-1 text-xs font-semibold text-bgray-700 disabled:opacity-40 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        @vite('resources/js/modules/projects/imports/project-imports.js')
    @endpush
</div>
