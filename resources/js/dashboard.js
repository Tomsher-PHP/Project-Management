import { initDatepicker } from './components/datepicker';

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // 1. Dashboard summary stats loading and animating
    // -------------------------------------------------------------
    const summarySection = document.querySelector('[data-dashboard-summary-section]');
    if (summarySection) {
        const summaryUrl = summarySection.getAttribute('data-dashboard-summary-url');
        if (summaryUrl) {
            const animateCounter = (element, target, duration = 700) => {
                const skeleton = element.querySelector('.animate-pulse');
                if (skeleton) {
                    element.innerText = '0';
                }

                const start = parseInt(element.innerText.replace(/,/g, '')) || 0;
                const targetValue = parseInt(target) || 0;

                if (start === targetValue) {
                    element.innerText = targetValue.toLocaleString();
                    return;
                }

                const change = targetValue - start;
                const startTime = performance.now();

                const updateCounter = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easedProgress = progress * (2 - progress);
                    const currentValue = Math.floor(start + (change * easedProgress));
                    
                    element.innerText = currentValue.toLocaleString();

                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        element.innerText = targetValue.toLocaleString();
                    }
                };

                requestAnimationFrame(updateCounter);
            };

            const loadDashboardSummary = async () => {
                try {
                    const response = await fetch(summaryUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Dashboard summary fetch failed');

                    const result = await response.json();

                    if (result.success && result.data) {
                        Object.entries(result.data).forEach(([key, value]) => {
                            const elements = summarySection.querySelectorAll(`[data-dashboard-count="${key}"]`);
                            elements.forEach(element => {
                                animateCounter(element, value);
                            });
                        });
                    }
                } catch (error) {
                    console.warn('Dashboard Summary Error:', error);
                    const countElements = summarySection.querySelectorAll('[data-dashboard-count]');
                    countElements.forEach(element => {
                        const skeleton = element.querySelector('.animate-pulse');
                        if (skeleton) {
                            element.innerText = '0';
                        }
                    });
                }
            };

            loadDashboardSummary();
        }
    }

    // -------------------------------------------------------------
    // 2. Users Task Worked Time Filtering
    // -------------------------------------------------------------
    const workedTimeSection = document.querySelector('[data-worked-time-section]');
    if (workedTimeSection) {
        const filterButtons = workedTimeSection.querySelectorAll('[data-worked-time-filter]');
        const datepickerContainer = document.getElementById('custom-datepicker-container');
        const datepickerInput = document.getElementById('worked-time-datepicker');

        const getLocalDateString = (offsetDays = 0) => {
            const d = new Date();
            d.setDate(d.getDate() + offsetDays);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const escapeHtml = (str) => {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
        };

        const loadWorkedTime = async (dateString) => {
            const tableBody = document.getElementById('worked-time-table-body');
            if (!tableBody) return;

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;

            try {
                const workedTimeUrl = workedTimeSection.getAttribute('data-worked-time-url');
                const response = await fetch(`${workedTimeUrl}?date=${dateString}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Worked time fetch failed');

                const result = await response.json();
                if (result.success && Array.isArray(result.data)) {
                    if (result.data.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `;
                    } else {
                        tableBody.innerHTML = result.data.map(row => {
                            const endHtml = row.end_time === 'Running'
                                ? '<span class="text-success-300 font-semibold">Running</span>'
                                : escapeHtml(row.end_time);

                            const shiftHtml = row.shift_working_hour === 'Day Off'
                                ? '<span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>'
                                : escapeHtml(row.shift_working_hour);

                            return `
                                <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                         <div class="flex items-center gap-2">
                                             ${row.user_avatar_html || ''}
                                             <span>${escapeHtml(row.user_name)}</span>
                                         </div>
                                    </td>
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${escapeHtml(row.date)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(row.start_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${endHtml}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(row.total_worked_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${shiftHtml}</td>
                                </tr>
                            `;
                        }).join('');
                    }
                }
            } catch (error) {
                console.error('Worked Time Load Error:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `;
            }
        };

        const setActiveFilterButton = (activeFilter) => {
            filterButtons.forEach(btn => {
                const filterType = btn.getAttribute('data-worked-time-filter');
                const isActive = filterType === activeFilter;
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                btn.classList.toggle('active', isActive);
            });
        };

        // Initialize custom flatpickr on datepicker input
        if (datepickerInput) {
            initDatepicker('#worked-time-datepicker', {
                onChange: (selectedDates, dateStr) => {
                    if (dateStr) {
                        const todayStr = getLocalDateString(0);
                        const yesterdayStr = getLocalDateString(-1);
                        if (dateStr === todayStr) {
                            setActiveFilterButton('today');
                        } else if (dateStr === yesterdayStr) {
                            setActiveFilterButton('yesterday');
                        } else {
                            setActiveFilterButton(null);
                        }
                        loadWorkedTime(dateStr);
                    }
                }
            });
        }

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.getAttribute('data-worked-time-filter');
                
                if (filter === 'today') {
                    const todayStr = getLocalDateString(0);
                    if (datepickerInput) {
                        datepickerInput.value = todayStr;
                        if (datepickerInput._flatpickr) {
                            datepickerInput._flatpickr.setDate(todayStr);
                        }
                    }
                    setActiveFilterButton('today');
                    loadWorkedTime(todayStr);
                } else if (filter === 'yesterday') {
                    const yesterdayStr = getLocalDateString(-1);
                    if (datepickerInput) {
                        datepickerInput.value = yesterdayStr;
                        if (datepickerInput._flatpickr) {
                            datepickerInput._flatpickr.setDate(yesterdayStr);
                        }
                    }
                    setActiveFilterButton('yesterday');
                    loadWorkedTime(yesterdayStr);
                }
            });
        });
    }

    // -------------------------------------------------------------
    // 3. Running Tasks Load More Button
    // -------------------------------------------------------------
    const runningTasksCard = document.querySelector('[data-running-tasks-card]');
    if (runningTasksCard) {
        const tableBody = runningTasksCard.querySelector('[data-running-tasks-table-body]');
        const loadMoreBtn = runningTasksCard.querySelector('[data-running-tasks-load-more-btn]');
        const loadMoreContainer = runningTasksCard.querySelector('[data-running-tasks-load-more-container]');
        const emptyRow = runningTasksCard.querySelector('[data-running-tasks-empty-row]');

        let isLoading = false;

        const escapeHtml = (str) => {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
        };

        const limitStringChar = (str, count, end = '...') => {
            if (!str) return '';
            if (str.length > count) {
                return str.substring(0, count) + end;
            }
            return str;
        };

        const loadNextPage = async () => {
            if (isLoading) return;

            const hasMore = runningTasksCard.getAttribute('data-running-tasks-has-more') === 'true';
            if (!hasMore) return;

            const url = runningTasksCard.getAttribute('data-running-tasks-url');
            const nextPage = runningTasksCard.getAttribute('data-running-tasks-next-page');

            if (!url || !nextPage) return;

            isLoading = true;
            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Loading More...';
            }

            try {
                const response = await fetch(`${url}?page=${nextPage}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to load running tasks');

                const result = await response.json();

                if (result.success && result.data) {
                    // Prevent showing empty row if we fetched actual items
                    if (result.data.length > 0 && emptyRow) {
                        emptyRow.classList.add('hidden');
                    }

                    // Append new rows
                    result.data.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150';

                        // Task edit URL
                        const taskEditUrl = `/tasks/${row.task_id}/edit`;

                        tr.innerHTML = `
                            <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                <div class="flex items-center gap-2">
                                    ${row.user_avatar_html || ''}
                                    <span>${escapeHtml(row.user_name)}</span>
                                </div>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-success-300 hover:text-success-400 transition-colors">
                                <a href="${taskEditUrl}">
                                    ${escapeHtml(limitStringChar(row.task_name, 30))}
                                </a>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${escapeHtml(row.estimated_time)}</td>
                            <td class="py-3.5 text-sm ${escapeHtml(row.color_class)}">${escapeHtml(row.worked_time)}</td>
                        `;
                        tableBody.appendChild(tr);
                    });

                    // Update metadata
                    runningTasksCard.setAttribute('data-running-tasks-has-more', result.has_more_pages ? 'true' : 'false');
                    runningTasksCard.setAttribute('data-running-tasks-next-page', result.next_page || '');

                    // Hide container if there are no more pages
                    if (!result.has_more_pages) {
                        if (loadMoreContainer) {
                            loadMoreContainer.classList.add('hidden');
                        }
                    }
                }
            } catch (error) {
                console.error('Running Tasks Load Error:', error);
            } finally {
                isLoading = false;
                if (loadMoreBtn) {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Load More';
                }
            }
        };

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadNextPage);
        }
    }

    // -------------------------------------------------------------
    // 4. Dashboard Tile Drill-down Modal
    // -------------------------------------------------------------
    const tiles = document.querySelectorAll('[data-dashboard-tile]');
    const tileModal = document.getElementById('dashboard-tile-modal');
    const tileModalContent = document.getElementById('dashboard-tile-modal-content');
    const summarySectionEl = document.querySelector('[data-dashboard-summary-section]');

    if (tiles.length > 0 && tileModal && tileModalContent && summarySectionEl) {
        const tileUrl = summarySectionEl.getAttribute('data-dashboard-tile-url');

        tiles.forEach(tile => {
            tile.addEventListener('click', async () => {
                const type = tile.getAttribute('data-dashboard-tile');
                
                // Show modal loading state
                tileModal.classList.remove('hidden');
                tileModal.classList.add('flex');
                tileModalContent.innerHTML = `
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Loading...</h3>
                        <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="flex-1 p-6 flex justify-center items-center">
                        <span class="animate-pulse text-bgray-500 font-semibold text-lg">Fetching records...</span>
                    </div>
                `;

                try {
                    const response = await fetch(`${tileUrl}?type=${type}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to load tile details');

                    const result = await response.json();
                    if (result.success && result.html) {
                        tileModalContent.innerHTML = result.html;
                    }
                } catch (error) {
                    console.error('Tile Details Load Error:', error);
                    tileModalContent.innerHTML = `
                        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                            <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Error</h3>
                            <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="flex-1 p-6 flex justify-center items-center">
                            <span class="text-red-500 font-semibold">Failed to load details. Please try again.</span>
                        </div>
                    `;
                }
            });
        });

        // Close modal logic
        const tileModalOverlay = document.querySelector('[data-dashboard-tile-overlay]');
        if (tileModalOverlay) {
            tileModalOverlay.addEventListener('click', () => {
                tileModal.classList.add('hidden');
                tileModal.classList.remove('flex');
            });
        }

        // Allow close buttons inside modal content to work dynamically
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-dashboard-tile-close]')) {
                tileModal.classList.add('hidden');
                tileModal.classList.remove('flex');
            }
        });
    }
});
