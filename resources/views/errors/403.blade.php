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
@endphp

@extends('layouts.master')

@section('pageTitle', $pageTitle)
@section('without-main', true)
@section('page-content')
    <div class="flex flex-col items-center justify-center h-screen text-center">
        <h1 class="text-xl font-bold text-error-300 dark:text-error-50 lg:text-3xl" style="font-size: 100px;">{{ $errorCode }}</h1>
        <p class="text-xl mt-4">{{ $defaultMessages[$errorCode ?? 403] }}</p>
        <a href="{{ url()->previous() }}" class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Go Back
        </a>
    </div>
@endsection
