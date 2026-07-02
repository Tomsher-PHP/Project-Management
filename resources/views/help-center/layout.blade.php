@extends('layouts.master')
@section('without-main', true)

@section('page-content')
    <main class="w-full px-3 pb-12 pt-[84px] sm:px-4 sm:pt-[88px] md:pt-[68px] lg:px-6" data-help-center>
        <script type="application/json" data-search-index>
            @json($searchIndex)
        </script>

        <div class="w-full">
            <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-bgray-900 via-bgray-800 to-bgray-900 px-5 py-6 shadow-xl dark:from-darkblack-600 dark:via-darkblack-500 dark:to-darkblack-600 sm:px-8 sm:py-7 lg:px-10">
                <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-success-300/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -left-20 -bottom-24 h-64 w-64 rounded-full bg-success-400/10 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-bgray-700 dark:text-white sm:text-3xl">
                            @yield('help-title', 'Help Center')
                        </h1>
                        <p class="mt-1.5 text-xs leading-5 text-bgray-300 dark:text-bgray-300 sm:text-sm">
                            @yield('help-description', 'Find answers, learn features, and quickly get help with using the project management system.')
                        </p>
                    </div>

                    <div class="relative w-full max-w-lg shrink-0">
                        <label for="help-search" class="sr-only">Search Help Center articles</label>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 stroke-bgray-400" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="1.7" />
                            <path d="m16.25 16.25 4 4" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                        <input id="help-search" type="search" autocomplete="off" data-help-search class="h-11 w-full rounded-xl border border-white/10 bg-white/10 py-2.5 pl-10 pr-10 text-sm text-white shadow-inner backdrop-blur-md transition placeholder:text-bgray-300 focus:border-success-300 focus:bg-white focus:text-bgray-900 focus:placeholder:text-bgray-500 focus:outline-none focus:ring-2 focus:ring-success-300/40 dark:bg-darkblack-500 dark:focus:bg-darkblack-500 dark:focus:text-white" placeholder="Search articles, questions, or keywords..." />
                        <button type="button" data-search-clear hidden aria-label="Clear search" class="absolute right-2.5 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-bgray-300 transition hover:bg-white/20 hover:text-white dark:hover:bg-darkblack-400">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <div data-help-search-results hidden class="absolute left-0 right-0 top-[calc(100%+0.75rem)] z-30 overflow-hidden rounded-2xl border border-bgray-200 bg-white shadow-xl dark:border-darkblack-400 dark:bg-darkblack-600">
                            <div data-search-results-list class="max-h-80 overflow-y-auto p-2"></div>
                            <div data-search-no-results hidden class="px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-bgray-900 dark:text-white">No results found</p>
                                <p class="mt-1 text-xs text-bgray-500 dark:text-bgray-300">Try another keyword or clear your search.</p>
                            </div>
                        </div>
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
                @include('help-center.partials.sidebar')

                <div class="min-w-0">
                    @yield('help-content')
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/modules/help-center.js')
@endpush
