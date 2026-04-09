import { initTomSelect } from '../../components/tom-select';
import { initDatepicker } from '../../components/datepicker';
import { initTimepicker } from '../../components/timepicker';
import { initWeekPicker } from '../../components/weekpicker';

document.addEventListener('DOMContentLoaded', function () {
    const tabsRoot = document.querySelector('[data-project-tabs]');

    if (!tabsRoot) {
        return;
    }

    const projectId = tabsRoot.dataset.projectId;
    const defaultTab = tabsRoot.dataset.defaultTab || 'overview';
    const tabsUrlTemplate = tabsRoot.dataset.tabsUrlTemplate || window.ProjectApp?.tabsUrlTemplate;
    const storageKey = `projectTab_${projectId}`;
    const triggers = Array.from(tabsRoot.querySelectorAll('[data-project-tab-trigger]'));
    const panels = Array.from(tabsRoot.querySelectorAll('[data-project-tab-panel]'));
    const availableTabs = triggers.map((trigger) => trigger.dataset.projectTabTrigger);
    const params = new URLSearchParams(window.location.search);
    let activeRequestTab = null;

    if (!projectId || !tabsUrlTemplate || !triggers.length || !panels.length) {
        return;
    }

    const getPanel = (tab) => tabsRoot.querySelector(`[data-project-tab-panel="${tab}"]`);
    const getTrigger = (tab) => tabsRoot.querySelector(`[data-project-tab-trigger="${tab}"]`);
    const invalidateTab = (tab) => {
        const panel = getPanel(tab);

        if (!panel) {
            return;
        }

        panel.dataset.loaded = 'false';
        panel.innerHTML = '';
    };

    const setActiveStyles = (activeTab) => {
        triggers.forEach((trigger) => {
            const isActive = trigger.dataset.projectTabTrigger === activeTab;
            trigger.classList.toggle('border-success-300', isActive);
            trigger.classList.toggle('text-success-300', isActive);
            trigger.classList.toggle('border-transparent', !isActive);
            trigger.classList.toggle('text-bgray-500', !isActive);
        });
    };

    const showTab = (tab) => {
        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.projectTabPanel !== tab);
        });

        setActiveStyles(tab);
        localStorage.setItem(storageKey, tab);
    };

    const initializeInjectedContent = (panel, tab) => {
        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(panel);
        }

        initTomSelect(panel);
        initDatepicker('.datepicker', {}, panel);
        initTimepicker('.timepicker', {}, panel);
        initWeekPicker('.weekPicker', undefined, panel);

        document.dispatchEvent(new CustomEvent('project-tab:loaded', {
            detail: { tab, panel },
        }));
    };

    const replaceTabContent = (tab, html, activate = false) => {
        const panel = getPanel(tab);

        if (!panel || typeof html !== 'string') {
            return;
        }

        panel.innerHTML = html;
        panel.dataset.loaded = 'true';
        initializeInjectedContent(panel, tab);

        if (activate) {
            showTab(tab);
        }
    };

    const loadTab = async (tab) => {
        const panel = getPanel(tab);

        if (!panel) {
            return;
        }

        if (panel.dataset.loaded === 'true') {
            showTab(tab);
            return;
        }

        if (activeRequestTab === tab) {
            return;
        }

        activeRequestTab = tab;
        panel.innerHTML = `
            <div class="flex items-center justify-center rounded-xl border border-dashed border-bgray-300 px-6 py-12 text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                Loading ${tab.charAt(0).toUpperCase() + tab.slice(1)}...
            </div>
        `;

        try {
            const requestUrl = new URL(tabsUrlTemplate.replace('__TAB__', tab), window.location.origin);

            params.forEach((value, key) => {
                requestUrl.searchParams.append(key, value);
            });

            const response = await fetch(requestUrl.toString(), {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const result = await response.json();

            if (!response.ok || !result.status) {
                throw new Error(result.message || `Unable to load the ${tab} tab.`);
            }

            panel.innerHTML = result.html;
            panel.dataset.loaded = 'true';
            initializeInjectedContent(panel, tab);
            showTab(tab);
        } catch (error) {
            panel.innerHTML = '';
            Alert.error(error.message || `Unable to load the ${tab} tab.`);
        } finally {
            activeRequestTab = null;
        }
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', function () {
            const tab = this.dataset.projectTabTrigger;
            loadTab(tab);
        });
    });

    document.addEventListener('project-tab:invalidate', function (event) {
        const tab = event.detail?.tab;

        if (!tab) {
            return;
        }

        invalidateTab(tab);
    });

    document.addEventListener('project-tab:replace', function (event) {
        const tab = event.detail?.tab;
        const html = event.detail?.html;
        const activate = Boolean(event.detail?.activate);

        if (!tab || typeof html !== 'string') {
            return;
        }

        replaceTabContent(tab, html, activate);
    });

    const savedTab = localStorage.getItem(storageKey);
    const requestedTab = params.get('tab');
    const initialTab = availableTabs.includes(requestedTab)
        ? requestedTab
        : (availableTabs.includes(savedTab) ? savedTab : defaultTab);

    loadTab(initialTab);
});
