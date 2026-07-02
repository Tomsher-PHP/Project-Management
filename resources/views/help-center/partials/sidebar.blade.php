<aside id="help-mobile-navigation" data-mobile-nav class="hidden lg:sticky lg:top-20 lg:block lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto">
    <nav aria-label="Help Center articles" class="rounded-2xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <a href="{{ route('help-center.index') }}" class="mb-4 flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition hover:bg-bgray-50 dark:hover:bg-darkblack-500 {{ request()->routeIs('help-center.index') ? 'bg-success-50 text-success-400 dark:bg-darkblack-500' : 'text-bgray-700 dark:text-bgray-300' }}">
            <svg class="stroke-current" width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Help Center Home
        </a>

        @foreach ($categories as $category)
            <div data-help-category class="{{ !$loop->first ? 'mt-5 border-t border-bgray-100 pt-4 dark:border-darkblack-400' : '' }}">
                <p class="px-3 text-xs font-bold uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">
                    {{ $category['title'] }}
                </p>
                <ul class="mt-2 space-y-1">
                    @foreach ($category['articles'] as $article)
                        @php($isActive = ($currentArticle['slug'] ?? null) === $article['slug'])
                        <li>
                            <a href="{{ route('help-center.show', $article['slug']) }}" data-help-nav-item data-article-slug="{{ $article['slug'] }}" class="block rounded-lg px-3 py-2 text-sm leading-5 transition hover:bg-bgray-50 hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:hover:bg-darkblack-500 dark:hover:text-white {{ $isActive ? 'bg-success-50 font-semibold text-success-400 dark:bg-darkblack-500' : 'text-bgray-700 dark:text-bgray-300' }}" @if ($isActive) aria-current="page" @endif>
                                {{ $article['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
