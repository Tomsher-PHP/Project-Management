/**
 * Project Imports - Frontend module for single-upload, live tracking, results filtering and pagination.
 */
export function initProjectImports() {
    // 1. File input name display & form submit guard (prevents double submits)
    const fileInput = document.getElementById('excel-file-input');
    const fileNameDisplay = document.getElementById('file-chosen-name');
    const importForm = document.getElementById('import-form');
    const startBtn = document.getElementById('btn-start-import');
    const btnLabel = document.getElementById('btn-import-label');
    const btnIcon = document.getElementById('btn-import-icon');
    const btnSpinner = document.getElementById('btn-import-spinner');

    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = 'No file chosen';
            }
        });
    }

    if (importForm && startBtn) {
        importForm.addEventListener('submit', function (e) {
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select an Excel (.xlsx) file to import.');
                return;
            }

            // Prevent duplicate clicks
            startBtn.disabled = true;
            if (btnIcon) btnIcon.classList.add('hidden');
            if (btnSpinner) btnSpinner.classList.remove('hidden');
            if (btnLabel) btnLabel.textContent = 'Uploading & Starting...';
        });
    }

    // 2. Real-time Status & Results Polling
    const card = document.getElementById('import-status-card');
    if (!card) return;

    const importId = card.getAttribute('data-import-id');
    const moduleKey = card.getAttribute('data-module-key');
    const statusUrl = card.getAttribute('data-status-url')
        || (importId && moduleKey ? `/projects/import/${encodeURIComponent(moduleKey)}/status/${encodeURIComponent(importId)}` : null);

    if (!statusUrl) return;

    let pollInterval = null;
    let cachedRowResults = [];
    let currentFilter = 'all';
    let currentPage = 1;
    const pageSize = 25;

    // Helper: Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function updateProgressBar(processed, total, isFinished) {
        const progressContainer = document.getElementById('import-progress-container');
        const progressBar = document.getElementById('import-progress-bar');
        const progressPercent = document.getElementById('import-progress-percent');

        if (!progressContainer || !progressBar) return;

        let pct = 0;
        if (total > 0) {
            pct = Math.min(100, Math.round((processed / total) * 100));
        } else if (isFinished) {
            pct = 100;
        }

        progressBar.style.width = pct + '%';
        if (progressPercent) progressPercent.textContent = pct + '%';

        if (isFinished) {
            progressContainer.classList.add('hidden');
        } else {
            progressContainer.classList.remove('hidden');
        }
    }

    function renderStatusBadge(status) {
        const badge = document.getElementById('import-status-badge');
        if (!badge) return;

        if (status === 'completed') {
            badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-600 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400"><svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Completed Successfully</span>';
        } else if (status === 'completed_with_errors') {
            badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400">Completed with Row Errors</span>';
        } else if (status === 'failed') {
            badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">Failed</span>';
        } else if (status === 'interrupted') {
            badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">Processing Interrupted</span>';
        } else if (status === 'processing') {
            badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-darkblack-500 dark:text-blue-400"><svg class="animate-spin h-3.5 w-3.5 text-blue-600 mr-1 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Processing Rows...</span>';
        } else {
            badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending in Queue</span>';
        }
    }

    function renderRowResultsTable() {
        const tbody = document.getElementById('import-row-results-body');
        if (!tbody) return;

        const filtered = cachedRowResults.filter(row => {
            const st = (row.status || '').toLowerCase();
            if (currentFilter === 'failed') return st === 'failed';
            if (currentFilter === 'success') return st === 'success';
            return true;
        });

        const totalFiltered = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * pageSize;
        const pageRows = filtered.slice(startIndex, startIndex + pageSize);

        let html = '';
        if (pageRows.length === 0) {
            html = '<tr><td colspan="5" class="py-6 text-center text-xs text-bgray-500">No rows matching current filter.</td></tr>';
        } else {
            pageRows.forEach(item => {
                const rowStatus = (item.status || 'unknown').toLowerCase();
                let statusBadge = '';
                let textColor = '';

                if (rowStatus === 'success') {
                    statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-success-50 text-success-600 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400"><span class="h-1.5 w-1.5 rounded-full bg-success-500"></span> Success</span>';
                    textColor = 'text-success-600 dark:text-success-400';
                } else if (rowStatus === 'unprocessed') {
                    statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-bgray-100 text-bgray-700 ring-1 ring-inset ring-bgray-300 dark:bg-darkblack-500 dark:text-bgray-300">Unprocessed</span>';
                    textColor = 'text-bgray-500 dark:text-bgray-400';
                } else {
                    statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Failed</span>';
                    textColor = 'text-red-600 dark:text-red-400';
                }

                html += `<tr class="result-row hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/50" data-row-status="${escapeHtml(rowStatus)}">
                    <td class="py-2.5 px-4 font-mono font-bold text-bgray-800 dark:text-bgray-200">Row ${escapeHtml(item.excel_row || '-')}</td>
                    <td class="py-2.5 px-4 font-mono text-bgray-900 dark:text-white">${escapeHtml(item.project_code || '-')}</td>
                    <td class="py-2.5 px-4 text-bgray-900 dark:text-white font-medium">${escapeHtml(item.service_name || '-')}</td>
                    <td class="py-2.5 px-4">${statusBadge}</td>
                    <td class="py-2.5 px-4 ${textColor}">${escapeHtml(item.message || '-')}</td>
                </tr>`;
            });
        }

        tbody.innerHTML = html;

        // Update pagination bar
        const infoEl = document.getElementById('pagination-info');
        const pageEl = document.getElementById('pagination-current-page');
        const prevBtn = document.getElementById('pagination-prev');
        const nextBtn = document.getElementById('pagination-next');

        if (infoEl) {
            const end = Math.min(totalFiltered, startIndex + pageSize);
            infoEl.textContent = totalFiltered > 0
                ? `Showing ${startIndex + 1} to ${end} of ${totalFiltered} rows`
                : 'No rows';
        }
        if (pageEl) pageEl.textContent = `Page ${currentPage} of ${totalPages}`;
        if (prevBtn) prevBtn.disabled = (currentPage <= 1);
        if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);
    }

    // Filter bar event listeners
    const filterButtons = document.querySelectorAll('#results-filter-bar .filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => {
                b.classList.remove('active', 'bg-white', 'shadow-sm', 'text-bgray-900', 'dark:bg-darkblack-600', 'dark:text-white');
                b.classList.add('text-bgray-600', 'dark:text-bgray-400');
            });
            this.classList.add('active', 'bg-white', 'shadow-sm', 'text-bgray-900', 'dark:bg-darkblack-600', 'dark:text-white');
            this.classList.remove('text-bgray-600', 'dark:text-bgray-400');

            currentFilter = this.getAttribute('data-filter') || 'all';
            currentPage = 1;
            renderRowResultsTable();
        });
    });

    // Pagination buttons
    const prevBtn = document.getElementById('pagination-prev');
    const nextBtn = document.getElementById('pagination-next');
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                renderRowResultsTable();
            }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            currentPage++;
            renderRowResultsTable();
        });
    }

    async function checkStatus() {
        try {
            const res = await fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) {
                if (res.status === 404) {
                    const errBox = document.getElementById('import-error-container');
                    const errTxt = document.getElementById('import-error-text');
                    if (errBox && errTxt) {
                        errTxt.textContent = 'The import tracking session has expired or was not found.';
                        errBox.classList.remove('hidden');
                    }
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                }
                return;
            }

            const payload = await res.json();
            if (!payload || !payload.data) return;

            const d = payload.data;
            const total = d.total_rows !== null && d.total_rows !== undefined ? d.total_rows : null;
            const processed = d.processed_rows ?? 0;
            const success = d.successful_rows ?? 0;
            const failed = d.failed_rows ?? 0;

            const totalEl = document.getElementById('metric-total-rows');
            const procEl = document.getElementById('metric-processed-rows');
            const succEl = document.getElementById('metric-successful-rows');
            const failEl = document.getElementById('metric-failed-rows');

            if (totalEl) totalEl.textContent = total !== null ? total : '-';
            if (procEl) procEl.textContent = processed;
            if (succEl) succEl.textContent = success;
            if (failEl) failEl.textContent = failed;

            renderStatusBadge(d.status);
            updateProgressBar(processed, total || 0, d.is_finished);

            if (d.error_message) {
                const errBox = document.getElementById('import-error-container');
                const errTxt = document.getElementById('import-error-text');
                if (errBox && errTxt) {
                    errTxt.textContent = d.error_message;
                    errBox.classList.remove('hidden');
                }
            }

            // When import finishes
            if (d.is_finished) {
                const titleText = document.getElementById('import-title-text');
                if (titleText) titleText.textContent = 'Import Results';

                const summaryBanner = document.getElementById('import-summary-banner');
                const summaryText = document.getElementById('import-summary-text');
                if (summaryBanner && summaryText) {
                    if (d.status === 'interrupted') {
                        summaryText.textContent = `Import interrupted: ${processed} rows processed, ${success} successful, ${failed} failed.`;
                    } else {
                        summaryText.textContent = `Import completed: ${processed} rows processed, ${success} successful, ${failed} failed.`;
                    }
                    summaryBanner.classList.remove('hidden');
                }

                // Download Buttons
                const btnFailed = document.getElementById('btn-download-failed');
                if (btnFailed) {
                    if (failed > 0 && d.download_failed_url) {
                        btnFailed.href = d.download_failed_url;
                        btnFailed.classList.remove('hidden');
                        btnFailed.classList.add('inline-flex');
                    } else {
                        btnFailed.classList.add('hidden');
                        btnFailed.classList.remove('inline-flex');
                    }
                }

                const btnResults = document.getElementById('btn-download-results');
                if (btnResults && d.download_results_url) {
                    btnResults.href = d.download_results_url;
                }

                // Row Results
                if (Array.isArray(d.row_results) && d.row_results.length > 0) {
                    cachedRowResults = d.row_results;
                    const resultsSection = document.getElementById('import-results-section');
                    if (resultsSection) resultsSection.classList.remove('hidden');

                    const countAll = document.getElementById('filter-count-all');
                    const countFailed = document.getElementById('filter-count-failed');
                    const countSuccess = document.getElementById('filter-count-success');

                    if (countAll) countAll.textContent = cachedRowResults.length;
                    if (countFailed) countFailed.textContent = failed;
                    if (countSuccess) countSuccess.textContent = success;

                    renderRowResultsTable();
                }

                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
            }
        } catch (e) {
            console.error('Status fetch error', e);
        }
    }

    const refreshBtn = document.getElementById('btn-refresh-import-status');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', checkStatus);
    }

    // Auto-poll every 2.5 seconds if status is pending or processing
    const initialStatus = card.getAttribute('data-initial-status');
    if (initialStatus === 'pending' || initialStatus === 'processing') {
        pollInterval = setInterval(checkStatus, 2500);
    } else {
        // Initialize table from page content if already finished on load
        const existingRows = document.querySelectorAll('#import-row-results-body .result-row');
        if (existingRows.length > 0) {
            // Read initial data from DOM if present
            cachedRowResults = Array.from(existingRows).map(tr => {
                const cols = tr.querySelectorAll('td');
                const rowText = cols[0] ? cols[0].textContent.replace('Row ', '').trim() : '';
                return {
                    excel_row: rowText,
                    project_code: cols[1] ? cols[1].textContent.trim() : '',
                    service_name: cols[2] ? cols[2].textContent.trim() : '',
                    status: tr.getAttribute('data-row-status') || 'unknown',
                    message: cols[4] ? cols[4].textContent.trim() : '',
                };
            });
            renderRowResultsTable();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectImports);
} else {
    initProjectImports();
}
