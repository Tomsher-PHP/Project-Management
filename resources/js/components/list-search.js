const SEARCH_FIELD_NAME = 'search';

function initListSearch(component) {
    const input = component.querySelector('[data-list-search-input]');
    const button = component.querySelector('[data-list-search-button]');
    const formSelector = component.dataset.filterForm;
    const form = formSelector ? document.querySelector(formSelector) : null;

    if (!input || !button || !form) {
        return;
    }

    let searchField = form.elements.namedItem(SEARCH_FIELD_NAME);

    if (!searchField) {
        searchField = document.createElement('input');
        searchField.type = 'hidden';
        searchField.name = SEARCH_FIELD_NAME;
        form.appendChild(searchField);
    }

    searchField.value = input.value.trim();

    const submitSearch = () => {
        searchField.value = input.value.trim();
        form.requestSubmit();
    };

    button.addEventListener('click', submitSearch);

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        submitSearch();
    });

    input.addEventListener('input', () => {
        if (input.value === '' && searchField.value !== '') {
            submitSearch();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-list-search]').forEach(initListSearch);
});
