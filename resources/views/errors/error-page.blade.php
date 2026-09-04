@php
    $errorCode = $code ?? ($status ?? (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 403));

    $defaultTitles = [
        401 => 'Unauthenticated',
        403 => 'Unauthorized',
        404 => 'Page Not Found',
        419 => 'Page Expired',
        429 => 'Too Many Requests',
        500 => 'Server Error',
        503 => 'Service Unavailable',
    ];

    $defaultMessages = [
        401 => 'Oops! You need to log in to access this page.',
        403 => 'Oops! You are not authorized to access this page.',
        404 => 'Oops! The page you are looking for could not be found.',
        419 => 'Oops! Your session has expired. Please refresh and try again.',
        429 => 'Oops! Too many requests. Please slow down and try again later.',
        500 => 'Oops! Something went wrong on our end.',
        503 => 'Oops! We are currently undergoing maintenance. Please check back soon.',
    ];

    $pageTitle = $title ?? ($defaultTitles[$errorCode] ?? 'Error ' . $errorCode);

    // Resolve safe Go Back URL
    $currentUrl = request()->fullUrl();
    $currentPath = ltrim(request()->getPathInfo(), '/');
    $fallbackUrl = url('/');

    $isValidGetUrl = function ($url) use ($currentUrl, $currentPath) {
        if (empty($url) || $url === '#' || str_starts_with($url, 'javascript:')) {
            return false;
        }

        if ($url === $currentUrl) {
            return false;
        }

        $parsedUrl = parse_url($url);
        $path = ltrim($parsedUrl['path'] ?? '', '/');

        if ($path === $currentPath) {
            return false;
        }

        if (isset($parsedUrl['scheme']) && !in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
            return false;
        }

        $ignoredPatterns = [
            'api/*',
            '*/refresh*',
            '*/chart/*',
            '*/summary*',
            '*/worked-time*',
            '*/running-tasks*',
            '*/tile-details*',
            'livewire/*',
            '_debugbar/*',
            '*.json',
            '*.js',
            '*.css',
        ];

        foreach ($ignoredPatterns as $pattern) {
            if (\Illuminate\Support\Str::is($pattern, $path)) {
                return false;
            }
        }

        return true;
    };

    $goBackUrl = null;

    $history = session('valid_get_history', []);
    if (is_array($history)) {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            $candidate = $history[$i];
            if ($isValidGetUrl($candidate)) {
                $goBackUrl = $candidate;
                break;
            }
        }
    }

    if (!$goBackUrl) {
        $previousUrl = url()->previous();
        if ($isValidGetUrl($previousUrl)) {
            $goBackUrl = $previousUrl;
        }
    }

    if (!$goBackUrl) {
        $referer = request()->headers->get('referer');
        if ($isValidGetUrl($referer)) {
            $goBackUrl = $referer;
        }
    }

    $goBackUrl = $goBackUrl ?: $fallbackUrl;
@endphp

@extends('layouts.master')

@section('pageTitle', $pageTitle)
@section('without-main', true)
@section('page-content')
    <div class="flex flex-col items-center justify-center h-screen text-center">
        <h1 class="text-xl font-bold text-error-300 dark:text-error-50 lg:text-3xl" style="font-size: 100px;">{{ $errorCode }}</h1>
        <p class="text-xl mt-4">{{ $defaultMessages[$errorCode ?? 403] ?? 'Oops! Something went wrong.' }}</p>
        <a href="{{ $goBackUrl }}" class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Go Back
        </a>
    </div>
@endsection
