export function initTomSelect() {

    // Standard Select
    document.querySelectorAll('.select-no-search').forEach(el => {

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

        new TomSelect(el, {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            sortField: { field: "text", direction: "asc" },
            plugins: ['dropdown_input', 'clear_button'],
        });
    });

    // Multiple select
    document.querySelectorAll('.tom-select-multiple').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        new TomSelect(el, {
            plugins: ['remove_button', 'dropdown_input', 'clear_button'],
            maxItems: null,
            // dropdownParent: document.body
        });
    });

    // Subtype Select
    document.querySelectorAll('.select-subtypes').forEach(el => {

        if (el.tomselect) return;

        const sort = el.dataset.sort != "0";

        const config = {
            create: false,
            persist: false,
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


        // Apply sorting only if enabled
        if (sort) {
            config.sortField = { field: "text", direction: "asc" };
        }

        new TomSelect(el, config);
    });
}