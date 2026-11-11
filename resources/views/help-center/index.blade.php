@extends('help-center.layout')

@section('help-title', 'Help Center')
@section('help-description', 'Find answers, learn features, and quickly get help with using the project management system.')

@section('help-content')
    <section class="rounded-[8px] border border-bgray-200 bg-white px-4 py-2 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">
        <div class="max-w-3xl">
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-bgray-900 dark:text-white">
                Browse help articles
            </h2>
            <p class="mt-2 text-sm leading-6 text-bgray-600 dark:text-bgray-300">
                Start with the topic that matches what you are trying to do. Each article opens on its own page with focused guidance, screenshots, and next steps.
            </p>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-help-cards>
        @foreach ($articles as $article)
            <a href="{{ $article['url'] }}"
                data-help-card
                data-search-text="{{ $article['searchable'] }}"
                class="group flex min-h-[190px] flex-col rounded-[8px] border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-success-300 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-success-300 dark:bg-darkblack-600
                    {{ $article['is_unread']
                        ? 'border-success-300 dark:border-success-300'
                        : 'border-bgray-200 dark:border-darkblack-400' }}">

                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-success-400">
                        {{ $article['category'] }}
                    </span>

                    @if ($article['is_unread'])
                        <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-success-500 dark:bg-success-500/10 dark:text-success-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-success-400"></span>
                            Unread
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-bgray-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-bgray-500 dark:bg-darkblack-500 dark:text-bgray-300">
                            <i class="fa-solid fa-check text-[9px]"></i>
                            Read
                        </span>
                    @endif
                </div>

                <h3 class="mt-3 text-lg font-bold leading-7 text-bgray-900 dark:text-white">
                    {{ $article['title'] }}
                </h3>

                <p class="mt-2 flex-1 text-sm leading-6 text-bgray-600 dark:text-bgray-300">
                    {{ $article['description'] }}
                </p>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-bgray-500 dark:text-bgray-400">
                        {{ str_replace('_', ' ', $article['feature_type']) }}
                    </span>

                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-success-400">
                        Read more
                        <svg class="stroke-current transition group-hover:translate-x-1"
                            width="17" height="17" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
            </a>
        @endforeach
    </section>


    <div data-card-no-results hidden class="mt-6 rounded-[8px] border border-dashed border-bgray-300 bg-white px-6 py-16 text-center dark:border-darkblack-400 dark:bg-darkblack-600" role="status">
        <h2 class="text-lg font-semibold text-bgray-900 dark:text-white">No results found</h2>
        <p class="mt-1 text-sm text-bgray-500 dark:text-bgray-300">Try another keyword or clear your search.</p>
    </div>
@endsection
