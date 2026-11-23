function initReportTabs() {
    const tabs = document.querySelectorAll('[data-report-tab]');
    const panels = document.querySelectorAll('[data-report-panel]');

    if (!tabs.length || !panels.length) return;

    const activeClasses = ['bg-success-300', 'text-white', 'shadow-sm'];
    const inactiveClasses = [
        'border',
        'border-bgray-200',
        'bg-bgray-50',
        'text-bgray-700',
        'hover:border-success-300',
        'hover:text-success-400',
        'dark:border-darkblack-400',
        'dark:bg-darkblack-500',
        'dark:text-bgray-50',
        'dark:hover:border-success-300',
        'dark:hover:text-success-300',
    ];

    function activateTab(name) {
        tabs.forEach(function (tab) {
            const isActive = tab.dataset.reportTab === name;

            tab.classList.remove(...(isActive ? inactiveClasses : activeClasses));
            tab.classList.add(...(isActive ? activeClasses : inactiveClasses));
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.dataset.reportPanel !== name);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.dataset.reportTab);
        });
    });

    const firstTab = document.querySelector('[data-report-tab]');
    if (firstTab) {
        activateTab(firstTab.dataset.reportTab);
    }
}

function initReportSettingsForm() {
    // Enabled / Disabled label
    const enabledCheckbox = document.getElementById('daily-work-reports-enabled');
    const enabledLabel = document.getElementById('daily-work-reports-enabled-label');

    if (enabledCheckbox && enabledLabel) {
        enabledCheckbox.addEventListener('change', function () {
            enabledLabel.textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    }

    // Recipient scope filtering
    const scope = document.getElementById('recipient-scope');
    const targetWrapper = document.getElementById('recipient-scope-id-wrapper');
    const targetSelect = document.getElementById('recipient-scope-id');
    const targetLabel = document.getElementById('recipient-scope-id-label');

    function updateRecipientScope() {
        if (!scope || !targetWrapper || !targetSelect || !targetLabel) {
            return;
        }

        const selectedScope = scope.value;
        const needsTarget = selectedScope !== 'global';

        targetWrapper.classList.toggle('hidden', !needsTarget);
        targetSelect.required = needsTarget;
        targetSelect.disabled = !needsTarget;

        targetLabel.textContent = selectedScope === 'team'
            ? 'Select team'
            : 'Select employee';

        Array.from(targetSelect.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.scope !== selectedScope;
        });

        const selectedOption = targetSelect.selectedOptions[0];

        if (
            selectedOption &&
            selectedOption.value &&
            selectedOption.dataset.scope !== selectedScope
        ) {
            targetSelect.value = '';
        }
    }

    if (scope && targetSelect) {
        scope.addEventListener('change', function () {
            targetSelect.value = '';
            updateRecipientScope();
        });

        updateRecipientScope();
    }

    // Weekly delivery day visibility
    const frequencySelect = document.getElementById('report-frequency');
    const weeklyDayWrapper = document.getElementById('weekly-day-wrapper');

    function updateWeeklyDayVisibility() {
        if (!frequencySelect || !weeklyDayWrapper) {
            return;
        }

        weeklyDayWrapper.classList.toggle(
            'hidden',
            frequencySelect.value !== 'weekly'
        );
    }

    if (frequencySelect) {
        frequencySelect.addEventListener('change', updateWeeklyDayVisibility);
        updateWeeklyDayVisibility();
    }
}

function initReportUserSettings() {
    const searchInput = document.getElementById('report-user-search');
    const teamFilter = document.getElementById('report-team-filter');
    const rows = Array.from(document.querySelectorAll('.report-user-row'));
    const selectAll = document.getElementById('select-all-report-users');
    const noResults = document.getElementById('report-user-no-results');

    if (!rows.length && !searchInput && !teamFilter) return;

    function filterUsers() {
        const search = (searchInput?.value || '').trim().toLowerCase();
        const selectedTeam = teamFilter?.value || '';
        let visibleCount = 0;

        rows.forEach(function (row) {
            const name = row.dataset.userName || '';
            const email = row.dataset.userEmail || '';
            const teamIds = (row.dataset.teamIds || '')
                .split(',')
                .filter(Boolean);

            const matchesSearch = !search ||
                name.includes(search) ||
                email.includes(search);

            const matchesTeam = !selectedTeam ||
                teamIds.includes(selectedTeam);

            const visible = matchesSearch && matchesTeam;

            row.classList.toggle('hidden', !visible);

            if (visible) {
                visibleCount++;
            } else {
                const checkbox = row.querySelector('.report-user-checkbox');
                if (checkbox) checkbox.checked = false;
            }
        });

        if (noResults) {
            noResults.classList.toggle('hidden', visibleCount > 0);
        }

        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
    }

    searchInput?.addEventListener('input', filterUsers);
    teamFilter?.addEventListener('change', filterUsers);

    selectAll?.addEventListener('change', function () {
        rows.forEach(function (row) {
            if (row.classList.contains('hidden')) return;

            const checkbox = row.querySelector('.report-user-checkbox');

            if (checkbox) {
                checkbox.checked = selectAll.checked;
            }
        });
    });

    document.querySelectorAll('.report-user-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const visibleCheckboxes = rows
                .filter(row => !row.classList.contains('hidden'))
                .map(row => row.querySelector('.report-user-checkbox'))
                .filter(Boolean);

            const checkedCount = visibleCheckboxes.filter(item => item.checked).length;

            if (selectAll) {
                selectAll.checked =
                    visibleCheckboxes.length > 0 &&
                    checkedCount === visibleCheckboxes.length;

                selectAll.indeterminate =
                    checkedCount > 0 &&
                    checkedCount < visibleCheckboxes.length;
            }
        });
    });
}

function initDailyReportSettings() {
    initReportTabs();
    initReportSettingsForm();
    initReportUserSettings();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDailyReportSettings);
} else {
    initDailyReportSettings();
}
