export function initTomSelect(root = document) {
    const normalizeText = (value = '') => String(value).trim().toLowerCase();

    const applyDisabledStyles = (instance, el) => {
        if (!instance?.wrapper || !instance?.control || !el.disabled) return;

        instance.wrapper.classList.add('opacity-100');
        instance.control.classList.add(
            'border-bgray-200',
            'bg-bgray-50',
            'text-bgray-600',
            'dark:border-darkblack-400',
            'dark:bg-darkblack-500',
            'dark:text-bgray-200'
        );
        instance.control.classList.remove('bg-white');

        instance.control.querySelectorAll('.item, input, .ts-control > div').forEach(node => {
            node.classList.add('text-bgray-600', 'dark:text-bgray-200');
        });
    };

    // Standard Select
    root.querySelectorAll('select.tom-select-no-search, input.tom-select-no-search').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        const instance = new TomSelect(el, {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            plugins: ['clear_button'],
        });

        applyDisabledStyles(instance, el);
    });

    // Standard Select
    root.querySelectorAll('select.tom-select, input.tom-select').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        const sort = el.dataset.sort != "0";

        const config = {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            plugins: ['dropdown_input', 'clear_button'],
            render: {
                option: function (data, escape) {
                    return `
                        <div>
                            <div class="font-medium">${escape(data.text)}</div>
                            <div class="text-sm text-gray-600">${escape(data.subtype || '')}</div>
                        </div>
                    `;
                },
                item: function (data, escape) {
                    return `
                        <div>
                            <span class="font-medium">${escape(data.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${escape(data.subtype || '')}</span>
                        </div>
                    `;
                }
            }
        };

        if (sort) {
            config.sortField = { field: "text", direction: "asc" };
        }

        const instance = new TomSelect(el, config);
        applyDisabledStyles(instance, el);
    });

    root.querySelectorAll('select.tom-select-tags, input.tom-select-tags').forEach(el => {
        if (el.tomselect) return;

        const instance = new TomSelect(el, {
            plugins: ['remove_button', 'clear_button'],
            maxItems: null,
            persist: false,
            createOnBlur: true,
            hideSelected: true,
            closeAfterSelect: false,
            placeholder: 'Search or add tags',
            create: el.disabled ? false : (input) => {
                const text = String(input || '').trim();

                return {
                    value: text,
                    text,
                };
            },
            createFilter(input) {
                const normalizedInput = normalizeText(input);

                if (!normalizedInput) {
                    return false;
                }

                return !Object.values(this.options).some((option) => {
                    const optionText = normalizeText(option?.text ?? option?.value ?? '');
                    return optionText === normalizedInput;
                });
            },
            score(search) {
                const normalizedSearch = normalizeText(search);

                return function (item) {
                    const text = normalizeText(item.text);

                    if (!normalizedSearch) {
                        return 1;
                    }

                    if (text === normalizedSearch) {
                        return 2;
                    }

                    return text.includes(normalizedSearch) ? 1 : 0;
                };
            },
            render: {
                option(data, escape) {
                    return `
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${escape(data.text)}</span>
                            ${data.$option ? '' : '<span class="text-xs font-semibold text-success-400">Create</span>'}
                        </div>
                    `;
                },
                item(data, escape) {
                    return `<div class="font-medium">${escape(data.text)}</div>`;
                },
            },
        });

        applyDisabledStyles(instance, el);
    });

    // Multiple select
    root.querySelectorAll('select.tom-select-multiple, input.tom-select-multiple').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        const instance = new TomSelect(el, {
            plugins: ['remove_button', 'dropdown_input', 'clear_button'],
            maxItems: null,
        });

        applyDisabledStyles(instance, el);
    });

    // Lazy load tom select
    root.querySelectorAll('select.tom-select-lazy, input.tom-select-lazy').forEach(el => {
        if (el.tomselect) return; // Prevent double init

        const sort = el.dataset.sort != "0";
        const route = el.dataset.route;

        const config = {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            plugins: ['dropdown_input', 'clear_button'],
            sortField: sort ? { field: "text", direction: "asc" } : null,

            // Lazy load items via AJAX
            load: function (query, callback) {
                if (!query.length) return callback();

                fetch(`${route}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(json => {
                        // Expect JSON array [{id: 1, name: 'Afghanistan'}, ...]
                        callback(json.map(c => ({ value: c.id, text: c.name })));
                    })
                    .catch(() => callback());
            }
        };

        const instance = new TomSelect(el, config);
        applyDisabledStyles(instance, el);
    });

    document.dispatchEvent(new Event('tomselect:ready'));
}

//make auto select for dropdown input
export const autoTomSelect = (el, value) => {
    const select = document.getElementById(el);

    if (!select || !select.tomselect) return;

    if (value) {
        select.tomselect.setValue(value);
    } else {
        select.tomselect.clear();
    }
};
