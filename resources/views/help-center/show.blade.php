@extends('help-center.layout')

@section('help-title', $currentArticle['title'])
@section('help-description', $currentArticle['description'])

@section('help-content')
    <article class="rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-7">
        <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-2 text-xs font-medium text-bgray-500 dark:text-bgray-300">
            <a href="{{ route('help-center.index') }}" class="transition hover:text-success-400">Help Center</a>
            <span aria-hidden="true">/</span>
            <span>{{ $currentArticle['category'] }}</span>
        </nav>

        <header class="border-b border-bgray-100 pb-5 dark:border-darkblack-400">
            <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-400 dark:bg-darkblack-500">
                {{ $currentArticle['category'] }}
            </span>
            <h2 class="mt-3 text-2xl font-bold tracking-tight text-bgray-900 dark:text-white sm:text-3xl">
                {{ $currentArticle['title'] }}
            </h2>
            <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                Last updated: Coming soon
            </p>
        </header>

        <div class="mt-6 space-y-5 text-sm leading-6 text-bgray-700 dark:text-bgray-300 sm:text-base">
            @include($currentArticle['view'])
        </div>
    </article>

    <nav aria-label="Article navigation" class="mt-6 grid gap-4 sm:grid-cols-2">
        @if ($previousArticle)
            <a href="{{ $previousArticle['url'] }}" class="group rounded-2xl border border-bgray-200 bg-white p-5 shadow-sm transition hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-bgray-400 dark:text-bgray-300">Previous</span>
                <span class="mt-2 flex items-center gap-2 text-sm font-semibold text-bgray-900 group-hover:text-success-400 dark:text-white">
                    <svg class="stroke-current transition group-hover:-translate-x-1" width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M19 12H5m6-6-6 6 6 6" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ $previousArticle['title'] }}
                </span>
            </a>
        @else
            <span></span>
        @endif

        @if ($nextArticle)
            <a href="{{ $nextArticle['url'] }}" class="group rounded-2xl border border-bgray-200 bg-white p-5 text-right shadow-sm transition hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600">
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-bgray-400 dark:text-bgray-300">Next</span>
                <span class="mt-2 flex items-center justify-end gap-2 text-sm font-semibold text-bgray-900 group-hover:text-success-400 dark:text-white">
                    {{ $nextArticle['title'] }}
                    <svg class="stroke-current transition group-hover:translate-x-1" width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </a>
        @endif
    </nav>
@endsection
