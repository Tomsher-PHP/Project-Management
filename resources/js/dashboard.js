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
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${escapeHtml(row.user_name)}</td>
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
});
