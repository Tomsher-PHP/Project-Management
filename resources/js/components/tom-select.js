export function initTomSelect() {

    // Standard Select
    document.querySelectorAll('.tom-select').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        new TomSelect(el, {
            create: false,
            persist: false,
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

        new TomSelect(el, {
            create: false,
            persist: false,
            sortField: { field: "text", direction: "asc" },
            plugins: ['dropdown_input', 'clear_button'],
            render: {
                option: function (data, escape) {
                    return `
                <div>
                    <div class="font-medium">${escape(data.text)}</div>
                    <div class="text-xs text-gray-400">${escape(data.subtype || '')}</div>
                </div>
            `;
                }
            }
        });
    });
}