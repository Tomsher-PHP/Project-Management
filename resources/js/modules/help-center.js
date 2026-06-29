const helpCenter = document.querySelector('[data-help-center]');

if (helpCenter) {
    const searchInput = helpCenter.querySelector('[data-help-search]');
    const clearButton = helpCenter.querySelector('[data-search-clear]');
    const articles = [...helpCenter.querySelectorAll('[data-help-article]')];
    const navItems = [...helpCenter.querySelectorAll('[data-help-nav-item]')];
    const categoryGroups = [...helpCenter.querySelectorAll('[data-help-category]')];
    const contentCategories = [...helpCenter.querySelectorAll('[data-help-content-category]')];
    const noResults = helpCenter.querySelector('[data-no-results]');
    const mobileToggle = helpCenter.querySelector('[data-mobile-nav-toggle]');
    const mobileNav = helpCenter.querySelector('[data-mobile-nav]');
    let observer;

    const normalize = (value) => value.toLocaleLowerCase().trim();

    const setActiveArticle = (id) => {
        navItems.forEach((item) => {
            const active = item.dataset.articleId === id;
            item.classList.toggle('bg-success-50', active);
            item.classList.toggle('text-success-400', active);
            item.classList.toggle('dark:bg-darkblack-500', active);
            item.classList.toggle('font-semibold', active);
            item.setAttribute('aria-current', active ? 'true' : 'false');
        });
    };

    const observeVisibleArticles = () => {
        observer?.disconnect();
        observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

                if (visible.length) {
                    setActiveArticle(visible[0].target.id);
                }
            },
            { rootMargin: '-18% 0px -68% 0px', threshold: 0 },
        );

        articles
            .filter((article) => !article.hidden)
            .forEach((article) => observer.observe(article));
    };

    const filterArticles = () => {
        const query = normalize(searchInput.value);
        let matches = 0;

        articles.forEach((article) => {
            const match = !query || normalize(article.textContent).includes(query);
            article.hidden = !match;
            article.classList.toggle('ring-2', Boolean(query && match));
            article.classList.toggle('ring-success-200', Boolean(query && match));
            matches += Number(match);

            navItems
                .filter((item) => item.dataset.articleId === article.id)
                .forEach((item) => {
                    item.hidden = !match;
                });
        });

        categoryGroups.forEach((category) => {
            category.hidden = !category.querySelector('[data-help-nav-item]:not([hidden])');
        });
        contentCategories.forEach((category) => {
            category.hidden = !category.querySelector('[data-help-article]:not([hidden])');
        });

        noResults.hidden = matches !== 0;
        clearButton.hidden = !query;
        observeVisibleArticles();
    };

    searchInput.addEventListener('input', filterArticles);
    clearButton.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.focus();
        filterArticles();
    });

    navItems.forEach((item) => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            setActiveArticle(item.dataset.articleId);
            document.getElementById(item.dataset.articleId)?.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'start',
            });
            window.history.replaceState(null, '', item.hash);
            mobileNav?.classList.add('hidden');
            mobileToggle?.setAttribute('aria-expanded', 'false');
        });
    });

    mobileToggle?.addEventListener('click', () => {
        const expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
        mobileToggle.setAttribute('aria-expanded', String(!expanded));
        mobileNav.classList.toggle('hidden', expanded);
    });

    observeVisibleArticles();
}
