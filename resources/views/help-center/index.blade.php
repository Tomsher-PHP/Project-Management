@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-3 pb-12 pt-[84px] sm:px-4 sm:pt-[88px] md:pt-[68px] lg:px-6" data-help-center>
        <div class="w-full">
            <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-bgray-900 via-bgray-800 to-bgray-900 px-5 py-6 shadow-xl dark:from-darkblack-600 dark:via-darkblack-500 dark:to-darkblack-600 sm:px-8 sm:py-7 lg:px-10">
                <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-success-300/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -left-20 -bottom-24 h-64 w-64 rounded-full bg-success-400/10 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-bgray-700 dark:text-bgray-300 sm:text-3xl">Help Center</h1>
                        <p class="mt-1.5 text-xs leading-5 text-bgray-300 dark:text-bgray-300 sm:text-sm">
                            Find answers, learn features, and quickly get help with using the project management system.
                        </p>
                    </div>

                    <div class="relative w-full max-w-lg shrink-0">
                        <label for="help-search" class="sr-only">Search Help Center articles</label>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 stroke-bgray-400" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="1.7" />
                            <path d="m16.25 16.25 4 4" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                        <input id="help-search" type="search" autocomplete="off" data-help-search class="h-11 w-full rounded-xl border border-white/10 bg-white/10 py-2.5 pl-10 pr-10 text-sm text-bgray-300 dark:text-bgray-300 shadow-inner backdrop-blur-md transition placeholder:text-bgray-300 focus:border-success-300 focus:bg-white focus:text-bgray-900 focus:placeholder:text-bgray-500 focus:outline-none focus:ring-2 focus:ring-success-300/40 dark:bg-darkblack-500 dark:focus:bg-darkblack-500 dark:focus:text-white" placeholder="Search articles, questions, or keywords..." />
                        <button type="button" data-search-clear hidden aria-label="Clear search" class="absolute right-2.5 top-1.5 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-bgray-400 transition hover:bg-white/20 hover:text-white dark:hover:bg-darkblack-400">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </header>

            <button type="button" data-mobile-nav-toggle aria-expanded="false" aria-controls="help-mobile-navigation" class="mt-6 flex w-full items-center justify-between rounded-xl border border-bgray-200 bg-white px-4 py-3 text-left text-sm font-semibold text-bgray-900 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white lg:hidden">
                Browse articles
                <svg class="stroke-current" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m6 9 6 6 6-6" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,1fr)]">
                <aside id="help-mobile-navigation" data-mobile-nav class="hidden lg:sticky lg:top-20 lg:block lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto">
                    <nav aria-label="Help Center articles" class="rounded-2xl border border-bgray-200 bg-white p-4 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
                        @foreach ($categories as $category)
                            <div data-help-category class="{{ !$loop->first ? 'mt-5 border-t border-bgray-100 pt-4 dark:border-darkblack-400' : '' }}">
                                <ul class="space-y-1">
                                    @foreach ($category['articles'] as $article)
                                        <li>
                                            <a href="#{{ $article['id'] }}" data-help-nav-item data-article-id="{{ $article['id'] }}" class="block rounded-lg px-3 py-2 text-sm leading-5 text-bgray-700 transition hover:bg-bgray-50 hover:text-bgray-900 focus:outline-none focus:ring-2 focus:ring-success-300 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white">
                                                {{ $article['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </nav>
                </aside>

                <div class="min-w-0">
                    @foreach ($categories as $category)
                        <section data-help-content-category class="{{ !$loop->first ? 'mt-10' : '' }}" aria-labelledby="category-{{ $loop->index }}">
                            <div class="mb-4 flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-success-400"></span>
                                <h2 id="category-{{ $loop->index }}" class="text-xl font-bold tracking-tight text-bgray-900 dark:text-white sm:text-2xl">
                                    {{ $category['title'] }} Help
                                </h2>
                            </div>

                            <div class="space-y-5">
                                @foreach ($category['articles'] as $article)
                                    <article id="{{ $article['id'] }}" data-help-article class="scroll-mt-24 rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-7">
                                        <h3 class="border-b border-bgray-100 pb-4 text-lg font-bold leading-7 text-bgray-900 dark:border-darkblack-400 dark:text-white sm:text-xl">
                                            {{ $article['title'] }}
                                        </h3>
                                        <div class="mt-5 space-y-5 text-sm leading-6 text-bgray-700 dark:text-bgray-300 sm:text-base">
                                            @include($article['view'])
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    <div data-no-results hidden class="rounded-2xl border border-dashed border-bgray-300 bg-white px-6 py-16 text-center dark:border-darkblack-400 dark:bg-darkblack-600" role="status">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-bgray-100 dark:bg-darkblack-500">
                            <svg class="stroke-bgray-500" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" stroke-width="1.5" />
                                <path d="m16.25 16.25 4 4M8.5 8.5l5 5m0-5-5 5" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h2 class="mt-4 text-lg font-semibold text-bgray-900 dark:text-white">No results found</h2>
                        <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">Try another keyword or clear your search.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/modules/help-center.js')
@endpush
