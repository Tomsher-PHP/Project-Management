const helpCenter = document.querySelector('[data-help-center]');

if (helpCenter) {
    const searchInput = helpCenter.querySelector('[data-help-search]');
    const clearButton = helpCenter.querySelector('[data-search-clear]');
    const searchIndexScript = helpCenter.querySelector('[data-search-index]');
    const resultsPanel = helpCenter.querySelector('[data-help-search-results]');
    const resultsList = helpCenter.querySelector('[data-search-results-list]');
    const searchNoResults = helpCenter.querySelector('[data-search-no-results]');
    const homeCards = [...helpCenter.querySelectorAll('[data-help-card]')];
    const cardNoResults = helpCenter.querySelector('[data-card-no-results]');
    const mobileToggle = helpCenter.querySelector('[data-mobile-nav-toggle]');
    const mobileNav = helpCenter.querySelector('[data-mobile-nav]');

    const normalize = (value) => value.toLocaleLowerCase().trim();

    const articles = searchIndexScript
        ? JSON.parse(searchIndexScript.textContent)
        : [];

    const createResult = (article) => {
        const result = document.createElement('a');
        const title = document.createElement('span');
        const description = document.createElement('span');

        result.href = article.url;
        result.className = 'block rounded-xl px-3 py-3 transition hover:bg-bgray-50 focus:outline-none focus:ring-2 focus:ring-success-300 dark:hover:bg-darkblack-500';

        title.className = 'block text-sm font-semibold text-bgray-900 dark:text-white';
        title.textContent = article.title;

        description.className = 'mt-1 block text-xs leading-5 text-bgray-500 dark:text-bgray-300';
        description.textContent = article.description;

        result.append(title, description);

        return result;
    };

    const filterHomeCards = (query) => {
        if (!homeCards.length) {
            return;
        }

        let visibleCards = 0;

        homeCards.forEach((card) => {
            const matches = !query || normalize(card.dataset.searchText || card.textContent).includes(query);

            card.hidden = !matches;
            card.classList.toggle('ring-2', Boolean(query && matches));
            card.classList.toggle('ring-success-200', Boolean(query && matches));
            visibleCards += Number(matches);
        });

        if (cardNoResults) {
            cardNoResults.hidden = visibleCards !== 0;
        }
    };

    const renderResults = () => {
        const query = normalize(searchInput.value);
        const matchingArticles = query
            ? articles.filter((article) => normalize(article.searchable).includes(query))
            : [];

        clearButton.hidden = !query;
        resultsList.replaceChildren();

        matchingArticles.forEach((article) => {
            resultsList.appendChild(createResult(article));
        });

        filterHomeCards(query);

        if (!resultsPanel) {
            return;
        }

        resultsPanel.hidden = !query;
        searchNoResults.hidden = matchingArticles.length !== 0;
        resultsList.hidden = matchingArticles.length === 0;
    };

    searchInput?.addEventListener('input', renderResults);
    searchInput?.addEventListener('focus', renderResults);

    clearButton?.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.focus();
        renderResults();
    });

    document.addEventListener('click', (event) => {
        if (!resultsPanel || helpCenter.contains(event.target)) {
            return;
        }

        resultsPanel.hidden = true;
    });

    mobileToggle?.addEventListener('click', () => {
        const expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
        mobileToggle.setAttribute('aria-expanded', String(!expanded));
        mobileNav?.classList.toggle('hidden', expanded);
    });
}
