export function initProjectImports() {
    const card = document.getElementById('import-status-card');
    if (!card) return;

    const importId = card.getAttribute('data-import-id');
    const moduleKey = card.getAttribute('data-module-key');
    const statusUrl = card.getAttribute('data-status-url')
        || (importId && moduleKey ? `/projects/import/${encodeURIComponent(moduleKey)}/status/${encodeURIComponent(importId)}` : null);

    if (!statusUrl) return;

    let pollInterval = null;

    async function checkStatus() {
        try {
            const res = await fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;
            const payload = await res.json();
            if (!payload || !payload.data) return;

            const d = payload.data;
            const totalEl = document.getElementById('metric-total-rows');
            const procEl = document.getElementById('metric-processed-rows');
            const succEl = document.getElementById('metric-successful-rows');
            const failEl = document.getElementById('metric-failed-rows');

            if (totalEl) totalEl.textContent = d.total_rows !== null && d.total_rows !== undefined ? d.total_rows : '-';
            if (procEl) procEl.textContent = d.processed_rows ?? 0;
            if (succEl) succEl.textContent = d.successful_rows ?? 0;
            if (failEl) failEl.textContent = d.failed_rows ?? 0;

            const badge = document.getElementById('import-status-badge');
            if (badge) {
                if (d.status === 'completed') {
                    badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-bold text-success-600 ring-1 ring-inset ring-success-600/20 dark:bg-darkblack-500 dark:text-success-400"><svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Completed Successfully</span>';
                } else if (d.status === 'completed_with_errors') {
                    badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-darkblack-500 dark:text-amber-400">Completed with Row Errors</span>';
                } else if (d.status === 'failed') {
                    badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 ring-1 ring-inset ring-red-600/20 dark:bg-darkblack-500 dark:text-red-400">Failed</span>';
                } else if (d.status === 'processing') {
                    badge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-darkblack-500 dark:text-blue-400"><svg class="animate-spin h-3.5 w-3.5 text-blue-600 mr-1 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Processing Rows...</span>';
                }
            }

            if (d.error_message) {
                const errBox = document.getElementById('import-error-container');
                const errTxt = document.getElementById('import-error-text');
                if (errBox && errTxt) {
                    errTxt.textContent = d.error_message;
                    errBox.classList.remove('hidden');
                }
            }

            if (d.is_finished) {
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

    // Auto-poll every 3 seconds if status is pending or processing
    const initialStatus = card.getAttribute('data-initial-status');
    if (initialStatus === 'pending' || initialStatus === 'processing') {
        pollInterval = setInterval(checkStatus, 3000);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProjectImports);
} else {
    initProjectImports();
}
