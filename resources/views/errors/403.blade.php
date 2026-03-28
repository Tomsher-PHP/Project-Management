@extends('layouts.master')

@section('title', 'Unauthorized')
@section('page-content')
    <div class="flex flex-col items-center justify-center h-screen text-center">
        <h1 class="text-xl font-bold text-error-300 dark:text-error-50 lg:text-3xl" style="font-size: 100px;">403</h1>
        <p class="text-xl mt-4">Oops! You are not authorized to access this page.</p>
        <a href="{{ url()->previous() }}" class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Go Back
        </a>
    </div>
@endsection
