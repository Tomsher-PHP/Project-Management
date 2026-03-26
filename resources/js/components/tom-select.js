export function initTomSelect() {

    // Standard Select
    document.querySelectorAll('.tom-select-no-search').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        new TomSelect(el, {
            create: false,            // cannot create new options
            persist: false,
            hideDropdownArrow: false,
            plugins: ['clear_button'],
        });
    });

    // Standard Select
    document.querySelectorAll('.tom-select').forEach(el => {

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

        new TomSelect(el, config);
    });

    // Multiple select
    document.querySelectorAll('.tom-select-multiple').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        new TomSelect(el, {
            plugins: ['remove_button', 'dropdown_input', 'clear_button'],
            maxItems: null,
        });
    });

    // Lazy load tom select
    document.querySelectorAll('.tom-select-lazy').forEach(el => {
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

        new TomSelect(el, config);
    });
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