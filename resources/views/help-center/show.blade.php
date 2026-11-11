
@extends('help-center.layout')

@section('help-title', $currentArticle['title'])
@section('help-description', $currentArticle['description'])

@section('help-content')
    <article class="rounded-[8px] border border-bgray-200 bg-white p-5 shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600 sm:p-7">

        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb"
            class="mb-5 flex flex-wrap items-center gap-2 text-xs font-medium text-bgray-500 dark:text-bgray-300">
            <a href="{{ route('help-center.index') }}"
                class="transition hover:text-success-400">
                Help Center
            </a>
            <span aria-hidden="true">/</span>
            <span>{{ $currentArticle['category'] }}</span>
        </nav>

        {{-- Article Header --}}
        <header class="border-b border-bgray-100 pb-5 dark:border-darkblack-400">

            <div class="flex flex-wrap items-center justify-between gap-3">

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-400 dark:bg-darkblack-500">
                        {{ $currentArticle['category'] }}
                    </span>

                    <span class="inline-flex rounded-full bg-bgray-100 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                        {{ ucwords(str_replace('_', ' ', $currentArticle['feature_type'])) }}
                    </span>
                </div>

                {{-- Read / Unread --}}
                @if ($currentArticle['is_read'])
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-bgray-100 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">
                        <i class="fa-solid fa-check text-success-400"></i>
                        Read
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-500 dark:bg-success-500/10 dark:text-success-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-success-400"></span>
                        Unread
                    </span>
                @endif

            </div>

            <h2 class="mt-4 text-2xl font-bold tracking-tight text-bgray-900 dark:text-white sm:text-3xl">
                {{ $currentArticle['title'] }}
            </h2>

            <p class="mt-2 text-sm text-bgray-500 dark:text-bgray-300">
                @if ($currentArticle['published_at'])
                    Published: {{ $currentArticle['published_at']->format('d M Y') }}
                @endif
            </p>

            @if ($currentArticle['description'])
                <p class="mt-4 text-sm leading-6 text-bgray-600 dark:text-bgray-300 sm:text-base">
                    {{ $currentArticle['description'] }}
                </p>
            @endif

        </header>

        {{-- Cover Image --}}
        @if ($currentArticle['cover_image'])
            <div class="mt-6 overflow-hidden rounded-lg">
                <img
                    src="{{ asset('storage/' . $currentArticle['cover_image']) }}"
                    alt="{{ $currentArticle['title'] }}"
                    class="max-h-[420px] w-full rounded-lg object-cover"
                >
            </div>
        @endif

        {{-- Main Description --}}
        @if ($currentArticle['content'])
            <section class="mt-6">
                <div class="prose prose-sm max-w-none text-bgray-700 dark:prose-invert dark:text-bgray-300 sm:prose-base">
                    {!! $currentArticle['content'] !!}
                </div>
            </section>
        @endif

        {{-- Instructions --}}
        @if ($currentArticle['instructions'])
            <section class="mt-8">
                <h3 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">
                    How to use this feature
                </h3>

                <div class="prose prose-sm max-w-none text-bgray-700 dark:prose-invert dark:text-bgray-300 sm:prose-base">
                    {!! $currentArticle['instructions'] !!}
                </div>
            </section>
        @endif

        {{-- Screenshots --}}
        @if (!empty($currentArticle['screenshots']))
            <section class="mt-8">
                <h3 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">
                    Screenshots
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($currentArticle['screenshots'] as $screenshot)
                        <a href="{{ asset('storage/' . $screenshot) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group overflow-hidden rounded-lg border border-bgray-200 dark:border-darkblack-400">

                            <img
                                src="{{ asset('storage/' . $screenshot) }}"
                                alt="Screenshot of {{ $currentArticle['title'] }}"
                                class="h-auto max-h-[320px] w-full object-contain transition duration-300 group-hover:scale-[1.02]"
                                loading="lazy"
                            >
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Video --}}
        @if ($currentArticle['video_url'])
            @php
                $videoUrl = $currentArticle['video_url'];
                $videoId = null;

                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $matches)) {
                    $videoId = $matches[1];
                }
            @endphp

            <section class="mt-8">
                <h3 class="mb-4 text-lg font-bold text-bgray-900 dark:text-white">
                    Video Guide
                </h3>

                @if ($videoId)
                    <div class="relative aspect-video overflow-hidden rounded-lg bg-black">
                        <iframe
                            src="https://www.youtube-nocookie.com/embed/{{ $videoId }}"
                            title="{{ $currentArticle['title'] }} video guide"
                            class="absolute inset-0 h-full w-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        ></iframe>
                    </div>
                @else
                    <a href="{{ $videoUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-success-400 hover:underline">
                        <i class="fa-solid fa-circle-play"></i>
                        Watch video guide
                    </a>
                @endif
            </section>
        @endif

    </article>

    {{-- Previous / Next Navigation --}}
    <nav aria-label="Article navigation" class="mt-6 grid gap-4 sm:grid-cols-2">

        @if ($previousArticle)
            <a href="{{ $previousArticle['url'] }}"
                class="group rounded-[8px] border border-bgray-200 bg-white p-5 shadow-sm transition hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600">

                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">
                    Previous
                </span>

                <span class="mt-2 flex items-center gap-2 text-sm font-semibold text-bgray-900 group-hover:text-success-400 dark:text-white">
                    <svg class="stroke-current transition group-hover:-translate-x-1"
                        width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M19 12H5m6-6-6 6 6 6"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ $previousArticle['title'] }}
                </span>
            </a>
        @else
            <span></span>
        @endif

        @if ($nextArticle)
            <a href="{{ $nextArticle['url'] }}"
                class="group rounded-[8px] border border-bgray-200 bg-white p-5 text-right shadow-sm transition hover:border-success-300 hover:shadow-md dark:border-darkblack-400 dark:bg-darkblack-600">

                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-bgray-600 dark:text-bgray-300">
                    Next
                </span>

                <span class="mt-2 flex items-center justify-end gap-2 text-sm font-semibold text-bgray-900 group-hover:text-success-400 dark:text-white">
                    {{ $nextArticle['title'] }}

                    <svg class="stroke-current transition group-hover:translate-x-1"
                        width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </a>
        @endif

    </nav>
@endsection
